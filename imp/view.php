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
 *   'save_message'
 *   'view_attach'
 *   'view_face'
 *   'view_source'
 * 'ctype' - (string) The content-type to use instead of the content-type
 *           found in the original Horde_Mime_Part object.
 * 'id' - (string) The MIME part ID to display.
 * 'mailbox' - (string) The mailbox of the message.
 * 'mode' - (integer) The view mode to use.
 *          DEFAULT: IMP_Contents::RENDER_FULL
 * 'uid - (string) The UID of the message.
 * 'zip' - (boolean) Download in .zip format?
 * </pre>
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */

function _sanitizeName($name)
{
    return Horde_String::convertCharset(trim(preg_replace('/[^\pL\pN-+_. ]/u', '_', Horde_String::convertCharset($name, Horde_Nls::getCharset(), 'UTF-8')), ' _'), 'UTF-8');
}

require_once dirname(__FILE__) . '/lib/Application.php';

/* Don't compress if we are already sending in compressed format. */
$actionID = Horde_Util::getFormData('actionID');
Horde_Registry::appInit('imp', array(
    'nocompress' => (($actionID == 'download_all') || Horde_Util::getFormData('zip')),
    'session_control' => 'readonly'
));

$ctype = Horde_Util::getFormData('ctype');
$id = Horde_Util::getFormData('id');

/* 'compose_attach_preview' doesn't use IMP_Contents since there is no mail
 * message data. Rather, we must use the IMP_Compose object to get the
 * necessary data for Horde_Mime_Part. */
if ($actionID == 'compose_attach_preview') {
    $imp_compose = IMP_Compose::singleton(Horde_Util::getFormData('composeCache'));
    $mime = $imp_compose->buildAttachment($id);
    $mime->setMimeId($id);

    /* Create a dummy IMP_Contents() object so we can use the view code below.
     * Then use the 'view_attach' handler to output. */
    $contents = IMP_Contents::singleton($mime);
} else {
    $uid = Horde_Util::getFormData('uid');
    $mailbox = Horde_Util::getFormData('mailbox');
    if (!$uid || !$mailbox) {
        exit;
    }

    $contents = IMP_Contents::singleton($uid . IMP::IDX_SEP . $mailbox);
}

/* Run through action handlers */
switch ($actionID) {
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
    switch ($actionID) {
    case 'download_attach':
        $mime = $contents->getMIMEPart($id);
        if ($contents->canDisplay($id, IMP_Contents::RENDER_RAW)) {
            $render = $contents->renderMIMEPart($id, IMP_Contents::RENDER_RAW);
            reset($render);
            $mime->setContents($render[key($render)]['data']);
        }

        if (!$name = $mime->getName(true)) {
            $name = _("unnamed");
        }

        /* Compress output? */
        if (Horde_Util::getFormData('zip')) {
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
        $render = $contents->renderMIMEPart($id, Horde_Util::getFormData('mode', IMP_Contents::RENDER_FULL), array('type' => $ctype));
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
    $render = $contents->renderMIMEPart($id, Horde_Util::getFormData('mode', IMP_Contents::RENDER_FULL), array('params' => array('raw' => ($actionID == 'compose_attach_preview'), 'type' => $ctype)));
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
}
