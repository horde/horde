<?php
/**
 * The IMP_Maillog:: class contains all functions related to handling
 * logging of responses to individual e-mail messages.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Maillog
{
    /**
     * Create a log entry.
     *
     * @param string $type    Either 'forward', 'redirect', 'reply', or 'mdn'.
     * @param mixed $msg_ids  Either a single Message-ID or an array of
     *                        Message-IDs to log.
     * @param string $data    Any additional data to store. For 'forward' and
     *                        'redirect' this is the list of recipients the
     *                        message was sent to. For 'mdn' this is the
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
            case 'forward':
                $params = array('recipients' => $data, 'action' => 'forward');
                break;

            case 'mdn':
                $params = array('type' => $data, 'action' => 'mdn');
                break;

            case 'redirect':
                $params = array('recipients' => $data, 'action' => 'redirect');
                break;

            case 'reply':
            case 'reply_all':
            case 'reply_list':
                $params = array('action' => $type);
                break;
            }

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
     * TODO
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
                case 'forward':
                    $msg = sprintf(_("You forwarded this message on %%s to: %s."), $entry['recipients']);
                    break;

                case 'mdn':
                    /* We don't display 'mdn' log entries. */
                    break;

                case 'redirect':
                    $msg = sprintf(_("You redirected this message to %s on %%s."), $entry['recipients']);
                    break;

                case 'reply':
                    $msg = _("You replied to this message on %s.");
                    break;

                case 'reply_all':
                    $msg = _("You replied to all recipients of this message on %s.");
                    break;

                case 'reply_list':
                    $msg = _("You replied to this message via the mailing list on %s.");
                    break;
                }
            }

            if ($msg) {
                $ret[] = array(
                    'action' => $entry['action'],
                    'msg' => @sprintf($msg, strftime($df . ' ' . $tf, $entry['ts']))
                );
            }
        }

        return $ret;
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
        $msg_ids = array_map(array('IMP_Maillog', '_getUniqueHistoryId'), $msg_ids);

        $GLOBALS['injector']->getInstance('Horde_History')->removeByNames($msg_ids);
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
        if (is_array($msgid)) {
            return '';
        }

        return implode('.', array('imp', str_replace('.', '*', $GLOBALS['registry']->getAuth()), $msgid));
    }

}
