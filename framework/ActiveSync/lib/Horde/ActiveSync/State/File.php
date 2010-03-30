<?php
/**
 * File based state management. Some code based on the Z-Push project's
 * diff backend, original copyright notice appears below.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
/**
 * File      :   statemachine.php
 * Project   :   Z-Push
 * Descr     :   This class handles state requests;
 *               Each differential mechanism can
 *               store its own state information,
 *               which is stored through the
 *               state machine. SyncKey's are
 *               of the  form {UUID}N, in which
 *               UUID is allocated during the
 *               first sync, and N is incremented
 *               for each request to 'getNewSyncKey'.
 *               A sync state is simple an opaque
 *               string value that can differ
 *               for each backend used - normally
 *               a list of items as the backend has
 *               sent them to the PIM. The backend
 *               can then use this backend
 *               information to compute the increments
 *               with current data.
 *
 *               Old sync states are not deleted
 *               until a sync state is requested.
 *               At that moment, the PIM is
 *               apparently requesting an update
 *               since sync key X, so any sync
 *               states before X are already on
 *               the PIM, and can therefore be
 *               removed. This algorithm is
 *                automatically enforced by the
 *                StateMachine class.
 *
 *
 * Created   :   01.10.2007
 *
 * � Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL v2.
 * Consult LICENSE file for details
 */
class Horde_ActiveSync_State_File extends Horde_ActiveSync_State_Base
{
    /**
     * Directory to store state files
     *
     * @var stirng
     */
    private $_stateDir;

    /**
     * Cache for ping state
     *
     * @var array
     */
    private $_pingState;

    /**
     * Local cache for changes to *send* to PIM
     * (Will remain null until getChanges() is called)
     *
     * @var
     */
    private $_changes;

    /**
     * Const'r
     *
     * @param array  $params   Must contain 'stateDir' entry
     *
     * @return Horde_ActiveSync_StateMachine_File
     */
    public function __construct($params = array())
    {
        parent::__construct($params);

        if (empty($this->_params['stateDir'])) {
            throw new InvalidArgumentException('Missing required "stateDir" parameter.');
        }

        $this->_stateDir = $this->_params['stateDir'];
    }

    /**
     * Load the sync state
     *
     * @param string $syncKey   The synckey
     *
     * @return void
     * @throws Horde_ActiveSync_Exception
     */
    public function loadState($syncKey)
    {
        /* Ensure state directory is present */
        $this->_ensureUserDirectory();

        /* Prime the state cache for the first sync */
        if (empty($syncKey)) {
            $this->_stateCache = array();
            return;
        }

        /* Check if synckey is allowed */
        if (!preg_match('/^s{0,1}\{([0-9A-Za-z-]+)\}([0-9]+)$/', $syncKey, $matches)) {
            throw new Horde_ActiveSync_Exception('Invalid sync key');
        }

        /* Cleanup all older syncstates */
        $this->_gc($syncKey);

        /* Read current sync state */
        $filename = $dir . '/' . $syncKey;
        if (!file_exists($filename)) {
            throw new Horde_ActiveSync_Exception('Sync state not found');
        }
        $this->_stateCache = unserialize(file_get_contents($filename));
        $this->_syncKey = $syncKey;
    }

    /**
     * Determines if the server version of the message represented by $stat
     * conflicts with the PIM version of the message according to the current
     * state.
     *
     * @see Horde_ActiveSync_State_Base::isConflict()
     */
    public function isConflict($stat, $type)
    {
        foreach ($this->_stateCache as $state) {
            if ($state['id'] == $stat['id']) {
                $oldstat = $state;
                break;
            }
        }

        // New message on server - can never conflict, but we shouldn't be
        // here in this case anyway, right?
        if (!isset($oldstat)) {
            return false;
        }

        if ($state['mod'] != $oldstat['mod']) {
            // Changed here
            if ($type == 'delete' || $type == 'change') {
                // changed here, but deleted there -> conflict,
                // or changed here and changed there -> conflict
                return true;
            } else {
                // changed here, and other remote changes (move or flags)
                return false;
            }
        }
    }

