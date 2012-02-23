<?php
/**
 * Horde_ActiveSync_State_Sql
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
 * SQL based state management. Responsible for maintaining device state
 * information such as last sync time, provisioning status, client-sent changes,
 * and for calculating deltas between server and client.
 *
 * Needs a number of SQL tables present:
 * <pre>
 *    syncStateTable (horde_activesync_state):
 *        sync_time:    - The timestamp of last sync
 *        sync_key:     - The syncKey for the last sync
 *        sync_pending: - If the last sync resulted in a MOREAVAILABLE, this
 *                        contains a list of UIDs that still need to be sent to
 *                        the client.
 *        sync_data:    - Any state data that we need to track for the specific
 *                        syncKey. Data such as current folder list on the client
 *                        (for a FOLDERSYNC) and IMAP email UIDs (for Email
 *                        collections during a SYNC).
 *        sync_devid:   - The device id.
 *        sync_folderid:- The folder id for this sync.
 *        sync_user:    - The user for this synckey
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
 *        device_id         - The unique id for this device
 *        device_type       - The device type the PIM identifies itself with
 *        device_agent      - The user agent string sent by the device
 *        device_policykey  - The current policykey for this device
 *        device_rwstatus   - The current remote wipe status for this device
 *
 *    syncUsersTable (horde_activesync_device_users):
 *        device_user      - A username attached to the device
 *        device_id        - The device id
 *        device_ping      - The account's ping state
 *        device_folders   - Account's folder data
 *        device_policykey - The provisioned policykey for this device/user
 *                           combonation.
 * </pre>
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2010-2012 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=ActiveSync
 * @package   ActiveSync
 */
