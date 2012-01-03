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

class Horde_ActiveSync_Folder_Imap
{
    /** The UID validity status */
    const UIDVALIDITY = 'uidvalidity';

    /** The next UID status */
    const UIDNEXT     = 'uidnext';

    /** The MODSEQ value */
    const MODSEQ      = 'modseq';

    /**
     * The folder status.
     *
     * @var array
     */
    protected $_status;

    /**
     * The folder's current message list.
     * An array of UIDs.
     *
     * @var array
     */
    protected $_messages;

    protected $_added;
    protected $_changed;
    protected $_removed;

    /**
     * The server id for this folder
     */
    protected $_serverid;

    /**
     * Const'r
     *
     * @var array $status  The folder status array. Should contain:
     *<pre>
     *
     * </pre>
     * @var array $messages  An array of messages. Each entry contains:
     *<pre>
     *   -uid  The message UID
     *   -seen The message's seen status.
     *</pre>
     */
    public function __construct(
        $serverid,
        array $status)
    {
        $this->_imap = $imap;
        $this->_serverid = $serverid;
    }

    public function serverid()
    {
        return $this->_serverid;
    }

    public function setChanges($messages) {
        $this->_added = array_diff($messages, $this->_messages);
        $this->_changed = array_diff($messages, $this->_add);
    }

    public function setRemoved($message_ids) {
        $this->_removed = $message_ids;
    }

    /**
     * Updates the internal UID cache, and clears the internal
     * update/deleted/changed cache.
     */
    public function updateState()
    {
        $this->_messages = array_diff($this->_messages, $this->_removed);
        $this->_removed = array();
        foreach ($this->_added as $add) {
            $this->_messages[] = $add;
        }
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
        return $this->_status[self::MODSEQ];
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
        return new Horde_Imap_Client_Ids(array_keys($this->_messages));
    }

    /**
     * Indicate if there was a complete folder reset.
     *
     * @param Horde_ActiveSync_Folder_Imap  The folder to compare against.
     *
     * @return boolean  True if there was a complete folder reset, false if not.
     */
    public function isReset(Horde_ActiveSync_Folder_Imap $folder)
    {
        if ($this->uidvalidity() != $folder->uidvalidity()) {
            return true;
        }

        return false;
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
        return serialize(array($this->_status, $this->_messages));
    }

    /**
     * Reconstruct the object from serialized data.
     *
     * @param string $data  The serialized data.
     */
    public function unserialize($data)
    {
        list($this->_status, $this->_messages) = @unserialize($data);
    }

    /**
     * Convert the instance into a string.
     *
     * @return string The string representation for this instance.
     */
    public function __toString()
    {
        return sprintf(
            "uidvalidity: %s\nuidnext: %s\nuids: %s\nmodseq: %s",
            $this->uidvalidity(),
            $this->uidnext(),
            join(', ', $this->ids(),
            $this->modseq())
        );
    }


}
