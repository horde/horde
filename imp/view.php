<?php
/**
 * This script displays various data elements generated in IMP.
 *
 * URL parameters:
 * ---------------
 *   - actionID: (string) The action ID to perform:
 *     - compose_attach_preview
 *     - download_all
 *     - download_attach
 *     - download_mbox
 *     - download_render
 *     - print_attach
 *     - save_message
 *     - view_attach
 *     - view_face
 *     - view_source
 *   - autodetect: (integer) If set, tries to autodetect MIME type when
 *                 viewing based on data.
 *   - composeCache: (string) Cache ID for compose object.
 *   - ctype: (string) The content-type to use instead of the content-type
 *            found in the original Horde_Mime_Part object.
 *   - id: (string) The MIME part ID to display.
 *   - mode: (integer) The view mode to use.
 *           DEFAULT: IMP_Contents::RENDER_FULL
 *   - pmode: (string) The print mode of this request ('content', 'headers').
 *   - zip: (boolean) Download in .zip format?
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

function _sanitizeName($name)
{
    return trim(preg_replace('/[^\pL\pN-+_. ]/u', '_', $name), ' _');
}

require_once dirname(__FILE__) . '/lib/Application.php';

/* Don't compress if we are already sending in compressed format. */
$vars = Horde_Variables::getDefaultVariables();
Horde_Registry::appInit('imp', array(
    'nocompress' => (($vars->actionID == 'download_all') || $vars->zip),
    'session_control' => (Horde_Util::getFormData('ajax') ? null : 'readonly')
));

/* We may reach this page from the download script - need to check for
 * an authenticated user. */
if (!$registry->isAuthenticated(array('app' => 'imp'))) {
    throw new IMP_Exception(_("User is not authenticated."));
}

switch ($vars->actionID) {
case 'compose_attach_preview':
    /* 'compose_attach_preview' doesn't use IMP_Contents since there is no
     * mail message data. Rather, we must use the IMP_Compose object to get
     * the necessary data for Horde_Mime_Part. */
    $imp_compose = $injector->getInstance('IMP_Factory_Compose')->create($vars->composeCache);
    if (!$mime = $imp_compose->buildAttachment($vars->id)) {
        throw new IMP_Exception(_("Could not display attachment data."));
    }
    $mime->setMimeId($vars->id);

    /* Create a dummy IMP_Contents() object so we can use the view code below.
     * Then use the 'view_attach' handler to output. */
    $contents = new IMP_Contents($mime);
    break;

case 'download_mbox':
    if (!IMP::mailbox(true)) {
        exit;
    }

    // Exception will be displayed as fatal error.
    $injector->getInstance('IMP_Ui_Folder')->downloadMbox(array(strval(IMP::mailbox(true))), $vars->zip);
    break;

default:
    if (!IMP::mailbox(true) || !IMP::uid()) {
        exit;
    }

    $contents = $injector->getInstance('IMP_Factory_Contents')->create(IMP::mailbox(true)->getIndicesOb(IMP::uid()));
    break;
}

/* Run through action handlers */
switch ($vars->actionID) {
case 'download_all':
    $headers = $contents->getHeader();
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
        while (!feof($body)) {
            echo fread($body, 8192);
        }
        fclose($body);
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
            $mime->setContents($render[key($render)]['data'], array('encoding' => 'binary'));
        }

        $name = $contents->getPartName($mime);

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
        if (strlen($render[$key]['name'])) {
            $name = $render[$key]['name'];
        }
        break;
    }

    if (is_resource($body)) {
        fseek($body, 0, SEEK_END);
        $browser->downloadHeaders($name, $type, false, ftell($body));
        rewind($body);
        while (!feof($body)) {
            echo fread($body, 8192);
        }
        fclose($body);
    } else {
        $browser->downloadHeaders($name, $type, false, strlen($body));
        echo $body;
    }
    break;

case 'compose_attach_preview':
case 'view_attach':
    $render_mode = ($vars->actionID == 'compose_attach_preview')
        ? IMP_Contents::RENDER_RAW_FALLBACK
        : (isset($vars->mode) ? $vars->mode : IMP_Contents::RENDER_FULL);
    $render = $contents->renderMIMEPart($vars->id, $render_mode, array(
        'autodetect' => $vars->autodetect,
        'type' => $vars->ctype
    ));

    if (!empty($render)) {
        reset($render);
        $key = key($render);
        $browser->downloadHeaders($render[$key]['name'], $render[$key]['type'], true, strlen($render[$key]['data']));
        echo $render[$key]['data'];
    } elseif ($vars->autodetect) {
        echo _("Could not auto-determine data type.");
    }
    break;

