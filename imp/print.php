<?php
/**
 * Print a message part.
 *
 * <pre>
 * URL parameters:
 * ---------------
 * 'id' - (string) The MIME ID of the part to print.
 * 'mailbox' - (string) The mailbox of the message.
 * 'mode' - (string) The print mode to use ('content', 'headers', empty).
 *          DEFAULT: Prints frameset page
 * 'uid' - (integer) The UID of the message.
 * </pre>
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp', array('session_control' => 'readonly'));

$vars = Horde_Variables::getDefaultVariables();

/* Bug #8708 - Mozilla can't print multipage data in frames. No choice but
 * to output headers and data on same page. */
if ($browser->isBrowser('mozilla')) {
    $vars->mode = 'headers';
}

switch ($vars->mode) {
case 'content':
case 'headers':
    if (!$vars->uid || !$vars->mailbox || !$vars->id) {
        exit;
    }

    $contents = IMP_Contents::singleton($vars->uid . IMP::IDX_SEP . $vars->mailbox);

    switch ($vars->mode) {
    case 'headers':
        $imp_ui = new IMP_Ui_Message();
        $basic_headers = $imp_ui->basicHeaders();
        unset($basic_headers['bcc'], $basic_headers['reply-to']);
        $headerob = $contents->getHeaderOb();

        $headers = array();
        foreach ($basic_headers as $key => $val) {
            if ($hdr_val = $headerob->getValue($key)) {
                $headers[] = array(
                    'header' => htmlspecialchars($val),
                    'value' => htmlspecialchars($hdr_val)
                );
            }
        }

        if (!empty($conf['print']['add_printedby'])) {
            $user_identity = Horde_Prefs_Identity::singleton(array('imp', 'imp'));
            $headers[] = array(
                'header' => htmlspecialchars(_("Printed By")),
                'value' => htmlspecialchars($user_identity->getFullname() ? $user_identity->getFullname() : Horde_Auth::getAuth())
            );
        }

        $t = $injector->createInstance('Horde_Template');
        $t->set('headers', $headers);

        if (!$browser->isBrowser('mozilla')) {
            $t->set('css', Horde_Util::bufferOutput(array('Horde', 'includeStylesheetFiles')));
            echo $t->fetch(IMP_TEMPLATES . '/print/headers.html');
            break;
        }

        $elt = DOMDocument::loadHTML($t->fetch(IMP_TEMPLATES . '/print/headers.html'))->getElementById('headerblock');
        $elt->removeAttribute('id');

        if ($elt->hasAttribute('class')) {
            $selectors = array('body');
            foreach (explode(' ', $elt->getAttribute('class')) as $val) {
                if (strlen($val = trim($val))) {
                    $selectors[] = '.' . $val;
                }
            }

            $css = '';
            foreach (Horde::getStylesheets() as $val) {
                $css .= file_get_contents($val['f']);
            }

            if ($style = Horde_Text_Filter::filter($css, 'csstidy', array('ob' => true))->filterBySelector($selectors)) {
                $elt->setAttribute('style', ($elt->hasAttribute('style') ? rtrim($elt->getAttribute('style'), ' ;') . ';' : '') . $style);
            }
        }

        $elt->removeAttribute('class');

        // Fall-through

    case 'content':
        $render = $contents->renderMIMEPart($vars->id, IMP_Contents::RENDER_FULL);
        if (!empty($render)) {
            reset($render);
            $key = key($render);
            $browser->downloadHeaders($render[$key]['name'], $render[$key]['type'], true, strlen($render[$key]['data']));
            if ($browser->isBrowser('mozilla')) {
                $doc = DOMDocument::loadHTML($render[$key]['data']);
                $bodyelt = $doc->getElementsByTagName('body')->item(0);
                $bodyelt->insertBefore($doc->importNode($elt, true), $bodyelt->firstChild);
                echo $doc->saveHTML();
            } else {
                echo $render[$key]['data'];
            }
        }
        break;

    }
    break;

default:
    $self_url = Horde::selfUrl(true, true);
    $t = $injector->createInstance('Horde_Template');
    $t->set('headers', $self_url->copy()->add('mode', 'headers'));
    $t->set('content', $self_url->copy()->add('mode', 'content'));
    echo $t->fetch(IMP_TEMPLATES . '/print/print.html');
    break;
}
