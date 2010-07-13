<?php
/**
 * This script displays various data elements generated in IMP.
 *
 * URL parameters:
 * ---------------
 * <pre>
 * 'actionID' - (string) The action ID to perform
 *   'compose_attach_preview'
 *   'download_all'
 *   'download_attach'
 *   'download_render'
 *   'print_attach'
 *   'save_message'
 *   'view_attach'
 *   'view_face'
 *   'view_source'
 * 'composeCache' - (string) Cache ID for compose object.
 * 'ctype' - (string) The content-type to use instead of the content-type
 *           found in the original Horde_Mime_Part object.
 * 'id' - (string) The MIME part ID to display.
 * 'mailbox' - (string) The mailbox of the message.
 * 'mode' - (integer) The view mode to use.
 *          DEFAULT: IMP_Contents::RENDER_FULL
 * 'pmode' - (string) The print mode of this request ('content', 'headers').
 * 'uid - (string) The UID of the message.
 * 'zip' - (boolean) Download in .zip format?
 * </pre>
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */

function _sanitizeName($name)
{
    return Horde_String::convertCharset(trim(preg_replace('/[^\pL\pN-+_. ]/u', '_', Horde_String::convertCharset($name, $GLOBALS['registry']->getCharset(), 'UTF-8')), ' _'), 'UTF-8');
}

require_once dirname(__FILE__) . '/lib/Application.php';

/* Don't compress if we are already sending in compressed format. */
$vars = Horde_Variables::getDefaultVariables();
Horde_Registry::appInit('imp', array(
    'nocompress' => (($vars->actionID == 'download_all') || $vars->zip),
    'session_control' => 'readonly'
));

/* 'compose_attach_preview' doesn't use IMP_Contents since there is no mail
 * message data. Rather, we must use the IMP_Compose object to get the
 * necessary data for Horde_Mime_Part. */
if ($vars->actionID == 'compose_attach_preview') {
    $imp_compose = $injector->getInstance('IMP_Compose')->getOb($vars->composeCache);
    $mime = $imp_compose->buildAttachment($vars->id);
    $mime->setMimeId($vars->id);

    /* Create a dummy IMP_Contents() object so we can use the view code below.
     * Then use the 'view_attach' handler to output. */
    $contents = new IMP_Contents($mime);
} else {
    if (!$vars->uid || !$vars->mailbox) {
        exit;
    }
    $contents = $injector->getInstance('IMP_Contents')->getOb(new IMP_Indices($vars->mailbox, $vars->uid));
}