    /**
     * Save the current state to storage
     *
     * @param string $syncKey  The sync key to save
     *
     * @return boolean
     */
    public function save()
    {
        return file_put_contents(
            $this->_stateDir . '/' . $this->_backend->getUser() . '/' . $this->_syncKey,
            !empty($this->_stateCache) ? serialize($this->_stateCache) : '');
    }

    /**
     * Update the state to reflect changes
     *
     * @param string $type       The type of change (change, delete, flags)
     * @param array $change      Array describing change
     *
     * @return void
     */
    public function updateState($type, $change)
    {
        if (empty($this->_stateCache)) {
            $this->_stateCache = array();
        }

        // Change can be a change or an add
        if ($type == 'change') {
            for($i = 0; $i < count($this->_stateCache); $i++) {
                if($this->_stateCache[$i]['id'] == $change['id']) {
                    $this->_stateCache[$i] = $change;
                    /* If we have a pingState, keep it in sync */
                    if (!empty($this->_pingState['collections'])) {
                        $this->_pingState['collections'][$this->_collection['class']]['state'] = $this->_stateCache;
                    }
                    return;
                }
            }
            // Not found, add as new
            $this->_stateCache[] = $change;
        } else {
            for ($i = 0; $i < count($this->_stateCache); $i++) {
                // Search for the entry for this item
                if ($this->_stateCache[$i]['id'] == $change['id']) {
                    if ($type == 'flags') {
                        // Update flags
                        $this->_stateCache[$i]['flags'] = $change['flags'];
                    } elseif ($type == 'delete') {
                        // Delete item
                        array_splice($this->_stateCache, $i, 1);
                    }
                    break;
                }
            }
        }

        /* If we have a pingState, keep it in sync */
        if (!empty($this->_pingState['collections'])) {
            $this->_pingState['collections'][$this->_collection['class']]['state'] = $this->_stateCache;
        }
    }

    /**
     * Save folder data for a specific device. Used only for compatibility with
     * older (version 1) ActiveSync requests.
     *
     * @param string $devId     The device Id
     * @param array $folders    The folder data
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    public function setFolderData($devId, $folders)
    {
        if (!is_array($folders) || empty ($folders)) {
            return false;
        }

        $unique_folders = array ();
        foreach ($folders as $folder) {
            // don't save folder-ids for emails
            if ($folder->type == SYNC_FOLDER_TYPE_INBOX) {
                continue;
            }

            // no folder from that type or the default folder
            if (!array_key_exists($folder->type, $unique_folders) || $folder->parentid == 0) {
                $unique_folders[$folder->type] = $folder->serverid;
            }
        }

        // Treo does initial sync for calendar and contacts too, so we need to fake
        // these folders if they are not supported by the backend
        if (!array_key_exists(SYNC_FOLDER_TYPE_APPOINTMENT, $unique_folders)) {
            $unique_folders[SYNC_FOLDER_TYPE_APPOINTMENT] = SYNC_FOLDER_TYPE_DUMMY;
        }
        if (!array_key_exists(SYNC_FOLDER_TYPE_CONTACT, $unique_folders)) {
            $unique_folders[SYNC_FOLDER_TYPE_CONTACT] = SYNC_FOLDER_TYPE_DUMMY;

        }
        if (!file_put_contents($this->_stateDir . '/' . $this->_backend->getUser() . '/compat-' . $devId, serialize($unique_folders))) {
            $this->logError('_saveFolderData: Data could not be saved!');
            throw new Horde_ActiveSync_Exception('Folder data could not be saved');
        }
    }

    /**
     * Get the folder data for a specific collection for a specific device. Used
     * only with older (version 1) ActiveSync requests.
     *
     * @param string $devId  The device id
     * @param string $class  The folder class to fetch (Calendar, Contacts etc.)
     *
     * @return mixed  Either an array of folder data || false
     */
    public function getFolderData($devId, $class)
    {
        $filename = $this->_stateDir . '/' . $this->_backend->getUser() . '/compat-' . $devId;
        if (file_exists($filename)) {
            $arr = unserialize(file_get_contents($filename));
            if ($class == "Calendar") {
                return $arr[SYNC_FOLDER_TYPE_APPOINTMENT];
            }
            if ($class == "Contacts") {
                return $arr[SYNC_FOLDER_TYPE_CONTACT];
            }
        }

        return false;
    }

