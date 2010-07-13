<?php
/**
 * This file should be the basis for serving hosted attachments.  It
 * should fetch the file from the VFS and funnel it to the client
 * wants to download the attachment. This will allow for the
 * exchange of massive attachments without causing mail server havoc.
 *
 * Copyright 2004-2007 Andrew Coleman <mercury@appisolutions.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Andrew Coleman <mercury@appisolutions.net>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */

// We do not need to be authenticated to get the file. Most users won't send
// linked attachments just to other IMP users.
require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp', array('authentication' => 'none', 'session_control' => 'none'));

// Lets see if we are even able to send the user an attachment.
if (!$conf['compose']['link_attachments']) {
    throw new IMP_Exception(_("Linked attachments are forbidden."));
}

// Gather required form variables.
$mail_user = Horde_Util::getFormData('u');
$time_stamp = Horde_Util::getFormData('t');
$file_name = Horde_Util::getFormData('f');
if (is_null($mail_user) || is_null($time_stamp) || is_null($file_name)) {
    throw new IMP_Exception(_("The attachment was not found."));
}

// Initialize the VFS.
try {
    $vfsroot = $injector->getInstance('Horde_Vfs')->getVfs();
} catch (VFS_Exception $e) {
    throw new IMP_Exception(sprintf(_("Could not create the VFS backend: %s"), $e->getMessage()));
}

// Check if the file exists.
$mail_user = basename($mail_user);
$time_stamp = basename($time_stamp);
$file_name = escapeshellcmd(basename($file_name));
$full_path = sprintf(IMP_Compose::VFS_LINK_ATTACH_PATH . '/%s/%d', $mail_user, $time_stamp);
if (!$vfsroot->exists($full_path, $file_name)) {
    throw new IMP_Exception(_("The specified attachment does not exist. It may have been deleted by the original sender."));
}

// Check to see if we need to send a verification message.
if ($conf['compose']['link_attachments_notify']) {
    if ($vfsroot->exists($full_path, $file_name . '.notify')) {
        $delete_id = Horde_Util::getFormData('d');
        try {
            $read_id = $vfsroot->read($full_path, $file_name . '.notify');
            if ($delete_id == $read_id) {
                $vfsroot->deleteFile($full_path, $file_name);
                $vfsroot->deleteFile($full_path, $file_name . '.notify');
                printf(_("Attachment %s deleted."), $file_name);
                exit;
            }
        } catch (VFS_Exception $e) {
            Horde::logMessage($read_id, 'ERR');
        }
    } else {
        /* Create a random identifier for this file. */
        $id = uniqid(mt_rand());
        try {
            $vfsroot->writeData($full_path, $file_name . '.notify', $id, true);

            /* Load $mail_user's preferences so that we can use their
             * locale information for the notification message. */
            $prefs = Horde_Prefs::singleton($conf['prefs']['driver'], 'horde', $mail_user);
            $prefs->retrieve();

            $mail_identity = $injector->getInstance('Horde_Prefs_Identity')->getIdentity($mail_user);
            $mail_address = $mail_identity->getDefaultFromAddress();

            /* Ignore missing addresses, which are returned as <>. */
            if (strlen($mail_address) > 2) {
                $mail_address_full = $mail_identity->getDefaultFromAddress(true);
                $registry->setTimeZone();
                $registry->setLanguageEnvironment();

                /* Set up the mail headers and read the log file. */
                $msg_headers = new Horde_Mime_Headers();
                $msg_headers->addReceivedHeader(array(
                    'dns' => $injector->getInstance('Net_DNS_Resolver'),
                    'server' => $conf['server']['name']
                ));
                $msg_headers->addMessageIdHeader();
                $msg_headers->addUserAgentHeader();
                $msg_headers->addHeader('Date', date('r'));
                $msg_headers->addHeader('From', $mail_address_full);
                $msg_headers->addHeader('To', $mail_address_full);
                $msg_headers->addHeader('Subject', _("Notification: Linked attachment downloaded"));

                $msg = new Horde_Mime_Part();
                $msg->setType('text/plain');
                $msg->setCharset($registry->getCharset());

                $d_url = new Horde_Url(Horde::selfUrl(true, false, true));
                $msg->setContents(Horde_String::wrap(sprintf(_("Your linked attachment has been downloaded by at least one user.\n\nAttachment name: %s\nAttachment date: %s\n\nClick on the following link to permanently delete the attachment:\n%s"), $file_name, date('r', $time_stamp), $d_url->add('d', $id))));

                $msg->send($mail_address, $msg_headers);
            }
        } catch (VFS_Exception $e) {
            Horde::logMessage($e, 'ERR');
        }
    }
}

// Find the file's mime-type.
try {
    $file_data = $vfsroot->read($full_path, $file_name);
} catch (VFS_Exception $e) {
    Horde::logMessage($file_data, 'ERR');
    throw new IMP_Exception(_("The specified file cannot be read."));
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
