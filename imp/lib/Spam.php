<?php
/**
 * The IMP_Spam:: class contains functions related to reporting spam
 * messages in IMP.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Spam
{
    /**
     * Reports a list of messages as spam, based on the local configuration
     * parameters.
     *
     * @param IMP_Indices $indices  An indices object.
     * @param string $action        Either 'spam' or 'notspam'.
     * @param array $opts           Additional options:
     *   - mailboxob: (IMP_Mailbox_List) Update this mailbox list object.
     *                DEFAULT: No update.
     *   - noaction: (boolean) Don't perform any action after reporting?
     *               DEFAULT: false
     *
     * @return integer  1 if messages have been deleted, 2 if messages have
     *                  been moved.
     */
    static public function reportSpam(IMP_Indices $indices, $action,
                                      array $opts = array())
    {
        global $conf, $injector, $notification, $prefs, $registry;

        /* Abort immediately if spam reporting has not been enabled, or if
         * there are no messages. */
        if (empty($conf[$action]['reporting']) ||
            !count($indices)) {
            return 0;
        }

        $report_count = $result = 0;

        foreach ($indices as $ob) {
            try {
                $ob->mbox->uidvalid;
            } catch (IMP_Exception $e) {
                continue;
            }

            foreach ($ob->uids as $idx) {
                /* Fetch the raw message contents (headers and complete
                 * body). */
                try {
                    $imp_contents = $injector->getInstance('IMP_Factory_Contents')->create($ob->mbox->getIndicesOb($idx));
                } catch (IMP_Exception $e) {
                    continue;
                }

                $raw_msg = $to = null;
                $report_flag = false;

                /* If a (not)spam reporting program has been provided, use
                 * it. */
                if (!empty($conf[$action]['program'])) {
                    $raw_msg = $imp_contents->fullMessageText(array('stream' => true));

                    /* Use a pipe to write the message contents. This should
                     * be secure. */
                    $prog = str_replace(array('%u','%l', '%d'),
                        array(
                            escapeshellarg($registry->getAuth()),
                            escapeshellarg($registry->getAuth('bare')),
                            escapeshellarg($registry->getAuth('domain'))
                        ), $conf[$action]['program']);
                    $proc = proc_open($prog,
                        array(
                            0 => array('pipe', 'r'),
                            1 => array('pipe', 'w'),
                            2 => array('pipe', 'w')
                        ), $pipes);
                    if (!is_resource($proc)) {
                        Horde::logMessage('Cannot open process ' . $prog, 'ERR');
                        return 0;
                    }
                    stream_copy_to_stream($raw_msg, $pipes[0]);
                    fclose($pipes[0]);
                    $stderr = '';
                    while (!feof($pipes[2])) {
                        $stderr .= fgets($pipes[2]);
                    }
                    fclose($pipes[2]);
                    if (!empty($stderr)) {
                        Horde::logMessage('Error reporting spam: ' . $stderr, 'ERR');
                    }
                    proc_close($proc);
                    $report_flag = true;
                }

                /* If a (not)spam reporting email address has been provided,
                 * use it. */
                if (!empty($conf[$action]['email'])) {
                    $to = $conf[$action]['email'];
                } else {
                    /* Call the email generation hook, if requested. */
                    try {
                        $to = Horde::callHook('spam_email', array($action), 'imp');
                    } catch (Horde_Exception_HookNotSet $e) {}
                }

                if ($to) {
                    if (!isset($imp_compose)) {
                        $imp_compose = $injector->getInstance('IMP_Factory_Compose')->create();
                    }

                    if (!isset($conf[$action]['email_format'])) {
                        $conf[$action]['email_format'] = 'digest';
                    }

                    switch ($conf[$action]['email_format']) {
                    case 'redirect':
                        $index = new IMP_Indices($ob->mbox, $idx);
                        $imp_compose->redirectMessage($index);

                        /* Send the message. */
                        try {
                            $imp_compose->sendRedirectMessage($to, false);
                            $report_flag = true;
                        } catch (IMP_Compose_Exception $e) {
                            $e->log();
                        }
                        break;

                    case 'digest':
                    default:
                        try {
                            $from_line = $injector->getInstance('IMP_Identity')->getFromLine();
                        } catch (Horde_Exception $e) {
                            $from_line = null;
                        }

                        if (!isset($raw_msg)) {
                            $raw_msg = $imp_contents->fullMessageText(array('stream' => true));
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
                        if (!is_null($from_line)) {
                            $spam_headers->addHeader('From', $from_line);
                        }
                        $spam_headers->addHeader('Subject', sprintf(_("%s report from %s"), $action, $registry->getAuth()));

                        /* Send the message. */
                        try {
                            $recip_list = $imp_compose->recipientList(array('to' => $to));
                            $imp_compose->sendMessage($recip_list['list'], $spam_headers, $mime, 'UTF-8');
                            $report_flag = true;
                        } catch (IMP_Compose_Exception $e) {
                            $e->log();
                        }
                        break;
                    }
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
        if ($report_count == 1) {
            $hdrs = $imp_contents->getHeader();
            if ($subject = $hdrs->getValue('subject')) {
                $subject = Horde_String::truncate($subject, 30);
            } elseif ($from = $hdrs->getValue('from')) {
                $from = Horde_String::truncate($from, 30);
            } else {
                $subject = '[' . _("No Subject") . ']';
            }

            switch ($action) {
            case 'spam':
                $msg = $subject
                    ? sprintf(_("The message \"%s\" has been reported as spam."), $subject)
                    : sprintf(_("The message from \"%s\" has been reported as spam."), $from);
                break;

            case 'notspam':
                $msg = $subject
                    ? sprintf(_("The message \"%s\" has been reported as innocent."), $subject)
                    : sprintf(_("The message from \"%s\" has been reported as innocent."), $from);
                break;
            }
        } elseif ($action == 'spam') {
            $msg = sprintf(_("%d messages have been reported as spam."), $report_count);
        } else {
            $msg = sprintf(_("%d messages have been reported as innocent."), $report_count);
        }
        $notification->push($msg, 'horde.message');

        $mbox_args = array();
        if (isset($opts['mailboxob'])) {
            $mbox_args['mailboxob'] = $opts['mailboxob'];
        }

        /* Run post-reporting hook. */
        try {
            Horde::callHook('post_spam', array($action, $indices), 'imp');
        } catch (Horde_Exception_HookNotSet $e) {}

        if (!empty($opts['noaction'])) {
            return $result;
        }

        /* Delete/move message after report. */
        switch ($action) {
        case 'spam':
            /* Always flag messages as Junk. */
            $imp_message = $injector->getInstance('IMP_Message');
            $imp_message->flag(array('$junk'), $indices, true);
            $imp_message->flag(array('$notjunk'), $indices, false);

            if ($result = $prefs->getValue('delete_spam_after_report')) {
                switch ($result) {
                case 1:
                    $msg_count = $imp_message->delete($indices, $mbox_args);
                    if ($msg_count === false) {
                        $result = 0;
                    } else {
                        if ($msg_count == 1) {
                            $notification->push(_("The message has been deleted."), 'horde.message');
                        } else {
                            $notification->push(sprintf(_("%d messages have been deleted."), $msg_count), 'horde.message');
                        }
                    }
                    break;

                case 2:
                    if ($targetMbox = IMP_Mailbox::getPref('spam_folder')) {
                        if (!$imp_message->copy($targetMbox, 'move', $indices, array_merge($mbox_args, array('create' => true)))) {
                            $result = 0;
                        }
                    } else {
                        $notification->push(_("Could not move message to spam mailbox - no spam mailbox defined in preferences."), 'horde.error');
                        $result = 0;
                    }
                    break;
                }
            }
            break;

        case 'notspam':
            /* Always flag messages as NotJunk. */
            $imp_message = $injector->getInstance('IMP_Message');
            $imp_message->flag(array('$notjunk'), $indices, true);
            $imp_message->flag(array('$junk'), $indices, false);

            if (($result = $prefs->getValue('move_innocent_after_report')) &&
                !$imp_message->copy('INBOX', 'move', $indices, $mbox_args)) {
                $result = 0;
            }
            break;
        }

        return $result;
    }

}
