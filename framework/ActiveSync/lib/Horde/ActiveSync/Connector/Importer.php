<?php
/**
 * Connector class for importing ActiveSync messages from the wbxml input stream
 * Contains code written by the Z-Push project. Original file header preserved
 * below.
 *
 * @copyright 2010-2011 The Horde Project (http://www.horde.org)
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
/**
 * File      :   streamimporter.php
 * Project   :   Z-Push
 * Descr     :   Stream import classes
 *
 * Created   :   01.10.2007
 *
 * � Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL v2.
 * Consult LICENSE file for details
 */
class Horde_ActiveSync_Connector_Importer
{
    /**
     * State machine
     *
     * @var Horde_ActiveSync_State_Base
     */
    protected $_state;

    /**
     * The backend driver for communicating with the server we are syncing with.
     *
     * @var Horde_ActiveSync_Driver_Base
     */
    protected $_backend;

    /**
     * Flags
     *
     * @var integer
     */
    protected $_flags;

    /**
     * The server specific folder id
     *
     * @var string
     */
    protected $_folderId;

    protected $_logger;

    /**
     * Const'r
     *
     * @param Horde_ActiveSync_Driver_Base $backend
     */
    public function __construct(Horde_ActiveSync_Driver_Base $backend)
    {
        $this->_backend = $backend;
    }

    /**
     * Initialize the exporter for this collection
     *
     * @param Horde_ActiveSync_State_Base $state  The state machine
     * @param string $folderId                    The collection's id
     * @param integer $flags                      Any flags
     */
    public function init(Horde_ActiveSync_State_Base &$state, $folderId, $flags = 0)
    {
        $this->_state = &$state;
        $this->_flags = $flags;
        $this->_folderId = $folderId;
    }

    /**
     * Setter for a logger instance
     *
     * @param Horde_Log_Logger $logger  The logger
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Import a message change from the wbxml stream
     *
     * @param mixed $id                                A server message id or
     *                                                 false if a new message
     * @param Horde_ActiveSync_Message_Base $message   A message object
     *
     * @return mixed The server message id or false
     */
    public function importMessageChange($id, $message, $device)
    {
        /* do nothing if it is in a dummy folder */
        if ($this->_folderId == Horde_ActiveSync::FOLDER_TYPE_DUMMY) {
            return false;
        }

        /* Changing an existing object */
        if ($id) {
            /* Check for conflicts */
            $conflict = $this->_isConflict('change', $this->_folderId, $id);

            /* Update client state before we attempt to save changes, so we
             * have a record of the change. This way, if the server change fails
             * the server copy will be re-sync'd back to the PIM, maintaining
             * at least some sort of consistency. */
            $change = array();
            $change['id'] = $id;
            // mod is 0 to force a re-synch in the case of server failure. This
            // is updated after the change succeeds in the next updateState()
            $change['mod'] = 0;
            $change['parent'] = $this->_folderId;
            $change['flags'] = (isset($message->read)) ? $message->read : 0;
            $this->_state->updateState('change', $change, Horde_ActiveSync::CHANGE_ORIGIN_NA);

            /* If this is a conflict, see if the server wins */
            if ($conflict && $this->_flags == Horde_ActiveSync::CONFLICT_OVERWRITE_PIM) {
                return true;
            }
        }

        /* Tell the backend about the change */
        $stat = $this->_backend->changeMessage($this->_folderId, $id, $message, $device);
        $stat['parent'] = $this->_folderId;
        if (!is_array($stat)) {
            return $stat;
        }

        /* Record the state of the message */
        $this->_state->updateState('change', $stat, Horde_ActiveSync::CHANGE_ORIGIN_PIM, $this->_backend->getUser());

        return $stat['id'];
    }

