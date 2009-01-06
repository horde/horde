<?php
/**
 * The IMP_Spam:: class contains functions related to reporting spam
 * messages in IMP.
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Spam
{
    /**
     * Reports a list of messages as spam, based on the local configuration
     * parameters.
     *
     * @param mixed $indices   See IMP::parseIndicesList().
     * @param string $action   Either 'spam' or 'notspam'.
     *
     * @return integer  1 if messages have been deleted, 2 if messages have
     *                  been moved.
     */
    static public function reportSpam($indices, $action)
    {
        global $notification;

        /* Abort immediately if spam reporting has not been enabled, or if
         * there are no messages. */
        if (empty($GLOBALS['conf'][$action]['reporting']) ||
            !($msgList = IMP::parseIndicesList($indices))) {
            return 0;
        }

        $report_count = 0;

        foreach ($msgList as $mbox => $msgIndices) {
            foreach ($msgIndices as $idx) {
                /* Fetch the raw message contents (headers and complete
                 * body). */
                $imp_contents = &IMP_Contents::singleton($idx . IMP::IDX_SEP . $mbox);
                if (is_a($imp_contents, 'PEAR_Error')) {
                    continue;
                }

                $raw_msg = $to = null;
                $report_flag = false;

                /* If a (not)spam reporting program has been provided, use
                 * it. */
                if (!empty($GLOBALS['conf'][$action]['program'])) {
                    $raw_msg = $imp_contents->fullMessageText();

                    /* Use a pipe to write the message contents. This should
                     * be secure. */
                    $prog = str_replace(array('%u','%l', '%d'),
                        array(
                            escapeshellarg(Auth::getAuth()),
					        escapeshellarg(Auth::getBareAuth()),
                            escapeshellarg(Auth::getAuthDomain())
                        ), $GLOBALS['conf'][$action]['program']);
                    $proc = proc_open($prog,
                        array(
                            0 => array('pipe', 'r'),
                            1 => array('pipe', 'w'),
                            2 => array('pipe', 'w')
                        ), $pipes);
                    if (!is_resource($proc)) {
                        Horde::logMessage('Cannot open process ' . $prog, __FILE__, __LINE__, PEAR_LOG_ERR);
                        return 0;
                    }
                    fwrite($pipes[0], $raw_msg);
                    fclose($pipes[0]);
                    $stderr = '';
                    while (!feof($pipes[2])) {
                        $stderr .= fgets($pipes[2]);
                    }
                    fclose($pipes[2]);
                    if (!empty($stderr)) {
                        Horde::logMessage('Error reporting spam: ' . $stderr, __FILE__, __LINE__, PEAR_LOG_ERR);
                    }
                    proc_close($proc);
                    $report_flag = true;
                }

                /* If a (not)spam reporting email address has been provided,
                 * use it. */
                if (!empty($GLOBALS['conf'][$action]['email'])) {
                    $to = $GLOBALS['conf'][$action]['email'];
                } elseif (!empty($GLOBALS['conf']['hooks']['spam_email'])) {
                    /* Call the email generation hook, if requested. */
                    $to = Horde::callHook('_imp_hook_spam_email', array($action), 'imp');
                }

                if ($to) {
                    if (!isset($raw_msg)) {
                        $raw_msg = $imp_contents->fullMessageText();
                    }

                    if (!isset($imp_compose)) {
                        require_once 'Horde/Identity.php';
                        $imp_compose = &IMP_Compose::singleton();
                        $identity = &Identity::singleton(array('imp', 'imp'));
                        $from_line = $identity->getFromLine();
                    }

                    /* Build the MIME structure. */
                    $mime = new Horde_Mime_Part();
                    $mime->setType('multipart/digest');

                    $rfc822 = new Horde_Mime_Part();
                    $rfc822->setType('message/rfc822');
                    $rfc822->setContents($raw_msg);
                    $mime->addPart($rfc822);

                    $spam_headers = new Horde_Mime_Headers();
                    $spam_headers->addMessageIdHeader();
                    $spam_headers->addHeader('Date', date('r'));
                    $spam_headers->addHeader('To', $to);
                    $spam_headers->addHeader('From', $from_line);
                    $spam_headers->addHeader('Subject', sprintf(_("%s report from %s"), $action, $_SESSION['imp']['uniquser']));

                    /* Send the message. */
                    $imp_compose->sendMessage($to, $spam_headers, $mime, NLS::getCharset());
                    $report_flag = true;
                }

                if ($report_flag) {
                    ++$report_count;
                }
            }
        }

        if (!$report_count) {
            return 0;
        }

        /* Report what we've done. */
        switch ($action) {
        case 'spam':
            if ($report_count > 1) {
                $notification->push(sprintf(_("%d messages have been reported as spam."), $report_count), 'horde.message');
            } else {
                $notification->push(_("The message has been reported as spam."), 'horde.message');
            }
            break;

        case 'notspam':
            if ($report_count > 1) {
                    $notification->push(sprintf(_("%d messages have been reported as not spam."), $report_count), 'horde.message');
            } else {
                    $notification->push(_("The message has been reported as not spam."), 'horde.message');
            }
            break;
        }

        /* Delete spam after report. */
        $delete_spam = $GLOBALS['prefs']->getValue('delete_spam_after_report');
        if ($delete_spam) {
            $imp_message = &IMP_Message::singleton();
            switch ($delete_spam) {
            case 1:
                if ($action == 'spam') {
                    $msg_count = $imp_message->delete($indices);
                    if ($msg_count === false) {
                        $delete_spam = 0;
                    } else {
                        if ($msg_count == 1) {
                            $notification->push(_("The message has been deleted."), 'horde.message');
                        } else {
                            $notification->push(sprintf(_("%d messages have been deleted."), $msg_count), 'horde.message');
                        }
                    }
                }
                break;

            case 2:
                $targetMbox = ($action == 'spam') ? IMP::folderPref($GLOBALS['prefs']->getValue('spam_folder'), true) : 'INBOX';
                if ($targetMbox) {
                    if ($imp_message->copy($targetMbox, 'move', $indices, true) === false) {
                        $delete_spam = 0;
                    }
                } else {
                    $notification->push(_("Could not move message to spam mailbox - no spam mailbox defined in preferences."), 'horde.error');
                    $delete_spam = 0;
                }
                break;
            }
        }

        return $delete_spam;
    }
}
