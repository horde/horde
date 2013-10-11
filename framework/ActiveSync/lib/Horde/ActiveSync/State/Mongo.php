<?php
/**
 * Horde_ActiveSync_State_Mongo::
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
 * NoSQL based state management.
 *
 * Collections used:
 *  - state:   Holds sync state documents.
 *
 *  - device: Holds device and device_user info.
 *      - _id:
 *      - device_type:       The device's device_type.
 *      - device_agent:      The client's user agent string.
 *      - device_rwstatus:   The current RW status.
 *      - device_supported:  An array of SUPPORTED properties.
 *      - device_properties: An array of device properties.
 *      - device_users:      An array of each user with a known account on device
 *                           with each entry containing:
 *         - device_user:
 *         - device_policykey:
 *
 *  - map: Holds the incoming change (non-mail) map.
 *      - message_uid:    The message's server uid.
 *      - sync_modtime:   The modtime.
 *      - sync_key:       The sync_key in effect when the change was imported.
 *      - sync_devid:     The device_id sending the change.
 *      - sync_folderid:  The folderid of the collection the change belongs to.
 *      - sync_user:      The username.
 *      - sync_clientid:  The client's clientid of incoming new items.
 *      - sync_deleted:   Flag to indicate change was a deletion.
 *
 *  - mailmap: Holds the incoming mail change map.
 *      - message_uid:    The message's UID.
 *      - sync_key:       The sync_key in effect when the change was imported.
 *      - sync_devid:     The device_id sending the change.
 *      - sync_folderid:  The folderid of the collection the change belongs to.
 *      - sync_user:      The username.
 *      - sync_read:      Flag to indicate change is a change in the /seen flag.
 *      - sync_flagged:   Flag to indicate change is a change to the flagged status.
 *      - sync_deleted:   Flag to indicate change is a message deletion.
 *
 *  - cache:   Holds the sync cache.
 *      - cache_user:
 *      - cache_devid:
 *      - cache_data: An object containing:
 *          - confirmed_synckeys: Array to hold synckeys for confirmation.
 *          - lasthbsyncstarted:  Timestamp of the start of last heartbeat sync.
 *          - lastsyncendnormal:  Timestamp of the last successfully ended sync.
 *          - timestamp:          Timestamp of cache.
 *          - wait:               Current wait interval.
 *          - hbinterval:         Current heartbeat interval.
 *          - folders:            Array of known folders.
 *          - hierarchy:          Current hierarchy key.
 *          - collections:
 *          - pingheartbeat:
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2010-2013 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=ActiveSync
 * @package   ActiveSync
 */
class Horde_ActiveSync_State_Mongo extends Horde_ActiveSync_State_Base implements Horde_Mongo_Collection_Index
{
    /**
     * Mongo connection
     *
     * @var MongoClient
     */
    protected $_mongo;

    /**
     * Mongo database
     *
     * @var MongoDB
     */
    protected $_db;