    /**
     * Import a message deletion. This may conflict if the local object has been
     * modified.
     *
     * @param string $id  Server message uid
     *
     * @return boolean
     */
    public function importMessageDeletion($id)
    {
        /* Do nothing if it is in a dummy folder */
        if ($this->_folderId == Horde_ActiveSync::FOLDER_TYPE_DUMMY) {
            return true;
        }

        /* Check for conflict */
        $conflict = $this->_isConflict('delete', $this->_folderId, $id);

        /* Update client state */
        $change = array();
        $change['id'] = $id;
        $change['mod'] = time();
        $change['parent'] = $this->_folderId;
        $this->_state->updateState('delete', $change, Horde_ActiveSync::CHANGE_ORIGIN_PIM, $this->_backend->getUser());

        /* If server wins the conflict, don't import change - it will be
         * detected on next sync and sent back to PIM (since we updated the PIM
         * state). */
        if ($conflict && $this->_flags == Horde_ActiveSync::CONFLICT_OVERWRITE_PIM) {
            return true;
        }

        /* Tell backend about the deletion */
        $this->_backend->deleteMessage($this->_folderId, $id);

        return true;
    }

    /**
     * Import a change in 'read' flags .. This can never conflict
     *
     * @param string $id  Server message id
     * @param ??  $flags  The read flags to set
     */
    public function importMessageReadFlag($id, $flags)
    {
        /* Do nothing if it is a dummy folder */
        if ($this->_folderId == Horde_ActiveSync::FOLDER_TYPE_DUMMY) {
            return true;
        }

        /* Update client state */
        $change = array();
        $change['id'] = $id;
        $change['flags'] = $flags;
        $this->_state->updateState('flags', $change, Horde_ActiveSync::CHANGE_ORIGIN_NA);

        /* Tell backend */
        $this->_backend->setReadFlag($this->_folderId, $id, $flags);

        return true;
    }

    /**
     * Perform a message move initiated on the PIM.
     *
     * @TODO
     *
     * @param string $id  The message id
     * @param  $newfolder
     *
     * @return boolean
     */
    public function importMessageMove($id, $newfolder)
    {
        return true;
    }

    /**
     * Import a folder change from the wbxml stream
     *
     * @param string $id            The folder id
     * @param string $parent        The parent folder id?
     * @param string $displayname   The folder display name
     * @param <unknown_type> $type  The collection type?
     *
     * @return boolean
     */
    public function importFolderChange($id, $parent, $displayname, $type)
    {
        /* do nothing if it is a dummy folder */
        if ($parent == Horde_ActiveSync::FOLDER_TYPE_DUMMY) {
            return false;
        }

        if ($id) {
            $change = array();
            $change['id'] = $id;
            $change['mod'] = $displayname;
            $change['parent'] = $parent;
            $change['flags'] = 0;
            $this->_state->updateState('change', $change, Horde_ActiveSync::CHANGE_ORIGIN_NA);
        }

        /* Tell the backend */
        $stat = $this->_backend->ChangeFolder($parent, $id, $displayname, $type);
        if ($stat) {
            $this->_state->updateState('change', $stat, Horde_ActiveSync::CHANGE_ORIGIN_NA);
        }

        return $stat['id'];
    }

    /**
     * Imports a folder deletion from the PIM
     *
     * @param string $id      The folder id
     * @param string $parent  The folder id of the parent folder
     *
     * @return boolean
     */
    public function importFolderDeletion($id, $parent)
    {
        /* Do nothing if it is a dummy folder */
        if ($parent == Horde_ActiveSync::FOLDER_TYPE_DUMMY) {
            return false;
        }

        $change = array();
        $change['id'] = $id;

        $this->_state->updateState('delete', $change, Horde_ActiveSync::CHANGE_ORIGIN_NA);
        $this->_backend->deleteFolder($parent, $id);

        return true;
    }

    /**
     *  Check if this change conflicts with server changes
     *  This is only true in the following situations:
     *
     *    Changed here and changed there
     *    Changed here and deleted there
     *    Deleted here and changed there
     *
     * Any other combination of operations can be done
     * (e.g. change flags & move or move & delete)
     *
     * @param string $type  The type of change('change', 'delete' etc...)
     * @param string $folderid  The id of the folder this change is from.
     * @param string $id        The uid for the changed message.
     *
     * @return boolean
     */
    protected function _isConflict($type, $folderid, $id)
    {
        $stat = $this->_backend->statMessage($folderid, $id);
        if (!$stat) {
            /* Message is gone, if type is change, this is a conflict */
            if ($type == 'change') {
                return true;
            } else {
                return false;
            }
        }

        return $this->_state->isConflict($stat, $type);
    }

}
