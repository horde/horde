<?php
/**
 * Horde_History based state management. Needs a number of SQL tables present:
 * <pre>
 *    syncStateTable (horde_activesync_state):
 *        sync_time:  timestamp of last sync
 *        sync_key:   the syncKey for the last sync
 *        sync_data:  If the last sync resulted in a MOREAVAILABLE, this contains
 *                    a list of UIDs that still need to be sent to the PIM.  If
 *                    this sync_key represents a FOLDERSYNC state, then this
 *                    contains the current folder state on the PIM.
 *        sync_devid: The device id.
 *        sync_folderid: The folder id for this sync.
 *        sync_user:     The user for this synckey
 *
 *    syncMapTable (horde_activesync_map):
 *        message_uid    - The server uid for the object
 *        sync_modtime   - The time the change was received from the PIM and
 *                         applied to the server data store.
 *        sync_key       - The syncKey that was current at the time the change
 *                         was received.
 *        sync_devid     - The device id this change was done on.
 *        sync_user      - The user that initiated the change.
 *
 *    syncDeviceTable (horde_activesync_device):
 *        device_id      - The unique id for this device
 *        device_type    - The device type the PIM identifies itself with
 *        device_agent   - The user agent string sent by the device
 *        device_policykey  - The current policykey for this device
 *        device_rwstatus   - The current remote wipe status for this device
 *
 *    syncUsersTable (horde_activesync_device_users):
 *        device_user    - A username attached to the device
 *        device_id      - The device id
 *        device_ping    - The account's ping state
 *        device_folders - Account's folder data
 * </pre>
 *
 * Copyright 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
class Horde_ActiveSync_State_History extends Horde_ActiveSync_State_Base
{
    /**
     * The timestamp for the last syncKey
     *
     * @var timestamp
     */
    protected $_lastSyncTS = 0;

    /**
     * The current sync timestamp
     *
     * @var timestamp
     */
    protected $_thisSyncTS = 0;

    /**
     * Local cache of state.
     *
     * @var array
     */
    protected $_state;

    /**
     * DB handle
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /* Table names */
    protected $_syncStateTable;
    protected $_syncMapTable;
    protected $_syncDeviceTable;
    protected $_syncUsersTable;

    /**
     * Const'r
     *
     * @param array  $params   Must contain:
     *      'db'  - Horde_Db
     *      'syncStateTable'    - Name of table for storing syncstate
     *      'syncDeviceTable'   - Name of table for storing device and ping data
     *      'syncMapTable'      - Name of table for remembering what changes
     *                            are due to PIM import so we don't mirror the
     *                            changes back to the PIM on next Sync
     *      'syncUsersTable'    - Name of table for mapping users to devices.
     *
     * @return Horde_ActiveSync_StateMachine_File
     */
    public function __construct($params = array())
    {
        parent::__construct($params);
        if (empty($this->_params['db']) || !($this->_params['db'] instanceof Horde_Db_Adapter)) {
            throw new InvalidArgumentException('Missing or invalid Horde_Db parameter.');
        }

        $this->_syncStateTable = $params['statetable'];
        $this->_syncMapTable = $params['maptable'];
        $this->_syncDeviceTable = $params['devicetable'];
        $this->_syncUsersTable = $params['userstable'];
        $this->_db = $params['db'];
    }

    /**
     * Load the sync state
     *
     * @param string $syncKey   The synckey of the state to load. If empty will
     *                          force a reset of the state for the class
     *                          specified in $id
     * @prarm string $type      The type of state (sync, foldersync).
     * @param string $id        The folder id this state represents. If empty
     *                          assumed to be a foldersync state.
     *
     * @return void
     * @throws Horde_ActiveSync_Exception
     */
    public function loadState($syncKey, $type = null, $id = null)
    {
        $this->_type = $type;
        if ($type == 'foldersync' && empty($id)) {
            $id = 'foldersync';
        }
        if (empty($syncKey)) {
            $this->_state = array();
            $this->_resetDeviceState($id);
            return;
        }

        $this->_logger->debug(sprintf('[%s] Loading state for synckey %s', $this->_devId, $syncKey));
        /* Check if synckey is allowed */
        if (!preg_match('/^s{0,1}\{([0-9A-Za-z-]+)\}([0-9]+)$/', $syncKey, $matches)) {
            throw new Horde_ActiveSync_Exception('Invalid sync key');
        }
        $this->_syncKey = $syncKey;

        /* Cleanup all older syncstates */
        $this->_gc($syncKey);

        /* Load the previous syncState from storage */
        try {
            $results = $this->_db->selectOne('SELECT sync_data, sync_devid, sync_time FROM '
                . $this->_syncStateTable . ' WHERE sync_key = ?', array($this->_syncKey));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
        if (!$results) {
            throw new Horde_ActiveSync_Exception('Sync State Not Found.');
        }

        /* Load the last known sync time for this collection */
        $this->_lastSyncTS = !empty($results['sync_time']) ? $results['sync_time'] : 0;

        /* Restore any state or pending changes */
        if ($type == 'foldersync') {
            $state = unserialize($results['sync_data']);
            $this->_state = ($state !== false) ? $state : array();
        } elseif ($type == 'sync') {
            $changes = unserialize($results['sync_data']);
            $this->_changes = ($changes !== false) ? $changes : null;
            if ($this->_changes) {
                $this->_logger->debug(sprintf('[%s] Found %d changes remaining from previous SYNC.', $this->_devId, count($this->_changes)));
            }
        }
    }

    /**
     * Determines if the server version of the message represented by $stat
     * conflicts with the PIM version of the message.  For this driver, this is
     * true whenever $lastSyncTime is older then $stat['mod']. Method is only
     * called from the Importer during an import of a non-new change from the
     * PIM.
     *
     * @see Horde_ActiveSync_State_Base::isConflict()
     */
    public function isConflict($stat, $type)
    {
        // $stat == server's message information
         if ($stat['mod'] > $this->_lastSyncTS) {
             if ($type == 'delete' || $type == 'change') {
                 // changed here - deleted there
                 // changed here - changed there
                 return true;
             } else {
                 // all other remote cahnges are fine (move/flags)
                 return false;
             }
        }
    }

    /**
     * Save the current state to storage
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    public function save()
    {
        $this->_logger->debug(sprintf('[%s] Saving state for synckey %s', $this->_devId, $this->_syncKey));

        /*  Update state table to remember this last synctime and key */
        $sql = 'INSERT INTO ' . $this->_syncStateTable
            . ' (sync_key, sync_data, sync_devid, sync_time, sync_folderid, sync_user) VALUES (?, ?, ?, ?, ?, ?)';

        /* Remember any left over changes */
        if ($this->_type == 'foldersync') {
            $data = (isset($this->_state) ? serialize($this->_state) : '');
        } elseif ($this->_type == 'sync') {
            $data = (isset($this->_changes) ? serialize(array_values($this->_changes)) : '');
        } else {
            $data = '';
        }

        $params = array($this->_syncKey,
                        $data,
                        $this->_devId,
                        $this->_thisSyncTS,
                        !empty($this->_collection['id']) ? $this->_collection['id'] : 'foldersync',
                        $this->_deviceInfo->user);
        try {
            $this->_db->insert($sql, $params);
        } catch (Horde_Db_Exception $e) {
            /* Might exist already if the last sync attempt failed. */
            $this->_db->delete('DELETE FROM ' . $this->_syncStateTable . ' WHERE sync_key = ?', array($this->_syncKey));
            $this->_db->insert($sql, $params);
        }

        return true;
    }

    /**
     * Update the state to reflect changes
     *
     * Notes: If we are importing PIM changes, need to update the syncMapTable
     * so we don't mirror back the changes on next sync. If we are exporting
     * server changes, we need to track which changes have been sent (by
     * removing them from $this->_changes) so we know which items to send on the
     * next sync if a MOREAVAILBLE response was needed.
     *
     * @param string $type     The type of change (change, delete, flags)
     * @param array $change    A stat/change hash describing the change
     * @param integer $origin  Flag to indicate the origin of the change.
     * @param string $user     The current sync user, only needed if change
     *                         origin is CHANGE_ORIGIN_PIM
     *
     * @return void
     */
    public function updateState($type, $change, $origin = Horde_ActiveSync::CHANGE_ORIGIN_NA, $user = null)
    {
        if ($origin == Horde_ActiveSync::CHANGE_ORIGIN_PIM) {
            $sql = 'INSERT INTO ' . $this->_syncMapTable . ' (message_uid, sync_modtime, sync_key, sync_devid, sync_folderid, sync_user) VALUES (?, ?, ?, ?, ?, ?)';
            try {
               $this->_db->insert($sql, array($change['id'], $change['mod'], $this->_syncKey, $this->_devId, $change['parent'], $user));
            } catch (Horde_Db_Exception $e) {
                $this->_logger->err($e->getMessage());
               throw new Horde_ActiveSync_Exception($e);
            }
            /* @TODO: Deal with PIM generated folder changes (mail only) */
        } else {
           /* When sending server changes, $this->_changes will contain all
            * changes. Need to track which ones are sent since we might not
            * send all of them.
            */
            $this->_logger->debug('Updating state during ' . $this->_type);
            foreach ($this->_changes as $key => $value) {
               if ($value['id'] == $change['id']) {
                   if ($this->_type == 'foldersync') {
                       foreach ($this->_state as $fi => $state) {
                           if ($state['id'] == $value['id']) {
                               unset($this->_state[$fi]);
                           }
                       }
                       /* Only save what we need, and ensure we have a mod time */
                       $stat = array(
                           'id' => $value['id'],
                           'mod' => (empty($value['mod']) ? time() : $value['mod']),
                           'parent' => (empty($value['parent']) ? 0 : $value['parent'])
                       );
                       $this->_state[] = $stat;
                       $this->_state = array_values($this->_state);
                   }
                   unset($this->_changes[$key]);
                   break;
               }
           }
       }
    }

    /**
     * Save folder data for a specific device. This is needed for BC with older
     * activesync versions that use GETHIERARCHY requests to get the folder info
     * instead of maintaining the folder state with FOLDERSYNC requests.
     *
     * @param object $device  The device object
     * @param array $folders  The folder data
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    public function setFolderData($device, $folders)
    {
        if (!is_array($folders) || empty ($folders)) {
            return false;
        }

        $unique_folders = array ();
        foreach ($folders as $folder) {
            /* don't save folder-ids for emails */
            if ($folder->type == Horde_ActiveSync::FOLDER_TYPE_INBOX) {
                continue;
            }

            /* no folder from that type or the default folder */
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

        /* Store it*/
        $sql = 'UPDATE ' . $this->_syncUsersTable . ' SET device_folders = ? WHERE device_id = ? AND device_user = ?';
        try {
            return $this->_db->update($sql, array(serialize($folders), $device->id, $device->user));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Get the folder data for a specific device
     *
     * @param object $device  The device object
     * @param string $class   The folder class to fetch (Calendar, Contacts etc.)
     *
     * @return mixed  Either an array of folder data || false
     */
    public function getFolderData($device, $class)
    {
        $sql = 'SELECT device_folders FROM ' . $this->_syncUsersTable . ' WHERE device_id = ? AND device_user = ?';
        try {
            $folders = $this->_db->selectValue($sql, array($device->id, $device->user));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
        if ($folders) {
            $folders = unserialize($folders);
            if ($class == "Calendar") {
                return $folders[Horde_ActiveSync::FOLDER_TYPE_APPOINTMENT];
            }
            if ($class == "Contacts") {
                return $folders[Horde_ActiveSync::FOLDER_TYPE_CONTACT];
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
        if (!isset($this->_state)) {
            throw new Horde_ActiveSync_Exception('Sync state not loaded');
        }
        $folders = array();
        foreach ($this->_state as $folder) {
            $folders[] = $folder['id'];
        }

        return $folders;
    }

    /**
     * Perform any initialization needed to deal with pingStates
     * For this driver
     *
     * @param string $devId  The device id of the PIM to load PING state for
     *
     * @return The $collection array
     */
    public function initPingState($device)
    {
        /* This would normally already be loaded by loadDeviceInfo() but we
         * should verify we have the correct device loaded etc... */
         if (!isset($this->_pingState) || $this->_devId !== $device->id) {
             throw new Horde_ActiveSync_Exception('Device not loaded');
         }

         /* Need to get the last sync time for this collection */
         return $this->_pingState['collections'];
    }

    /**
     * Obtain the device object. For this driver, we also store the PING data
     * in the device table.
     *
     * @param string $devId   The device id to obtain
     * @param string $user    The user to retrieve user-specific device info for
     *
     * @return object  The device obejct
     * @throws Horde_ActiveSync_Exception
     */
    public function loadDeviceInfo($devId, $user)
    {
        //@TODO - combine _devId and _deviceInfo
        /* See if we have it already */
        if ($this->_devId == $devId && !empty($this->_deviceInfo)) {
            return $this->_deviceInfo;
        }

        $this->_devId = $devId;
        $query = 'SELECT device_type, device_agent, device_ping, device_policykey, device_rwstatus, device_supported FROM '
            . $this->_syncDeviceTable . ' d INNER JOIN ' . $this->_syncUsersTable . ' u ON d.device_id = u.device_id WHERE u.device_id = ? AND u.device_user = ?';
        try {
            $this->_logger->debug('SQL QUERY: ' . $query . ' VALUES: ' . $devId . ' ' . $user);
            $result = $this->_db->selectOne($query, array($devId, $user));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
        $this->_deviceInfo = new StdClass();
        if ($result) {
            $this->_deviceInfo->policykey = $result['device_policykey'];
            $this->_deviceInfo->rwstatus = $result['device_rwstatus'];
            $this->_deviceInfo->deviceType = $result['device_type'];
            $this->_deviceInfo->userAgent = $result['device_agent'];
            $this->_deviceInfo->id = $devId;
            $this->_deviceInfo->user = $user;
            $this->_deviceInfo->supported = unserialize($result['device_supported']);
            if ($result['device_ping']) {
                $this->_pingState = empty($result['device_ping']) ? array() : unserialize($result['device_ping']);
            } else {
                $this->resetPingState();
            }
        } else {
            throw new Horde_ActiveSync_Exception('Device not found.');
        }

        return $this->_deviceInfo;
    }

    /**
     * Set new device info
     *
     * @param object $data  The device information
     *
     * @return boolean
     */
    public function setDeviceInfo($data)
    {
        /* Make sure we have the device entry */
        try {
            if (!$this->deviceExists($data->id)) {
                $this->_logger->debug('[' . $data->id . '] Device entry does not exist, creating it.');
                $query = 'INSERT INTO ' . $this->_syncDeviceTable
                    . ' (device_type, device_agent, device_policykey, device_rwstatus, device_id, device_supported)'
                    . ' VALUES(?, ?, ?, ?, ?, ?)';
                $values = array(
                    $data->deviceType,
                    $data->userAgent,
                    $data->policykey,
                    $data->rwstatus,
                    $data->id,
                    (!empty($data->supported) ? serialize($data->supported) : '')
                );
                $this->_db->execute($query, $values);
            }
        } catch(Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }

        $this->_deviceInfo = $data;

        /* See if we have the user already also */
        try {
            $query = 'SELECT COUNT(*) FROM ' . $this->_syncUsersTable . ' WHERE device_id = ? AND device_user = ?';
            $cnt = $this->_db->selectValue($query, array($data->id, $data->user));
            if (!$cnt) {
                $this->_logger->debug('[' . $data->id . '] Device entry does not exist for user ' . $data->user . ', creating it.');
                $query = 'INSERT INTO ' . $this->_syncUsersTable
                    . ' (device_ping, device_id, device_user)'
                    . ' VALUES(?, ?, ?)';

                $values = array(
                    '',
                    $data->id,
                    $data->user
                );
                $this->_devId = $data->id;
                return $this->_db->insert($query, $values);
            } else {
                return true;
            }
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Check that a given device id is known to the server. This is regardless
     * of Provisioning status. If $user is provided, checks that the device
     * is attached to the provided username.
     *
     * @param string $devId  The device id to check.
     * @param string $user   The device should be owned by this user.
     *
     * @return boolean
     */
    public function deviceExists($devId, $user = null)
    {
        if (!empty($user)) {
            $query = 'SELECT COUNT(*) FROM ' . $this->_syncDeviceTable . ' d INNER JOIN '
                . $this->_syncUsersTable . ' u ON d.device_id = u.device_id WHERE '
                . ' d.device_id = ? AND u.device_user = ?';
            $values = array($devId, $user);
        } else {
            $query = 'SELECT COUNT(*) FROM ' . $this->_syncDeviceTable . ' WHERE device_id = ?';
            $values = array($devId);
        }
        try {
            return $this->_db->selectValue($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * List all devices that we know about.
     *
     * @return array  An array of device hashes
     * @throws Horde_ActiveSync_Exception
     */
    public function listDevices($user = null)
    {
        $query = 'SELECT d.device_id device_id, device_type, device_agent,'
            . ' device_policykey, device_rwstatus, device_user FROM '
            . $this->_syncDeviceTable . ' d INNER JOIN ' . $this->_syncUsersTable
            . ' u ON d.device_id = u.device_id';
        $values = array();
        if (!empty($user)) {
            $query .= ' WHERE u.device_user = ?';
            $values[] = $user;
        }

        try {
            return $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Get the last time the loaded device issued a SYNC request.
     *
     * @return integer  The timestamp of the last sync, regardless of collection
     * @throws Horde_ActiveSync_Exception
     */
    public function getLastSyncTimestamp()
    {
        if (empty($this->_deviceInfo)) {
            throw new Horde_ActiveSync_Exception('Device not loaded.');
        }

        $sql = 'SELECT MAX(sync_time) FROM ' . $this->_syncStateTable . ' WHERE sync_devid = ? AND sync_user = ?';
        try {
            return $this->_db->selectValue($sql, array($this->_devId, $this->_deviceInfo->user));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
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
        $this->_logger->debug('[' . $this->_devId . '] Attempting to load PING state for: ' . $pingCollection['class']);
        if (!empty($this->_pingState['collections'][$pingCollection['class']])) {
            $this->_collection = $this->_pingState['collections'][$pingCollection['class']];
            $this->_collection['synckey'] = $this->_devId;
            $this->_lastSyncTS = $this->_getLastSyncTS();
            $this->_logger->debug('[' . $this->_devId . '] Obtained lasst sync time for ' . $pingCollection['class'] . ' - ' . $this->_lastSyncTS);
            if ($this->_lastSyncTS === false) {
                throw new Horde_ActiveSync_Exception('Previous syncstate has been removed.');
            }
            $haveState = true;
        }

        /* Initialize state for this collection */
        if (!$haveState) {
            $this->_logger->info('[' . $this->_devId . '] Empty state for '. $pingCollection['class']);

            /* Init members for the getChanges call */
            $this->_collection = $pingCollection;
            $this->_collection['synckey'] = $this->_devId;
            $this->_lastSyncTS = $this->_getLastSyncTS();
            if ($this->_lastSyncTS === false) {
                throw new Horde_ActiveSync_Exception('No previous SYNC command?');
            }
            /* If we are here, then the pingstate was empty, prime it */
            $this->_pingState['collections'][$this->_collection['class']] = $this->_collection;
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
        /* Update the ping's collection */
        if (!empty($this->_collection)) {
            $this->_pingState['collections'][$this->_collection['class']] = $this->_collection;
        }

        $state = serialize(array('lifetime' => $this->_pingState['lifetime'], 'collections' => $this->_pingState['collections']));
        $query = 'UPDATE ' . $this->_syncUsersTable . ' SET device_ping = ? WHERE device_id = ? AND device_user = ?';

        try {
            return $this->_db->update($query, array($state, $this->_devId, $this->_deviceInfo->user));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
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
     * Get all items that have changed since the last sync time
     *
     * @param integer $flags
     *
     * @return array
     */
    public function getChanges($flags = 0)
    {
        /* How far back to sync (for those collections that use this) */
        $cutoffdate = self::_getCutOffDate(!empty($this->_collection['filtertype'])
                ? $this->_collection['filtertype']
                : 0);

        /* Get the timestamp for THIS request */
        $this->_thisSyncTS = time();

        if (!empty($this->_collection['id'])) {
            $folderId = $this->_collection['id'];
            $this->_logger->debug('[' . $this->_devId . '] Initializing message diff engine for ' . $this->_collection['id']);
            /* Do nothing if it is a dummy folder */
            if ($folderId != Horde_ActiveSync::FOLDER_TYPE_DUMMY) {
                /* First, need to see if we have exising changes left over
                 * from a previous sync that resulted in a MORE_AVAILABLE */
                if (!empty($this->_changes) && count($this->_changes)) {
                    $this->_logger->debug('[' . $this->_devId . '] Returning previously found changes.');
                    return $this->_changes;
                }

                /* No existing changes, poll the backend */
                $changes = $this->_backend->getServerChanges($folderId, (int)$this->_lastSyncTS, (int)$this->_thisSyncTS, $cutoffdate);
            }
            /* Unfortunately we can't use an empty synckey to detect an initial
             * sync. The AS protocol doesn't start looking for changes until
             * after the device/server negotiate a synckey. What we CAN do is
             * at least query the map table to see if there are any entries at
             * all for this device before going through and stating all the
             * messages. */
            $this->_logger->debug('[' . $this->_devId . '] Found ' . count($changes) . ' message changes, checking for PIM initiated changes.');
            if ($this->_havePIMChanges()) {
                $this->_changes = array();
                foreach ($changes as $change) {
                    $stat = $this->_backend->statMessage($folderId, $change['id']);
                    $ts = $this->_getPIMChangeTS($change['id']);
                    if ($ts && $ts >= $stat['mod']) {
                        $this->_logger->debug('[' . $this->_devId . '] Ignoring PIM initiated change for ' . $change['id'] . '(PIM TS: ' . $ts . ' Stat TS: ' . $stat['mod']);
                    } else {
                        $this->_changes[] = $change;
                    }
                }
            } else {
                // No known PIM originated changes
                $this->_logger->debug('[' . $this->_devId . '] No PIM changes present, returning all messages.');
                $this->_changes = $changes;
            }
        } else {
            $this->_logger->debug('[' . $this->_devId . '] Initializing folder diff engine');
            $folderlist = $this->_backend->getFolderList();
            if ($folderlist === false) {
                return false;
            }
            $this->_changes = $this->_getDiff((empty($this->_state) ? array() : $this->_state), $folderlist);
            $this->_logger->debug('[' . $this->_devId . '] Found ' . count($this->_changes) . ' folder changes');
        }

        return $this->_changes;
    }

    /**
     * Save a new device policy key to storage.
     *
     * @param string $devId  The device id
     * @param integer $key   The new policy key
     */
    public function setPolicyKey($devId, $key)
    {
        if (empty($this->_deviceInfo) || $devId != $this->_deviceInfo->id) {
            throw new Horde_ActiveSync_Exception('Device not loaded');
        }

        $query = 'UPDATE ' . $this->_syncDeviceTable . ' SET device_policykey = ? WHERE device_id = ?';
        try {
            $this->_db->update($query, array($key, $devId));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
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
        $query = 'UPDATE ' . $this->_syncDeviceTable . ' SET device_policykey = 0';
        try {
            $this->_db->update($query);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Set a new remotewipe status for the device
     *
     * @param string $devid
     * @param string $status
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    public function setDeviceRWStatus($devId, $status)
    {
        $query = 'UPDATE ' . $this->_syncDeviceTable . ' SET device_rwstatus = ?';
        $values = array($status);

        if ($status == Horde_ActiveSync::RWSTATUS_PENDING) {
            /* Need to clear the policykey to force a PROVISION */
            $query .= ',device_policykey = ?';
            $values[] = 0;
        }
        $query .= ' WHERE device_id = ?';
        $values[] = $devId;
        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Explicitly remove a state from storage.
     *
     * @param string $synckey  The specific state to remove
     * @param string $devId    Remove all information for this device.
     * @param string $user     When removing device info, restrict to removing
     *                         data for this user only.
     *
     * @throws Horde_ActiveSyncException
     */
    public function removeState($synckey = null, $devId = null, $user = null)
    {
        $state_query = 'DELETE FROM ' . $this->_syncStateTable . ' WHERE';
        $map_query = 'DELETE FROM ' . $this->_syncMapTable . ' WHERE';
        if ($devId && $user) {
            $state_query .= ' sync_devid = ? AND sync_user = ?';
            $map_query .= ' sync_devid = ? AND sync_user = ?';
            $user_query = 'DELETE FROM ' . $this->_syncUsersTable . ' WHERE device_id = ? AND device_user = ?';
            $values = array($devId, $user);
            $this->_logger->debug('[' . $devId . '] Removing device state for user ' . $user . '.');
        } elseif ($devId){
            $state_query .= ' sync_devid = ?';
            $map_query .= ' sync_devid = ?';
            $user_query = 'DELETE FROM ' . $this->_syncUsersTable . ' WHERE device_id = ?';
            $device_query = 'DELETE FROM ' . $this->_syncDeviceTable . ' WHERE device_id = ?';
            $values = array($devId);
            $this->_logger->debug('[' . $devId . '] Removing all device state for device ' . $devId . '.');
        } else {
            $state_query .= ' sync_key = ?';
            $map_query .= ' sync_key = ?';
            $values = array($synckey);
            $this->_logger->debug('[' . $this->_devId . '] Removing device state for sync_key ' . $synckey . ' only.');
        }

        try {
            $this->_db->delete($state_query, $values);
            $this->_db->delete($map_query, $values);
            if (!empty($user_query)) {
                $this->_db->delete($user_query, $values);
            }
            if (!empty($device_query)) {
                $this->_db->delete($device_query, $values);
            } elseif (!empty($user_query)) {
                /* If there was a user_deletion, check if we should remove the
                 * device entry as well */
                $sql = 'SELECT COUNT(*) FROM ' . $this->_syncUsersTable . ' WHERE device_id = ?';
                if (!$this->_db->selectValue($sql, array($devId))) {
                    $query = 'DELETE FROM ' . $this->_syncDeviceTable . ' WHERE device_id = ?';
                    $this->_db->delete($query, array($devId));
                }
            }
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Get a timestamp from the map table for the last PIM-initiated change for
     * the provided uid. Used to avoid mirroring back changes to the PIM that it
     * sent to the server.
     *
     * @param string $uid
     */
    protected function _getPIMChangeTS($uid)
    {
        $sql = 'SELECT sync_modtime FROM ' . $this->_syncMapTable . ' WHERE message_uid = ? AND sync_devid = ? AND sync_user = ?';
        try {
            return $this->_db->selectValue($sql, array($uid, $this->_devId, $this->_deviceInfo->user));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Check for the existence of ANY entries in the map table for this device
     * and user.
     *
     * An extra database query for each sync, but the payoff is that we avoid
     * having to stat every message change we send to the PIM if there are no
     * PIM generated changes for this sync period.
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    protected function _havePIMChanges()
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->_syncMapTable . ' WHERE sync_devid = ? AND sync_user = ?';
        try {
            return (bool)$this->_db->selectValue($sql, array($this->_devId, $this->_deviceInfo->user));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Get the timestamp for the last successful sync for the current collection
     * or specified syncKey.
     *
     * @param string $syncKey  The (optional) syncKey to check.
     *
     * @return integer  The timestamp of the last successful sync or 0 if none
     */
    protected function _getLastSyncTS($syncKey = 0)
    {
        $sql = 'SELECT MAX(sync_time) FROM ' . $this->_syncStateTable . ' WHERE sync_folderid = ? AND sync_devid = ?';
        $values = array($this->_collection['id'], $this->_devId);
        if (!empty($syncKey)) {
            $sql .= ' AND sync_key = ?';
            array_push($values, $syncKey);
        }
        try {
            $this->_lastSyncTS = $this->_db->selectValue($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }

        return !empty($this->_lastSyncTS) ? $this->_lastSyncTS : 0;
    }

    /**
     * Garbage collector - clean up from previous sync requests.
     *
     * @params string $syncKey  The sync key
     *
     * @throws Horde_ActiveSync_Exception
     * @return boolean?
     */
    protected function _gc($syncKey)
    {
        if (!preg_match('/^s{0,1}\{([0-9A-Za-z-]+)\}([0-9]+)$/', $syncKey, $matches)) {
            return false;
        }
        $guid = $matches[1];
        $n = $matches[2];

        /* Clean up all but the last 2 syncs for any given sync series, this
         * ensures that we can still respond to SYNC requests for the previous
         * key if the PIM never received the new key in a SYNC response. */
        $sql = 'SELECT sync_key FROM ' . $this->_syncStateTable . ' WHERE sync_devid = ? AND sync_folderid = ?';
        $values = array($this->_devId,
                        !empty($this->_collection['id']) ? $this->_collection['id'] : 'foldersync');

        $this->_logger->debug('[' . $this->_devId . '] SQL query by Horde_ActiveSync_State:_gc(): ' . $sql . ' VALUES: ' . print_r($values, true));
        $results = $this->_db->selectAll($sql, $values);
        $remove = array();
        $guids = array($guid);
        foreach ($results as $oldkey) {
            if (preg_match('/^s{0,1}\{([0-9A-Za-z-]+)\}([0-9]+)$/', $oldkey['sync_key'], $matches)) {
                if ($matches[1] == $guid && $matches[2] < $n) {
                    $remove[] = $oldkey['sync_key'];
                }
            } else {
                /* stale key from previous key series */
                $remove[] = $oldkey['sync_key'];
                $guids[] = $matches[1];
            }
        }
        if (count($remove)) {
            $sql = 'DELETE FROM ' . $this->_syncStateTable . ' WHERE sync_key IN (' . str_repeat('?,', count($remove) - 1) . '?)';
            $this->_db->delete($sql, $remove);
        }

        /* Also clean up the map table since this data is only needed for one
         * SYNC cycle. Keep the same number of old keys for the same reasons as
         * above. */
        $sql = 'SELECT sync_key FROM ' . $this->_syncMapTable . ' WHERE sync_devid = ? AND sync_user = ?';
        $maps = $this->_db->selectValues($sql, array($this->_devId, $this->_deviceInfo->user));
        foreach ($maps as $key) {
            if (preg_match('/^s{0,1}\{([0-9A-Za-z-]+)\}([0-9]+)$/', $key, $matches)) {
                if ($matches[1] == $guid && $matches[2] < $n) {
                    $remove[] = $key;
                }
            }
        }
        if (count($remove)) {
            $sql = 'DELETE FROM ' . $this->_syncMapTable . ' WHERE sync_key IN (' . str_repeat('?,', count($remove) - 1) . '?)';
            $this->_db->delete($sql, $remove);
        }
        return true;
    }

    /**
     * Reset the sync state for this device, for the specified collection.
     *
     * @param string $id  The collection to reset.
     *
     * @return void
     * @throws Horde_ActiveSync_Exception
     */
    protected function _resetDeviceState($id)
    {
        $this->_logger->debug('[' . $this->_devId . '] Resetting device state.');
        $state_query = 'DELETE FROM ' . $this->_syncStateTable . ' WHERE sync_devid = ? AND sync_folderid = ?';
        $map_query = 'DELETE FROM ' . $this->_syncMapTable . ' WHERE sync_devid = ? AND sync_folderid = ?';
        try {
            $this->_db->delete($state_query, array($this->_devId, $id));
            $this->_db->delete($map_query, array($this->_devId, $id));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

}