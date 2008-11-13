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
 * 'download_ids' - (string) For 'download_all', the serialized list of IDs to
 *                  download.
 * 'id' - (string) The MIME part ID to display.
 * 'index - (integer) The index of the message.
 * 'mailbox' - (string) The mailbox of the message.
 * 'mode' - (string) The view mode to use (DEFAULT: 'full').
 * 'zip' - (boolean) Download in .zip format?
 * </pre>
 *
 * Copyright 1999-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Michael Slusarz <slusarz@horde.org>
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
@define('IMP_BASE', dirname(__FILE__));
require_once IMP_BASE . '/lib/base.php';

$actionID = Util::getFormData('actionID');
$id = Util::getFormData('id');
$ctype = Util::getFormData('ctype');

/* 'compose_attach_preview' doesn't use IMP_Contents since there is no
 * IMAP message data - rather, we must use the IMP_Compose object to
 * get the necessary Horde_Mime_Part. */
if ($actionID == 'compose_attach_preview') {
    /* Initialize the IMP_Compose:: object. */
    $imp_compose = &IMP_Compose::singleton(Util::getFormData('messageCache'));
    $mime = $imp_compose->buildAttachment($id);

    /* Create a dummy IMP_Contents() object so we can use the view
     * code below.  Then use the 'view_attach' handler to output to
     * the user. */
    $contents = new IMP_Contents($mime);
    $actionID = 'view_attach';
} else {
    $index = Util::getFormData('index');
    $mailbox = Util::getFormData('mailbox');
    if (!$index || !$mailbox) {
        exit;
    }

    $contents = &IMP_Contents::singleton($index . IMP::IDX_SEP . $mailbox);
    if (is_a($contents, 'PEAR_Error')) {
        Horde::fatal($contents, __FILE__, __LINE__);
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
    foreach (unserialize(Util::getFormData('download_ids')) as $val) {
        $mime = $contents->getMIMEPart($val);
        $tosave[] = array('data' => $mime->getContents(), 'name' => $mime->getName(true));
    }

    $horde_compress = &Horde_Compress::singleton('zip');
    $body = $horde_compress->compress($tosave);
    $browser->downloadHeaders($zipfile, 'application/zip', false, strlen($body));
    echo $body;
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
        $body = $render[$id]['data'];
        $type = $render[$id]['type'];
        $name = $render[$id]['name'];
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
    $browser->downloadHeaders($render[$id]['name'], $render[$id]['type'], true, strlen($render[$id]['data']));
    echo $render[$id]['data'];
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
