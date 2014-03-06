<?php
/**
 * Copyright 2003-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2003-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Base class implementing logging of responses to e-mail messages.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2003-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Maillog
{
    /** Log Actions. */
    const FORWARD = 'forward';
    const MDN = 'mdn';
    const REDIRECT = 'redirect';
    const REPLY = 'reply';
    const REPLY_ALL = 'reply_all';
    const REPLY_LIST = 'reply_list';

    /**
     * Storage driver.
     *
     * @var IMP_Maillog_Storage_Base
     */
    public $storage;

    /**
     * Constructor.
     *
     * @param IMP_Maillog_Storage_Base $storage  Storage driver.
     */
    public function __construct(IMP_Maillog_Storage_Base $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Create a log entry.
     *
     * @param mixed $type     Either an IMP_Compose:: constant or an
     *                        IMP_Maillog:: constant.
     * @param mixed $msg_ids  Either a single Message-ID or an array of
     *                        Message-IDs to log.
     * @param string $data    Any additional data to store. For forward and
     *                        redirect this is the list of recipients the
     *                        message was sent to. For mdn this is the
     *                        MDN-type of the message that was sent.
     */
    public function log($type, $msg_ids, $data = null)
    {
        if (!is_array($msg_ids)) {
            $msg_ids = array($msg_ids);
        }

        foreach (array_filter($msg_ids) as $val) {
            switch ($type) {
            case IMP_Compose::FORWARD:
            case IMP_Compose::FORWARD_ATTACH:
            case IMP_Compose::FORWARD_BODY:
            case IMP_Compose::FORWARD_BOTH:
            case self::FORWARD:
                $this->storage->saveLog($val, array(
                    'action' => self::FORWARD,
                    'recipients' => $data
                ));
                break;

            case self::MDN:
                $this->storage->saveLog($val, array(
                    'action' => self::MDN,
                    'type' => $data
                ));
                break;

            case IMP_Compose::REDIRECT:
            case self::REDIRECT:
                $this->storage->saveLog($val, array(
                    'action' => self::REDIRECT,
                    'recipients' => $data
                ));
                break;

            case IMP_Compose::REPLY:
            case IMP_Compose::REPLY_SENDER:
            case self::REPLY:
                $this->storage->saveLog($val, array(
                    'action' => self::REPLY
                ));
                break;

            case IMP_Compose::REPLY_ALL:
            case self::REPLY_ALL:
                $this->storage->saveLog($val, array(
                    'action' => self::REPLY_ALL
                ));
                break;

            case IMP_Compose::REPLY_LIST:
            case self::REPLY_LIST:
                $this->storage->saveLog($val, array(
                    'action' => self::REPLY_LIST
                ));
                break;
            }
        }
    }

    /**
     * Retrieve any history for the given Message-ID.
     *
     * @param string $msg_id  The Message-ID of the message.
     *
     * @return Horde_History_Log  The object containing the log information.
     */
    public function getLog($msg_id)
    {
        return strlen($msg_id)
            ? $this->storage->getLog($msg_id)
            : new Horde_History_Log($msg_id);
    }

    /**
     * Determines if an MDN notification of a certain type has been sent
     * previously for this message-ID.
     *
     * @param string $msg_id  The Message-ID of the message.
     * @param string $type    The type of MDN.
     *
     * @return boolean  True if a MDN has been sent for this message with
     *                  the given type.
     */
    public function sentMDN($msg_id, $type)
    {
        try {
            $msg_history = $this->getLog($msg_id);
        } catch (Horde_Exception $e) {
            return false;
        }

        foreach ($msg_history as $entry) {
            if (($entry['action'] == 'mdn') && ($entry['type'] == $type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns log information for a message.
     *
     * @param string $msg_id  The Message-ID of the message.
     *
     * @return array  List of log information. Each element is an array with
     *                the following keys:
     *   - action: (string) The log action.
     *   - msg: (string) The log message.
     */
    public function parseLog($msg_id)
    {
        global $prefs;

        try {
            $history = $this->getLog($msg_id);
        } catch (Horde_Exception $e) {
            return array();
        }

        if (!count($history)) {
            return array();
        }

        $df = $prefs->getValue('date_format');
        $tf = $prefs->getValue('time_format');
        $ret = array();

        foreach ($history as $entry) {
            $msg = null;

            if (isset($entry['desc'])) {
                $msg = $entry['desc'];
            } else {
                switch ($entry['action']) {
                case self::FORWARD:
                    $msg = sprintf(_("You forwarded this message on %%s to: %s."), $entry['recipients']);
                    break;

                case self::MDN:
                    /* We don't display 'mdn' log entries. */
                    break;

                case self::REDIRECT:
                    $msg = sprintf(_("You redirected this message to %s on %%s."), $entry['recipients']);
                    break;

                case self::REPLY:
                    $msg = _("You replied to this message on %s.");
                    break;

                case self::REPLY_ALL:
                    $msg = _("You replied to all recipients of this message on %s.");
                    break;

                case self::REPLY_LIST:
                    $msg = _("You replied to this message via the mailing list on %s.");
                    break;
                }
            }

            if ($msg) {
                $ret[$entry['ts']] = array(
                    'action' => $entry['action'],
                    'msg' => @sprintf($msg, strftime($df . ' ' . $tf, $entry['ts']))
                );
            }
        }

        ksort($ret);

        return array_values($ret);
    }

    /**
     * Delete the log entries for a given list of Message-IDs.
     *
     * @param mixed $msg_ids  Either a single Message-ID or an array
     *                        of Message-IDs to delete.
     */
    public function deleteLog($msg_ids)
    {
        $this->storage->deleteLogs(
            is_array($msg_ids) ? array_filter($msg_ids) : array($msg_ids)
        );
    }

    /**
     * Retrieve changes to the maillog since the provided timestamp.
     *
     * @param integer $ts  Timestamp.
     *
     * @return array  An array of Message-IDs changed since the provided
     *                timestamp.
     */
    public function getChanges($ts)
    {
        return $this->storage->getChanges($ts);
    }

}