    /**
     * Mongo Indexes
     *
     * @var array
     */
    protected $_indexes = array(
        'device' => array(
            'index_id_user' => array(
                '_id' => 1,
                'users.device_user' => 1
            )
        ),
        'state' => array(
            'index_devid_folderid' => array(
                'sync_devid' => 1,
                'sync_folderid' => 1
            )
        ),
        'map' => array(
            'index_folder_dev_uid_user' => array(
                'sync_devid' => 1,
                'sync_user' => 1,
                'sync_folderid' => 1,
                'message_uid' => 1
            ),
            'index_dev_user_uid_key' => array(
                'sync_devid' => 1,
                'sync_user' => 1,
                'message_uid' => 1,
                'sync_key' => 1,
                'sync_deleted' => 1,
            ),
            'index_client_user_dev' => array(
                'sync_clientid' => 1,
                'sync_user' => 1,
                'sync_devid' => 1
            )
        ),
        'mailmap' => array(
            'index_folder_dev_uid_user' => array(
                'sync_devid' => 1,
                'sync_user' => 1,
                'sync_folderid' => 1,
                'message_uid' => 1
            )
        ),
        'cache' => array(
            'index_dev_user' => array(
                'cache_devid' => 1,
                'cache_user' => 1
            )
        )
    );
    /**
     * Const'r
     *
     * @param array  $params   Must contain:
     *      - connection:  (Horde_Mongo_Client  The Horde_Db instance.
     *
     * @return Horde_ActiveSync_State_Sql
     */
    public function __construct(array $params = array())
    {
        parent::__construct($params);
        if (empty($this->_params['connection']) || !($this->_params['connection'] instanceof MongoClient)) {
            throw new InvalidArgumentException('Missing or invalid connection parameter.');
        }

        $this->_mongo = $params['connection'];
        $this->_db = $this->_mongo->selectDb(null);
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

        $query = array(
            'sync_devid' => $this->_deviceInfo->id,
            'sync_user' => $this->_deviceInfo->user,
            'sync_folderid' => $uid
        );

        try {
            $cursor = $this->_db->state->find($query);
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }

        foreach ($cursor as $folder) {
            $folder = unserialize($folder);
            $folder->setServerId($serverid);
            $folder = serialize($folder);
            try {
                $this->_db->update(
                    $query,
                    array('$set' => array('sync_data' => $folder)),
                    array('multiple' => true)
                );
            } catch (Exception $e) {
                $this->_logger->err($e->getMessage());
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
            $results = $this->_db->state->findOne(
                array('_id' => $this->_syncKey),
                array('sync_data', 'sync_devid', 'sync_mod', 'sync_pending'));
        } catch (Exception $e) {
            $this->_logger->err('Error in loading state from DB: ' . $e->getMessage());
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
        $data = unserialize($results['sync_data']);
        $pending = $results['sync_pending'];

        if ($type == Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC) {
            $this->_folder = ($data !== false) ? $data : array();
            $this->_logger->info(
                sprintf('[%s] Loading FOLDERSYNC state containing %d folders.',
                $this->_procid,
                count($this->_folder)));
        } elseif ($type == Horde_ActiveSync::REQUEST_TYPE_SYNC) {
            $this->_folder = $data;
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
        // Prepare state and pending data
        if ($this->_type == Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC) {
            $data = (isset($this->_folder) ? serialize($this->_folder) : '');
            $pending = '';
        } elseif ($this->_type == Horde_ActiveSync::REQUEST_TYPE_SYNC) {
            $pending = (isset($this->_changes) ? array_values($this->_changes) : '');
            $data = (isset($this->_folder) ? serialize($this->_folder) : '');
        } else {
            $pending = '';
            $data = '';
        }

        // If we are setting the first synckey iteration, do not save the
        // timestamp, otherwise we will never get the initial set of data.
        $document = array(
            '_id' => $this->_syncKey,
            'sync_key' => $this->_syncKey,
            'sync_data' => $data,
            'sync_devid' => $this->_deviceInfo->id,
            'sync_mod' => (self::getSyncKeyCounter($this->_syncKey) == 1 ? 0 : $this->_thisSyncStamp),
            'sync_folderid' => (!empty($this->_collection['id']) ? $this->_collection['id'] : Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC),
            'sync_user' => $this->_deviceInfo->user,
            'sync_pending' => $pending,
            'sync_timestamp' => time());

        $this->_logger->info(
            sprintf('[%s] Saving state for sync_key %s: %s',
                $this->_procid,
                $this->_syncKey,
                serialize($document)));
        try {
            $this->_db->state->insert($document);
        } catch (Exception $e) {
            // Might exist already if the last sync attempt failed.
            $this->_logger->notice(
                sprintf('[%s] Error saving state for synckey %s: %s - removing previous sync state and trying again.',
                        $this->_procid,
                        $this->_syncKey,
                        $e->getMessage()));
            try {
                $this->_db->state->remove(array('_id' => $this->_syncKey));
                $this->_db->state->insert($document);
            } catch (Exception $e) {
                throw new Horde_ActiveSync_Exception('Error saving state.');
            }
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

            // This is an incoming change from the PIM, store it so we
            // don't mirror it back to device.
            switch ($this->_collection['class']) {
            case Horde_ActiveSync::CLASS_EMAIL:
                if ($type == Horde_ActiveSync::CHANGE_TYPE_CHANGE &&
                    isset($change['flags']) && is_array($change['flags']) &&
                    !empty($change['flags'])) {
                    $type = Horde_ActiveSync::CHANGE_TYPE_FLAGS;
                }
                $document = array(
                    'message_uid' => $change['id'],
                    'sync_key' => (empty($this->_syncKey) ? 0 : $this->_syncKey),
                    'sync_devid' => $this->_deviceInfo->id,
                    'sync_folderid' => $this->_collection['id'],
                    'sync_user' => $user
                );
                if ($type == Horde_ActiveSync::CHANGE_TYPE_FLAGS) {
                    if (isset($change['flags']['read'])) {
                        $document['sync_read'] = !empty($change['flags']['read']);
                    } else {
                        $document['sync_flagged'] = $flag_value = !empty($change['flags']['flagged']);
                    }
                } else {
                    $document['sync_deleted'] = true;
                }
                try {
                    $this->_db->mailmap->insert($document);
                } catch (Exception $e) {
                    throw Horde_ActiveSync_Exception($e);
                }
                break;

            default:
                $document = array(
                   'message_uid' => $change['id'],
                   'sync_modtime' => $change['mod'],
                   'sync_key' => empty($this->_syncKey) ? 0 : $this->_syncKey,
                   'sync_devid' => $this->_deviceInfo->id,
                   'sync_folderid' => $change['serverid'],
                   'sync_user' => $user,
                   'sync_clientid' => $clientid,
                   'sync_deleted' => $type == Horde_ActiveSync::CHANGE_TYPE_DELETE
                );
            }

            try {
                $this->_db->map->insert($document);
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

        $query = array('_id' => $devId);
        if (!empty($user)) {
            $query['users.device_user'] = $user;
        }

        try {
            $device = $this->_db->device->findOne($query);
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }
        if (empty($device)) {
            throw new Horde_ActiveSync_Exception('Device not found.');
        }

        $this->_deviceInfo = new Horde_ActiveSync_Device($this);
        $this->_deviceInfo->rwstatus = $device['device_rwstatus'];
        $this->_deviceInfo->deviceType = $device['device_type'];
        $this->_deviceInfo->userAgent = $device['device_agent'];
        $this->_deviceInfo->id = $devId;
        $this->_deviceInfo->user = $user;
        $this->_deviceInfo->supported = $device['device_supported'];
        $this->_deviceInfo->policykey = 0;
        foreach ($device['users'] as $user_entry) {
            if ($user_entry['device_user'] == $user) {
                $this->_deviceInfo->policykey = $user_entry['device_policykey'];
                break;
            }
        }
        $this->_deviceInfo->properties = $device['device_properties'];

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
        $user_data = array(
            'device_user' => $data->user,
            'device_policykey' => $data->policykey
        );
        $device = array(
            'device_type' => $data->deviceType,
            'device_agent' => !empty($data->userAgent) ? $data->userAgent : '',
            'device_rwstatus' => $data->rwstatus,
            'device_supported' => empty($data->supported) ? $data->supported : array(),
            'device_properties' => $data->properties,
        );
        try {
            $this->_db->device->update(
                array('_id' => $data->id),
                array('$set' => $device),
                array('upsert' => true)
            );
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }

        try {
            $this->_db->device->update(
                array('_id' => $data->id),
                array('$addToSet' => array('users' => $user_data))
            );
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }

        $this->_deviceInfo = $data;
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
        Horde::debug($data);
        $query = array('_id' => $deviceId);
        $update = array(
            '$set' => array(
                'device_properties' => $data,
                'device_agent' => !empty($data[Horde_ActiveSync_Request_Settings::SETTINGS_USERAGENT]) ? $data[Horde_ActiveSync_Request_Settings::SETTINGS_USERAGENT] : ''
            )
        );
        try {
            $this->_db->device->update($query, $update);
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
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
        $query = array('_id' => $devId);
        if (!empty($user)) {
            $query['users.device_user'] = $user;
        }

        try {
            return $this->_db->device->find($query)->limit(1)->count();
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
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
        $query = array();
        if (!empty($user)) {
            $query['users.device_user'] = $user;
        }
        try {
            $cursor = $this->_db->device->find($query);
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }
        $results = array();
        foreach ($cursor as $item) {
            if (!empty($item['users'])) {
                foreach ($item['users'] as $user) {
                    $device = array_merge($item, $user);
                    unset($device['users']);
                    $results[] = $device;
                }
            } else {
                $results[] = $item;
            }
        }
        return $results;
    }

    /**
     * Reset ALL device policy keys. Used when server policies have changed
     * and you want to force ALL devices to pick up the changes. This will
     * cause all devices that support provisioning to be reprovisioned.
     *
     * @throws Horde_ActiveSync_Exception
     */
    public function resetAllPolicyKeys()
    {
        // Holy cr*p. Can't believe this can't be done in MongoDB, but
        // we can't update a field in all subdocuments?! This can be
        // a very expensive operation in MongoDB with lots of devices.
        // See https://jira.mongodb.org/browse/SERVER-1243
        // try {
        //     $this->_db->device->update(
        //         array(),
        //         array('$set' => array('users.device_policykey' => 0)),
        //         array('multiple' => true)
        //     );
        // } catch (Exception $e) {
        //     $this->_logger->err($e->getMessage());
        //     throw new Horde_ActiveSync_Exception($e);
        // }
        $cursor = $this->_db->device->find();
        foreach ($cursor as $row) {
            foreach ($row['users'] as $user) {
                $this->_db->device->update(
                    array('users.device_user' => $user['device_user']),
                    array('$set' => array('users.$.device_policykey' => 0)),
                    array('multiple' => true)
                );
            }
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
        $query = array('_id' => $devId);
        $new_data = array('device_rwstatus' => $status);
        if ($status == Horde_ActiveSync::RWSTATUS_PENDING) {
            $new_data['users.device_policykey'] = 0;
        }
        $update = array('$set' => $new_data);
        try {
            $this->_db->device->update($query, $update);
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
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

        $query = array(
            'sync_devid' => $this->_deviceInfo->id,
            'sync_folderid' => $id,
            'sync_user' => $this->_deviceInfo->user
        );

        try {
            $this->_db->state->remove($query);
            $this->_db->map->remove($query);
            $this->_db->mailmap->remove($query);
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
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

        $match = array('sync_devid' => $id);
        if (!empty($user)) {
            $match['sync_user'] = $user;
        }

        try {
            $results = $this->_db->state->aggregate(
                array('$match' => $match),
                array('$group' => array('_id' => '$sync_dev', 'max' => array('$max' => '$sync_timestamp')))
            );
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }

        if (empty($results) || empty($results['ok'])) {
            throw new Horde_ActiveSync_Exception('Error running aggregation.');
        }
        $results = current($results);
        return $results['max'];
    }

    /**
     * Save a new device policy key to storage for the current user.
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
        $this->_logger->debug(sprintf(
            'Setting policykey: %s, %s, %s',
            $devId, $this->_backend->getUser(), $key));
        $query = array('_id' => $devId, 'users.device_user' => $this->_backend->getUser());
        $update = array('$set' => array('users.$.device_policykey' => "$key"));
        try {
            $this->_db->device->update($query, $update);
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
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
        // If the device is flagged as wiped, and we are removing the state,
        // we MUST NOT restrict to user since it will not remove the device's
        // device table entry, and the device will continue to be wiped each
        // time it connects.
        if (!empty($options['devId']) && !empty($options['user'])) {
            $query = array(
                '_id' => $options['devId'],
                '$or' => array(array('device_rwstatus' => Horde_ActiveSync::RWSTATUS_PENDING), array('device_rwstatus' => Horde_ActiveSync::RWSTATUS_WIPED))
            );
            try {
                $results = $this->_db->device->findOne($query);
            } catch (Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_ActiveSync_Exception($e);
            }
            if (!empty($results)) {
                unset($options['user']);
                return $this->removeState($options);
            }

            // Query for state and map tables.
            $query = array(
                'sync_devid' => $options['devId'],
                'sync_user' => $options['user']
            );
            if (!empty($options['id'])) {
                $query['sync_folderid'] = $options['id'];
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

            // Remove device data for user
            try {
                $this->_db->device->update(
                    array('_id' => $options['devId'], 'users.device_user' => $options['user']),
                    array('$pull' => array('users' => array('device_user' => $options['user'])))
                );
            } catch (Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_ActiveSync_Exception($e);
            }
        } elseif (!empty($options['devId'])) {
            // Query for state and map tables.
            $query = array('sync_devid' => $options['devId']);
            $this->_logger->info(sprintf(
                '[%s] Removing all device state for device %s.',
                $options['devId'],
                $options['devId'])
            );
            $this->deleteSyncCache($options['devId']);

            // Remove device data.
            try {
                $this->_db->device->remove(array('_id' => $options['devId']));
            } catch (Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_ActiveSync_Exception($e);
            }
        } elseif (!empty($options['user'])) {
            // Query for state and map tables.
            $query = array('sync_user' => $options['user']);
            $this->_logger->info(sprintf(
                '[%s] Removing all device state for user %s.',
                $this->_procid,
                $options['user'])
            );
            $this->deleteSyncCache(null, $options['user']);

            // Delete all user's device info.
            try {
                $this->_db->device->update(
                    array('users.device_user'),
                    array('$pull' => array('users' => array('device_user' => $options['user'])))
                );
            } catch (Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_ActiveSync_Exception($e);
            }
        } elseif (!empty($options['synckey'])) {
            $query = array('sync_key' => $options['synckey']);
            $this->_logger->info(sprintf(
                '[%s] Removing device state for sync_key %s only.',
                $this->_procid,
                $options['synckey'])
            );
        } else {
            return;
        }

        // Do the state/map deletions and GC the device collection.
        try {
            $this->_db->state->remove($query);
            $this->_db->map->remove($query);
            $this->_db->mailmap->remove($query);
            $this->_db->device->remove(array('users' => array('$size' => 0)));
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
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
        $query = array(
            'sync_clientid' => $id,
            'sync_user' => $this->_deviceInfo->user,
            'sync_devid' => $this->_deviceInfo->id
        );

        try {
            $result = $this->_db->map->findOne(
                $query,
                array('message_uid')
            );
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }

        if (empty($result)) {
            return null;
        }

        return $result['message_uid'];
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
        $query = array(
            'cache_devid' => $devid,
            'cache_user' => $user
        );
        try {
            $data = $this->_db->cache->findOne(
                $query,
                array('cache_data')
            );
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }

        if (empty($data) || empty($data['cache_data'])) {
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
                'pingheartbeat' => false);
        } else {
            return $data['cache_data'];
        }
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
    public function saveSyncCache(array $cache, $devid, $user, array $dirty = array())
    {
        $cache['timestamp'] = strval($cache['timestamp']);
        $this->_logger->info(
            sprintf('[%s] Saving SYNC_CACHE entry fields %s for user %s and device %s.',
                $this->_procid, serialize($dirty), $user, $devid));

        $query = array(
            'cache_devid' => $devid,
            'cache_user' => $user
        );

        $update = array();
        foreach ($dirty as $property => $value) {
            if ($property == 'collections' && is_array($value)) {
                foreach (array_keys($value) as $collection) {
                    $update['cache_data.collections.' . $collection] = $cache['collections'][$collection];
                }
            } else {
                $update['cache_data.' . $property] = $cache[$property];
            }
        }

        try {
            $this->_db->cache->update(
                $query,
                array('$set' => $update),
                array('upsert' => true)
            );
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
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
            'Horde_ActiveSync_State_Mongo::deleteSyncCache(%s, %s)',
            $devid, $user));

        $params = array();
        if (!empty($devid)) {
            $params['cache_devid'] = $devid;
        }
        if (!empty($user)) {
            $params['cache_user'] = $user;
        }

        try {
            $this->_db->cache->remove($params);
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
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
    protected function _getPIMChangeTS($uid, $type)
    {
        // // Get the allowed synckeys to include.
        $uuid = self::getSyncKeyUid($this->_syncKey);
        $cnt = self::getSyncKeyCounter($this->_syncKey);
        $keys = array();
        foreach (array($uuid . $cnt, $uuid . ($cnt - 1)) as $v) {
            $keys[] = $v;
        }
        $match = array(
            'sync_devid' => $this->_deviceInfo->id,
            'sync_user' => $this->_deviceInfo->user,
            'message_uid' => $uid,
            'sync_key' => array('$in' => $keys)
        );

        if ($type == Horde_ActiveSync::CHANGE_TYPE_DELETE) {
            $match['sync_deleted'] = true;
        }
        try {
            $results = $this->_db->map->aggregate(
                array('$match' => $match),
                array('$group' => array('_id' => '$message_uid', 'max' => array('$max' => '$sync_modtime')))
            );
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }
        if (empty($results) || empty($results['ok'])) {
            throw new Horde_ActiveSync_Exception('Error running aggregation.');
        }
        $result = current($results);
        return $result['max'];
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
        $c = $this->_collection['class'] == Horde_ActiveSync::CLASS_EMAIL ?
            $this->_db->mailmap :
            $this->_db->map;
        $query = array(
            'sync_devid' => $this->_deviceInfo->id,
            'sync_user' => $this->_deviceInfo->user,
            'sync_folderid' => $this->_collection['id']
        );
        try {
            return (bool)$c->find($query)->count();
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
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
     * @param integer  $flag   The flag value.
     * @param string $field  The field containing the change type.
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    protected function _isPIMChangeQuery($id, $flag, $field)
    {
        $query = array(
            'sync_devid' => $this->_deviceInfo->id,
            'sync_user' => $this->_deviceInfo->user,
            'sync_folderid' => $this->_collection['id'],
            'message_uid' => "$id",
        );

        try {
            $result = $this->_db->mailmap->findOne(
                $query,
                array($field)
            );
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }

        if (empty($result)) {
            return false;
        }

        return $result[$field] == $flag;
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
        $n = $matches[2] - 1;

        // Clean up all but the last 2 syncs for any given sync series, this
        // ensures that we can still respond to SYNC requests for the previous
        // key if the PIM never received the new key in a SYNC response.
        $js = <<<EOT
        function() {
            var p = /^\{([0-9A-Za-z-]+)\}([0-9]+)$/;
            var results = p.exec(this.sync_key);
            if (results && (results[1] == "$guid") && (results[2] < $n)) {
                return true;
            } else if (!results) {
                return true;
            }

            return false;
        }
EOT;
        $query = array(
            'sync_devid' => $this->_deviceInfo->id,
            'sync_folderid' => !empty($this->_collection['id'])
                ? $this->_collection['id']
                : Horde_ActiveSync::CHANGE_TYPE_FOLDERSYNC,
            '$where' => $js
        );

        try {
            $this->_db->state->remove($query);
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }

        // Also clean up the map table since this data is only needed for one
        // SYNC cycle. Keep the same number of old keys for the same reasons as
        // above.
        $js = <<<EOT
        function() {
            var p = /^\{([0-9A-Za-z-]+)\}([0-9]+)$/;
            var results = p.exec(this.sync_key);
            if (results && (results[1] == "$guid") && (results[2] < $n)) {
                return true;
            }

            return false;
        }
EOT;
        foreach (array($this->_db->map, $this->_db->mailmap) as $c) {
            $query = array(
                'sync_devid' => $this->_deviceInfo->id,
                'sync_user' => $this->_deviceInfo->user,
                '$where' => $js
            );
            try {
                $c->remove($query);
            } catch (Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_ActiveSync_Exception($e);
            }
        }
    }

    /* Horde_Mongo_Collection_Index methods. */

    /**
     */
    public function checkMongoIndices()
    {
        foreach ($this->_indexes as $collection => $indices) {
            if (!$this->_mongo->checkIndices($collection, $indices)) {
                return false;
            }
        }
        return true;
    }

    /**
     */
    public function createMongoIndices()
    {
        foreach ($this->_indexes as $collection => $indices) {
            $this->_mongo->createIndices($collection, $indices);
        }
    }

}
