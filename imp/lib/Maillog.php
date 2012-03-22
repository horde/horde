<?php
/**
 * This class contains all functions related to handling logging of responses
 * to individual e-mail messages.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Maillog
{
    /* Log Actions. */
    const FORWARD = 'forward';
    const MDN = 'mdn';
    const REDIRECT = 'redirect';
    const REPLY = 'reply';
    const REPLY_ALL = 'reply_all';
    const REPLY_LIST = 'reply_list';

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
    static public function log($type, $msg_ids, $data = null)
    {
        $history = $GLOBALS['injector']->getInstance('Horde_History');

        if (!is_array($msg_ids)) {
            $msg_ids = array($msg_ids);
        }

        foreach ($msg_ids as $val) {
            switch ($type) {
            case IMP_Compose::FORWARD:
            case IMP_Compose::FORWARD_ATTACH:
            case IMP_Compose::FORWARD_BODY:
            case IMP_Compose::FORWARD_BOTH:
                $params = array(
                    'action' => self::FORWARD,
                    'recipients' => $data
                );
                break;

            case self::MDN:
                $params = array(
                    'action' => self::MDN,
                    'type' => $data
                );
                break;

            case IMP_Compose::REDIRECT:
                $params = array(
                    'action' => self::REDIRECT,
                    'recipients' => $data
                );
                break;

            case IMP_Compose::REPLY:
            case IMP_Compose::REPLY_SENDER:
                $params = array(
                    'action' => self::REPLY
                );
                break;

            case IMP_Compose::REPLY_ALL:
                $params = array(
                    'action' => self::REPLY_ALL
                );
                break;

            case IMP_Compose::REPLY_LIST:
                $params = array(
                    'action' => self::REPLY_LIST
                );
                break;

            default:
                $params = null;
                break;
            }

            if ($params) {
                try {
                    $history->log(self::_getUniqueHistoryId($val), $params);
                } catch (Exception $e) {
                    /* On error, log the error message only since informing the
                     * user is just a waste of time and a potential point of
                     * confusion, especially since they most likely don't even
                     * know the message is being logged. */
                    $entry = sprintf('Could not log message details to Horde_History. Error returned: %s', $e->getMessage());
                    Horde::logMessage($entry, 'ERR');
                }
            }
        }
    }

    /**
     * Retrieve any history for the given Message-ID.
     *
     * @param string $msg_id  The Message-ID of the message.
     *
     * @return Horde_History_Log  The object containing the log information.
     * @throws Horde_Exception
     */
    static public function getLog($msg_id)
    {
        return $GLOBALS['injector']->getInstance('Horde_History')->getHistory(self::_getUniqueHistoryId($msg_id));
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
    static public function sentMDN($msg_id, $type)
    {
        try {
            $msg_history = self::getLog($msg_id);
        } catch (Horde_Exception $e) {
            return false;
        }

        if ($msg_history) {
            foreach ($msg_history as $entry) {
                if (($entry['action'] == 'mdn') && ($entry['type'] == $type)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Retrieve any history for the given Message-ID and (optionally) display
     * via the Horde notification system.
     *
     * @param string $msg_id  The Message-ID of the message.
     */
    static public function displayLog($msg_id)
    {
        foreach (self::parseLog($msg_id) as $entry) {
            $GLOBALS['notification']->push($entry['msg'], 'imp.' . $entry['action']);
        }
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
    static public function parseLog($msg_id)
    {
        try {
            if (!$msg_history = self::getLog($msg_id)) {
                return array();
            }
        } catch (Horde_Exception $e) {
            return array();
        }

        $df = $GLOBALS['prefs']->getValue('date_format');
        $tf = $GLOBALS['prefs']->getValue('time_format');
        $ret = array();

        foreach ($msg_history as $entry) {
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
    static public function deleteLog($msg_ids)
    {
        if (!is_array($msg_ids)) {
            $msg_ids = array($msg_ids);
        }
        $GLOBALS['injector']->getInstance('Horde_History')->removeByNames(
            array_map(array('IMP_Maillog', '_getUniqueHistoryId'), $msg_ids)
        );
    }

    /**
     * Generate the unique log ID for a forward/reply/redirect event.
     *
     * @param string $msgid  The Message-ID of the original message.
     *
     * @return string  The unique log ID to use with Horde_History::.
     */
    static protected function _getUniqueHistoryId($msgid)
    {
        return is_array($msgid)
            ? ''
            : implode('.', array('imp', str_replace('.', '*', $GLOBALS['registry']->getAuth()), $msgid));
    }

}
