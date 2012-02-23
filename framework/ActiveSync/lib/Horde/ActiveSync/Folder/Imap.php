<?php
/**
 * Horde_ActiveSync_Folder_Imap
 *
 * PHP Version 5
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2010-2012 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=ActiveSync
 * @package   ActiveSync
 */
/**
  * The class contains functionality for maintaining state for a single IMAP
  * folder, and generating server deltas.
  *
  * @license   http://www.horde.org/licenses/gpl GPLv2
  * @copyright 2012 Horde LLC (http://www.horde.org/)
  * @author    Michael J Rubinsky <mrubinsk@horde.org>
  * @link      http://pear.horde.org/index.php?package=ActiveSync
  * @package   ActiveSync
  */

class Horde_ActiveSync_Folder_Imap extends Horde_ActiveSync_Folder_Base
{
    /** The UID validity status */
    const UIDVALIDITY = 'uidvalidity';

    /** The next UID status */
    const UIDNEXT     = 'uidnext';

    /** The MODSEQ value */
    const MODSEQ      = 'highestmodseq';

    /**
     * The folder's current message list.
     * An array of UIDs.
     *
     * @var array
     */
    protected $_messages = array();
    protected $_added = array();
    protected $_changed = array();
    protected $_removed = array();

    public function setChanges($messages)
    {
        foreach ($messages as $uid) {
            if ($uid >= $this->uidnext()) {
                $this->_added[] = $uid;
            } else {
                $this->_changed[] = $uid;
            }
        }
    }

    public function setRemoved($message_ids)
    {
        $this->_removed = $message_ids;
    }

    public function setMessages($messages)
    {
        $this->_messages = $messages;
    }

    /**
     * Updates the internal UID cache, and clears the internal
     * update/deleted/changed cache.
     */
    public function updateState()
    {
        // If we support modseq, do not bother keeping a cache of messages,
        // since we do not need them.
        if (!$this->modseq()) {
            $this->_messages = array_diff($this->_messages, $this->_removed);
            foreach ($this->_added as $add) {
                $this->_messages[] = $add;
            }
        }
        $this->_removed = array();
        $this->_added = array();
        $this->_changed = array();
    }

    /**
     * Return the folder UID validity.
     *
     * @return string The folder UID validity marker.
     */
    public function uidvalidity()
    {
        return $this->_status[self::UIDVALIDITY];
    }

    /**
     * Return the folder next UID number.
     *
     * @return string The next UID number.
     */
    public function uidnext()
    {
        return $this->_status[self::UIDNEXT];
    }

    /**
     * Return the folder's modseq value.
     *
     * @return string  The modseq number.
     */
    public function modseq()
    {
        return empty($this->_status[self::MODSEQ]) ? 0 : $this->_status[self::MODSEQ];
    }

    /**
     * Return the backend object messages in the folder.
     *
     * @return array The list of backend messages. Each entry contains:
     *<pre>
     *    -uid     The message UID.
     *    -seen    The message's seen status.
     *</pre>
     */
    public function messages()
    {
        return $this->_messages;
    }

    public function ids()
    {
        return new Horde_Imap_Client_Ids($this->_messages);
    }

    public function added()
    {
        return $this->_added;
    }

    public function changed()
    {
        return $this->_changed;
    }

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
        return serialize(array($this->_status, $this->_messages, $this->_serverid, $this->_class));
    }

    /**
     * Reconstruct the object from serialized data.
     *
     * @param string $data  The serialized data.
     */
    public function unserialize($data)
    {
        list($this->_status, $this->_messages, $this->_servierid, $this->_class) = @unserialize($data);
    }

    /**
     * Convert the instance into a string.
     *
     * @return string The string representation for this instance.
     */
    public function __toString()
    {
        return sprintf(
            "uidvalidity: %s\nuidnext: %s\nuids: %s\nmodseq: %s\nchanged: %s\nadded: %s\nremoved: %s",
            $this->uidvalidity(),
            $this->uidnext(),
            join(', ', $this->ids()->ids),
            $this->modseq(),
            join(', ', $this->_changed),
            join(', ', $this->_added),
            join(', ', $this->_removed)
        );
    }

}
