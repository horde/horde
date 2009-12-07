<?php
/**
 * This file should be the basis for serving hosted attachments.  It
 * should fetch the file from the VFS and funnel it to the client
 * wishing to download the attachment. This will allow for the
 * exchange of massive attachments without causing mail server havoc.
 *
 * Copyright 2004-2007 Andrew Coleman <mercury@appisolutions.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Andrew Coleman <mercury@appisolutions.net>
 * @package IMP
 */

// We do not need to be authenticated to get the file. Most users won't send
// linked attachments just to other IMP users.
require_once dirname(__FILE__) . '/lib/Application.php';
new IMP_Application(array('init' => array('authentication' => 'none', 'session_control' => 'none')));

$self_url = Horde::selfUrl(false, true, true);

// Lets see if we are even able to send the user an attachment.
if (!$conf['compose']['link_attachments']) {
    throw new Horde_Exception(_("Linked attachments are forbidden."));
}

// Gather required form variables.
$mail_user = Horde_Util::getFormData('u');
$time_stamp = Horde_Util::getFormData('t');
$file_name = Horde_Util::getFormData('f');
if (is_null($mail_user) || is_null($time_stamp) || is_null($file_name)) {
    throw new Horde_Exception(_("The attachment was not found."));
}

// Initialize the VFS.
$vfsroot = VFS::singleton($conf['vfs']['type'], Horde::getDriverConfig('vfs', $conf['vfs']['type']));
if ($vfsroot instanceof PEAR_Error) {
    throw new Horde_Exception(sprintf(_("Could not create the VFS backend: %s"), $vfsroot->getMessage()));
}

// Check if the file exists.
$mail_user = basename($mail_user);
$time_stamp = basename($time_stamp);
$file_name = escapeshellcmd(basename($file_name));
$full_path = sprintf(IMP_Compose::VFS_LINK_ATTACH_PATH . '/%s/%d', $mail_user, $time_stamp);
if (!$vfsroot->exists($full_path, $file_name)) {
    throw new Horde_Exception(_("The specified attachment does not exist. It may have been deleted by the original sender."));
}

// Check to see if we need to send a verification message.
if ($conf['compose']['link_attachments_notify']) {
    if ($vfsroot->exists($full_path, $file_name . '.notify')) {
        $delete_id = Horde_Util::getFormData('d');
        $read_id = $vfsroot->read($full_path, $file_name . '.notify');
        if ($read_id instanceof PEAR_Error) {
            Horde::logMessage($read_id, __FILE__, __LINE__, PEAR_LOG_ERR);
        } elseif ($delete_id == $read_id) {
            $vfsroot->deleteFile($full_path, $file_name);
            $vfsroot->deleteFile($full_path, $file_name . '.notify');
            printf(_("Attachment %s deleted."), $file_name);
            exit;
        }
    } else {
        /* Create a random identifier for this file. */
        $id = uniqid(mt_rand());
        $res = $vfsroot->writeData($full_path, $file_name . '.notify', $id, true);
        if ($res instanceof PEAR_Error) {
            Horde::logMessage($res, __FILE__, __LINE__, PEAR_LOG_ERR);
        } else {
            /* Load $mail_user's preferences so that we can use their
             * locale information for the notification message. */
            $prefs = Horde_Prefs::singleton($conf['prefs']['driver'], 'horde', $mail_user);
            $prefs->retrieve();

            $mail_identity = Horde_Prefs_Identity::singleton('none', $mail_user);
            $mail_address = $mail_identity->getDefaultFromAddress();

            /* Ignore missing addresses, which are returned as <>. */
            if (strlen($mail_address) > 2) {
                $mail_address_full = $mail_identity->getDefaultFromAddress(true);
                Horde_Nls::setTimeZone();
                Horde_Nls::setLanguageEnvironment();

                /* Set up the mail headers and read the log file. */
                $msg_headers = new Horde_Mime_Headers();
                $msg_headers->addReceivedHeader();
                $msg_headers->addMessageIdHeader();
                $msg_headers->addUserAgentHeader();
                $msg_headers->addHeader('Date', date('r'));
                $msg_headers->addHeader('From', $mail_address_full);
                $msg_headers->addHeader('To', $mail_address_full);
                $msg_headers->addHeader('Subject', _("Notification: Linked attachment downloaded"));

                $msg = new Horde_Mime_Part();
                $msg->setType('text/plain');
                $msg->setCharset(Horde_Nls::getCharset());

                $d_url = new Horde_Url(Horde::selfUrl(true, false, true));
                $msg->setContents(Horde_String::wrap(sprintf(_("Your linked attachment has been downloaded by at least one user.\n\nAttachment name: %s\nAttachment date: %s\n\nClick on the following link to permanently delete the attachment:\n%s"), $file_name, date('r', $time_stamp), $d_url->add('d', $id))));

                $msg->send($mail_address, $msg_headers);
            }
        }
    }
}

// Find the file's mime-type.
$file_data = $vfsroot->read($full_path, $file_name);
if ($file_data instanceof PEAR_Error) {
    Horde::logMessage($file_data, __FILE__, __LINE__, PEAR_LOG_ERR);
    throw new Horde_Exception(_("The specified file cannot be read."));
}
$mime_type = Horde_Mime_Magic::analyzeData($file_data, isset($conf['mime']['magic_db']) ? $conf['mime']['magic_db'] : null);
if ($mime_type === false) {
    $mime_type = Horde_Mime_Magic::filenameToMIME($file_name, false);
}

// Prevent 'jar:' attacks on Firefox.  See Ticket #5892.
if ($browser->isBrowser('mozilla')) {
    if (in_array(Horde_String::lower($mime_type), array('application/java-archive', 'application/x-jar'))) {
        $mime_type = 'application/octet-stream';
    }
}

// Send the client the file.
$browser->downloadHeaders($file_name, $mime_type, false, strlen($file_data));
echo $file_data;