case 'save_message':
case 'view_source':
    $msg = $contents->fullMessageText(array('stream' => true));
    fseek($msg, 0, SEEK_END);

    if ($vars->actionID == 'save_message') {
        $name = ($subject = $contents->getHeader()->getValue('subject'))
            ? _sanitizeName($subject)
            : 'saved_message';
        $browser->downloadHeaders($name . '.eml', 'message/rfc822', false, ftell($msg));
    } else {
        $browser->downloadHeaders(_("Message Source"), 'text/plain', true, ftell($msg));
    }

    rewind($msg);
    while (!feof($msg)) {
        echo fread($msg, 8192);
    }
    fclose($msg);
    break;

case 'view_face':
    $mime_headers = $contents->getHeader();
    if ($face = $mime_headers->getValue('face')) {
        $face = base64_decode($face);
        $browser->downloadHeaders(null, 'image/png', true, strlen($face));
        echo $face;
    }
    break;

case 'print_attach':
    if (!isset($vars->id) ||
        !($render = $contents->renderMIMEPart($vars->id, IMP_Contents::RENDER_FULL))) {
        break;
    }
    reset($render);
    $render_key = key($render);

    if (stripos($render[$render_key]['type'], 'text/html') !== 0) {
        header('Content-Type: ' . $render[$render_key]['type']);
        echo $render[$render_key]['data'];
        exit;
    }

    $imp_ui = new IMP_Ui_Message();
    $basic_headers = $imp_ui->basicHeaders();
    unset($basic_headers['bcc'], $basic_headers['reply-to']);
    $headerob = $contents->getHeader();

    $d_param = Horde_Mime::decodeParam('content-type', $render[$render_key]['type']);

    $headers = array();
    foreach ($basic_headers as $key => $val) {
        if ($hdr_val = $headerob->getValue($key)) {
            /* Format date string. */
            if ($key == 'date') {
                $imp_ui_mbox = new IMP_Ui_Mailbox();
                $hdr_val = $imp_ui_mbox->getDate($hdr_val, IMP_Ui_Mailbox::DATE_FORCE | IMP_Ui_Mailbox::DATE_FULL);
            }

            $headers[] = array(
                'header' => htmlspecialchars($val),
                'value' => htmlspecialchars($hdr_val)
            );
        }
    }

    if ($prefs->getValue('add_printedby')) {
        $user_identity = $injector->getInstance('IMP_Identity');
        $headers[] = array(
            'header' => htmlspecialchars(_("Printed By")),
            'value' => htmlspecialchars($user_identity->getFullname() ? $user_identity->getFullname() : $registry->getAuth())
        );
    }

    $t = $injector->createInstance('Horde_Template');
    $t->set('headers', $headers);

    $header_dom = new Horde_Domhtml(Horde_String::convertCharset($t->fetch(IMP_TEMPLATES . '/print/headers.html'), 'UTF-8', $d_param['params']['charset']), $d_param['params']['charset']);
    $elt = $header_dom->dom->getElementById('headerblock');
    $elt->removeAttribute('id');

    if ($elt->hasAttribute('class')) {
        $selectors = array('body');
        foreach (explode(' ', $elt->getAttribute('class')) as $val) {
            if (strlen($val = trim($val))) {
                $selectors[] = '.' . $val;
            }
        }

        $css = $injector->getInstance('Horde_Themes_Css');
        // Csstidy filter may not be available.
        try {
            if ($style = $injector->getInstance('Horde_Core_Factory_TextFilter')->filter($css->loadCssFiles($css->getStylesheets()), 'csstidy', array('ob' => true, 'preserve_css' => false))->filterBySelector($selectors)) {
                $elt->setAttribute('style', ($elt->hasAttribute('style') ? rtrim($elt->getAttribute('style'), ' ;') . ';' : '') . $style);
            }
        } catch (Horde_Exception $e) {}
    }

    $elt->removeAttribute('class');

    /* Need to wrap headers in another DIV. */
    $newdiv = new DOMDocument();
    $div = $newdiv->createElement('div');
    $div->appendChild($newdiv->importNode($elt, true));

    $browser->downloadHeaders($render[$render_key]['name'], $render[$render_key]['type'], true, strlen($render[$render_key]['data']));

    $pstring = Horde_Mime::decodeParam('content-type', $render[$render_key]['type']);

    $doc = new Horde_Domhtml($render[$render_key]['data'], $pstring['params']['charset']);

    $bodyelt = $doc->dom->getElementsByTagName('body')->item(0);
    $bodyelt->insertBefore($doc->dom->importNode($div, true), $bodyelt->firstChild);

    /* Make the title the e-mail subject. */
    $headers = $contents->getHeader();
    $imp_ui_mbox = new IMP_Ui_Mailbox();

    $headelt = $doc->getHead();
    foreach ($headelt->getElementsByTagName('title') as $node) {
        $headelt->removeChild($node);
    }
    $headelt->appendChild($doc->dom->createElement('title', htmlspecialchars($imp_ui_mbox->getSubject($headers->getValue('subject')))));

    echo $doc->returnHtml();
    break;
}
