<?php
/**
 * Copyright 2004-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2004-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Handles spam/innocent reporting within IMP.
 *
 * Copyright 2004-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2004-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Spam
{
    /* Constants. */
    const INNOCENT = 1;
    const SPAM = 2;

    /**
     * Reports a list of messages as innocent/spam.
     *
     * @param IMP_Indices $indices  An indices object.
     * @param integer $action       Either self::SPAM or self::INNOCENT.
     * @param array $opts           Additional options:
     *   - mailboxob: (IMP_Mailbox_List) Update this mailbox list object.
     *                DEFAULT: No update.
     *
     * @return integer  1 if messages have been deleted, 2 if messages have
     *                  been moved.
     */
    public function report(IMP_Indices $indices, $action,
                           array $opts = array())
    {
        global $injector, $notification, $prefs, $registry;

        switch ($action) {
        case self::INNOCENT:
            $config = $injector->getInstance('IMP_Imap')->innocent_params;
            break;

        case self::SPAM:
            $config = $injector->getInstance('IMP_Imap')->spam_params;
            break;

        default:
            $config = array();
            break;
        }

        /* Abort immediately if spam reporting has not been enabled, or if
         * there are no messages. */
        if (empty($config) || !count($indices)) {
            return 0;
        }

        $imp_compose = $injector->getInstance('IMP_Factory_Compose')->create();
        $imp_contents = $injector->getInstance('IMP_Factory_Contents');
        $report_count = $result = 0;

        foreach ($indices as $ob) {
            try {
                $ob->mbox->uidvalid;
            } catch (IMP_Exception $e) {
                continue;
            }

            foreach ($ob->uids as $idx) {
                try {
                    $contents = $imp_contents->create($ob->mbox->getIndicesOb($idx));
                } catch (IMP_Exception $e) {
                    continue;
                }

                $raw_msg = null;
                $report_flag = false;

                /* Report to program. */
                if (!empty($config['program'])) {
                    $raw_msg = $contents->fullMessageText(array(
                        'stream' => true
                    ));

                    /* Use a pipe to write the message contents. This should
                     * be secure. */
                    $proc = proc_open(
                        $this->_expand($config['program']),
                        array(
                            0 => array('pipe', 'r'),
                            1 => array('pipe', 'w'),
                            2 => array('pipe', 'w')
                        ),
                        $pipes
                    );
                    if (!is_resource($proc)) {
                        Horde::log(sprintf('Cannot open spam reporting program: %s', $prog), 'ERR');
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
                        Horde::log(sprintf('Error reporting spam: %s', $stderr), 'ERR');
                    }

                    proc_close($proc);
                    $report_flag = true;
                }

                /* Report to e-mail address. */
                if (!empty($config['email'])) {
                    $format = empty($config['email_format'])
                        ? 'digest'
                        : $config['email_format'];
                    $to = $this->_expand($config['email']);

                    switch ($format) {
                    case 'redirect':
                        /* Send the message. */
                        try {
                            $imp_compose->redirectMessage(new IMP_Indices($ob->mbox, $idx));
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
                            $raw_msg = $contents->fullMessageText(array(
                                'stream' => true
                            ));
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
                        $spam_headers->addHeader('Subject', sprintf(_("%s report from %s"), $action == self::SPAM ? 'spam' : 'innocent', $registry->getAuth()));

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
            $hdrs = $contents->getHeader();
            if ($subject = $hdrs->getValue('subject')) {
                $subject = Horde_String::truncate($subject, 30);
            } elseif ($from = $hdrs->getValue('from')) {
                $from = Horde_String::truncate($from, 30);
            } else {
                $subject = '[' . _("No Subject") . ']';
            }

            switch ($action) {
            case self::INNOCENT:
                $msg = $subject
                    ? sprintf(_("The message \"%s\" has been reported as innocent."), $subject)
                    : sprintf(_("The message from \"%s\" has been reported as innocent."), $from);
                break;

            case self::SPAM:
                $msg = $subject
                    ? sprintf(_("The message \"%s\" has been reported as spam."), $subject)
                    : sprintf(_("The message from \"%s\" has been reported as spam."), $from);
                break;
            }
        } elseif ($action == self::INNOCENT) {
            $msg = sprintf(_("%d messages have been reported as innocent."), $report_count);
        } else {
            $msg = sprintf(_("%d messages have been reported as spam."), $report_count);
        }
        $notification->push($msg, 'horde.message');

        $mbox_args = array();
        if (isset($opts['mailboxob'])) {
            $mbox_args['mailboxob'] = $opts['mailboxob'];
        }

        /* Run post-reporting hook. */
        try {
            Horde::callHook('post_spam', array($action == self::SPAM ? 'spam' : 'innocent', $indices), 'imp');
        } catch (Horde_Exception_HookNotSet $e) {}

        /* Delete/move message after report. */
        switch ($action) {
        case self::INNOCENT:
            /* Always flag messages as NotJunk. */
            $imp_message = $injector->getInstance('IMP_Message');
            $imp_message->flag(array('$notjunk'), $indices, true);
            $imp_message->flag(array('$junk'), $indices, false);

            if (($result = $prefs->getValue('move_innocent_after_report')) &&
                !$imp_message->copy('INBOX', 'move', $indices, $mbox_args)) {
                $result = 0;
            }
            break;

        case self::SPAM:
            /* Always flag messages as Junk. */
            $imp_message = $injector->getInstance('IMP_Message');
            $imp_message->flag(array('$junk'), $indices, true);
            $imp_message->flag(array('$notjunk'), $indices, false);

            switch ($result = $prefs->getValue('delete_spam_after_report')) {
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
                if ($targetMbox = IMP_Mailbox::getPref(IMP_Mailbox::MBOX_SPAM)) {
                    if (!$imp_message->copy($targetMbox, 'move', $indices, array_merge($mbox_args, array('create' => true)))) {
                        $result = 0;
                    }
                } else {
                    $notification->push(_("Could not move message to spam mailbox - no spam mailbox defined in preferences."), 'horde.error');
                    $result = 0;
                }
                break;
            }
            break;
        }

        return $result;
    }

    /**
     * Expand placeholders in 'email' and 'program' options.
     *
     * @param string $str  The option.
     *
     * @return string  The expanded option.
     */
    private function _expand($str)
    {
        global $registry;

        $replace = array(
            '%u' => escapeshellarg($registry->getAuth()),
            '%l' => escapeshellarg($registry->getAuth('bare')),
            '%d' => escapeshellarg($registry->getAuth('domain'))
        );

        return str_replace(
            array_keys($replace),
            array_values($replace),
            $str
        );
    }

}
