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
    $name = String::convertCharset($name, NLS::getCharset(), 'UTF-8');
    return String::convertCharset(trim(preg_replace('/[^\pL\pN-+_. ]/u', '_', $name), ' _'), 'UTF-8');
}

/* Don't compress if we are already sending in compressed format. */
if ((isset($_GET['actionID']) && ($_GET['actionID'] == 'download_all')) ||
    !empty($_GET['zip'])) {
    $no_compress = true;
}

$session_control = 'readonly';
require_once dirname(__FILE__) . '/lib/base.php';

$actionID = Util::getFormData('actionID');
$ctype = Util::getFormData('ctype');
$id = Util::getFormData('id');

/* 'compose_attach_preview' doesn't use IMP_Contents since there is no
 * IMAP message data - rather, we must use the IMP_Compose object to
 * get the necessary Horde_Mime_Part. */
if ($actionID == 'compose_attach_preview') {
    /* Initialize the IMP_Compose:: object. */
    $imp_compose = &IMP_Compose::singleton(Util::getFormData('composeCache'));
    $mime = $imp_compose->buildAttachment($id);

    /* Create a dummy IMP_Contents() object so we can use the view code below.
     * Then use the 'view_attach' handler to output. */
    $contents = &IMP_Contents::singleton($mime);
    $actionID = 'view_attach';
    $id = $mime->getMimeId();
} else {
    $uid = Util::getFormData('uid');
    if (!$uid) {
        // TODO: Remove usage of 'index'
        $uid = Util::getFormData('index');
    }
    $mailbox = Util::getFormData('mailbox');
    if (!$uid || !$mailbox) {
        exit;
    }

    try {
        $contents = &IMP_Contents::singleton($uid . IMP::IDX_SEP . $mailbox);
    } catch (Horde_Exception $e) {
        Horde::fatal($e, __FILE__, __LINE__);
    }
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
        $tosave[] = array('data' => $mime->getContents(), 'name' => $name);
    }

    if (!empty($tosave)) {
        $horde_compress = &Horde_Compress::singleton('zip');
        $body = $horde_compress->compress($tosave);
        $browser->downloadHeaders($zipfile, 'application/zip', false, strlen($body));
        echo $body;
    }
    exit;

case 'download_attach':
case 'download_render':
    switch ($actionID) {
    case 'download_attach':
        $mime = $contents->getMIMEPart($id);
        $body = $mime->getContents();
        $type = $mime->getType(true);
        $name = $mime->getName(true);
        break;

    case 'download_render':
        $render = $contents->renderMIMEPart($id, Util::getFormData('mode', IMP_Contents::RENDER_FULL), array('type' => $ctype));
        reset($render);
        $key = key($render);
        $body = $render[$key]['data'];
        $type = $render[$key]['type'];
        $name = $render[$key]['name'];
        break;
    }

    if (empty($name)) {
        $name = _("unnamed");
    }

    /* Compress output? */
    if (($actionID == 'download_attach') && Util::getFormData('zip')) {
        $horde_compress = &Horde_Compress::singleton('zip');
        $body = $horde_compress->compress(array(array('data' => $body, 'name' => $name)));
        $name .= '.zip';
        $type = 'application/zip';
    }
    $browser->downloadHeaders($name, $type, false, strlen($body));
    echo $body;
    exit;

case 'view_attach':
    $render = $contents->renderMIMEPart($id, Util::getFormData('mode', IMP_Contents::RENDER_FULL), array('type' => $ctype));
    if (!empty($render)) {
        reset($render);
        $key = key($render);
        $browser->downloadHeaders($render[$key]['name'], $render[$key]['type'], true, strlen($render[$key]['data']));
        echo $render[$key]['data'];
    }
    exit;

case 'view_source':
    $msg = $contents->fullMessageText();
    $browser->downloadHeaders('Message Source', 'text/plain', true, strlen($msg));
    echo $msg;
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

    $body = 'From ' . $from . ' ' . $date->format('D M d H:i:s Y') . "\r\n" . $contents->fullMessageText();
    $browser->downloadHeaders($name . '.eml', 'message/rfc822', false, strlen($body));
    echo $body;
    exit;
}