class Horde_ActiveSync_State_Sql extends Horde_ActiveSync_State_Base
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
     *
     * @return Horde_ActiveSync_StateMachine_File
     */
    public function __construct($params = array())
    {
        parent::__construct($params);
        if (empty($this->_params['db']) || !($this->_params['db'] instanceof Horde_Db_Adapter)) {
            throw new InvalidArgumentException('Missing or invalid Horde_Db parameter.');
        }

        $this->_syncStateTable   = 'horde_activesync_state';
        $this->_syncMapTable     = 'horde_activesync_map';
        $this->_syncDeviceTable  = 'horde_activesync_device';
        $this->_syncUsersTable   = 'horde_activesync_device_users';
        $this->_syncMailMapTable = 'horde_activesync_mailmap';

        $this->_db = $params['db'];
    }

    /**
     * Load and initialize the sync state
     *
     * @param string $syncKey   The synckey of the state to load. If empty will
     *                          force a reset of the state for the class
     *                          specified in $id
     * @prarm string $type      The type of state a
     *                          Horde_ActiveSync::REQUEST_TYPE constant.
     * @param string $id        The folder id this state represents. If empty
     *                          assumed to be a foldersync state.
     *
     * @throws Horde_ActiveSync_Exception
     */
    public function loadState($syncKey, $type = null, $id = null)
    {
        $this->_type = $type;
        if ($type == Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC && empty($id)) {
            $id = Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC;
        }
        if (empty($syncKey)) {
            if ($type == Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC) {
                $this->_folder = array();
            } else {
                $this->_folder = ($this->_collection['class'] == Horde_ActiveSync::CLASS_EMAIL) ?
                    new Horde_ActiveSync_Folder_Imap($this->_collection['id'], Horde_ActiveSync::CLASS_EMAIL) :
                    new Horde_ActiveSync_Folder_Collection($this->_collection['id'], $this->_collection['class']);
            }
            $this->_resetDeviceState($id);
            return;
        }
        $this->_logger->debug(
            sprintf('[%s] Loading state for synckey %s',
                $this->_devId,
                $syncKey));

        // Check if synckey is allowed
        if (!preg_match('/^s{0,1}\{([0-9A-Za-z-]+)\}([0-9]+)$/', $syncKey, $matches)) {
            throw new Horde_ActiveSync_Exception('Invalid sync key');
        }
        $this->_syncKey = $syncKey;

        // Cleanup older syncstates
        $this->_gc($syncKey);

        // Load the previous syncState from storage
        try {
            $results = $this->_db->selectOne('SELECT sync_data, sync_devid, sync_time, sync_pending FROM '
                . $this->_syncStateTable . ' WHERE sync_key = ?', array($this->_syncKey));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
        if (!$results) {
            throw new Horde_ActiveSync_Exception('Sync State Not Found.');
        }

        // Load the last known sync time for this collection
        $this->_lastSyncTS = !empty($results['sync_time']) ? $results['sync_time'] : 0;

        // Pre-Populate the current sync timestamp in case this is only a
        // Client -> Server sync.
        $this->_thisSyncTS = $this->_lastSyncTS;

        // Restore any state or pending changes
        $data = unserialize($results['sync_data']);
        // @TODO: Convert from previous state data format.
        $pending = unserialize($results['sync_pending']);


        if ($type == Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC) {
            $this->_folder = ($data !== false) ? $data : array();
            $this->_logger->debug(
                sprintf('[%s] Loading FOLDERSYNC state: %s',
                $this->_devId,
                print_r($this->_folder, true)));

        } elseif ($type == Horde_ActiveSync::REQUEST_TYPE_SYNC) {
            $this->_folder = ($data !== false
                ? $data
                : ($this->_collection['class'] == Horde_ActiveSync::CLASS_EMAIL
                    ? new Horde_ActiveSync_Folder_Imap($this->_collection['id'], Horde_ActiveSync::CLASS_EMAIL)
                    : new Horde_ActiveSync_Folder_Collection($this->_collection['id'], $this->_collection['class']))
            );


            $this->_logger->debug(sprintf(
                '[%s] Loaded previous state data: %s',
                $this->_devId,
                print_r($this->_folder, true))
            );

            $this->_changes = ($pending !== false) ? $pending : null;
            if ($this->_changes) {
                $this->_logger->debug(
                    sprintf('[%s] Found %d changes remaining from previous SYNC.',
                    $this->_devId,
                    count($this->_changes)));
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
             if ($type == Horde_ActiveSync::CHANGE_TYPE_DELETE ||
                 $type == Horde_ActiveSync::CHANGE_TYPE_CHANGE) {
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
     * @throws Horde_ActiveSync_Exception
     */
    public function save()
    {
        // Update state table to remember this last synctime and key
        $sql = 'INSERT INTO ' . $this->_syncStateTable
            . ' (sync_key, sync_data, sync_devid, sync_time, sync_folderid, sync_user, sync_pending)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?)';

        // Prepare state and pending data
        if ($this->_type == Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC) {
            $data = (isset($this->_folder) ? serialize($this->_folder) : '');
            $pending = '';
        } elseif ($this->_type == Horde_ActiveSync::REQUEST_TYPE_SYNC) {
            $pending = (isset($this->_changes) ? serialize(array_values($this->_changes)) : '');
            $data = (isset($this->_folder) ? serialize($this->_folder) : '');
        } else {
            $pending = '';
            $data = '';
        }

        $params = array(
            $this->_syncKey,
            $data,
            $this->_devId,
            $this->_thisSyncTS,
            !empty($this->_collection['id']) ? $this->_collection['id'] : Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC,
            $this->_deviceInfo->user,
            $pending);
        $this->_logger->debug(
            sprintf('[%s] Saving state: %s', $this->_devId, print_r($params, true)));
        try {
            $this->_db->insert($sql, $params);
        } catch (Horde_Db_Exception $e) {
            // Might exist already if the last sync attempt failed.
            $this->_logger->notice(
                sprintf('[%s] Error saving state for synckey %s: %s - removing previous sync state and trying again.',
                        $this->_devId,
                        $this->_syncKey,
                        $e->getMessage()));
            $this->_db->delete('DELETE FROM ' . $this->_syncStateTable . ' WHERE sync_key = ?', array($this->_syncKey));
            $this->_db->insert($sql, $params);
        }
    }

    /**
     * Update the state to reflect changes
     *
     * Notes: If we are importing PIM changes, need to update the syncMapTable
     * so we don't mirror back the changes on next sync. If we are exporting
     * server changes, we need to track which changes have been sent (by
     * removing them from $this->_changes) so we know which items to send on the
     * next sync if a MOREAVAILBLE response was needed.  If this is being called
     * from a FOLDERSYNC command, update state accordingly. Yet another reason
     * to break out state handling into different classes based on the command
     * being run (Horde_ActiveSync_State_Sync, *_FolderSync, *_Ping etc...);
     *
     *  @TODO: Deal with PIM generated folder changes (mail only)
     *
     * @param string $type      The type of change (change, delete, flags or
     *                          foldersync)
     * @param array $change     A stat/change hash describing the change.
     *  Contains:
     *    'id'      - The message uid the change applies to
     *    'parent'  - The parent of the message, normally the folder id.
     *    'flags'   - If this is a flag change, the state of the read flag.
     *    'mod'     - The modtime of this change for collections that use it.
     *
     * @param integer $origin   Flag to indicate the origin of the change.
     *  Either:
     *    Horde_ActiveSync::CHANGE_ORIGIN_NA  - Not applicapble/not important
     *    Horde_ActiveSync::CHANGE_ORIGIN_PIM - Change originated from PIM
     *
     * @param string $user      The current sync user, only needed if change
     *                          origin is CHANGE_ORIGIN_PIM
     * @param string $clientid  PIM clientid sent when adding a new message
     *
     * @return void
     */
    public function updateState(
        $type, array $change, $origin = Horde_ActiveSync::CHANGE_ORIGIN_NA,
        $user = null, $clientid = '')
    {
        $this->_logger->debug('Updating state during ' . $type);
        if ($origin == Horde_ActiveSync::CHANGE_ORIGIN_PIM) {
            // This is an incoming change from the PIM, store it so we
            // don't mirror it back to device.
            if ($type == Horde_ActiveSync::CHANGE_TYPE_FLAGS) {
                // This is a mail sync changing only a read flag.
                $sql = 'INSERT INTO ' . $this->_syncMailMapTable
                    . ' (message_uid, sync_key, sync_devid,'
                    . ' sync_folderid, sync_user, sync_read)'
                    . ' VALUES (?, ?, ?, ?, ?, ?)';
                $params = array(
                    $change['id'],
                    $this->_syncKey,
                    $this->_devId,
                    $change['parent'],
                    $user,
                    $change['flags']
                );
            } else {
                $sql = 'INSERT INTO ' . $this->_syncMapTable
                    . ' (message_uid, sync_modtime, sync_key, sync_devid,'
                    . ' sync_folderid, sync_user, sync_clientid)'
                    . ' VALUES (?, ?, ?, ?, ?, ?, ?)';
                $params = array(
                   $change['id'],
                   $change['mod'],
                   $this->_syncKey,
                   $this->_devId,
                   $change['parent'],
                   $user,
                   $clientid);
            }
            try {
                $this->_db->insert($sql, $params);
            } catch (Horde_Db_Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_ActiveSync_Exception($e);
            }
        } else {
            // We are sending server changes; $this->_changes will contain all
            // changes so we need to track which ones are sent since not all
            // may be sent. We need to store the leftovers for sending next
            // request.
            foreach ($this->_changes as $key => $value) {
                if ($value['id'] == $change['id']) {
                    if ($type == Horde_ActiveSync::CHANGE_TYPE_FOLDERSYNC) {
                        foreach ($this->_folder as $fi => $state) {
                            if ($state['id'] == $value['id']) {
                                unset($this->_folder[$fi]);
                                break;
                            }
                        }
                        // Only save what we need. Note that 'mod' is eq to the
                        // folder id, since that is the only thing that can
                        // change in a folder.
                        $folder = $this->_backend->getFolder($value['id']);
                        $stat = array(
                           'id' => $value['id'],
                           'mod' => $folder->displayname,
                           'parent' => (empty($value['parent']) ? 0 : $value['parent'])
                        );
                        $this->_folder[] = $stat;
                        $this->_folder = array_values($this->_folder);
                    }
                    // @TODO: This makes NO sense. Probably a merge artifact. Remove
                    // after tested.
                    // if ($this->_collection['class'] != Horde_ActiveSync::CLASS_EMAIL) {
                    //     // Track the UIDs sent to the PIM.
                    //     foreach ($this->_state as $fi => $state) {
                    //         if ($state['id'] == $value['id']) {
                    //             unset($this->_state[$fi]);
                    //             break;
                    //         }
                    //     }
                    //     // @TODO - can we just use the entire $value here?
                    //     $stat = array(
                    //         'id' => $value['id'],
                    //         'mod' => $value['mod'],
                    //         'flags' => $value['flags']
                    //     );
                    //     $this->_state[] = $stat;
                    //     $this->_state = array_values($this->_state);
                    // }
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
     * Get the folder data for a specific device. Used only from very old
     * devices...and this is probably currently broken.
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
        if (!isset($this->_folder)) {
            throw new Horde_ActiveSync_Exception('Sync state not loaded');
        }
        $folders = array();
        foreach ($this->_folder as $folder) {
            $folders[] = $folder['id'];
        }

        return $folders;
    }

    /**
     * Perform any initialization needed to deal with pingStates for this driver
     *
     * @param string $devId  The device id to load pingState for
     *
     * @return The $collection array
     */
    public function initPingState($device)
    {
        // This would normally already be loaded by loadDeviceInfo() but we
        // should verify we have the correct device loaded etc...
        if (!isset($this->_pingState) || $this->_devId !== $device->id) {
            throw new Horde_ActiveSync_Exception('Device not loaded');
        }

        return $this->_pingState['collections'];
    }

    /**
     * Obtain the device object. We also store the PING data in the device
     * table.
     *
     * @param string $devId   The device id to obtain
     * @param string $user    The user to retrieve user-specific device info for
     *
     * @return StdClass The device object
     * @throws Horde_ActiveSync_Exception
     */
    public function loadDeviceInfo($devId, $user)
    {
        $this->_logger->debug(sprintf(
            "[%s] loadDeviceInfo: %s",
            $devId,
            $user));

        // See if we already have this device, for this user loaded
        if ($this->_devId == $devId && !empty($this->_deviceInfo) &&
            $user == $this->_deviceInfo->user) {
            return $this->_deviceInfo;
        }

        $this->_devId = $devId;
        $query = 'SELECT device_type, device_agent, '
            . 'device_rwstatus, device_supported FROM '
            . $this->_syncDeviceTable . ' WHERE device_id = ?';

        try {
            $device = $this->_db->selectOne($query, array($devId));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }

        if (!empty($user)) {
            $query = 'SELECT device_ping, device_policykey FROM ' . $this->_syncUsersTable
                . ' WHERE device_id = ? AND device_user = ?';
            try {
                $duser = $this->_db->selectOne($query, array($devId, $user));
            } catch (Horde_Db_Exception $e) {
                throw new Horde_ActiveSync_Exception($e);
            }
        } else {
            $this->resetPingState();
        }

        $this->_deviceInfo = new StdClass();
        if ($device) {
            $this->_deviceInfo->rwstatus = $device['device_rwstatus'];
            $this->_deviceInfo->deviceType = $device['device_type'];
            $this->_deviceInfo->userAgent = $device['device_agent'];
            $this->_deviceInfo->id = $devId;
            $this->_deviceInfo->user = $user;
            $this->_deviceInfo->supported = unserialize($device['device_supported']);
            if (empty($duser)) {
                $this->resetPingState();
                $this->_deviceInfo->policykey = 0;
            } else {
                if (empty($duser['device_ping'])) {
                    $this->resetPingState();
                } else {
                    $this->_pingState = unserialize($duser['device_ping']);
                }
                $this->_deviceInfo->policykey =
                    (empty($duser['device_policykey']) ?
                        0 :
                        $duser['device_policykey']);
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
                    . ' (device_type, device_agent, device_rwstatus, device_id, device_supported)'
                    . ' VALUES(?, ?, ?, ?, ?)';
                $values = array(
                    $data->deviceType,
                    $data->userAgent,
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
                    . ' (device_ping, device_id, device_user, device_policykey)'
                    . ' VALUES(?, ?, ?, ?)';

                $values = array(
                    '',
                    $data->id,
                    $data->user,
                    $data->policykey
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
            $query = 'SELECT COUNT(*) FROM ' . $this->_syncUsersTable
                . ' WHERE device_id = ? AND device_user = ?';
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
        $query = 'SELECT d.device_id AS device_id, device_type, device_agent,'
            . ' device_policykey, device_rwstatus, device_user FROM '
            . $this->_syncDeviceTable . ' d  INNER JOIN ' . $this->_syncUsersTable
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
     * Add a collection to the PING state. Ping state must already be loaded.
     *
     * @param array $collections  An array of collection information to replace
     *                            any existing cached ping collection state.
     */
    public function addPingCollections($collections)
    {
        if (empty($this->_pingState)) {
            throw new Horde_ActiveSync_Exception('PING state not initialized');
        }
        $this->_pingState['collections'] = array();
        foreach ($collections as $collection) {
            $this->_pingState['collections'][$collection['class']] = $collection;
        }
    }

    /**
     * Load a specific collection's ping state. Ping state must already have
     * been loaded.
     *
     * @param array $pingCollection  The collection array from the PIM request
     *
     * @throws Horde_ActiveSync_Exception, Horde_ActiveSync_Exception_StateGone,
     *         Horde_ActiveSync_Exception_InvalidRequest
     */
    public function loadPingCollectionState($pingCollection)
    {
        if (empty($this->_pingState)) {
            throw new Horde_ActiveSync_Exception('PING state not initialized');
        }
        $haveState = false;

        // Load any existing state
        // @TODO: I'm almost positive we need to key these by 'id', not 'class'
        // but this is what z-push did so...
        $this->_logger->debug(sprintf(
            "[%s] Attempting to load PING state for: %s",
            $this->_devId,
            $pingCollection['class']));

        if (!empty($this->_pingState['collections'][$pingCollection['class']])) {
            $this->_collection = $this->_pingState['collections'][$pingCollection['class']];
            $this->_collection['synckey'] = $this->_devId;
            if (!$this->_lastSyncTS = $this->_getLastSyncTS()) {
                throw new Horde_ActiveSync_Exception_StateGone('Previous syncstate has been removed.');
            }
            $this->_logger->debug(sprintf(
                "[%s] Obtained last SYNC time for %s - %s",
                $this->_devId,
                $pingCollection['class'],
                $this->_lastSyncTS));
            $this->_logger->debug(sprintf(
                "[%s] PING for %s folder, loading last known SYNC state.",
                $this->_devId,
                $pingCollection['id']));
            $this->_folder = $this->_getLastState(
                $this->_collection['id'],
                $this->_lastSyncTS);
        } else {
            // Initialize the collection's state.
            $this->_logger->info(sprintf(
                "[%s] Found empty state for %s",
                $this->_devID,
                $pingCollection['class']));

            // Init members for the getChanges call.
            $this->_collection = $pingCollection;
            $this->_collection['synckey'] = $this->_devId;

            // The PING state was empty, need to prime it.
            $this->_pingState['collections'][$this->_collection['class']] = $this->_collection;
            $this->savePingState();

            // We MUST have a previous successful SYNC before PING.
            if (!$this->_lastSyncTS = $this->_getLastSyncTS()) {
                throw new Horde_ActiveSync_Exception_InvalidRequest('No previous SYNC found for collection ' . $pingCollection['class']);
            }
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
        $this->_logger->debug(sprintf('Saving PING state: %s', $state));
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
     * @param integer $flags  Any flags to use
     *
     * @return array
     */
    public function getChanges($flags = 0)
    {
        // How far back to sync (for those collections that use this)
        $cutoffdate = self::_getCutOffDate(!empty($this->_collection['filtertype'])
                ? $this->_collection['filtertype']
                : 0);

        // Get the timestamp for THIS request
        $this->_thisSyncTS = time();

        if (!empty($this->_collection['id'])) {
            $this->_logger->debug(sprintf(
                "[%s] Initializing message diff engine for %s",
                $this->_devId,
                $this->_collection['id']));
            if ($this->_collection['id'] != Horde_ActiveSync::FOLDER_TYPE_DUMMY) {
                $folder = &$this->_folder;
                if (!empty($this->_changes)) {
                    $this->_logger->debug(sprintf(
                        "[%s] Returning previously found changes.",
                        $this->_devId));
                    return $this->_changes;
                }

                // No existing changes, poll the backend
                $changes = $this->_backend->getServerChanges(
                    $folder,
                    (int)$this->_lastSyncTS,
                    (int)$this->_thisSyncTS,
                    $cutoffdate);

                // @TODO: Need to test this.
                $this->_folder->updateState();
            }

            $this->_logger->debug(sprintf(
                "[%s] Found %n message changes, checking for PIM initiated changes.",
                $this->_devId,
                count($changes));

            $this->_changes = array();
            if ($this->_havePIMChanges($this->_collection['class'])) {
                switch ($this->_collection['class']) {
                case Horde_ActiveSync::CLASS_EMAIL:
                    foreach ($changes as $change) {
                        $stat = $this->_backend->statMailMessage($this->_collection['id'], $change['id']);
                        if ($stat && $this->_isPIMFlagChange($change['id'], $stat['flags'])) {
                            $this->_logger->debug(sprintf(
                                "[%s] Ignoring PIM initiated flag change for %s",
                                $this->_devId,
                                $change['id']));
                        } else {
                            // @TODO: Need to catch device-deleted messages,
                            // device moved messages etc...
                            $this->_changes[] = $change;
                        }
                    }
                default:
                    foreach ($changes as $change) {
                        $stat = $this->_backend->statMessage($folderId, $change['id']);
                        $ts = $this->_getPIMChangeTS($change['id']);
                        if ($ts && $ts >= $stat['mod']) {
                            $this->_logger->debug(sprintf(
                                "[%s] Ignoring PIM initiated change for %s (PIM TS: %s Stat TS: %s",
                                $this->_devId,
                                $change['id'], $ts, $stat['mod']));
                        } else {
                            $this->_changes[] = $change;
                        }
                    }
                }
            } else {
                $this->_logger->debug(sprintf(
                    "[%s] No PIM changes present, returning all messages.",
                    $this->_devId));
                $this->_changes = $changes;
            }
        } else {
            $this->_logger->debug(sprintf(
                "[%s] Initializing folder diff engine",
                $this->_devId));
            $folderlist = $this->_backend->getFolderList();
            if ($folderlist === false) {
                return false;
            }
            $this->_changes = $this->_getDiff(
                (empty($this->_folder) ? array() : $this->_folder),
                $folderlist);

            $this->_logger->debug(sprintf(
                "[%s] Found %n folder changes.",
                $this->_devId,
                count($this->_changes)));
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
            $this->_logger->err('Device not loaded');
            throw new Horde_ActiveSync_Exception('Device not loaded');
        }

        $query = 'UPDATE ' . $this->_syncUsersTable . ' SET device_policykey = ? WHERE device_id = ? AND device_user = ?';
        try {
            $this->_db->update($query, array($key, $devId, $this->_backend->getUser()));
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
        $query = 'UPDATE ' . $this->_syncUsersTable . ' SET device_policykey = 0';
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
        $query = 'UPDATE ' . $this->_syncDeviceTable . ' SET device_rwstatus = ?'
            . ' WHERE device_id = ?';
        $values = array($status, $devId);
        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }

        if ($status == Horde_ActiveSync::RWSTATUS_PENDING) {
            // Need to clear the policykey to force a PROVISION. Clear ALL
            // entries, to ensure the device is wiped.
            $query = 'UPDATE ' . $this->_syncUsersTable
                . ' SET device_policykey = 0 WHERE device_id = ?';
            try {
                $this->_db->update($query, array($devId));
            } catch (Horde_Db_Exception $e) {
                throw new Horde_ActiveSync_Exception($e);
            }
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
     * Check and see that we didn't already see the incoming change from the PIM.
     * This would happen e.g., if the PIM failed to receive the server response
     * after successfully importing new messages.
     *
     * @param string $id  The client id sent during message addition.
     *
     * @return string The UID for the given clientid, null if none found.
     * @throws Horde_ActiveSync_Exception
     */
     public function isDuplicatePIMAddition($id)
     {
        $sql = 'SELECT message_uid FROM ' . $this->_syncMapTable
            . ' WHERE sync_clientid = ? AND sync_user = ?';
        try {
            $uid = $this->_db->selectValue($sql, array($id, $this->_deviceInfo->user));

            return $uid;
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
     }

    /**
     * Get a timestamp from the map table for the last PIM-initiated change for
     * the provided uid. Used to avoid mirroring back changes to the PIM that it
     * sent to the server.
     *
     * @param string $uid  The uid of the entry to check.
     *
     * @return integer|null The timestamp of the last PIM-initiated change for
     *                      the specified uid, or null if none found.
     */
    protected function _getPIMChangeTS($uid)
    {
        $sql = 'SELECT sync_modtime FROM ' . $this->_syncMapTable
            . ' WHERE message_uid = ? AND sync_devid = ? AND sync_user = ?';
        try {
            return $this->_db->selectValue(
                $sql, array($uid, $this->_devId, $this->_deviceInfo->user));
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
     * @param string $class  The collection class to check for.
     *
     * @TODO: Optimize to only check for changes in a specific collection.
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    protected function _havePIMChanges($class = null)
    {
        $table = $class == Horde_ActiveSync::CLASS_EMAIL ?
            $this->_syncMailMapTable :
            $this->_syncMapTable;
        $sql = 'SELECT COUNT(*) FROM ' . $table
            . ' WHERE sync_devid = ? AND sync_user = ?';
        try {
            return (bool)$this->_db->selectValue(
                $sql, array($this->_devId, $this->_deviceInfo->user));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Get the timestamp for the last successful sync for the current collection
     * or specified syncKey.
     *
     * @return integer  The timestamp of the last successful sync or 0 if none
     */
    protected function _getLastSyncTS()
    {
        $sql = 'SELECT MAX(sync_time) FROM ' . $this->_syncStateTable
            . ' WHERE sync_folderid = ? AND sync_devid = ?';

        try {
            $this->_lastSyncTS = $this->_db->selectValue(
                $sql, array($this->_collection['id'], $this->_devId));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }

        return !empty($this->_lastSyncTS) ? $this->_lastSyncTS : 0;
    }

    protected function _isPIMFlagChange($id, $flag)
    {
        $sql = 'SELECT sync_read FROM ' . $this->_syncMailMapTable
            . ' WHERE sync_folderid = ? AND sync_devid = ? AND message_uid = ?'
            . ' AND sync_user = ?';

        try {
            $mflag = $this->_db->selectValue(
                $sql,
                array(
                    $this->_collection['id'],
                    $this->_devId, $id,
                    $this->_deviceInfo->user));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
        if ($mflag === false) {
            return false;
        }

        return $mflag == $flag;
    }

    protected function _getLastState($id, $ts)
    {
        $sql = 'SELECT sync_data FROM ' . $this->_syncStateTable
            . ' WHERE sync_folderid = ? AND sync_time = ?';

        return unserialize($this->_db->selectValue($sql, array($id, $ts)));
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

        // Clean up all but the last 2 syncs for any given sync series, this
        // ensures that we can still respond to SYNC requests for the previous
        // key if the PIM never received the new key in a SYNC response.
        $sql = 'SELECT sync_key FROM ' . $this->_syncStateTable
            . ' WHERE sync_devid = ? AND sync_folderid = ?';
        $values = array(
            $this->_devId,
            !empty($this->_collection['id'])
                ? $this->_collection['id']
                : Horde_ActiveSync::CHANGE_TYPE_FOLDERSYNC);

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
            $sql = 'DELETE FROM ' . $this->_syncStateTable . ' WHERE sync_key IN ('
                . str_repeat('?,', count($remove) - 1) . '?)';
            $this->_db->delete($sql, $remove);
        }

        // Also clean up the map table since this data is only needed for one
        // SYNC cycle. Keep the same number of old keys for the same reasons as
        // above.
        foreach (array($this->_syncMapTable, $this->_syncMailMapTable) as $table) {
            $remove = array();
            $sql = 'SELECT sync_key FROM ' . $table
                . ' WHERE sync_devid = ? AND sync_user = ?';
            $maps = $this->_db->selectValues(
                $sql,
                array($this->_devId, $this->_deviceInfo->user));
            foreach ($maps as $key) {
                if (preg_match('/^s{0,1}\{([0-9A-Za-z-]+)\}([0-9]+)$/', $key, $matches)) {
                    if ($matches[1] == $guid && $matches[2] < $n) {
                        $remove[] = $key;
                    }
                }
            }
            if (count($remove)) {
                $sql = 'DELETE FROM ' . $table . ' WHERE sync_key IN ('
                    . str_repeat('?,', count($remove) - 1) . '?)';
                $this->_db->delete($sql, $remove);
            }
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
        $user = 'DELETE FROM ' . $this->_syncUsersTable . ' WHERE device_id = ? AND device_user = ?';
        try {
            $this->_db->delete($state_query, array($this->_devId, $id));
            $this->_db->delete($map_query, array($this->_devId, $id));
            $this->_db->delete($user, array($this->_devId, $this->_devInfo->user));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

}