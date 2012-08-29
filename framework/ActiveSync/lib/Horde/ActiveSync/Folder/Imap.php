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
 * @copyright 2012 Horde LLC (http://www.horde.org)
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
 * @copyright 2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Folder_Imap extends Horde_ActiveSync_Folder_Base
{
    /* Key names for various IMAP server status values */
    const UIDVALIDITY = 'uidvalidity';
    const UIDNEXT     = 'uidnext';
    const MODSEQ      = 'highestmodseq';
    const MINUID      = 'min';
    const COUNT       = 'messages';

    /**
     * The folder's current message list. Only used for servers that do not
     * support QRESYNC.
     *
     * An array of UIDs.
     *
     * @var array
     */
    protected $_messages = array();

    /**
     * Internal cache of message UIDs that have been added since last sync.
     *
     * @var array
     */
    protected $_added = array();

    /**
     * Internal cache of message UIDs that have been modified on the server
     * since the last sync.
     *
     * @var array
     */
    protected $_changed = array();

    /**
     * Internal cache of message UIDs that have been expunged from the IMAP
     * server since last sync.
     *
     * @var array
     */
    protected $_removed = array();

    /**
     * Internal cache of message flag changes. Should be one entry for each UID
     * also listed in the $_changed array. An array keyed by message UID:
     *   uid => array('read' => 1)
     *
     *  @var array
     */
    protected $_flags = array();

    /**
     * Cache the known lowest UID we received during the initial SYNC request.
     * Only available (or even needed) if server supports QRESYNC. This value
     * will never change unless the syncstate is removed.
     *
     * @var integer
     */
    protected $_min = 0;

    /**
     * Set message changes.
     *
     * @param array $messages  An array of message UIDs.
     * @param array $flags     A hash of message read flags, keyed by UID.
     */
    public function setChanges($messages, $flags = array())
    {
        foreach ($messages as $uid) {
            if ($uid >= $this->uidnext()) {
                $this->_added[] = $uid;
            } else {
                if ($this->modseq() > 0) {
                    $this->_changed[] = $uid;
                } else {
                    if ($flags[$uid]['read'] != $this->_messages[$uid]['read'] ||
                        (isset($flags[$uid]['flagged']) && $flags[$uid]['flagged'] != $this->_messages[$uid]['flagged']) ||
                         !isset($flags[$uid]['flagged'])) {

                        $this->_changed[] = $uid;
                    }
                }
            }
        }
        $this->_flags = $flags;
    }

    /**
     * Set server status values. Overrides parent class to save the MINUID.
     *
     * @param array $status  The server status array.
     */
    public function setStatus($status)
    {
        if (!empty($status[self::MINUID])) {
            $this->_min = $status[self::MINUID];
            unset($status[self::MINUID]);
        }
        parent::setStatus($status);
    }

    /**
     * Check the validity of various values.
     *
     * @throws Horde_ActiveSync_Exception_StaleState
     */
    public function checkValidity($params = array())
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
        if (!empty($this->_min) && count($uids)) {
            if ($uids[0] < $this->_min) {
                throw new Horde_ActiveSync_Exception_StaleState(
                    'BROKEN IMAP server has returned all VANISHED UIDs.');
            }
        }

        $this->_removed = $uids;
    }

    /**
     * Updates the internal UID cache if needed and clears the internal
     * update/deleted/changed cache.
     */
    public function updateState()
    {
        // If we support QRESYNC, do not bother keeping a cache of messages,
        // since we do not need them.
        if ($this->modseq() == 0) {
            $this->_messages = array_diff(array_keys($this->_messages), $this->_removed);
            foreach ($this->_added as $add) {
                $this->_messages[] = $add;
            }
            $this->_messages = array_intersect_key($this->_flags, array_flip($this->_messages));
        }
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
            ? 1
            : $this->_status[self::UIDNEXT];
    }

    /**
     * Return the total count of messages in this mailbox, if available.
     *
     * @return integer  The total count.
     */
    public function count()
    {
        return isset($this->_status[self::COUNT])
            ? $this->_status[self::COUNT]
            : null;
    }

    /**
     * Return the folder's MODSEQ value.
     *
     * @return string  The MODSEQ number.
     */
    public function modseq()
    {
        return empty($this->_status[self::MODSEQ])
            ? 0
            : $this->_status[self::MODSEQ];
    }

    /**
     * Return the list of UIDs currently on the device.
     *
     * @return array The list of backend messages.
     */
    public function messages()
    {
        return array_keys($this->_messages);
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
     * Serialize this object.
     *
     * @return string  The serialized data.
     */
    public function serialize()
    {
        return serialize(array($this->_status, $this->_messages, $this->_serverid, $this->_class, $this->_min));
    }

    /**
     * Reconstruct the object from serialized data.
     *
     * @param string $data  The serialized data.
     */
    public function unserialize($data)
    {
        list($this->_status, $this->_messages, $this->_serverid, $this->_class, $this->_min) = @unserialize($data);
    }

    /**
     * Convert the instance into a string.
     *
     * @return string The string representation for this instance.
     */
    public function __toString()
    {
        return sprintf(
            "status: %s\nchanged: %s\nadded: %s\nremoved: %s",
            join(', ', $this->_status),
            join(', ', $this->_changed),
            join(', ', $this->_added),
            join(', ', $this->_removed)
        );
    }

}
