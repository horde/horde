<?php
/**
 * This script displays a rendered Horde_Mime_Part object.
 * The following are potential URL parameters that are honored:
 * <pre>
 * 'actionID' - (string) The action ID to perform
 *   'compose_attach_preview'
 *   'download_all'
 *   'download_attach'
 *   'download_render'
 *   'save_message'
 *   'view_attach'
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
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
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
    $name = Horde_String::convertCharset($name, Horde_Nls::getCharset(), 'UTF-8');
    return Horde_String::convertCharset(trim(preg_replace('/[^\pL\pN-+_. ]/u', '_', $name), ' _'), 'UTF-8');
}

function _fullMessageTextLength($ob)
{
    $stat = fseek($ob[1], 0, SEEK_END);
    $len = strlen($ob[0]) + ftell($ob[1]);
    rewind($ob[1]);
    return $len;
}

/* Don't compress if we are already sending in compressed format. */
if ((isset($_GET['actionID']) && ($_GET['actionID'] == 'download_all')) ||
    !empty($_GET['zip'])) {
    $imp_no_compress = true;
}

$imp_session_control = 'readonly';
require_once dirname(__FILE__) . '/lib/base.php';

$actionID = Horde_Util::getFormData('actionID');
$ctype = Horde_Util::getFormData('ctype');
$id = Horde_Util::getFormData('id');

/* 'compose_attach_preview' doesn't use IMP_Contents since there is no
 * IMAP message data - rather, we must use the IMP_Compose object to
 * get the necessary Horde_Mime_Part. */
if ($actionID == 'compose_attach_preview') {
    /* Initialize the IMP_Compose:: object. */
    $imp_compose = IMP_Compose::singleton(Horde_Util::getFormData('composeCache'));
    $mime = $imp_compose->buildAttachment($id);

    /* Create a dummy IMP_Contents() object so we can use the view code below.
     * Then use the 'view_attach' handler to output. */
    $contents = IMP_Contents::singleton($mime);
    $actionID = 'view_attach';
    $id = $mime->getMimeId();
} else {
    $uid = Horde_Util::getFormData('uid');
    if (!$uid) {
        // TODO: Remove usage of 'index'
        $uid = Horde_Util::getFormData('index');
    }
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
    exit;

case 'download_attach':
case 'download_render':
    switch ($actionID) {
    case 'download_attach':
        $mime = $contents->getMIMEPart($id);
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
    exit;

case 'view_attach':
    $render = $contents->renderMIMEPart($id, Horde_Util::getFormData('mode', IMP_Contents::RENDER_FULL), array('type' => $ctype));
    if (!empty($render)) {
        reset($render);
        $key = key($render);
        $browser->downloadHeaders($render[$key]['name'], $render[$key]['type'], true, strlen($render[$key]['data']));
        echo $render[$key]['data'];
    }
    exit;

case 'view_source':
    $msg = $contents->fullMessageText(array('stream' => true));
    $browser->downloadHeaders('Message Source', 'text/plain', true, _fullMessageTextLength($msg));
    echo $msg[0];
    rewind($msg[1]);
    fpassthru($msg[1]);
    exit;

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
    $browser->downloadHeaders($name . '.eml', 'message/rfc822', false, strlen($hdr) + _fullMessageTextLength($msg));
    echo $hdr . $msg[0];
    rewind($msg[1]);
    fpassthru($msg[1]);
    exit;
}
