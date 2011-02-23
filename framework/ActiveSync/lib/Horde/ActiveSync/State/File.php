<?php
/**
 * File based state management. Some code based on the Z-Push project's
 * diff backend, original copyright notice appears below.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org)
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
     * Cache results of file_exists for user's state directory
     *
     * @var boolean
     */
    private $_haveStateDirectory;

    /**
     * Const'r
     *
     * @param array  $params   Must contain 'directory' entry
     *
     * @return Horde_ActiveSync_StateMachine_File
     */
    public function __construct($params = array())
    {
        parent::__construct($params);

        if (empty($this->_params['directory'])) {
            throw new InvalidArgumentException('Missing required "stateDir" parameter.');
        }

        $this->_stateDir = $this->_params['directory'];
    }

    /**
     * Load the sync state
     *
     * @param string $syncKey   The synckey
     * @prarm string $type      Treat loaded state as this type of state.
     *
     * @return void
     * @throws Horde_ActiveSync_Exception
     */
    public function loadState($syncKey, $type = null, $id = '')
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
        $filename = $this->_stateDir . '/' . $this->_backend->getUser() . '/' . $syncKey;
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

        if ($stat['mod'] != $oldstat['mod']) {
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
    public function updateState($type, $change, $origin = Horde_ActiveSync::CHANGE_ORIGIN_NA, $user = null)
    {
        if (empty($this->_stateCache)) {
            $this->_stateCache = array();
        }

        // Change can be a change or an add
        if ($type == 'change') {
            /* If we are a change and don't already have a mod time, stat the
             * message. This would only happen when exporting a server side
             * change. We need the mod time to track the version of the message
             * on the PIM. (Folder changes will already have a mod value)
             */
            if (!isset($change['mod'])) {
                $change = $this->_backend->statMessage($this->_collection['id'], $change['id']);
            }
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
            if ($folder->type == Horde_ActiveSync::FOLDER_TYPE_INBOX) {
                continue;
            }

            // no folder from that type or the default folder
            if (!array_key_exists($folder->type, $unique_folders) || $folder->parentid == 0) {
                $unique_folders[$folder->type] = $folder->serverid;
            }
        }

        // Treo does initial sync for calendar and contacts too, so we need to fake
        // these folders if they are not supported by the backend
        if (!array_key_exists(Horde_ActiveSync::FOLDER_TYPE_APPOINTMENT, $unique_folders)) {
            $unique_folders[Horde_ActiveSync::FOLDER_TYPE_APPOINTMENT] = Horde_ActiveSync::FOLDER_TYPE_DUMMY;
        }
        if (!array_key_exists(Horde_ActiveSync::FOLDER_TYPE_CONTACT, $unique_folders)) {
            $unique_folders[Horde_ActiveSync::FOLDER_TYPE_CONTACT] = Horde_ActiveSync::FOLDER_TYPE_DUMMY;

        }
        if (!file_put_contents($this->_stateDir . '/' . $this->_backend->getUser() . '/compat-' . $devId, serialize($unique_folders))) {
            $this->_logError('_saveFolderData: Data could not be saved!');
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
                return $arr[Horde_ActiveSync::FOLDER_TYPE_APPOINTMENT];
            }
            if ($class == "Contacts") {
                return $arr[Horde_ActiveSync::FOLDER_TYPE_CONTACT];
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
            $this->resetPingState();
        }

        return $this->_pingState['collections'];
    }

    /**
     * Obtain the device object.
     *
     * @param string $devId  The device id to obtain
     * @param string $user   The user account to use
     *
     * @return object  The device info object
     * @throws Horde_ActiveSync_Exception
     */
    public function loadDeviceInfo($devId, $user)
    {
        $this->_devId = $devId;
        $file = $this->_stateDir . '/' . $user . '/info-' . $devId;
        if (file_exists($file)) {
            return unserialize(file_get_contents($file));
        } else {
            throw new Horde_ActiveSync_Exception('Device not found.');
        }
    }

    /**
     * Set new device info
     *
     * @param string $devId   The device id.
     * @param StdClass $data  The device information
     *
     * @return boolean
     */
    public function setDeviceInfo($data)
    {
        $this->_ensureUserDirectory();
        $this->_devId = $data->id;
        $file = $this->_stateDir . '/' . $this->_backend->getUser() . '/info-' . $this->_devId;
        return file_put_contents($file, serialize($data));
    }

    /**
     * Check that a given device id is known to the server. This is regardless
     * of Provisioning status.
     *
     * @param string $devId
     * @param string $user
     *
     * @return boolean
     */
    public function deviceExists($devId, $user = null)
    {
        if (empty($user)) {
            return count(glob($this->_stateDir . '/*/info-' . $devId)) > 0;
        }
        return file_exists($this->_stateDir . '/' . $user . '/info-' . $devId);
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
            $this->_logger->info('[' . $this->_devId . '] Empty state for '. $pingCollection['class']);

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
                    $stat = $this->_backend->statMessage($this->_collection['id'], $change['id']);
                    if (!$message = $this->_backend->getMessage($this->_collection['id'], $change['id'], 0)) {
                        continue;
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
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    public function savePingState()
    {
        if (empty($this->_pingState)) {
            throw new Horde_ActiveSync_Exception('PING state not initialized');
        }
        $this->_ensureUserDirectory();
        $state = serialize(array('lifetime' => $this->_pingState['lifetime'],
                                 'collections' => $this->_pingState['collections']));

        $this->_logger->info('[' . $this->_devId . '] Saving new PING state.');
        return file_put_contents($this->_stateDir . '/' . $this->_backend->getUser() . '/' . $this->_devId, $state);
    }

    /**
     * Return the heartbeat interval, or zero if we have no existing state
     *
     * @return integer  The hearbeat interval, or zero if not found.
     * @throws Horde_ActiveSync_Exception
     */
    public function getHeartbeatInterval()
    {
        if (empty($this->_pingState)) {
            throw new Horde_ActiveSync_Exception('PING state not initialized');
        }

        return (!$this->_pingState) ? 0 : $this->_pingState['lifetime'];
    }

    /**
     * Set the device's heartbeat interval
     *
     * @param integer $lifetime
     */
    public function setHeartbeatInterval($lifetime)
    {
        $this->_pingState['lifetime'] = $lifetime;
    }

    /**
     * Save a new device policy key to storage.
     *
     * @param string $devId  The device id
     * @param integer $key   The new policy key
     */
    public function setPolicyKey($devId, $key)
    {
        $info = $this->loadDeviceInfo($devId);
        $info->policykey = $key;
        $this->setDeviceInfo($info);
        $this->_logger->info('[' . $devId . '] New policykey saved: ' . $key);
    }

    /**
     * Reset ALL device policy keys. Used when server policies have changed
     * and you want to force ALL devices to pick up the changes. This will
     * cause all devices that support provisioning to be reprovisioned.
     *
     * @throws Horde_ActiveSync_Exception
     *
     */
    public function resetAllPolicyKeys()
    {
        throw new Horde_ActiveSync_Exception('Not Implemented');
    }

    /**
     * Set a new remotewipe status for the device
     *
     * @param string $devId
     * @param integer $status
     *
     * @return boolean
     */
    public function setDeviceRWStatus($devId, $status)
    {
        $info = $this->loadDeviceInfo($devId);
        $info->rwstatus = $status;
        $this->setDeviceInfo($info);
        $this->_logger->info('[' . $devId . '] Setting DeviceRWStatus: ' . $status);
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
            $this->_logger->info('[' . $this->_devId . '] Initializing message diff engine.');
            if (!$syncState) {
                $syncState = array();
            }
            $this->_logger->debug('[' . $this->_devId . ']' . count($syncState) . ' messages in state.');

            /* do nothing if it is a dummy folder */
            if ($folderId != Horde_ActiveSync::FOLDER_TYPE_DUMMY) {
                // on ping: check if backend supports alternative PING mechanism & use it
                if ($this->_collection['class'] === false && $flags == Horde_ActiveSync::BACKEND_DISCARD_DATA && $this->_backend->alterPing()) {
                    //@TODO - look at the passing of syncstate here - should probably pass self??
                    $this->_changes = $this->_backend->alterPingChanges($folderId, $syncState);
                } else {
                    /* Get our lists - syncstate (old)  and msglist (new) */
                    $msglist = $this->_backend->getMessageList($this->_collection['id'], $cutoffdate);
                    if ($msglist === false) {
                        return false;
                    }
                    $this->_changes = $this->_getDiff($syncState, $msglist);
                }
            }
            $this->_logger->info('[' . $this->_devId . '] Found ' . count($this->_changes) . ' message changes.');

        } else {

            $this->_logger->info('[' . $this->_devId . '] Initializing folder diff engine.');
            $folderlist = $this->_backend->getFolderList();
            if ($folderlist === false) {
                return false;
            }

            $this->_changes = $this->_getDiff($syncState, $folderlist);
            $this->_logger->info('[' . $this->_devId . '] Found ' . count($this->_changes) . ' folder changes.');
        }

        return $this->_changes;
    }

    /**
     * Explicitly remove a specific state. Normally used if a request results in
     * a synckey mismatch. This isn't strictly needed, but helps keep the state
     * storage clean.
     *
     */
    public function removeState($syncKey = null, $devId = null)
    {
        if ($devId) {
            throw new Horde_ActiveSync_Exception('Not implemented.');
        }
        $this->_gc($syncKey, true);
    }

    public function listDevices()
    {
       throw new Horde_ActiveSync_Exception('Not Implemented');
    }

    /**
     * Get the last time a particular device issued a SYNC request.
     *
     * @param string $devId  The device id
     *
     * @return integer  The timestamp of the last sync, regardless of collection
     * @throws Horde_ActiveSync_Exception
     */
    public function getLastSyncTimestamp()
    {
        throw new Horde_ActiveSync_Exception('Not Implemented');
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
    private function _gc($syncKey, $all = false)
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
                if ($matches[1] == $guid && ((!$all && $matches[2] < $n) || $all)) {
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

}
