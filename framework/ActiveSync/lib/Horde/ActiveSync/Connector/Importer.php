<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class Horde_ActiveSync_Connector_Importer
{
    /**
     *
     * @var Horde_ActiveSync_StateMachine_Base
     */
    protected $_stateMachine;

    /**
     *
     * @var Horde_ActiveSync_Driver_Base
     */
    protected $_backend;

    /**
     * Sync key for current request
     *
     * @var string
     */
    protected $_syncKey;

    /**
     * @TODO
     * @var <type>
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
     * @param Horde_ActiveSync_StateMachine_Base $stateMachine
     * @param <type> $syncKey
     * @param <type> $flags
     */
    public function __construct(Horde_ActiveSync_Driver_Base $backend)
    {
        $this->_backend = $backend;
    }

    public function init(Horde_ActiveSync_State_Base &$stateMachine,
                         $folderId, $syncKey, $flags = 0)
    {
        $this->_stateMachine = &$stateMachine;
        $this->_syncKey = $syncKey;
        $this->_flags = $flags;
        $this->_folderId = $folderId;
    }

    public function setLogger($logger)
    {
        $this->_logger = $logger;
    }

    /**
     *
     * @param mixed $id                                A server message id or
     *                                                 false if a new message
     * @param Horde_ActiveSync_Message_Base $message   A message object
     *
     * @return mixed The server message id or false
     */
    public function ImportMessageChange($id, $message)
    {
        //do nothing if it is in a dummy folder
        if ($this->_folderId == SYNC_FOLDER_TYPE_DUMMY) {
            return false;
        }

        if ($id) {
            // See if there's a conflict
            $conflict = $this->_isConflict('change', $this->_folderId, $id);

            // Update client state if this is an update
            $change = array();
            $change['id'] = $id;
            $change['mod'] = 0; // dummy, will be updated later if the change succeeds
            $change['parent'] = $this->_folderId;
            $change['flags'] = (isset($message->read)) ? $message->read : 0;
            $this->_stateMachine->updateState('change', $change);

            if ($conflict && $this->_flags == SYNC_CONFLICT_OVERWRITE_PIM) {
                return true;
            }
        }

        $stat = $this->_backend->ChangeMessage($this->_folderId, $id, $message);
        // @TODO: Isn't this an error?
        if (!is_array($stat)) {
            return $stat;
        }

        // Record the state of the message
        $this->_stateMachine->updateState('change', $stat);

        return $stat['id'];
    }

    /**
     * Import a deletion. This may conflict if the local object has been
     * modified.
     *
     * @param string $id  Server message id
     */
    public function ImportMessageDeletion($id)
    {
        //do nothing if it is in a dummy folder
        if ($this->_folderId == SYNC_FOLDER_TYPE_DUMMY) {
            return true;
        }

        // See if there's a conflict
        $conflict = $this->_isConflict('delete', $this->_folderId, $id);

        // Update client state
        $change = array();
        $change['id'] = $id;
        $this->_stateMachine->updateState('delete', $change);

        // If there is a conflict, and the server 'wins', then return OK without
        // performing the change this will cause the exporter to 'see' the
        // overriding item as a change, and send it back to the PIM
        if ($conflict && $this->_flags == SYNC_CONFLICT_OVERWRITE_PIM) {
            return true;
        }

        $this->_backend->DeleteMessage($this->_folderId, $id);

        return true;
    }

    /**
     * Import a change in 'read' flags .. This can never conflict
     *
     * @param string $id  Server message id
     * @param ??  $flags  The read flags to set
     */
    public function ImportMessageReadFlag($id, $flags)
    {
        //do nothing if it is a dummy folder
        if ($this->_folderId == SYNC_FOLDER_TYPE_DUMMY) {
            return true;
        }

        // Update client state
        $change = array();
        $change['id'] = $id;
        $change['flags'] = $flags;
        $this->_stateMachine->updateState('flags', $change);
        $this->_backend->SetReadFlag($this->_folderId, $id, $flags);

        return true;
    }

    /**
     * Not supported/todo?
     *
     * @param <type> $id
     * @param <type> $newfolder
     * @return <type>
     */
    public function ImportMessageMove($id, $newfolder)
    {
        return true;
    }

    /**
     *
     * @param $id
     * @param $parent
     * @param $displayname
     * @param $type
     * @return unknown_type
     */
    public function ImportFolderChange($id, $parent, $displayname, $type)
    {
        //do nothing if it is a dummy folder
        if ($parent == SYNC_FOLDER_TYPE_DUMMY) {
            return false;
        }

        if ($id) {
            $change = array();
            $change['id'] = $id;
            $change['mod'] = $displayname;
            $change['parent'] = $parent;
            $change['flags'] = 0;
            $this->_stateMachine->updateState('change', $change);
        }

        // @TODO: ChangeFolder did not exist in ZPush's code??
        $stat = $this->_backend->ChangeFolder($parent, $id, $displayname, $type);
        if ($stat) {
            $this->_stateMachine->updateState('change', $stat);
        }

        return $stat['id'];
    }

    /**
     *
     * @param $id
     * @param $parent
     * @return unknown_type
     */
    public function ImportFolderDeletion($id, $parent)
    {
        //do nothing if it is a dummy folder
        if ($parent == SYNC_FOLDER_TYPE_DUMMY) {
            return false;
        }

        $change = array();
        $change['id'] = $id;

        $this->_stateMachine->updateState('delete', $change);
        $this->_backend->DeleteFolder($parent, $id);

        return true;
    }

    /**
     *  Returns TRUE if the given ID conflicts with the given operation.
     *  This is only true in the following situations:
     *
     *    Changed here and changed there
     *    Changed here and deleted there
     *    Deleted here and changed there
     *
     * Any other combination of operations can be done
     * (e.g. change flags & move or move & delete)
     */
    protected function _isConflict($type, $folderid, $id)
    {
        $stat = $this->_backend->StatMessage($folderid, $id);
        if (!$stat) {
            // Message is gone
            if ($type == 'change') {
                return true;
            } else {
                return false; // all other remote changes still result in a delete (no conflict)
            }
        }

        return $this->_stateMachine->isConflict($stat, $type);
    }

}