/* Run through action handlers */
switch ($vars->actionID) {
case 'download_all':
    $headers = $contents->getHeaderOb();
    $zipfile = _sanitizeName($headers->getValue('subject'));
    if (empty($zipfile)) {
        $zipfile = _("attachments.zip");
    } else {
        $zipfile .= '.zip';
    }

    $tosave = array();
    foreach ($contents->downloadAllList() as $val) {
        $mime = $contents->getMIMEPart($val);
        $name = $mime->getName(true);
        if (!$name) {
            $name = sprintf(_("part %s"), $val);
        }
        $tosave[] = array('data' => $mime->getContents(array('stream' => true)), 'name' => $name);
    }

    if (!empty($tosave)) {
        $horde_compress = Horde_Compress::factory('zip');
        $body = $horde_compress->compress($tosave, array('stream' => true));
        fseek($body, 0, SEEK_END);
        $browser->downloadHeaders($zipfile, 'application/zip', false, ftell($body));
        rewind($body);
        fpassthru($body);
    }
    break;

case 'download_attach':
case 'download_render':
    switch ($vars->actionID) {
    case 'download_attach':
        $mime = $contents->getMIMEPart($vars->id);
        if ($contents->canDisplay($vars->id, IMP_Contents::RENDER_RAW)) {
            $render = $contents->renderMIMEPart($vars->id, IMP_Contents::RENDER_RAW);
            reset($render);
            $mime->setContents($render[key($render)]['data']);
        }

        if (!$name = $mime->getName(true)) {
            $name = _("unnamed");
        }

        /* Compress output? */
        if ($vars->zip) {
            $horde_compress = Horde_Compress::factory('zip');
            $body = $horde_compress->compress(array(array('data' => $mime->getContents(), 'name' => $name)), array('stream' => true));
            $name .= '.zip';
            $type = 'application/zip';
        } else {
            $body = $mime->getContents(array('stream' => true));
            $type = $mime->getType(true);
        }
        break;

    case 'download_render':
        $render = $contents->renderMIMEPart($vars->id, isset($vars->mode) ? $vars->mode : IMP_Contents::RENDER_FULL, array('type' => $vars->ctype));
        reset($render);
        $key = key($render);
        $body = $render[$key]['data'];
        $type = $render[$key]['type'];
        if (!$name = $render[$key]['name']) {
            $name = _("unnamed");
        }
        break;
    }

    if (is_resource($body)) {
        fseek($body, 0, SEEK_END);
        $browser->downloadHeaders($name, $type, false, ftell($body));
        rewind($body);
        fpassthru($body);
    } else {
        $browser->downloadHeaders($name, $type, false, strlen($body));
        echo $body;
    }
    break;

case 'compose_attach_preview':
case 'view_attach':
    $render = $contents->renderMIMEPart($vars->id, isset($vars->mode) ? $vars->mode : IMP_Contents::RENDER_FULL, array('params' => array('raw' => ($vars->actionID == 'compose_attach_preview'), 'type' => $vars->ctype)));
    if (!empty($render)) {
        reset($render);
        $key = key($render);
        $browser->downloadHeaders($render[$key]['name'], $render[$key]['type'], true, strlen($render[$key]['data']));
        echo $render[$key]['data'];
    }
    break;

case 'view_source':
    $msg = $contents->fullMessageText(array('stream' => true));
    fseek($msg, 0, SEEK_END);
    $browser->downloadHeaders('Message Source', 'text/plain', true, ftell($msg));
    rewind($msg);
    fpassthru($msg);
    break;

case 'save_message':
    $mime_headers = $contents->getHeaderOb();

    if (($subject = $mime_headers->getValue('subject'))) {
        $name = _sanitizeName($subject);
    } else {
        $name = 'saved_message';
    }

    if (!($from = Horde_Mime_Address::bareAddress($mime_headers->getValue('from')))) {
        $from = '<>';
    }

    $date = new DateTime($mime_headers->getValue('date'));

    $hdr = 'From ' . $from . ' ' . $date->format('D M d H:i:s Y') . "\r\n";
    $msg = $contents->fullMessageText(array('stream' => true));
    fseek($msg, 0, SEEK_END);
    $browser->downloadHeaders($name . '.eml', 'message/rfc822', false, strlen($hdr) + ftell($msg));
    echo $hdr;
    rewind($msg);
    fpassthru($msg);
    break;

case 'view_face':
    $mime_headers = $contents->getHeaderOb();
    if ($face = $mime_headers->getValue('face')) {
        $face = base64_decode($face);
        $browser->downloadHeaders(null, 'image/png', true, strlen($face));
        echo $face;
    }
    break;

case 'print_attach':
    /* Bug #8708 - Mozilla can't print multipage data in frames. No choice but
     * to output headers and data on same page. */
    if ($browser->isBrowser('mozilla')) {
        $vars->pmode = 'headers';
    }

    switch ($vars->pmode) {
    case 'content':
    case 'headers':
        if (!$vars->id) {
            exit;
        }

        switch ($vars->pmode) {
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
                $user_identity = $injector->getInstance('IMP_Identity');
                $headers[] = array(
                    'header' => htmlspecialchars(_("Printed By")),
                    'value' => htmlspecialchars($user_identity->getFullname() ? $user_identity->getFullname() : $registry->getAuth())
                );
            }

            $t = $injector->createInstance('Horde_Template');
            $t->set('headers', $headers);

            if (!$browser->isBrowser('mozilla')) {
                Horde::startBuffer();
                Horde_Themes::includeStylesheetFiles();
                $t->set('css', Horde::endBuffer());
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

                if ($style = Horde_Text_Filter::filter(Horde_Themes::loadCssFiles(Horde_Themes::getStylesheets()), 'csstidy', array('ob' => true, 'preserve_css' => false))->filterBySelector($selectors)) {
                    $elt->setAttribute('style', ($elt->hasAttribute('style') ? rtrim($elt->getAttribute('style'), ' ;') . ';' : '') . $style);
                }
            }

            $elt->removeAttribute('class');

            /* Need to wrap headers in another DIV. */
            $newdiv = new DOMDocument();
            $div = $newdiv->createElement('div');
            $div->appendChild($newdiv->importNode($elt, true));

            // Fall-through

        case 'content':
            $render = $contents->renderMIMEPart($vars->id, IMP_Contents::RENDER_FULL);
            if (!empty($render)) {
                reset($render);
                $key = key($render);
                $browser->downloadHeaders($render[$key]['name'], $render[$key]['type'], true, strlen($render[$key]['data']));
                if ($browser->isBrowser('mozilla')) {
                    /* Silence errors from parsing HTML. */
                    $old_error = libxml_use_internal_errors(true);
                    $doc = DOMDocument::loadHTML($render[$key]['data']);
                    if (!$old_error) {
                        libxml_use_internal_errors(false);
                    }
                    $bodyelt = $doc->getElementsByTagName('body')->item(0);
                    $bodyelt->insertBefore($doc->importNode($div, true), $bodyelt->firstChild);
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
        $t->set('headers', $self_url->copy()->add('pmode', 'headers'));
        $t->set('content', $self_url->copy()->add('pmode', 'content'));
        echo $t->fetch(IMP_TEMPLATES . '/print/print.html');
        break;
    }
}