    /**
     * Return an array of known folders. This is essentially the state for a
     * FOLDERSYNC request. AS uses a seperate synckey for FOLDERSYNC requests
     * also, so need to treat it as any other collection.
     *
     * @return array
     */
    public function getKnownFolders()
    {
        if (!isset($this->_stateCache)) {
            throw new Horde_ActiveSync_Exception('Sync state not loaded');
        }
        $folders = array();
        foreach ($this->_stateCache as $folder) {
            $folders[] = $folder['id'];
        }
        return $folders;
    }

    /**
     * Perform any initialization needed to deal with pingStates
     * For this driver, it loads the device's state file.
     *
     * @param string $devId     The device id of the PIM to load PING state for
     *
     * @return array The $collection array
     */
    public function initPingState($devId)
    {
        $this->_devId = $devId;
        $file = $this->_stateDir . '/' . $this->_backend->getUser() . '/' . $devId;
        if (file_exists($file)) {
            $this->_pingState = unserialize(file_get_contents($file));
        } else {
            $this->_pingState = array(
                'lifetime' => 0,
                'collections' => array());
        }

        return $this->_pingState['collections'];
    }

    /**
     * Obtain the device object.
     *
     * @param string $devId
     *
     * @return StdClass
     */
    public function getDeviceInfo($devId)
    {
        $this->_devId = $devId;
        $file = $this->_stateDir . '/' . $this->_backend->getUser() . '/info-' . $devId;
        if (file_exists($file)) {
            return unserialize(file_get_contents($file));
        }

        /* Default structure */
        $device = new StdClass();
        $device->policykey = 0;
        $device->rwstatus = 0; // ??
        $device->deviceType = '';
        $device->userAgent = '';

        return $device;
    }

    /**
     * Set new device info
     *
     * @param string $devId   The device id.
     * @param StdClass $data  The device information
     *
     * @return boolean
     */
    public function setDeviceInfo($devId, $data)
    {
        $this->_ensureUserDirectory();
        $this->_devId = $devId;
        $file = $this->_stateDir . '/' . $this->_backend->getUser() . '/info-' . $devId;
        return file_put_contents($file, serialize($data));
    }

    /**
     * Check that a given device id is known to the server. This is regardless
     * of Provisioning status.
     *
     * @param string $devId
     *
     * @return boolean
     */
    public function deviceExists($devId)
    {
        return file_exists($this->_stateDir . '/' . $this->_backend->getUser() . '/info-' . $devId);
    }

    /**
     * Load a specific collection's ping state. Ping state must already have
     * been loaded.
     *
     * @param array $pingCollection  The collection array from the PIM request
     *
     * @return void
     * @throws Horde_ActiveSync_Exception
     */
    public function loadPingCollectionState($pingCollection)
    {
        if (empty($this->_pingState)) {
            throw new Horde_ActiveSync_Exception('PING state not initialized');
        }

        $haveState = false;

        /* Load any existing state */
        // @TODO: I'm almost positive we need to key these by 'id', not 'class'
        // but this is what z-push did so...
        if (!empty($this->_pingState['collections'][$pingCollection['class']])) {
            $this->_collection = $this->_pingState['collections'][$pingCollection['class']];
            $this->_collection['synckey'] = $this->_devId;
            $this->_stateCache = $this->_collection['state'];
            $haveState = true;
        }

        /* Initialize state for this collection */
        if (!$haveState) {
            $this->_logger->debug('Empty state for '. $pingCollection['class']);

            /* Init members for the getChanges call */
            $this->_syncKey = $this->_devId;
            $this->_collection = $pingCollection;
            $this->_collection['synckey'] = $this->_devId;
            $this->_collection['state'] = array();

            /* If we are here, then the pingstate was empty, prime it */
            $this->_pingState['collections'][$this->_collection['class']] = $this->_collection;

            /* Need to load _stateCache so getChanges has it */
            $this->_stateCache = array();

            $changes = $this->getChanges();
            foreach ($changes as $change) {
                switch ($change['type']) {
                case 'change':
                    $stat = $this->_backend->StatMessage($this->_collection['id'], $change['id']);
                    if (!$message = $this->_backend->GetMessage($this->_collection['id'], $change['id'], 0)) {
                        throw new Horde_ActiveSync_Exception('Message not found');
                    }
                    if ($stat && $message) {
                        $this->updateState('change', $stat);
                    }
                    break;

                default:
                    throw new Horde_ActiveSync_Exception('Unexpected change type in loadPingState');
                }
            }

            $this->_pingState['collections'][$this->_collection['class']]['state'] = $this->_stateCache;
            $this->savePingState();
        }
    }

