<?php
/**
 * Horde_ActiveSync_Folder_Imap::
 *
 * PHP Version 5
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * The class contains functionality for maintaining state for a single IMAP
 * folder, and generating server deltas.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Folder_Imap extends Horde_ActiveSync_Folder_Base implements Serializable
{
    /* Key names for various IMAP server status values */
    const UIDVALIDITY    = 'uidvalidity';
    const UIDNEXT        = 'uidnext';
    const HIGHESTMODSEQ  = 'highestmodseq';

    /* Serialize version */
    const VERSION        = 1;

    /**
     * The folder's current message list.
     *
     * @var array
     */
    protected $_messages = array();

    /**
     * Internal cache of message UIDs that have been added since last sync.
     * Used for transporting changes back to activesync.
     *
     * @var array
     */
    protected $_added = array();

    /**
     * Internal cache of message UIDs that have been modified on the server
     * since the last sync. Used for transporting changes back to activesync.
     *
     * @var array
     */
    protected $_changed = array();

    /**
     * Internal cache of message UIDs that have been expunged from the IMAP
     * server since last sync. Used for transporting changes back to activesync.
     *
     * @var array
     */
    protected $_removed = array();

    /**
     * Internal cache of message flag changes. Should be one entry for each UID
     * also listed in the $_changed array. Used for transporting changes back to
     * activesync. An array keyed by message UID:
     *   uid => array('read' => 1)
     *
     * @var array
     */
    protected $_flags = array();

    /**
     * Set message changes.
     *
     * @param array $messages  An array of message UIDs.
     * @param array $flags     A hash of message read flags, keyed by UID.
     */
    public function setChanges(array $messages, array $flags = array())
    {
        foreach ($messages as $uid) {
            if ($uid >= $this->uidnext()) {
                $this->_added[] = $uid;
            } elseif ($uid >= $this->minuid()) {
                if ($this->modseq() > 0) {
                    $this->_changed[] = $uid;
                } else {
                    if ($flags[$uid]['read'] != $this->_messages[$uid]['read'] ||
                        (isset($flags[$uid]['flagged']) && $flags[$uid]['flagged'] != $this->_messages[$uid]['flagged']) ||
                        (!isset($flags[$uid]['flagged']) && isset($this->_messages[$uid]['flagged']))) {

                        $this->_changed[] = $uid;
                    }
                }
            }
        }
        $this->_flags = $flags;
    }

    /**
     * Check the validity of various values.
     *
     * @param array $params  A status array containing status to check.
     *
     * @throws Horde_ActiveSync_Exception_StaleState
     */
    public function checkValidity(array $params = array())
    {
        if (!empty($params[self::UIDVALIDITY]) && $this->uidvalidity() != $params[self::UIDVALIDITY]) {
            throw new Horde_ActiveSync_Exception_StaleState('UIDVALIDTY no longer valid');
        }
    }

    /**
     * Set the list of expunged message UIDs.
     *
     * @param array $uids  An array of message UIDs that have been expunged.
     * @throws Horde_ActiveSync_Exception_StaleState
     */
    public function setRemoved(array $uids)
    {
        // Protect against HUGE numbers of UIDs from apparently broken(?) servers.
        if (count($uids)) {
            if ($uids[0] < $this->minuid()) {
                throw new Horde_ActiveSync_Exception_StaleState(
                    'BROKEN IMAP server has returned all VANISHED UIDs.');
            }
        }

        $this->_removed = $uids;
    }

    /**
     * Updates the internal UID cache if needed and clears the internal
     * update/deleted/changed cache. To be called after all changes have
     * been dealt with by the activesync client.
     */
    public function updateState()
    {
        if (empty($this->_status[self::HIGHESTMODSEQ]) && $this->_status[self::UIDNEXT]) {
            $this->_messages = array_diff(array_keys($this->_messages), $this->_removed);
            foreach ($this->_added as $add) {
                $this->_messages[] = $add;
            }
            $this->_messages = array_intersect_key($this->_flags, array_flip($this->_messages));
        } else {
            foreach ($this->_added as $add) {
                $this->_messages[] = $add;
            }
            $this->_messages = array_diff($this->_messages, $this->_removed);
        }

        // Clean up
        $this->_removed = array();
        $this->_added = array();
        $this->_changed = array();
        $this->_flags = array();
    }

    /**
     * Return the folder's UID validity.
     *
     * @return string The folder UID validity marker.
     */
    public function uidvalidity()
    {
        return $this->_status[self::UIDVALIDITY];
    }

    /**
     * Return the folder's next UID number.
     *
     * @return string The next UID number.
     */
    public function uidnext()
    {
        return empty($this->_status[self::UIDNEXT])
            ? 0
            : $this->_status[self::UIDNEXT];
    }

    /**
     * Return the folder's MODSEQ value.
     *
     * @return string  The MODSEQ number.
     */
    public function modseq()
    {
        return empty($this->_status[self::HIGHESTMODSEQ])
            ? 0
            : $this->_status[self::HIGHESTMODSEQ];
    }

    /**
     * Return the list of UIDs currently on the device.
     *
     * @return array The list of backend messages.
     */
    public function messages()
    {
        return empty($this->_status[self::HIGHESTMODSEQ])
            ? array_keys($this->_messages)
            : $this->_messages;
    }

    /**
     * Return the internal message flags changes cahce.
     *
     * @return array  The array of message flag changes.
     */
    public function flags()
    {
        return $this->_flags;
    }

    /**
     * Return the list of UIDs that need to be added to the device.
     *
     * @return array  The list of UIDs.
     */
    public function added()
    {
        return $this->_added;
    }

    /**
     * Return the list of UIDs that need to have flag changes sent to the device
     *
     * @return array The list of UIDs.
     */
    public function changed()
    {
        return $this->_changed;
    }

    /**
     * Return the list of UIDs that need to be removed from the device.
     *
     * @return array  The list of UIDs.
     */
    public function removed()
    {
        return $this->_removed;
    }

    /**
     * Return the minimum IMAP UID contained in this folder.
     *
     * @return integer  The IMAP UID.
     */
    public function minuid()
    {   if (empty($this->_status[self::HIGHESTMODSEQ])) {
            return min(array_keys($this->_messages));
        }
        return min($this->_messages);
    }

    /**
     * Serialize this object.
     *
     * @return string  The serialized data.
     */
    public function serialize()
    {
        return serialize(array(
            's' => $this->_status,
            'm' => $this->_messages,
            'f' => $this->_serverid,
            'c' => $this->_class,
            'v' => self::VERSION)
        );
    }

    /**
     * Reconstruct the object from serialized data.
     *
     * @param string $data  The serialized data.
     * @throws Horde_ActiveSync_Exception_StaleState
     */
    public function unserialize($data)
    {   $data = @unserialize($data);
        if (!is_array($data) || empty($data['v']) || $data['v'] != self::VERSION) {
            throw new Horde_ActiveSync_Exception_StaleState('Cache vesion change');
        }
        $this->_status = $data['s'];
        $this->_messages = $data['m'];
        $this->_serverid = $data['f'];
        $this->_class = $data['c'];
    }

    /**
     * Convert the instance into a string.
     *
     * @return string The string representation for this instance.
     */
    public function __toString()
    {
        return sprintf(
            'status: %s\nchanged: %s\nadded: %s\nremoved: %s',
            join(', ', $this->_status),
            join(', ', $this->_changed),
            join(', ', $this->_added),
            join(', ', $this->_removed)
        );
    }

}
