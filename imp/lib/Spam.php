<?php
/**
 * Copyright 2004-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2004-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Handles spam/innocent reporting within IMP.
 *
 * Copyright 2004-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2004-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Spam
{
    /* Action constants. */
    const INNOCENT = 1;
    const SPAM = 2;

    /**
     * Action.
     *
     * @var integer
     */
    protected $_action;

    /**
     * Driver list.
     *
     * @var array
     */
    protected $_drivers;

    /**
     * Constructor.
     *
     * @param integer $action  Either IMP_Spam::SPAM or IMP_Spam::INNOCENT.
     * @param array $drivers   List of reporting drivers.
     */
    public function __construct($action, array $drivers = array())
    {
        $this->_action = $action;
        $this->_drivers = $drivers;
    }

    /**
     * Reports a list of messages as innocent/spam.
     *
     * @param IMP_Indices $indices  An indices object.
     * @param array $opts           Additional options:
     *   - mailboxob: (IMP_Mailbox_List) Update this mailbox list object.
     *                DEFAULT: No update.
     *
     * @return integer  1 if messages have been deleted, 2 if messages have
     *                  been moved.
     */
    public function report(IMP_Indices $indices, array $opts = array())
    {
        global $injector, $notification, $prefs;

        /* Abort immediately if spam reporting has not been enabled, or if
         * there are no messages. */
        if (empty($this->_drivers) || !count($indices)) {
            return 0;
        }

        $imp_contents = $injector->getInstance('IMP_Factory_Contents');
        $report_count = 0;

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

                $report_flag = false;

                foreach ($this->_drivers as $val) {
                    if ($val->report($contents, $this->_action)) {
                        $report_flag = true;
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
            if ($subject = $hdrs['Subject']) {
                $subject = Horde_String::truncate($subject, 30);
            } elseif ($from = $hdrs['From']) {
                $from = Horde_String::truncate($from, 30);
            } else {
                $subject = '[' . _("No Subject") . ']';
            }

            switch ($this->_action) {
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
        } else {
            switch ($this->_action) {
            case self::INNOCENT:
                $msg = sprintf(_("%d messages have been reported as innocent."), $report_count);
                break;

            case self::SPAM:
                $msg = sprintf(_("%d messages have been reported as spam."), $report_count);
                break;
            }
        }
        $notification->push($msg, 'horde.message');

        $mbox_args = array();
        if (isset($opts['mailboxob'])) {
            $mbox_args['mailboxob'] = $opts['mailboxob'];
        }

        /* Run post-reporting hook. */
        try {
            $injector->getInstance('Horde_Core_Hooks')->callHook(
                'post_spam',
                'imp',
                array(
                    ($this->_action == self::SPAM) ? 'spam' : 'innocent',
                    $indices
                )
            );
        } catch (Horde_Exception_HookNotSet $e) {}

        /* Delete/move message after report. */
        switch ($this->_action) {
        case self::INNOCENT:
            /* Always flag messages as NotJunk. */
            $imp_message = $injector->getInstance('IMP_Message');
            $imp_message->flag(array(
                'add' => array(Horde_Imap_Client::FLAG_NOTJUNK),
                'remove' => array(Horde_Imap_Client::FLAG_JUNK)
            ), $indices);

            if (($result = $prefs->getValue('move_innocent_after_report')) &&
                !$indices->copy('INBOX', 'move', $mbox_args)) {
                $result = 0;
            }
            break;

        case self::SPAM:
            /* Always flag messages as Junk. */
            $imp_message = $injector->getInstance('IMP_Message');
            $imp_message->flag(array(
                'add' => array(Horde_Imap_Client::FLAG_JUNK),
                'remove' => array(Horde_Imap_Client::FLAG_NOTJUNK)
            ), $indices);

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
                    if (!$indices->copy($targetMbox, 'move', array_merge($mbox_args, array('create' => true)))) {
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

}
