<?php
/**
 * Horde_ActiveSync_State_Sql::
 *
 * PHP Version 5
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * SQL based state management. Responsible for maintaining device state
 * information such as last sync time, provisioning status, client-sent changes,
 * and for calculating deltas between server and client.
 *
 * Needs a number of SQL tables present:
 *    syncStateTable (horde_activesync_state):
 *        sync_timestamp:    - The timestamp of last sync
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
 *        sync_user:    - The user for this synckey.
 *        sync_mod:     - The last modification stamp.
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
 *        device_policykey - The provisioned policykey for this device/user
 *                           combination.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2010-2013 Horde LLC (http://www.horde.org/)
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
    protected $_lastSyncStamp = 0;

    /**
     * The current sync timestamp
     *
     * @var timestamp
     */
    protected $_thisSyncStamp = 0;

    /**
     * DB handle
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * State table name. This table holds the device's current state.
     *
     * @var string
     */
    protected $_syncStateTable;

    /**
     * The Sync Map table. This table temporarily holds information about
     * changes received FROM the client and is used to prevent mirroring back
     * changes to the client that originated there.
     *
     * @var string
     */
    protected $_syncMapTable;

    /**
     * The Sync Mail Map table. Same principle as self::_syncMapTable, but for
     * email collection data.
     *
     * @var string
     */
    protected $_syncMailMapTable;

    /**
     * Device information table.  Holds information about each client.
     *
     * @var string
     */
    protected $_syncDeviceTable;

    /**
     * Users table. Holds information specific to a user.
     *
     * @var string
     */
    protected $_syncUsersTable;

    /**
     * The Synccache table. Holds the sync cache and is used to cache info
     * about SYNC and PING request that are only sent a single time. Also stores
     * data supported looping SYNC requests.
     *
     * @var string
     */
    protected $_syncCacheTable;

    /**
     * The process id (used for logging).
     *
     * @var integer
     */
    protected $_procid;

    /**
     * Const'r
     *
     * @param array  $params   Must contain:
     *      - db:  (Horde_Db_Adapter_Base)  The Horde_Db instance.
     *
     * @return Horde_ActiveSync_State_Sql
     */
    public function __construct(array $params = array())
    {
        parent::__construct($params);
        if (empty($this->_params['db']) || !($this->_params['db'] instanceof Horde_Db_Adapter)) {
            throw new InvalidArgumentException('Missing or invalid Horde_Db parameter.');
        }

        $this->_procid = getmypid();
        $this->_syncStateTable   = 'horde_activesync_state';
        $this->_syncMapTable     = 'horde_activesync_map';
        $this->_syncDeviceTable  = 'horde_activesync_device';
        $this->_syncUsersTable   = 'horde_activesync_device_users';
        $this->_syncMailMapTable = 'horde_activesync_mailmap';
        $this->_syncCacheTable   = 'horde_activesync_cache';

        $this->_db = $params['db'];
    }

    /**
     * Update the serverid for a given folder uid in the folder's state object.
     * Needed when a folder is renamed on a client, but the UID must remain the
     * same.
     *
     * @param string $uid       The folder UID.
     * @param string $serverid  The new serverid for this uid.
     * @since 2.4.0
     */
    public function updateServerIdInState($uid, $serverid)
    {
        $this->_logger->info(sprintf(
            '[%s] Updating serverid in folder state. Setting %s for %s.',
            $this->_procid,
            $serverid,
            $uid));
        $sql = 'SELECT sync_data FROM ' . $this->_syncStateTable . ' WHERE '
            . 'sync_devid = ? AND sync_user = ? AND sync_folderid = ?';

        try {
            $results = $this->_db->selectValues($sql,
                array($this->_deviceInfo->id, $this->_devInfo->user, $uid));
        } catch (Horde_Db_Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }
        $update = 'UPDATE ' . $this->_syncStateTable . ' SET sync_data = ? WHERE '
            . 'sync_devid = ? AND sync_user = ? AND sync_folderid = ?';
        foreach ($results as $folder) {
            $folder = unserialize($folder);
            $folder->setServerId($serverid);
            $folder = serialize($folder);
            try {
                $this->_db->update($update,
                    array($folder, $this->_deviceInfo->id, $this->_devInfo->user, $uid));
            } catch (Horde_Db_Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_ActiveSync_Exception($e);
            }
        }
    }

    /**
     * Load and initialize the sync state
     *
     * @param array $collection  The collection array for the collection, if
     *                           a FOLDERSYNC, pass an empty array.
     * @param string $syncKey    The synckey of the state to load. If empty will
     *                           force a reset of the state for the class
     *                           specified in $id
     * @param string $type       The type of state a
     *                           Horde_ActiveSync::REQUEST_TYPE constant.
     * @param string $id         The folder id this state represents. If empty
     *                           assumed to be a foldersync state.
     *
     * @throws Horde_ActiveSync_Exception, Horde_ActiveSync_Exception_StateGone
     */
    public function loadState(array $collection, $syncKey, $type = null, $id = null)
    {
        // Initialize the local members.
        $this->_collection = $collection;
        $this->_changes = null;
        $this->_type = $type;

        // If this is a FOLDERSYNC, mock the device id.
        if ($type == Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC && empty($id)) {
            $id = Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC;
        }

        // synckey == 0 is an initial sync or reset.
        if (empty($syncKey)) {
            $this->_logger->notice(sprintf(
                '[%s] Horde_ActiveSync_State_Sql::loadState: clearing folder state.',
                $this->_procid));
            if ($type == Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC) {
                $this->_folder = array();
            } else {
                // Create a new folder object.
                $this->_folder = ($this->_collection['class'] == Horde_ActiveSync::CLASS_EMAIL) ?
                    new Horde_ActiveSync_Folder_Imap($this->_collection['serverid'], Horde_ActiveSync::CLASS_EMAIL) :
                    new Horde_ActiveSync_Folder_Collection($this->_collection['serverid'], $this->_collection['class']);
            }
            $this->_syncKey = '0';
            $this->_resetDeviceState($id);
            return;
        }

        $this->_logger->info(
            sprintf('[%s] Loading state for synckey %s',
                $this->_procid,
                $syncKey)
        );

        // Check if synckey is allowed
        if (!preg_match('/^s{0,1}\{([0-9A-Za-z-]+)\}([0-9]+)$/', $syncKey, $matches)) {
            throw new Horde_ActiveSync_Exception('Invalid sync key');
        }

        $this->_syncKey = $syncKey;

        // Cleanup older syncstates
        $this->_gc($syncKey);

        // Load the previous syncState from storage
        try {
            $results = $this->_db->selectOne('SELECT sync_data, sync_devid, sync_mod, sync_pending FROM '
                . $this->_syncStateTable . ' WHERE sync_key = ?', array($syncKey));
        } catch (Horde_Db_Exception $e) {
            $this->_logger->err('Error in loading state from DB: ' . $e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }

        if (empty($results)) {
            $this->_logger->err(sprintf(
                '[%s] Could not find state for synckey %s.',
                $this->_procid,
                $syncKey));
            throw new Horde_ActiveSync_Exception_StateGone();
        }

        $this->_loadStateFromResults($results, $type);
    }

    /**
     * Actually load the state data into the object from the query results.
     *
     * @param array $results  The results array from the state query.
     * @param string $type    The type of request we are handling.
     *
     * @throws Horde_ActiveSync_Exception_StateGone
     */
    protected function _loadStateFromResults($results, $type = Horde_ActiveSync::REQUEST_TYPE_SYNC)
    {
        // Load the last known sync time for this collection
        $this->_lastSyncStamp = !empty($results['sync_mod'])
            ? $results['sync_mod']
            : 0;

        // Pre-Populate the current sync timestamp in case this is only a
        // Client -> Server sync.
        $this->_thisSyncStamp = $this->_lastSyncStamp;

        // Restore any state or pending changes
        try {
            $columns = $this->_db->columns($this->_syncStateTable);
        } catch (Horde_Db_Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }
        $data = unserialize($columns['sync_data']->binaryToString($results['sync_data']));
        $pending = unserialize($results['sync_pending']);

        if ($type == Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC) {
            $this->_folder = ($data !== false) ? $data : array();
            $this->_logger->info(
                sprintf('[%s] Loading FOLDERSYNC state containing %d folders.',
                $this->_procid,
                count($this->_folder)));
        } elseif ($type == Horde_ActiveSync::REQUEST_TYPE_SYNC) {
            // @TODO: This shouldn't default to an empty folder object,
            // if we don't have the data, it's an exception.
            $this->_folder = ($data !== false
                ? $data
                : ($this->_collection['class'] == Horde_ActiveSync::CLASS_EMAIL
                    ? new Horde_ActiveSync_Folder_Imap($this->_collection['serverid'], Horde_ActiveSync::CLASS_EMAIL)
                    : new Horde_ActiveSync_Folder_Collection($this->_collection['serverid'], $this->_collection['class']))
            );
            $this->_changes = ($pending !== false) ? $pending : null;
            if ($this->_changes) {
                $this->_logger->info(
                    sprintf('[%s] Found %d changes remaining from previous SYNC.',
                    $this->_procid,
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
     * @param array $stat   A message stat array
     * @param string $type  The type of change (change, delete, add)
     *
     * @return boolean
     */
    public function isConflict($stat, $type)
    {
        // $stat == server's message information
        if ($stat['mod'] > $this->_lastSyncStamp &&
            ($type == Horde_ActiveSync::CHANGE_TYPE_DELETE ||
             $type == Horde_ActiveSync::CHANGE_TYPE_CHANGE)) {

             // changed here - deleted there
             // changed here - changed there
             return true;
        }

        return false;
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
            . ' (sync_key, sync_data, sync_devid, sync_mod, sync_folderid, sync_user, sync_pending, sync_timestamp)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

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

        // If we are setting the first synckey iteration, do not save the
        // timestamp, otherwise we will never get the initial set of data.
        $params = array(
            $this->_syncKey,
            new Horde_Db_Value_Binary($data),
            $this->_deviceInfo->id,
            (self::getSyncKeyCounter($this->_syncKey) == 1 ? 0 : $this->_thisSyncStamp),
            (!empty($this->_collection['id']) ? $this->_collection['id'] : Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC),
            $this->_deviceInfo->user,
            $pending,
            time());
        $this->_logger->info(
            sprintf('[%s] Saving state: %s',
                $this->_procid,
                print_r(
                    array(
                        $params[0],
                        $params[1],
                        $params[2],
                        $params[3],
                        $params[4],
                        $params[5],
                        count($this->_changes),
                        time()),
                    true)
                )
            );
        try {
            $this->_db->insert($sql, $params);
        } catch (Horde_Db_Exception $e) {
            // Might exist already if the last sync attempt failed.
            $this->_logger->notice(
                sprintf('[%s] Error saving state for synckey %s: %s - removing previous sync state and trying again.',
                        $this->_procid,
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
     * from a FOLDERSYNC command, update state accordingly.
     *
     * @param string $type      The type of change (change, delete, flags or
     *                          foldersync)
     * @param array $change     A stat/change hash describing the change.
     *  Contains:
     *    - id: (mixed)         The message uid the change applies to.
     *    - serverid: (string)  The backend server id for the folder.
     *    - folderuid: (string) The EAS folder UID for the folder.
     *    - parent: (string)    The parent of the current folder, if any.
     *    - flags: (array)      If this is a flag change, the state of the flags.
     *    - mod: (integer)      The modtime of this change.
     *
     * @param integer $origin   Flag to indicate the origin of the change:
     *    Horde_ActiveSync::CHANGE_ORIGIN_NA  - Not applicapble/not important
     *    Horde_ActiveSync::CHANGE_ORIGIN_PIM - Change originated from PIM
     *
     * @param string $user      The current sync user, only needed if change
     *                          origin is CHANGE_ORIGIN_PIM
     * @param string $clientid  PIM clientid sent when adding a new message
     */
    public function updateState(
        $type, array $change, $origin = Horde_ActiveSync::CHANGE_ORIGIN_NA,
        $user = null, $clientid = '')
    {
        $this->_logger->info(sprintf('[%s] Updating state during %s', $this->_procid, $type));

        if ($origin == Horde_ActiveSync::CHANGE_ORIGIN_PIM) {
            if ($this->_type == Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC) {
                foreach ($this->_folder as $fi => $state) {
                    if ($state['id'] == $change['id']) {
                        unset($this->_folder[$fi]);
                        break;
                    }
                }
                if ($type != Horde_ActiveSync::CHANGE_TYPE_DELETE) {
                    $this->_folder[] = $change;
                }
                $this->_folder = array_values($this->_folder);
                return;
            }

            // Some requests like e.g., MOVEITEMS do not include the state
            // information since there is no SYNCKEY. Attempt to map this from
            // the $change array.
            if (empty($this->_collection)) {
                $this->_collection = array(
                    'class' => $change['class'],
                    'id' => $change['folderuid']);
            }
            $syncKey = empty($this->_syncKey)
                ? $this->getLatestSynckeyForCollection($this->_collection['id'])
                : $this->_syncKey;

            // This is an incoming change from the PIM, store it so we
            // don't mirror it back to device.
            switch ($this->_collection['class']) {
            case Horde_ActiveSync::CLASS_EMAIL:
                if ($type == Horde_ActiveSync::CHANGE_TYPE_CHANGE &&
                    isset($change['flags']) && is_array($change['flags']) &&
                    !empty($change['flags'])) {
                    $type = Horde_ActiveSync::CHANGE_TYPE_FLAGS;
                }
                if ($type == Horde_ActiveSync::CHANGE_TYPE_FLAGS) {
                    if (isset($change['flags']['read'])) {
                        // This is a mail sync changing only a read flag.
                        $sql = 'INSERT INTO ' . $this->_syncMailMapTable
                            . ' (message_uid, sync_key, sync_devid,'
                            . ' sync_folderid, sync_user, sync_read)'
                            . ' VALUES (?, ?, ?, ?, ?, ?)';
                        $flag_value = !empty($change['flags']['read']);
                    } else {
                        $sql = 'INSERT INTO ' . $this->_syncMailMapTable
                            . ' (message_uid, sync_key, sync_devid,'
                            . ' sync_folderid, sync_user, sync_flagged)'
                            . ' VALUES (?, ?, ?, ?, ?, ?)';
                        $flag_value = !empty($change['flags']['flagged']);
                    }
                } else {
                    $sql = 'INSERT INTO ' . $this->_syncMailMapTable
                        . ' (message_uid, sync_key, sync_devid,'
                        . ' sync_folderid, sync_user, sync_deleted)'
                        . ' VALUES (?, ?, ?, ?, ?, ?)';
                }
                $params = array(
                    $change['id'],
                    $syncKey,
                    $this->_deviceInfo->id,
                    $this->_collection['id'],
                    $user,
                    ($type == Horde_ActiveSync::CHANGE_TYPE_FLAGS) ? $flag_value : true
                );
                break;

            default:
                $sql = 'INSERT INTO ' . $this->_syncMapTable
                    . ' (message_uid, sync_modtime, sync_key, sync_devid,'
                    . ' sync_folderid, sync_user, sync_clientid, sync_deleted)'
                    . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
                $params = array(
                   $change['id'],
                   $change['mod'],
                   $syncKey,
                   $this->_deviceInfo->id,
                   $change['serverid'],
                   $user,
                   $clientid,
                   $type == Horde_ActiveSync::CHANGE_TYPE_DELETE);
            }

            try {
                $this->_db->insert($sql, $params);
            } catch (Horde_Db_Exception $e) {
                throw new Horde_ActiveSync_Exception($e);
            }
        } else {
            // We are sending server changes; $this->_changes will contain all
            // changes so we need to track which ones are sent since not all
            // may be sent. We need to store the leftovers for sending next
            // request.
            foreach ($this->_changes as $key => $value) {
                if ($value['id'] == $change['id']) {
                    if ($this->_type == Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC) {
                        foreach ($this->_folder as $fi => $state) {
                            if ($state['id'] == $value['id']) {
                                unset($this->_folder[$fi]);
                                break;
                            }
                        }
                        // Only save what we need. Note that 'mod' is eq to the
                        // folder id, since that is the only thing that can
                        // change in a folder.
                        if ($type != Horde_ActiveSync::CHANGE_TYPE_DELETE) {
                            $folder = $this->_backend->getFolder($value['serverid']);
                            $stat = $this->_backend->statFolder(
                                $value['id'],
                                (empty($value['parent']) ? '0' : $value['parent']),
                                $folder->displayname,
                                $folder->_serverid);
                            $this->_folder[] = $stat;
                            $this->_folder = array_values($this->_folder);
                        }
                    }
                    unset($this->_changes[$key]);
                    break;
                }
            }
        }
    }

    /**
     * Return an array of known folders. This is essentially the state for a
     * FOLDERSYNC request. AS uses a seperate synckey for FOLDERSYNC requests
     * also, so need to treat it as any other collection.
     *
     * @return array  An array of folder uids.
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
     * Load the device object.
     *
     * @param string $devId   The device id to obtain
     * @param string $user    The user to retrieve user-specific device info for
     *
     * @return Horde_ActiveSync_Device  The device object
     * @throws Horde_ActiveSync_Exception
     */
    public function loadDeviceInfo($devId, $user = null)
    {
        // See if we already have this device, for this user loaded
        if (!empty($this->_deviceInfo) && $this->_deviceInfo->id == $devId &&
            !empty($this->_deviceInfo) &&
            $user == $this->_deviceInfo->user) {
            return $this->_deviceInfo;
        }

        $query = 'SELECT device_type, device_agent, '
            . 'device_rwstatus, device_supported, device_properties FROM '
            . $this->_syncDeviceTable . ' WHERE device_id = ?';

        try {
            if (!$device = $this->_db->selectOne($query, array($devId))) {
                throw new Horde_ActiveSync_Exception('Device not found.');
            }
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }

        if (!empty($user)) {
            $query = 'SELECT device_policykey FROM ' . $this->_syncUsersTable
                . ' WHERE device_id = ? AND device_user = ?';
            try {
                $duser = $this->_db->selectOne($query, array($devId, $user));
            } catch (Horde_Db_Exception $e) {
                throw new Horde_ActiveSync_Exception($e);
            }
        }

        $this->_deviceInfo = new Horde_ActiveSync_Device($this);
        $this->_deviceInfo->rwstatus = $device['device_rwstatus'];
        $this->_deviceInfo->deviceType = $device['device_type'];
        $this->_deviceInfo->userAgent = $device['device_agent'];
        $this->_deviceInfo->id = $devId;
        $this->_deviceInfo->user = $user;
        $this->_deviceInfo->supported = unserialize($device['device_supported']);
        if (empty($duser)) {
            $this->_deviceInfo->policykey = 0;
        } else {
            $this->_deviceInfo->policykey = empty($duser['device_policykey'])
                ? 0
                : $duser['device_policykey'];
        }
        $this->_deviceInfo->properties = unserialize($device['device_properties']);

        return $this->_deviceInfo;
    }

    /**
     * Set new device info
     *
     * @param Horde_ActiveSync_Device $data  The device information
     *
     * @throws Horde_ActiveSync_Exception
     */
    public function setDeviceInfo($data)
    {
        // Make sure we have the device entry
        try {
            if (!$this->deviceExists($data->id)) {
                $this->_logger->info(sprintf('[%s] Device entry does not exist for %s creating it.', $this->_procid, $data->id));
                $query = 'INSERT INTO ' . $this->_syncDeviceTable
                    . ' (device_type, device_agent, device_rwstatus, device_id, device_supported)'
                    . ' VALUES(?, ?, ?, ?, ?)';
                $values = array(
                    $data->deviceType,
                    (!empty($data->userAgent) ? $data->userAgent : ''),
                    $data->rwstatus,
                    $data->id,
                    (!empty($data->supported) ? serialize($data->supported) : '')
                );
                $this->_db->insert($query, $values);
            } else {
                $this->_logger->info((sprintf(
                    '[%s] Device entry exists for %s, updating userAgent and version.',
                    $this->_procid,
                    $data->id)));
                $query = 'UPDATE ' . $this->_syncDeviceTable
                    . ' SET device_agent = ?, device_properties = ?'
                    . ' WHERE device_id = ?';
                $values = array(
                    (!empty($data->userAgent) ? $data->userAgent : ''),
                    serialize($data->properties),
                    $data->id
                );
                $this->_db->update($query, $values);
            }
        } catch(Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }

        $this->_deviceInfo = $data;

        // See if we have the user already also
        try {
            $query = 'SELECT COUNT(*) FROM ' . $this->_syncUsersTable . ' WHERE device_id = ? AND device_user = ?';
            $cnt = $this->_db->selectValue($query, array($data->id, $data->user));
            if ($cnt == 0) {
                $this->_logger->info(sprintf('[%s] Device entry does not exist for device %s and user %s - creating it.', $this->_procid, $data->id, $data->user));
                $query = 'INSERT INTO ' . $this->_syncUsersTable
                    . ' (device_id, device_user, device_policykey)'
                    . ' VALUES(?, ?, ?)';

                $values = array(
                    $data->id,
                    $data->user,
                    $data->policykey
                );
                return $this->_db->insert($query, $values);
            }
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Set the device's properties as sent by a SETTINGS request.
     *
     * @param array $data       The device settings
     * @param string $deviceId  The device id.
     *
     * @throws Horde_ActiveSync_Exception
     */
    public function setDeviceProperties(array $data, $deviceId)
    {
        $query = 'UPDATE ' . $this->_syncDeviceTable . ' SET device_properties = ?,'
            . ' device_agent = ? WHERE device_id = ?';
        $properties = array(
            serialize($data),
            !empty($data[Horde_ActiveSync_Request_Settings::SETTINGS_USERAGENT]) ? $data[Horde_ActiveSync_Request_Settings::SETTINGS_USERAGENT] : '',
            $deviceId);
        try {
            $this->_db->update($query, $properties);
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
     * @return integer  The numer of device entries found for the give devId,
     *                  user combination. I.e., 0 == no device exists.
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
     * @param string $user  The username to list devices for. If empty, will
     *                      return all devices.
     *
     * @return array  An array of device hashes
     * @throws Horde_ActiveSync_Exception
     */
    public function listDevices($user = null)
    {
        $query = 'SELECT d.device_id AS device_id, device_type, device_agent,'
            . ' device_policykey, device_rwstatus, device_user, device_properties FROM '
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
     * @param string $id   The (optional) devivce id. If empty will use the
     *                     currently loaded device.
     * @param string $user The (optional) user id. If empty wil use the
     *                     currently loaded device.
     *
     * @return integer  The timestamp of the last sync, regardless of collection
     * @throws Horde_ActiveSync_Exception
     */
    public function getLastSyncTimestamp($id = null, $user = null)
    {
        if (empty($id) && empty($this->_deviceInfo)) {
            throw new Horde_ActiveSync_Exception('Device not loaded.');
        }
        $id = empty($id) ? $this->_deviceInfo->id : $id;
        $user = empty($user) ? $this->_deviceInfo->user : $user;
        $params = array($id);

        $sql = 'SELECT MAX(sync_timestamp) FROM ' . $this->_syncStateTable . ' WHERE sync_devid = ?';
        if (!empty($user)) {
            $sql .= ' AND sync_user = ?';
            $params[] = $user;
        }

        try {
            return $this->_db->selectValue($sql, $params);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Get all items that have changed since the last sync time
     *
     * @param array $options  An options array:
     *      - ping: (boolean)  Only detect if there is a change, do not build
     *                          any messages.
     *               DEFAULT: false (Build full change array).
     *
     * @return array  An array of hashes describing each change:
     *   - id:      The id of the item being changed.
     *   - type:    The type of change. a Horde_ActiveSync::CHANGE_TYPE_*
     *              constant.
     *   - flags:   Used to transport email message flags when type is
     *              Horde_ActiveSync::CHANGE_TYPE_FLAGS or set to
     *              Horde_ActiveSync::FLAG_NEWMESSAGE when type is
     *              Horde_ActiveSync::CHANGE_TYPE_CHANGE and the message
     *              represents a new message, as opposed to a change in an
     *              existing message.
     *   - ignore:  Set to true when the change should be ignored, and not sent
     *              to the client by the exporter. Usually due to the change
     *              being the result of a client originated change.
     *
     * @throws Horde_ActiveSync_Exception_StaleState
     */
    public function getChanges(array $options = array())
    {
        if (!empty($this->_collection['id'])) {
            // How far back to sync (for those collections that use this)
            $cutoffdate = self::_getCutOffDate(!empty($this->_collection['filtertype'])
                ? $this->_collection['filtertype']
                : 0);

            $this->_logger->info(sprintf(
                '[%s] Initializing message diff engine for %s (%s)',
                $this->_procid,
                $this->_collection['id'],
                $this->_folder->serverid()
                ));

            if (!empty($this->_changes)) {
                $this->_logger->info(sprintf(
                    '[%s] Returning previously found changes.',
                    $this->_procid));
                return $this->_changes;
            }

            // Get the current syncStamp from the backend.
            $this->_thisSyncStamp = $this->_backend->getSyncStamp(
                empty($this->_collection['id']) ? null : $this->_collection['id'],
                $this->_lastSyncStamp);
            if ($this->_thisSyncStamp === false) {
                throw new Horde_ActiveSync_Exception_StaleState(
                    'Detecting a change in timestamp or modification sequence. Reseting state.');
            }

            $this->_logger->info(sprintf(
                '[%s] Using SYNCSTAMP %s for %s.',
                $this->_procid,
                $this->_thisSyncStamp,
                $this->_collection['id']));

            // No existing changes, poll the backend
            $changes = $this->_backend->getServerChanges(
                $this->_folder,
                (int)$this->_lastSyncStamp,
                (int)$this->_thisSyncStamp,
                $cutoffdate,
                !empty($options['ping']),
                $this->_folder->haveInitialSync
            );

            // Only update the folderstate if we are not PINGing.
            if (empty($options['ping'])) {
                $this->_folder->updateState();
            }

            $this->_logger->info(sprintf(
                '[%s] Found %d message changes in %s.',
                $this->_procid,
                count($changes),
                $this->_collection['id']));

            // Check changes for mirrored client chagnes, but only if we KNOW
            // we have some client changes.
            $this->_changes = array();
            if (count($changes) && $this->_havePIMChanges()) {
                $this->_logger->info(sprintf(
                    '[%s] Checking for PIM initiated changes.',
                    $this->_procid));

                switch ($this->_collection['class']) {
                case Horde_ActiveSync::CLASS_EMAIL:
                    foreach ($changes as $change) {
                        switch ($change['type']) {
                        case Horde_ActiveSync::CHANGE_TYPE_FLAGS:
                            if ($this->_isPIMChange($change['id'], $change['flags'], $change['type'])) {
                                $this->_logger->info(sprintf(
                                    '[%s] Ignoring PIM initiated flag change for %s',
                                    $this->_procid,
                                    $change['id']));
                                $change['ignore'] = true;
                            }
                            $this->_changes[] = $change;
                            break;

                        case Horde_ActiveSync::CHANGE_TYPE_DELETE:
                            if ($this->_isPIMChange($change['id'], true, $change['type'])) {
                               $this->_logger->info(sprintf(
                                    '[%s] Ignoring PIM initiated deletion for %s',
                                    $this->_procid,
                                    $change['id']));
                                $change['ignore'] = true;
                            }
                            $this->_changes[] = $change;
                            break;

                        default:
                            // New message.
                            $this->_changes[] = $change;
                        }
                    }
                    break;

                default:
                    $pim_timestamps = $this->_getPIMChangeTS($changes);
                    foreach ($changes as $change) {
                        if (!empty($pim_timestamps[$change['id']]) &&
                            $change['type'] == Horde_ActiveSync::CHANGE_TYPE_DELETE) {
                            // If we have a delete, don't bother stating the message,
                            // If we have a delete entry in the map table, the
                            // entry should already be deleted on the client, we
                            // should never, ever need to send a REMOVE to the client
                            // if we have a delete entry in the map table.
                            $stat['mod'] = 0;
                        } elseif (!empty($pim_timestamps[$change['id']])) {
                            // stat only returns MODIFY times, not deletion times,
                            // so will return (int)0 for ADD or DELETE.
                            $stat = $this->_backend->statMessage($this->_folder->serverid(), $change['id']);
                        }
                        if (!empty($pim_timestamps[$change['id']]) && $pim_timestamps[$change['id']] >= $stat['mod']) {
                            $this->_logger->info(sprintf(
                                '[%s] Ignoring PIM initiated change for %s (PIM TS: %s Stat TS: %s)',
                                $this->_procid,
                                $change['id'], $pim_timestamps[$change['id']], $stat['mod']));
                        } else {
                            $this->_changes[] = $change;
                        }
                    }
                }
            } elseif (count($changes)) {
                $this->_logger->info(sprintf(
                    '[%s] No PIM changes present, returning all messages.',
                    $this->_procid));
                $this->_changes = $changes;
            }
        } else {
            $this->_getFolderChanges();
        }

        return $this->_changes;
    }

    /**
     * Get folder changes. Populates $this->_changes with an array of change
     * entries each containing 'type', 'id' and possibly 'flags'.
     */
    protected function _getFolderChanges()
    {
        $this->_logger->info(sprintf(
            '[%s] Initializing folder diff engine',
            $this->_procid));
        $folderlist = $this->_backend->getFolderList();
        if ($folderlist === false) {
            return false;
        }
        // @TODO Remove in H6. We need to ensure we have 'serverid' in the
        // returned stat data.
        foreach ($folderlist as &$folder)
        {
            // Either the backend populates this or not. So, if we have it, we
            // can stop checking.
            if (!empty($folder['serverid'])) {
                break;
            } else {
                $folder['serverid'] = $folder['id'];
            }
        }
        $this->_changes = $this->_getDiff(
            (empty($this->_folder) ? array() : $this->_folder),
            $folderlist);

        if (!count($this->_changes)) {
            $this->_logger->info(sprintf(
                '[%s] No folder changes found.',
                $this->_procid));
        } else {
            $this->_logger->info(sprintf(
                '[%s] Found %d folder changes.',
                $this->_procid,
                count($this->_changes)));
        }
    }

    /**
     * Save a new device policy key to storage.
     *
     * @param string $devId  The device id
     * @param integer $key   The new policy key
     *
     * @throws Horde_ActiveSync_Exception
     */
    public function setPolicyKey($devId, $key)
    {
        if (empty($this->_deviceInfo) || $devId != $this->_deviceInfo->id) {
            $this->_logger->err('Device not loaded');
            throw new Horde_ActiveSync_Exception('Device not loaded');
        }

        $query = 'UPDATE ' . $this->_syncUsersTable
            . ' SET device_policykey = ? WHERE device_id = ? AND device_user = ?';
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
     * @param string $devId    The device id.
     * @param string $status   A Horde_ActiveSync::RWSTATUS_* constant.
     *
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
     * @param array $options  An options array containing at least one of:
     *   - synckey: (string)  Remove only the state associated with this synckey.
     *              DEFAULT: All synckeys are removed for the specified device.
     *   - devId:   (string)  Remove all information for this device.
     *              DEFAULT: None. If no device, a synckey is required.
     *   - user:    (string) Restrict to removing data for this user only.
     *              DEFAULT: None - all users for the specified device are removed.
     *   - id:      (string)  When removing device state, restrict ro removing data
     *                        only for this collection.
     *
     * @throws Horde_ActiveSyncException
     */
    public function removeState(array $options)
    {
        $state_query = 'DELETE FROM ' . $this->_syncStateTable . ' WHERE';
        $map_query = 'DELETE FROM %TABLE% WHERE';

        // If the device is flagged as wiped, and we are removing the state,
        // we MUST NOT restrict to user since it will not remove the device's
        // device table entry, and the device will continue to be wiped each
        // time it connects.
        if (!empty($options['devId']) && !empty($options['user'])) {
            $q = 'SELECT device_rwstatus FROM ' . $this->_syncDeviceTable
                . ' WHERE device_id = ?';

            try {
                $results = $this->_db->selectValue($q, array($options['devId']));
                if ($results != Horde_ActiveSync::RWSTATUS_NA &&
                    $results != Horde_ActiveSync::RWSTATUS_OK) {
                    unset($options['user']);
                    return $this->removeState($options);
                }
            } catch (Horde_Db_Exception $e) {
                throw new Horde_ActiveSync_Exception($e);
            }

            $state_query .= ' sync_devid = ? AND sync_user = ?';
            $map_query .= ' sync_devid = ? AND sync_user = ?';
            $user_query = 'DELETE FROM ' . $this->_syncUsersTable
                . ' WHERE device_id = ? AND device_user = ?';
            $state_values = $values = array($options['devId'], $options['user']);

            if (!empty($options['id'])) {
                $state_query .= ' AND sync_folderid = ?';
                $map_query .= ' AND sync_folderid = ?';
                $state_values[] = $options['id'];

                $this->_logger->info(sprintf(
                    '[%s] Removing device state for user %s and collection %s.',
                    $options['devId'],
                    $options['user'],
                    $options['id'])
                );
            } else {
                $this->_logger->info(sprintf(
                    '[%s] Removing device state for user %s.',
                    $options['devId'],
                    $options['user'])
                );
                $this->deleteSyncCache($options['devId'], $options['user']);
            }
        } elseif (!empty($options['devId'])) {
            $state_query .= ' sync_devid = ?';
            $map_query .= ' sync_devid = ?';
            $user_query = 'DELETE FROM ' . $this->_syncUsersTable
                . ' WHERE device_id = ?';
            $device_query = 'DELETE FROM ' . $this->_syncDeviceTable
                . ' WHERE device_id = ?';
            $state_values = $values = array($options['devId']);
            $this->_logger->info(sprintf(
                '[%s] Removing all device state for device %s.',
                $options['devId'],
                $options['devId'])
            );
            $this->deleteSyncCache($options['devId']);
        } elseif (!empty($options['user'])) {
            $state_query .= ' sync_user = ?';
            $map_query .= ' sync_user = ?';
            $user_query = 'DELETE FROM ' . $this->_syncUsersTable
                . ' WHERE device_user = ?';
            $state_values = $values = array($options['user']);
            $this->_logger->info(sprintf(
                '[%s] Removing all device state for user %s.',
                $this->_procid,
                $options['user'])
            );
            $this->deleteSyncCache(null, $options['user']);
        } elseif (!empty($options['synckey'])) {
            $state_query .= ' sync_key = ?';
            $map_query .= ' sync_key = ?';
            $state_values = $values = array($options['synckey']);
            $this->_logger->info(sprintf(
                '[%s] Removing device state for sync_key %s only.',
                $this->_procid,
                $options['synckey'])
            );
        } else {
            return;
        }

        try {
            $this->_db->delete($state_query, $state_values);
            $this->_db->delete(
                str_replace('%TABLE%', $this->_syncMapTable, $map_query),
                $state_values);
            $this->_db->delete(
                str_replace('%TABLE%', $this->_syncMailMapTable, $map_query),
                $state_values);

            if (!empty($user_query)) {
                $this->_db->delete($user_query, $values);
            }
            if (!empty($device_query)) {
                $this->_db->delete($device_query, $values);
            } elseif (!empty($user_query) && empty($options['devId'])) {
                // If there was a user_deletion, check if we should remove the
                // device entry as well
                $sql = 'SELECT COUNT(*) FROM ' . $this->_syncUsersTable . ' WHERE device_id = ?';
                if (!$this->_db->selectValue($sql, array($options['devId']))) {
                    $query = 'DELETE FROM ' . $this->_syncDeviceTable . ' WHERE device_id = ?';
                    $this->_db->delete($query, array($options['devId']));
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
     * Return the sync cache.
     *
     * @param string $devid  The device id.
     * @param string $user   The user id.
     *
     * @return array  The current sync cache for the user/device combination.
     * @throws Horde_ActiveSync_Exception
     */
    public function getSyncCache($devid, $user)
    {
        $sql = 'SELECT cache_data FROM ' . $this->_syncCacheTable
            . ' WHERE cache_devid = ? AND cache_user = ?';
        try {
            $data = $this->_db->selectValue(
                $sql, array($devid, $user));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
        if (!$data = unserialize($data)) {
            return array(
                'confirmed_synckeys' => array(),
                'lasthbsyncstarted' => false,
                'lastsyncendnormal' => false,
                'timestamp' => false,
                'wait' => false,
                'hbinterval' => false,
                'folders' => array(),
                'hierarchy' => false,
                'collections' => array(),
                'pingheartbeat' => false,
                'synckeycounter' => array());
        } else {
            return $data;
        }
    }

    /**
     * Return the most recent synckey for the specified collection.
     *
     * @param  string $collection_id  The activesync collection id.
     *
     * @return string|integer  The synckey or 0 if not found.
     * @since 2.9.0
     */
    public function getLatestSynckeyForCollection($collection_id)
    {
        // For now, pull in the raw cache_data. Will change when each bit of
        // data gets it's own field.
        $data = $this->getSyncCache($this->_deviceInfo->id, $this->_deviceInfo->user);

        return !empty($data['collections'][$collection_id]['lastsynckey'])
            ? $data['collections'][$collection_id]['lastsynckey']
            : 0;
    }

    /**
     * Save the provided sync_cache.
     *
     * @param array $cache   The cache to save.
     * @param string $devid  The device id.
     * @param string $user   The user id.
     *
     * @throws Horde_ActiveSync_Exception
     */
    public function saveSyncCache(array $cache, $devid, $user)
    {
        $cache['timestamp'] = strval($cache['timestamp']);
        $sql = 'SELECT count(*) FROM ' . $this->_syncCacheTable
            . ' WHERE cache_devid = ? AND cache_user = ?';
        try {
            $have = $this->_db->selectValue($sql, array($devid, $user));
        } catch (Horde_Db_Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }
        $cache = serialize($cache);
        if ($have) {
            $this->_logger->info(
                sprintf('[%s] Replacing SYNC_CACHE entry for user %s and device %s: %s',
                    $this->_procid, $user, $devid, $cache));
            $sql = 'UPDATE ' . $this->_syncCacheTable
                . ' SET cache_data = ? WHERE cache_devid = ? AND cache_user = ?';
            try {
                $this->_db->update(
                    $sql,
                    array($cache, $devid, $user)
                );
            } catch (Horde_Db_Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_ActiveSync_Exception($e);
            }
        } else {
            $this->_logger->info(
                sprintf('[%s] Adding new SYNC_CACHE entry for user %s and device %s: %s',
                    $this->_procid, $user, $devid, $cache));
            $sql = 'INSERT INTO ' . $this->_syncCacheTable
                . ' (cache_data, cache_devid, cache_user) VALUES (?, ?, ?)';
            try {
                $this->_db->insert(
                    $sql,
                    array($cache, $devid, $user)
                );
            } catch (Horde_Db_Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_ActiveSync_Exception($e);
            }
        }
    }

    /**
     * Delete a complete sync cache
     *
     * @param string $devid  The device id
     * @param string $user   The user name.
     *
     * @throws Horde_ActiveSync_Exception
     */
    public function deleteSyncCache($devid, $user = null)
    {
        $this->_logger->info(sprintf(
            'Horde_ActiveSync_State_Sql::deleteSyncCache(%s, %s)',
            $devid, $user));

        $sql = 'DELETE FROM ' . $this->_syncCacheTable . ' WHERE ';

        $params = array();
        if (!empty($devid)) {
            $sql .= 'cache_devid = ? ';
            $params[] = $devid;
        }
        if (!empty($user)) {
            $sql .= (!empty($devid) ? 'AND ' : '') . 'cache_user = ?';
            $params[] = $user;
        }
        try {
            $this->_db->delete($sql, $params);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Get a EAS Folder Uid for the given backend server id.
     *
     * @param string $serverid  The backend server id. E.g., 'INBOX'.
     *
     * @return string|boolean  The EAS UID for the requested serverid, or false
     *                         if it is not found.
     * @since 2.4.0
     */
    public function getFolderUidForBackendId($serverid)
    {
        $cache = $this->getSyncCache($this->_deviceInfo->id, $this->_deviceInfo->user);
        $folders = $cache['folders'];
        foreach ($folders as $id => $folder) {
            if ($folder['serverid'] == $serverid) {
                $this->_logger->info(sprintf(
                    '[%s] Found serverid for %s: %s',
                    $this->_procid,
                    $serverid,
                    $id));
                return $id;
            }
        }

        $this->_logger->info(sprintf(
            '[%s] No folderid found for %s',
            $this->_procid,
            $serverid));

        return false;
    }

    /**
     * Return an array of timestamps from the map table for the last
     * PIM-initiated change for the provided uid. Used to avoid mirroring back
     * changes to the PIM that it sent to the server.
     *
     * @param array $changes  The changes array, containing 'id' and 'type'.
     *
     * @return array  An array of UID -> timestamp of the last PIM-initiated
     *                change for the specified uid, or null if none found.
     */
    protected function _getPIMChangeTS($changes)
    {
        $sql = 'SELECT message_uid, MAX(sync_modtime) FROM ' . $this->_syncMapTable
            . ' WHERE sync_devid = ? AND sync_user = ? AND sync_key IN (?, ?) ';

        // Get the allowed synckeys to include.
        $uuid = self::getSyncKeyUid($this->_syncKey);
        $cnt = self::getSyncKeyCounter($this->_syncKey);
        $values = array($this->_deviceInfo->id, $this->_deviceInfo->user);
        foreach (array($uuid . $cnt, $uuid . ($cnt - 1)) as $v) {
            $values[] = $v;
        }

        $conditions = '';
        foreach ($changes as $change) {
            $d = $change['type'] == Horde_ActiveSync::CHANGE_TYPE_DELETE;
            if (strlen($conditions)) {
                $conditions .= 'OR ';
            }
            $conditions .= '(message_uid = ?' . ($d ? ' AND sync_deleted = ?) ' : ') ');
            $values[] = $change['id'];
            if ($d) {
                $values[] = $d;
            }
        }
        $sql .= 'AND ' . $conditions . 'GROUP BY message_uid';
        try {
            return $this->_db->selectAssoc($sql, $values);
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
        $class = $this->_collection['class'];

        $table = $class == Horde_ActiveSync::CLASS_EMAIL ?
            $this->_syncMailMapTable :
            $this->_syncMapTable;
        $sql = 'SELECT COUNT(*) FROM ' . $table
            . ' WHERE sync_devid = ? AND sync_user = ? AND sync_folderid = ?';

        try {
            return (bool)$this->_db->selectValue(
                $sql, array($this->_deviceInfo->id, $this->_deviceInfo->user, $this->_collection['id']));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Determines if a specific email change originated from the client. Used to
     * avoid mirroring back client initiated changes.
     *
     * @param string $id     The object id.
     * @param array  $flags  An array of item flags.
     * @param string $type   The type of change;
     *                       A Horde_ActiveSync::CHANGE_TYPE_* constant.
     *
     * @return boolean  True if changes is due to an incoming client change.
     */
    protected function _isPIMChange($id, $flags, $type)
    {
        $this->_logger->info(sprintf(
            '_isPIMChange: %s, %s, %s',
            $id, print_r($flags, true), $type));
        if ($type == Horde_ActiveSync::CHANGE_TYPE_FLAGS) {
            if ($this->_isPIMChangeQuery($id, $flags['read'], 'sync_read')) {
                return true;
            }
            if ($this->_isPIMChangeQuery($id, $flags['flagged'], 'sync_flagged')) {
                return true;
            }

            return false;
        } else {
            return $this->_isPIMChangeQuery($id, true, 'sync_deleted');
        }
    }

    /**
     * Perform the change query.
     *
     * @param string $id     The object id
     * @param array  $flag   The flag value.
     * @param string $field  The field containing the change type.
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    protected function _isPIMChangeQuery($id, $flag, $field)
    {
        $sql = 'SELECT ' . $field . ' FROM ' . $this->_syncMailMapTable
            . ' WHERE sync_folderid = ? AND sync_devid = ? AND message_uid = ?'
            . ' AND sync_user = ?';

        try {
            $mflag = $this->_db->selectValue(
                $sql,
                array(
                    $this->_collection['id'],
                    $this->_deviceInfo->id, $id,
                    $this->_deviceInfo->user));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }

        if (is_null($mflag) || $mflag === false) {
            return false;
        }

        return $mflag == $flag;
    }

    /**
     * Garbage collector - clean up from previous sync requests.
     *
     * @param string $syncKey  The sync key
     *
     * @throws Horde_ActiveSync_Exception
     */
    protected function _gc($syncKey)
    {
        if (!preg_match('/^s{0,1}\{([0-9A-Za-z-]+)\}([0-9]+)$/', $syncKey, $matches)) {
            return;
        }
        $guid = $matches[1];
        $n = $matches[2];

        // Clean up all but the last 2 syncs for any given sync series, this
        // ensures that we can still respond to SYNC requests for the previous
        // key if the PIM never received the new key in a SYNC response.
        $sql = 'SELECT sync_key FROM ' . $this->_syncStateTable
            . ' WHERE sync_devid = ? AND sync_folderid = ?';
        $values = array(
            $this->_deviceInfo->id,
            !empty($this->_collection['id'])
                ? $this->_collection['id']
                : Horde_ActiveSync::CHANGE_TYPE_FOLDERSYNC);

        try {
            $results = $this->_db->selectAll($sql, $values);
        } catch (Horde_Db_Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }
        $remove = array();
        $guids = array($guid);
        foreach ($results as $oldkey) {
            if (preg_match('/^s{0,1}\{([0-9A-Za-z-]+)\}([0-9]+)$/', $oldkey['sync_key'], $matches)) {
                if ($matches[1] == $guid && $matches[2] < ($n - 1)) {
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

            try {
                $this->_db->delete($sql, $remove);
            } catch (Horde_Db_Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_ActiveSync_Exception($e);
            }
        }

        // Also clean up the map table since this data is only needed for one
        // SYNC cycle. Keep the same number of old keys for the same reasons as
        // above.
        foreach (array($this->_syncMapTable, $this->_syncMailMapTable) as $table) {
            $remove = array();
            $sql = 'SELECT sync_key FROM ' . $table
                . ' WHERE sync_devid = ? AND sync_user = ?';

            try {
                $maps = $this->_db->selectValues(
                    $sql,
                    array($this->_deviceInfo->id, $this->_deviceInfo->user)
                );
            } catch (Horde_Db_Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_ActiveSync_Exception($e);
            }
            foreach ($maps as $key) {
                if (preg_match('/^s{0,1}\{([0-9A-Za-z-]+)\}([0-9]+)$/', $key, $matches)) {
                    if ($matches[1] == $guid && $matches[2] < ($n - 1)) {
                        $remove[] = $key;
                    }
                }
            }
            if (count($remove)) {
                $sql = 'DELETE FROM ' . $table . ' WHERE sync_key IN ('
                    . str_repeat('?,', count($remove) - 1) . '?)';

                try {
                    $this->_db->delete($sql, $remove);
                } catch (Horde_Db_Exception $e) {
                    $this->_logger->err($e->getMessage());
                    throw new Horde_ActiveSync_Exception($e);
                }
            }
        }
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
        $this->_logger->info(sprintf(
            '[%s] Resetting device state for device: %s, user: %s, and collection: %s.',
            $this->_procid,
            $this->_deviceInfo->id,
            $this->_deviceInfo->user,
            $id));
        $state_query = 'DELETE FROM ' . $this->_syncStateTable . ' WHERE sync_devid = ? AND sync_folderid = ? AND sync_user = ?';
        $map_query = 'DELETE FROM ' . $this->_syncMapTable . ' WHERE sync_devid = ? AND sync_folderid = ? AND sync_user = ?';
        $mailmap_query = 'DELETE FROM ' . $this->_syncMailMapTable . ' WHERE sync_devid = ? AND sync_folderid = ? AND sync_user = ?';
        try {
            $this->_db->delete($state_query, array($this->_deviceInfo->id, $id, $this->_deviceInfo->user));
            $this->_db->delete($map_query, array($this->_deviceInfo->id, $id, $this->_deviceInfo->user));
            $this->_db->delete($mailmap_query, array($this->_deviceInfo->id, $id, $this->_deviceInfo->user));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }

        // Remove the collection data from the synccache as well.
        $cache = new Horde_ActiveSync_SyncCache($this, $this->_deviceInfo->id, $this->_deviceInfo->user, $this->_logger);
        if ($id != Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC) {
            $cache->removeCollection($id, false);
        } else {
            $this->_logger->notice(sprintf(
                '[%s] Clearing foldersync state from synccache.',
                $this->_procid));
            $cache->clearFolders();
            $cache->clearCollections();
            $cache->hierarchy = '0';
        }
        $cache->save();
    }

}