    /**
     * Save the current ping state to storage
     *
     * @param string $devId      The PIM device id
     * @param integer $lifetime  The ping heartbeat/lifetime interval
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    public function savePingState()
    {
        if (empty($this->_pingState)) {
            throw new Horde_ActiveSync_Exception('PING state not initialized');
        }
        $state = serialize(array('lifetime' => $this->_pingState['lifetime'],
                                 'collections' => $this->_pingState['collections']));

        return file_put_contents($this->_stateDir . '/' . $this->_backend->getUser() . '/' . $this->_devId, $state);
    }

    /**
     * Return the heartbeat interval, or zero if we have no existing state
     *
     * @param string $devId
     *
     * @return integer  The hearbeat interval, or zero if not found.
     * @throws Horde_ActiveSync_Exception
     */
    public function getPingLifetime()
    {
        if (empty($this->_pingState)) {
            throw new Horde_ActiveSync_Exception('PING state not initialized');
        }

        return (!$this->_pingState) ? 0 : $this->_pingState['lifetime'];
    }

    public function setPingLifetime($lifetime)
    {
        $this->_pingState['lifetime'] = $lifetime;
    }

    /**
     * Obtain the current policy key, if it exists.
     *
     * @param string $devId     The device id to obtain policy key for.
     *
     * @return integer  The current policy key for this device, or 0 if none
     *                  exists.
     */
    public function getPolicyKey($devId)
    {
        $info = $this->getDeviceInfo($devId);
        return $info->policykey;
    }

    /**
     * Save a new device policy key to storage.
     *
     * @param string $devId  The device id
     * @param integer $key   The new policy key
     */
    public function setPolicyKey($devId, $key)
    {
        $info = $this->getDeviceInfo($devId);
        $info->policykey = $key;
        $this->setDeviceInfo($devId, $info);
    }

    /**
     * Get list of server changes
     *
     * @param integer $flags
     *
     * @return array
     */
    public function getChanges($flags = 0)
    {
        $syncState = empty($this->_stateCache) ? array() : $this->_stateCache;
        $cutoffdate = self::_getCutOffDate(!empty($this->_collection['filtertype']) ? $this->_collection['filtertype'] : 0);

        if (!empty($this->_collection['id'])) {
            $folderId = $this->_collection['id'];
            $this->_logger->debug('Initializing message diff engine');
            if (!$syncState) {
                $syncState = array();
            }
            $this->_logger->debug(count($syncState) . ' messages in state');

            //do nothing if it is a dummy folder
            if ($folderId != SYNC_FOLDER_TYPE_DUMMY) {
                // on ping: check if backend supports alternative PING mechanism & use it
                if ($this->_collection['class'] === false && $flags == BACKEND_DISCARD_DATA && $this->_backend->AlterPing()) {
                    //@TODO - look at the passing of syncstate here - should probably pass self??
                    // Not even sure if we need this AlterPing?
                    $this->_changes = $this->_backend->AlterPingChanges($folderId, $syncState);
                } else {
                    // Get our lists - syncstate (old)  and msglist (new)
                    $msglist = $this->_backend->GetMessageList($this->_collection['id'], $cutoffdate);
                    if ($msglist === false) {
                        return false;
                    }
                    $this->_changes = $this->_getDiff($syncState, $msglist);
                }
            }
            $this->_logger->debug('Found ' . count($this->_changes) . ' message changes');

        } else {

            $this->_logger->debug('Initializing folder diff engine');
            $folderlist = $this->_backend->getFolderList();
            if ($folderlist === false) {
                return false;
            }

            $this->_changes = $this->_getDiff($syncState, $folderlist);
            $this->_logger->debug('Config: Found ' . count($this->_changes) . ' folder changes');
        }

        return $this->_changes;
    }

