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
 * @copyright 2010-2014 Horde LLC (http://www.horde.org)
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
 * @copyright 2010-2014 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=ActiveSync
 * @package   ActiveSync
 */
class Horde_ActiveSync_State_Sql extends Horde_ActiveSync_State_Base
{
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
                array($this->_deviceInfo->id, $this->_deviceInfo->user, $uid));
        } catch (Horde_Db_Exception $e) {
            $this->_logger->err(sprintf(
                '[%s] %s',
                $this->_procid,
                $e->getMessage())
            );
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
                    array($folder, $this->_deviceInfo->id, $this->_deviceInfo->user, $uid));
            } catch (Horde_Db_Exception $e) {
                $this->_logger->err(sprintf(
                    '[%s] %s',
                    $this->_procid,
                    $e->getMessage())
                );
                throw new Horde_ActiveSync_Exception($e);
            }
        }
    }

    /**
     * Load the state represented by $syncKey from storage.
     *
     * @param string $type       The type of state a
     *                           Horde_ActiveSync::REQUEST_TYPE constant.
     *
     * @throws Horde_ActiveSync_Exception, Horde_ActiveSync_Exception_StateGone
     */
    protected function _loadState($type)
    {
        // Load the previous syncState from storage
        try {
            $results = $this->_db->selectOne('SELECT sync_data, sync_devid, sync_mod, sync_pending FROM '
                . $this->_syncStateTable . ' WHERE sync_key = ?', array($this->_syncKey));
        } catch (Horde_Db_Exception $e) {
            $this->_logger->err(sprintf(
                '[%s] %s',
                $this->_procid,
                $e->getMessage())
            );
            throw new Horde_ActiveSync_Exception($e);
        }

        if (empty($results)) {
            $this->_logger->err(sprintf(
                '[%s] Could not find state for synckey %s.',
                $this->_procid,
                $this->_syncKey));
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
            $this->_logger->err(sprintf(
                '[%s] %s',
                $this->_procid,
                $e->getMessage())
            );
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
                serialize(array(
                    $params[0],
                    $params[1],
                    $params[2],
                    $params[3],
                    $params[4],
                    $params[5],
                    count($this->_changes),
                    time()))
                )
            );
        try {
            $this->_db->insert($sql, $params);
        } catch (Horde_Db_Exception $e) {
            // Might exist already if the last sync attempt failed.
            $this->_logger->notice(
                sprintf('[%s] Previous request processing for synckey %s failed to be accepted by the client, removing previous state and trying again.',
                        $this->_procid,
                        $this->_syncKey)
                );
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
     *
     * @todo This method needs some cleanup, abstraction.
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
                switch ($type) {
                case Horde_ActiveSync::CHANGE_TYPE_FLAGS:
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
                    break;
                case Horde_ActiveSync::CHANGE_TYPE_DELETE:
                    $sql = 'INSERT INTO ' . $this->_syncMailMapTable
                        . ' (message_uid, sync_key, sync_devid,'
                        . ' sync_folderid, sync_user, sync_deleted)'
                        . ' VALUES (?, ?, ?, ?, ?, ?)';
                    $flag_value = true;
                    break;
                case Horde_ActiveSync::CHANGE_TYPE_CHANGE:
                    // Used to remember "new" messages that are a result of
                    // a MOVEITEMS request.
                    $sql = 'INSERT INTO ' . $this->_syncMailMapTable
                        . ' (message_uid, sync_key, sync_devid,'
                        . ' sync_folderid, sync_user, sync_changed)'
                        . ' VALUES (?, ?, ?, ?, ?, ?)';
                    $flag_value = true;
                    break;
                }
                $params = array($change['id'], $syncKey, $this->_deviceInfo->id,
                    $change['serverid'], $user, $flag_value
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
                                $folder->_serverid,
                                $folder->type);
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
            $columns = $this->_db->columns($this->_syncDeviceTable);
            $device['device_properties'] = $columns['device_properties']
                ->binaryToString($device['device_properties']);
            $device['device_supported'] = $columns['device_supported']
                ->binaryToString($device['device_supported']);
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
     * @param array $dirty                   Array of dirty properties.
     *                                       @since 2.9.0
     *
     * @throws Horde_ActiveSync_Exception
     */
    public function setDeviceInfo(Horde_ActiveSync_Device $data, array $dirty = array())
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
        $query = 'UPDATE ' . $this->_syncDeviceTable . ' SET device_properties = ?'
            . ' WHERE device_id = ?';
        $properties = array(
            serialize($data),
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
     * @param array $filter An array of optional filters where the keys are
     *                      field names and the values are values to match.
     *
     * @return array  An array of device hashes
     * @throws Horde_ActiveSync_Exception
     */
    public function listDevices($user = null, $filter = array())
    {
        $query = 'SELECT d.device_id AS device_id, device_type, device_agent,'
            . ' device_policykey, device_rwstatus, device_user, device_properties FROM '
            . $this->_syncDeviceTable . ' d  INNER JOIN ' . $this->_syncUsersTable
            . ' u ON d.device_id = u.device_id';
        $values = array();
        $glue = false;
        if (!empty($user)) {
            $query .= ' WHERE u.device_user = ?';
            $values[] = $user;
            $glue = true;
        }
        $explicit_fields = array('device_id', 'device_type', 'device_agent', 'device_user');
        foreach ($filter as $key => $value) {
            if (in_array($key, $explicit_fields)) {
                $query .= ($glue ? ' AND ' : ' WHERE ') . 'd.' . $key . ' LIKE ?';
                $values[] = $value . '%';
            }
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
                    '[%s] Removing device %s state for user %s and collection %s.',
                    $this->_procid,
                    $options['devId'],
                    $options['user'],
                    $options['id'])
                );
            } else {
                $this->_logger->info(sprintf(
                    '[%s] Removing device %s state for user %s.',
                    $this->_procid,
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
                $this->_procid,
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
            } elseif (!empty($user_query)) {
                $sql = 'SELECT t1.device_id FROM horde_activesync_device t1 '
                    . 'LEFT JOIN horde_activesync_device_users t2 '
                    . 'ON t1.device_id = t2.device_id WHERE t2.device_id IS NULL';
                try {
                    $devids = $this->_db->selectValues($sql);
                    foreach ($devids as $id) {
                        $this->_db->delete(
                            'DELETE FROM horde_activesync_device WHERE device_id = ?',
                            array($id));
                    }
                } catch (Horde_Db_Exception $e) {
                    throw new Horde_ActiveSync_Exception($e->getMessage());
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
     * @param array $fields  An array of fields to return. Default is to return
     *                       the full cache.  @since 2.9.0
     *
     * @return array  The current sync cache for the user/device combination.
     * @throws Horde_ActiveSync_Exception
     */
    public function getSyncCache($devid, $user, array $fields = null)
    {
        $sql = 'SELECT cache_data FROM ' . $this->_syncCacheTable
            . ' WHERE cache_devid = ? AND cache_user = ?';
        try {
            $data = $this->_db->selectValue($sql, array($devid, $user));
            $columns = $this->_db->columns($this->_syncCacheTable);
            $data = $columns['cache_data']->binaryToString($data);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
        if (!$data = unserialize($data)) {
            $data = array(
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
        }
        if (!is_null($fields)) {
            $data = array_intersect_key($data, array_flip($fields));
        }

        return $data;
    }

    /**
     * Save the provided sync_cache.
     *
     * @param array $cache   The cache to save.
     * @param string $devid  The device id.
     * @param string $user   The user id.
     * @param array $dirty   An array of dirty properties. @since 2.9.0
     *
     * @throws Horde_ActiveSync_Exception
     */
    public function saveSyncCache(array $cache, $devid, $user, array $dirty = null)
    {
        $cache['timestamp'] = strval($cache['timestamp']);
        $sql = 'SELECT count(*) FROM ' . $this->_syncCacheTable
            . ' WHERE cache_devid = ? AND cache_user = ?';
        try {
            $have = $this->_db->selectValue($sql, array($devid, $user));
        } catch (Horde_Db_Exception $e) {
            $this->_logger->err(sprintf(
                '[%s] %s',
                $this->_procid,
                $e->getMessage())
            );
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
                $this->_logger->err(sprintf(
                    '[%s] %s',
                    $this->_procid,
                    $e->getMessage())
                );
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
                $this->_logger->err(sprintf(
                    '[%s] %s',
                    $this->_procid,
                    $e->getMessage())
                );
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
            '[%s] Horde_ActiveSync_State_Sql::deleteSyncCache(%s, %s)',
            $this->_procid,
            $devid,
            $user));

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
     * Return an array of timestamps from the map table for the last
     * PIM-initiated change for the provided uid. Used to avoid mirroring back
     * changes to the PIM that it sent to the server.
     *
     * @param array $changes  The changes array, each entry a hash containing
     *                        'id' and 'type' keys.
     *
     * @return array  An array of UID -> timestamp of the last PIM-initiated
     *                change for the specified UIDs, or null if none found.
     */
    protected function _getPIMChangeTS(array $changes)
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

        $conditions = array();
        foreach ($changes as $change) {
            $d = $change['type'] == Horde_ActiveSync::CHANGE_TYPE_DELETE;
            $conditions[] = '(message_uid = ?' . ($d ? ' AND sync_deleted = ?) ' : ') ');
            $values[] = $change['id'];
            if ($d) {
                $values[] = $d;
            }
        }

        $sql .= 'AND (' . implode('OR ', $conditions) . ') GROUP BY message_uid';
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
        // No benefit from making this extra query for email collections.
        if ($this->_collection['class'] == Horde_ActiveSync::CLASS_EMAIL) {
            return true;
        }
        $sql = 'SELECT COUNT(*) FROM ' . $this->_syncMapTable
            . ' WHERE sync_devid = ? AND sync_user = ? AND sync_folderid = ?';
        try {
            return (bool)$this->_db->selectValue(
                $sql, array($this->_deviceInfo->id, $this->_deviceInfo->user, $this->_collection['serverid']));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Return all available mailMap changes for the current folder.
     *
     * @param  array  $changes  The changes array
     *
     * @return array  An array of hashes, each in the form of
     *   {uid} => array(
     *     Horde_ActiveSync::CHANGE_TYPE_FLAGS => true|false,
     *     Horde_ActiveSync::CHANGE_TYPE_DELETE => true|false
     *   )
     */
    protected function _getMailMapChanges(array $changes)
    {
        $sql = 'SELECT message_uid, sync_read, sync_flagged, sync_deleted,'
            . 'sync_changed FROM ' . $this->_syncMailMapTable
            . ' WHERE sync_folderid = ? AND sync_devid = ?'
            . ' AND sync_user = ? AND message_uid IN '
            . '(' . implode(',', array_fill(0, count($changes), '?')) . ')';

        $ids = array();
        foreach ($changes as $change) {
            $ids[] = $change['id'];
        }

        $values = array_merge(
            array($this->_collection['serverid'],
                  $this->_deviceInfo->id,
                  $this->_deviceInfo->user),
            $ids);

        try {
            $rows = $this->_db->selectAll($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
        $results = array();
        foreach ($rows as $row) {
            foreach ($changes as $change) {
                if ($change['id'] == $row['message_uid']) {
                    switch ($change['type']) {
                    case Horde_ActiveSync::CHANGE_TYPE_FLAGS:
                        $results[$row['message_uid']][$change['type']] =
                            (!is_null($row['sync_read']) && $row['sync_read'] == $change['flags']['read']) ||
                            (!is_null($row['sync_flagged'] && $row['sync_flagged'] == $change['flags']['flagged']));
                        continue 3;
                    case Horde_ActiveSync::CHANGE_TYPE_DELETE:
                        $results[$row['message_uid']][$change['type']] =
                            !is_null($row['sync_deleted']) && $row['sync_deleted'] == true;
                        continue 3;
                    case Horde_ActiveSync::CHANGE_TYPE_CHANGE:
                        $results[$row['message_uid']][$change['type']] =
                            !is_null($row['sync_changed']) && $row['sync_changed'] == true;
                        continue 3;
                    }
                }
            }
        }

        return $results;
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
        if (!preg_match('/^\{([0-9A-Za-z-]+)\}([0-9]+)$/', $syncKey, $matches)) {
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
            $this->_logger->err(sprintf(
                '[%s] %s',
                $this->_procid,
                $e->getMessage())
            );
            throw new Horde_ActiveSync_Exception($e);
        }
        $remove = array();
        $guids = array($guid);
        foreach ($results as $oldkey) {
            if (preg_match('/^\{([0-9A-Za-z-]+)\}([0-9]+)$/', $oldkey['sync_key'], $matches)) {
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
                $this->_logger->err(sprintf(
                    '[%s] %s',
                    $this->_procid,
                    $e->getMessage())
                );
                throw new Horde_ActiveSync_Exception($e);
            }
        }

        // Also clean up the map table since this data is only needed for one
        // SYNC cycle. Keep the same number of old keys for the same reasons as
        // above.
        foreach (array($this->_syncMapTable, $this->_syncMailMapTable) as $table) {
            $remove = array();
            $sql = 'SELECT DISTINCT sync_key FROM ' . $table
                . ' WHERE sync_devid = ? AND sync_user = ?';

            try {
                $maps = $this->_db->selectValues(
                    $sql,
                    array($this->_deviceInfo->id, $this->_deviceInfo->user)
                );
            } catch (Horde_Db_Exception $e) {
                $this->_logger->err(sprintf(
                    '[%s] %s',
                    $this->_procid,
                    $e->getMessage())
                );
                throw new Horde_ActiveSync_Exception($e);
            }
            foreach ($maps as $key) {
                if (preg_match('/^\{([0-9A-Za-z-]+)\}([0-9]+)$/', $key, $matches)) {
                    if ($matches[1] == $guid && $matches[2] < $n) {
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
                    $this->_logger->err(sprintf(
                        '[%s] %s',
                        $this->_procid,
                        $e->getMessage())
                    );
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