    /**
     * Get the number of server changes.
     *
     * @return integer
     */
    public function getChangeCount()
    {
        if (!isset($this->_changes)) {
            $this->getChanges();
        }

        return count($this->_changes);
    }

    /**
     * Garbage collector - clean up from previous sync
     * requests.
     *
     * @params string $syncKey  The sync key
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    private function _gc($syncKey)
    {
        if (!preg_match('/^s{0,1}\{([0-9A-Za-z-]+)\}([0-9]+)$/', $syncKey, $matches)) {
            return false;
        }
        $guid = $matches[1];
        $n = $matches[2];

        $dir = @opendir($this->_stateDir . '/' . $this->_backend->getUser());
        if (!$dir) {
            return false;
        }
        while ($entry = readdir($dir)) {
            if (preg_match('/^s{0,1}\{([0-9A-Za-z-]+)\}([0-9]+)$/', $entry, $matches)) {
                if ($matches[1] == $guid && $matches[2] < $n) {
                    unlink($this->_stateDir . '/' . $this->_backend->getUser() . '/' . $entry);
                }
            }
        }

        return true;
    }

    /**
     * Ensure that the user's state directory is present.
     *
     * @return void
     */
    private function _ensureUserDirectory()
    {
        /* Make sure this user's state directory exists */
        if ($this->_haveStateDirectory) {
            return true;
        }

        $dir = $this->_stateDir . '/' . $this->_backend->getUser();
        if (!file_exists($dir)) {
            if (!mkdir($dir)) {
                throw new Horde_ActiveSync_Exception('Failed to create user state storage');
            }
        }

        $this->_haveStateDirectory = true;
    }

    /**
     * Helper function that performs the actual diff between PIM state and
     * server state arrays.
     *
     * @param array $old  The PIM state
     * @param array $new  The current server state
     *
     * @return unknown_type
     */
    private function _getDiff($old, $new)
    {
        $changes = array();

        // Sort both arrays in the same way by ID
        usort($old, array(__CLASS__, 'RowCmp'));
        usort($new, array(__CLASS__, 'RowCmp'));

        $inew = 0;
        $iold = 0;

        // Get changes by comparing our list of messages with
        // our previous state
        while (1) {
            $change = array();

            if ($iold >= count($old) || $inew >= count($new)) {
                break;
            }

            if ($old[$iold]['id'] == $new[$inew]['id']) {
                // Both messages are still available, compare flags and mod
                if (isset($old[$iold]['flags']) && isset($new[$inew]['flags']) && $old[$iold]['flags'] != $new[$inew]['flags']) {
                    // Flags changed
                    $change['type'] = 'flags';
                    $change['id'] = $new[$inew]['id'];
                    $change['flags'] = $new[$inew]['flags'];
                    $changes[] = $change;
                }

                if ($old[$iold]['mod'] != $new[$inew]['mod']) {
                    $change['type'] = 'change';
                    $change['id'] = $new[$inew]['id'];
                    $changes[] = $change;
                }

                $inew++;
                $iold++;
            } else {
                if ($old[$iold]['id'] > $new[$inew]['id']) {
                    // Message in state seems to have disappeared (delete)
                    $change['type'] = 'delete';
                    $change['id'] = $old[$iold]['id'];
                    $changes[] = $change;
                    $iold++;
                } else {
                    // Message in new seems to be new (add)
                    $change['type'] = 'change';
                    $change['flags'] = SYNC_NEWMESSAGE;
                    $change['id'] = $new[$inew]['id'];
                    $changes[] = $change;
                    $inew++;
                }
            }
        }

        while ($iold < count($old)) {
            // All data left in _syncstate have been deleted
            $change['type'] = 'delete';
            $change['id'] = $old[$iold]['id'];
            $changes[] = $change;
            $iold++;
        }

        while ($inew < count($new)) {
            // All data left in new have been added
            $change['type'] = 'change';
            $change['flags'] = SYNC_NEWMESSAGE;
            $change['id'] = $new[$inew]['id'];
            $changes[] = $change;
            $inew++;
        }

        return $changes;
    }

    /**
     * Helper function for the _diff method
     *
     * @param $a
     * @param $b
     * @return unknown_type
     */
    static public function RowCmp($a, $b)
    {
        return $a['id'] < $b['id'] ? 1 : -1;
    }

}