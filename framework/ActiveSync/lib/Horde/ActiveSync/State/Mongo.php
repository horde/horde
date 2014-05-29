<?php
/**
 * Horde_ActiveSync_State_Mongo::
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
 * NoSQL based state management.
 *
 * Collections used:
 *  - HAS_state:   Holds sync state documents.
 *
 *  - HAS_device: Holds device and device_user info.
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
 *  - HAS_map: Holds the incoming change (non-mail) map.
 *      - message_uid:    The message's server uid.
 *      - sync_modtime:   The modtime.
 *      - sync_key:       The sync_key in effect when the change was imported.
 *      - sync_devid:     The device_id sending the change.
 *      - sync_folderid:  The folderid of the collection the change belongs to.
 *      - sync_user:      The username.
 *      - sync_clientid:  The client's clientid of incoming new items.
 *      - sync_deleted:   Flag to indicate change was a deletion.
 *
 *  - HAS_mailmap: Holds the incoming mail change map.
 *      - message_uid:    The message's UID.
 *      - sync_key:       The sync_key in effect when the change was imported.
 *      - sync_devid:     The device_id sending the change.
 *      - sync_folderid:  The folderid of the collection the change belongs to.
 *      - sync_user:      The username.
 *      - sync_read:      Flag to indicate change is a change in the /seen flag.
 *      - sync_flagged:   Flag to indicate change is a change to the flagged status.
 *      - sync_deleted:   Flag to indicate change is a message deletion.
 *
 *  - HAS_cache:   Holds the sync cache.
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
 * @copyright 2010-2014 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=ActiveSync
 * @package   ActiveSync
 */
class Horde_ActiveSync_State_Mongo extends Horde_ActiveSync_State_Base implements Horde_Mongo_Collection_Index
{

    /** Collection names **/
    const COLLECTION_CACHE   = 'HAS_cache';
    const COLLECTION_MAILMAP = 'HAS_mailmap';
    const COLLECTION_MAP     = 'HAS_map';
    const COLLECTION_DEVICE  = 'HAS_device';
    const COLLECTION_STATE   = 'HAS_state';

    /** Field names **/
    const MONGO_ID               = '_id';
    const CACHE_USER             = 'cache_user';
    const CACHE_DEVID            = 'cache_devid';
    const CACHE_DATA             = 'cache_data';
    const MESSAGE_UID            = 'message_uid';
    const SYNC_KEY               = 'sync_key';
    const SYNC_DEVID             = 'sync_devid';
    const SYNC_FOLDERID          = 'sync_folderid';
    const SYNC_USER              = 'sync_user';
    const SYNC_READ              = 'sync_read';
    const SYNC_FLAGGED           = 'sync_flagged';
    const SYNC_DELETED           = 'sync_deleted';
    const SYNC_CHANGED           = 'sync_changed';
    const SYNC_MODTIME           = 'sync_modtime';
    const SYNC_CLIENTID          = 'sync_clientid';
    const SYNC_DATA              = 'sync_data';
    const SYNC_MOD               = 'sync_mod';
    const SYNC_PENDING           = 'sync_pending';
    const SYNC_TIMESTAMP         = 'sync_timestamp';
    const DEVICE_ID              = 'device_id';
    const DEVICE_TYPE            = 'device_type';
    const DEVICE_AGENT           = 'device_agent';
    const DEVICE_RWSTATUS        = 'device_rwstatus';
    const DEVICE_SUPPORTED       = 'device_supported';
    const DEVICE_PROPERTIES      = 'device_properties';
    const DEVICE_USERS           = 'device_users';
    const DEVICE_USER            = 'device_user';
    const DEVICE_USERS_USER      = 'users.device_user';
    const DEVICE_USERS_POLICYKEY = 'users.device_policykey';
    const DEVICE_POLICYKEY       = 'device_policykey';

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
        self::COLLECTION_DEVICE => array(
            'index_id_user' => array(
                self::MONGO_ID => 1,
                self::DEVICE_USERS_USER => 1
            )
        ),
        self::COLLECTION_STATE => array(
            'index_devid_folderid' => array(
                self::SYNC_DEVID => 1,
                self::SYNC_FOLDERID => 1
            )
        ),
        self::COLLECTION_MAP => array(
            'index_folder_dev_uid_user' => array(
                self::SYNC_DEVID => 1,
                self::SYNC_USER => 1,
                self::SYNC_FOLDERID => 1,
                self::MESSAGE_UID => 1
            ),
            'index_dev_user_uid_key' => array(
                self::SYNC_DEVID => 1,
                self::SYNC_USER => 1,
                self::MESSAGE_UID => 1,
                self::SYNC_KEY => 1,
                self::SYNC_DELETED => 1,
            ),
            'index_client_user_dev' => array(
                self::SYNC_CLIENTID => 1,
                self::SYNC_USER => 1,
                self::SYNC_DEVID => 1
            )
        ),
        self::COLLECTION_MAILMAP => array(
            'index_folder_dev_uid_user' => array(
                self::SYNC_DEVID => 1,
                self::SYNC_USER => 1,
                self::SYNC_FOLDERID => 1,
                self::MESSAGE_UID => 1
            )
        ),
        self::COLLECTION_CACHE => array(
            'index_dev_user' => array(
                self::CACHE_DEVID => 1,
                self::CACHE_USER => 1
            )
        )
    );

    protected $_propertyMap = array(
        'deviceType' => self::DEVICE_TYPE,
        'userAgent'  => self::DEVICE_AGENT,
        'rwstatus'   => self::DEVICE_RWSTATUS,
        'supported'  => self::DEVICE_SUPPORTED,
        'properties' => self::DEVICE_PROPERTIES,
        'id'         => self::DEVICE_ID
    );

    /**
     * Const'r
     *
     * @param array  $params   Must contain:
     *      - connection:  (Horde_Mongo_Client  The Horde_Mongo instance.
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
     *
     * @throws  Horde_ActiveSync_Exception
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
            self::SYNC_DEVID => $this->_deviceInfo->id,
            self::SYNC_USER => $this->_deviceInfo->user,
            self::SYNC_FOLDERID => $uid
        );

        try {
            $cursor = $this->_db->selectCollection(self::COLLECTION_STATE)
                ->find($query, array(self::SYNC_DATA));
        } catch (Exception $e) {
                $this->_logger->err(sprintf(
                    '[%s] %s',
                    $this->_procid,
                    $e->getMessage())
                );
            throw new Horde_ActiveSync_Exception($e);
        }

        foreach ($cursor as $folder) {
            $folder = unserialize($folder[self::SYNC_DATA]);
            $folder->setServerId($serverid);
            $folder = serialize($folder);
            try {
                $this->_db->selectCollection(self::COLLECTION_STATE)->update(
                    $query,
                    array('$set' => array(self::SYNC_DATA => $folder)),
                    array('multiple' => true)
                );
            } catch (Exception $e) {
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
     * @param string $type  The type of state a
     *                      Horde_ActiveSync::REQUEST_TYPE constant.
     *
     * @throws Horde_ActiveSync_Exception, Horde_ActiveSync_Exception_StateGone
     */
    protected function _loadState($type)
    {
        try {
            $results = $this->_db->selectCollection(self::COLLECTION_STATE)->findOne(
                array(self::MONGO_ID => $this->_syncKey),
                array(self::SYNC_DATA, self::SYNC_DEVID, self::SYNC_MOD, self::SYNC_PENDING));
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
    protected function _loadStateFromResults(array $results, $type = Horde_ActiveSync::REQUEST_TYPE_SYNC)
    {
        // Load the last known sync time for this collection
        $this->_lastSyncStamp = !empty($results[self::SYNC_MOD])
            ? $results[self::SYNC_MOD]
            : 0;

        // Pre-Populate the current sync timestamp in case this is only a
        // Client -> Server sync.
        $this->_thisSyncStamp = $this->_lastSyncStamp;

        // Restore any state or pending changes
        $data = unserialize($results[self::SYNC_DATA]);
        $pending = $results[self::SYNC_PENDING];

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
            self::MONGO_ID => $this->_syncKey,
            self::SYNC_KEY => $this->_syncKey,
            self::SYNC_DATA => $data,
            self::SYNC_DEVID => $this->_deviceInfo->id,
            self::SYNC_MOD => (self::getSyncKeyCounter($this->_syncKey) == 1 ? 0 : $this->_thisSyncStamp),
            self::SYNC_FOLDERID => (!empty($this->_collection['id']) ? $this->_collection['id'] : Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC),
            self::SYNC_USER => $this->_deviceInfo->user,
            self::SYNC_PENDING => $pending,
            self::SYNC_TIMESTAMP => time()
        );
        $this->_logger->info(
            sprintf('[%s] Saving state for sync_key %s: %s',
                $this->_procid,
                $this->_syncKey,
                serialize($document))
        );

        try {
            $this->_db->selectCollection(self::COLLECTION_STATE)->insert($document);
        } catch (Exception $e) {
            // Might exist already if the last sync attempt failed.
            $this->_logger->notice(
                sprintf('[%s] Previous request processing for synckey %s failed to be accepted by the client, removing previous state and trying again.',
                        $this->_procid,
                        $this->_syncKey)
                );
            try {
                $this->_db->selectCollection(self::COLLECTION_STATE)->remove(array(self::MONGO_ID => $this->_syncKey));
                $this->_db->selectCollection(self::COLLECTION_STATE)->insert($document);
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
     * @param string $clientid  PIM clientid sent when adding a new message.
     *
     * @throws  Horde_ActiveSync_Exception
     */
    public function updateState(
        $type, array $change, $origin = Horde_ActiveSync::CHANGE_ORIGIN_NA,
        $user = null, $clientid = '')
    {
        $this->_logger->info(sprintf(
            '[%s] Horde_ActiveSync_State_Mongo::updateState(%s, %s, %d, %s, %s)',
            $this->_procid, $type, serialize($change), $origin, $user, $clientid));

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
                $document = array(
                    self::MESSAGE_UID => (string)$change['id'],
                    self::SYNC_KEY => $syncKey,
                    self::SYNC_DEVID => $this->_deviceInfo->id,
                    self::SYNC_FOLDERID => $change['serverid'],
                    self::SYNC_USER => $user
                );
                switch ($type) {
                case Horde_ActiveSync::CHANGE_TYPE_FLAGS:
                    if (isset($change['flags']['read'])) {
                        $document[self::SYNC_READ] = !empty($change['flags']['read']);
                    } else {
                        $document[self::SYNC_FLAGGED] = $flag_value = !empty($change['flags']['flagged']);
                    }
                    break;
                case Horde_ActiveSync::CHANGE_TYPE_DELETE:
                    $document[self::SYNC_DELETED] = true;
                    break;
                case Horde_ActiveSync::CHANGE_TYPE_CHANGE:
                    $document[self::SYNC_CHANGED] = true;
                    break;
                }
                try {
                    $this->_db->selectCollection(self::COLLECTION_MAILMAP)->insert($document);
                } catch (Exception $e) {
                    throw Horde_ActiveSync_Exception($e);
                }
                break;

            default:
                $document = array(
                   self::MESSAGE_UID => $change['id'],
                   self::SYNC_MODTIME => $change['mod'],
                   self::SYNC_KEY => $syncKey,
                   self::SYNC_DEVID => $this->_deviceInfo->id,
                   self::SYNC_FOLDERID => $change['serverid'],
                   self::SYNC_USER => $user,
                   self::SYNC_CLIENTID => $clientid,
                   self::SYNC_DELETED => $type == Horde_ActiveSync::CHANGE_TYPE_DELETE
                );

                try {
                    $this->_db->selectCollection(self::COLLECTION_MAP)->insert($document);
                } catch (Exception $e) {
                    throw new Horde_ActiveSync_Exception($e);
                }
                break;
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

        $query = array(self::MONGO_ID => $devId);
        if (!empty($user)) {
            $query[self::DEVICE_USERS_USER] = $user;
        }

        try {
            $device_data = $this->_db->selectCollection(self::COLLECTION_DEVICE)->findOne($query);
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }
        if (empty($device_data)) {
            throw new Horde_ActiveSync_Exception('Device not found.');
        }
        $map = array_flip($this->_propertyMap);
        $device = array();
        foreach ($device_data as $field => $data) {
            if (!empty($map[$field])) {
                $device[$map[$field]] = $data;
            }
        }
        $device['id'] = $devId;
        $device['user'] = $user;
        foreach ($device_data['users'] as $user_entry) {
            if ($user_entry[self::DEVICE_USER] == $user) {
                $device['policykey'] = $user_entry[self::DEVICE_POLICYKEY];
                break;
            }
        }
        $this->_deviceInfo = new Horde_ActiveSync_Device($this, $device);

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
        if (count($dirty)) {
            $device = array();
            foreach (array_keys($dirty) as $property) {
                if (!empty($this->_propertyMap[$property])) {
                    $device[$this->_propertyMap[$property]] = $data->$property;
                }
            }
            $this->_logger->info(sprintf(
                '[%s] setDeviceInfo saving properties: %s',
                $this->_procid, serialize($dirty))
            );
            if (count($device)) {
                try {
                    $this->_db->selectCollection(self::COLLECTION_DEVICE)->update(
                        array(self::MONGO_ID => $data->id),
                        array('$set' => $device),
                        array('upsert' => true)
                    );
                } catch (Exception $e) {
                    $this->_logger->err($e->getMessage());
                    throw new Horde_ActiveSync_Exception($e);
                }
            }

            if (!empty($dirty['user']) || !empty($dirty['policykey'])) {
                $user_data = array(
                    self::DEVICE_USER => $data->user,
                    self::DEVICE_POLICYKEY => (string)$data->policykey
                );

                try {
                    $this->_db->selectCollection(self::COLLECTION_DEVICE)->update(
                        array(self::MONGO_ID => $data->id),
                        array('$pull' => array('users' => array(self::DEVICE_USER => $data->user)))
                    );
                    $this->_db->selectCollection(self::COLLECTION_DEVICE)->update(
                        array(self::MONGO_ID => $data->id),
                        array('$addToSet' => array('users' => $user_data))
                    );
                } catch (Exception $e) {
                    $this->_logger->err($e->getMessage());
                    throw new Horde_ActiveSync_Exception($e);
                }
            }

            $this->_deviceInfo = $data;
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
        $query = array(self::MONGO_ID => $deviceId);
        $update = array(
            '$set' => array(
                self::DEVICE_PROPERTIES => $data
            )
        );
        try {
            $this->_db->selectCollection(self::COLLECTION_DEVICE)->update($query, $update, array('upsert' => true));
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
        $query = array(self::MONGO_ID => $devId);
        if (!empty($user)) {
            $query[self::DEVICE_USERS_USER] = $user;
        }

        try {
            return $this->_db->selectCollection(self::COLLECTION_DEVICE)->find($query)->limit(1)->count();
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
     * @param array $filter An array of optional filters where the keys are
     *                      field names and the values are values to
     *                      prefix-match.
     *
     * @return array  An array of device hashes
     * @throws Horde_ActiveSync_Exception
     */
    public function listDevices($user = null, $filter = array())
    {
        $query = array();
        if (!empty($user)) {
            $query[self::DEVICE_USERS_USER] = $user;
        }
        $explicit_fields = array(self::DEVICE_ID, self::DEVICE_TYPE, self::DEVICE_AGENT, self::DEVICE_USER);
        foreach ($filter as $key => $value) {
            if (in_array($key, $explicit_fields)) {
                $query[$key] = new MongoRegex("/^$value*/");
            } else {
                $query['device_properties.' . $key] = new MongoRegex("/^$value*/");
            }
        }
        try {
            $cursor = $this->_db->selectCollection(self::COLLECTION_DEVICE)->find($query);
        } catch (Exception $e) {
            $this->_logger->err($e->getMessage());
            throw new Horde_ActiveSync_Exception($e);
        }
        $results = array();
        foreach ($cursor as $item) {
            if (!empty($item['users'])) {
                foreach ($item['users'] as $user) {
                    $device = $item;
                    $device = array_merge ($device, $user);
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
        //     $this->_db->selectCollection(self::COLLECTION_DEVICE)->update(
        //         array(),
        //         array('$set' => array(self::DEVICE_USERS_POLICYKEY => 0)),
        //         array('multiple' => true)
        //     );
        // } catch (Exception $e) {
        //     $this->_logger->err($e->getMessage());
        //     throw new Horde_ActiveSync_Exception($e);
        // }
        $cursor = $this->_db->selectCollection(self::COLLECTION_DEVICE)->find(array(), array('users'));
        foreach ($cursor as $row) {
            foreach ($row['users'] as $user) {
                $this->_db->selectCollection(self::COLLECTION_DEVICE)->update(
                    array(self::DEVICE_USERS_USER => $user[self::DEVICE_USER]),
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
        $query = array(self::MONGO_ID => $devId);
        $new_data = array(self::DEVICE_RWSTATUS => $status);
        if ($status == Horde_ActiveSync::RWSTATUS_PENDING) {
            $new_data[self::DEVICE_USERS_POLICYKEY] = 0;
        }
        $update = array('$set' => $new_data);
        try {
            $this->_db->selectCollection(self::COLLECTION_DEVICE)->update($query, $update);
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
            self::SYNC_DEVID => $this->_deviceInfo->id,
            self::SYNC_FOLDERID => $id,
            self::SYNC_USER => $this->_deviceInfo->user
        );

        try {
            $this->_db->selectCollection(self::COLLECTION_STATE)->remove($query);
            $this->_db->selectCollection(self::COLLECTION_MAP)->remove($query);
            $this->_db->selectCollection(self::COLLECTION_MAILMAP)->remove($query);
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

        $match = array(self::SYNC_DEVID => $id);
        if (!empty($user)) {
            $match[self::SYNC_USER] = $user;
        }

        try {
            $results = $this->_db->selectCollection(self::COLLECTION_STATE)->aggregate(
                array('$match' => $match),
                array('$group' => array(self::MONGO_ID => '$sync_dev', 'max' => array('$max' => '$sync_timestamp')))
            );
        } catch (Exception $e) {
            $this->_logger->err(sprintf(
                '[%s] %s',
                $this->_procid,
                $e->getMessage())
            );
            throw new Horde_ActiveSync_Exception($e);
        }

        if (empty($results) || empty($results['ok'])) {
            throw new Horde_ActiveSync_Exception(empty($results['errmsg']) ? 'Error running aggregation.' : $results['errmsg']);
        }
        if (empty($results) || empty($results['ok'])) {
            return 0;
        }
        $results = current($results['result']);
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
            $this->_logger->err(sprintf(
                '[%s] Device not loaded',
                $this->_procid)
            );
            throw new Horde_ActiveSync_Exception('Device not loaded');
        }
        $this->_logger->info(sprintf(
            '[%s] Setting policykey: %s, %s, %s',
            $this->_procid, $devId, $this->_backend->getUser(), $key));
        $this->_deviceInfo->policykey = $key;
        $this->_deviceInfo->save();
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
                self::MONGO_ID => $options['devId'],
                '$or' => array(array(self::DEVICE_RWSTATUS => Horde_ActiveSync::RWSTATUS_PENDING), array(self::DEVICE_RWSTATUS => Horde_ActiveSync::RWSTATUS_WIPED))
            );
            try {
                $results = $this->_db->selectCollection(self::COLLECTION_DEVICE)->findOne($query, array(self::MONGO_ID));
            } catch (Exception $e) {
                $this->_logger->err(sprintf(
                    '[%s] %s',
                    $this->_procid,
                    $e->getMessage())
                );
                throw new Horde_ActiveSync_Exception($e);
            }
            if (!empty($results)) {
                unset($options['user']);
                return $this->removeState($options);
            }

            // Query for state and map tables.
            $query = array(
                self::SYNC_DEVID => $options['devId'],
                self::SYNC_USER => $options['user']
            );
            if (!empty($options['id'])) {
                $query[self::SYNC_FOLDERID] = $options['id'];
                $this->_logger->info(sprintf(
                    '[%s] Removing device state for user %s and collection %s.',
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

            // Remove device data for user
            try {
                $this->_db->selectCollection(self::COLLECTION_DEVICE)->update(
                    array(self::MONGO_ID => $options['devId'], self::DEVICE_USERS_USER => $options['user']),
                    array('$pull' => array('users' => array(self::DEVICE_USER=> $options['user'])))
                );
            } catch (Exception $e) {
                $this->_logger->err(sprintf(
                    '[%s] %s',
                    $this->_procid,
                    $e->getMessage())
                );
                throw new Horde_ActiveSync_Exception($e);
            }
        } elseif (!empty($options['devId'])) {
            // Query for state and map tables.
            $query = array(self::SYNC_DEVID => $options['devId']);
            $this->_logger->info(sprintf(
                '[%s] Removing all device state for device %s.',
                $this->_procid,
                $options['devId'])
            );
            $this->deleteSyncCache($options['devId']);

            // Remove device data.
            try {
                $this->_db->selectCollection(self::COLLECTION_DEVICE)->remove(array(self::MONGO_ID => $options['devId']));
            } catch (Exception $e) {
                $this->_logger->err(sprintf(
                    '[%s] %s',
                    $this->_procid,
                    $e->getMessage())
                );
                throw new Horde_ActiveSync_Exception($e);
            }
        } elseif (!empty($options['user'])) {
            // Query for state and map tables.
            $query = array(self::SYNC_USER => $options['user']);
            $this->_logger->info(sprintf(
                '[%s] Removing all device state for user %s.',
                $this->_procid,
                $options['user'])
            );
            $this->deleteSyncCache(null, $options['user']);

            // Delete all user's device info.
            try {
                $this->_db->selectCollection(self::COLLECTION_DEVICE)->update(
                    array(self::DEVICE_USERS_USER),
                    array('$pull' => array('users' => array(self::DEVICE_USER => $options['user'])))
                );
            } catch (Exception $e) {
                $this->_logger->err($e->getMessage());
                throw new Horde_ActiveSync_Exception($e);
            }
        } elseif (!empty($options['synckey'])) {
            $query = array(self::SYNC_KEY => $options['synckey']);
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
            $this->_db->selectCollection(self::COLLECTION_STATE)->remove($query);
            $this->_db->selectCollection(self::COLLECTION_MAP)->remove($query);
            $this->_db->selectCollection(self::COLLECTION_MAILMAP)->remove($query);
            $this->_db->selectCollection(self::COLLECTION_DEVICE)->remove(array('users' => array('$size' => 0)));
        } catch (Exception $e) {
                $this->_logger->err(sprintf(
                    '[%s] %s',
                    $this->_procid,
                    $e->getMessage())
                );
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
            self::SYNC_CLIENTID => $id,
            self::SYNC_USER => $this->_deviceInfo->user,
            self::SYNC_DEVID => $this->_deviceInfo->id
        );

        try {
            $result = $this->_db->selectCollection(self::COLLECTION_MAP)->findOne(
                $query,
                array(self::MESSAGE_UID)
            );
        } catch (Exception $e) {
                $this->_logger->err(sprintf(
                    '[%s] %s',
                    $this->_procid,
                    $e->getMessage())
                );
            throw new Horde_ActiveSync_Exception($e);
        }

        if (empty($result)) {
            return null;
        }

        return $result[self::MESSAGE_UID];
     }

    /**
     * Return the sync cache.
     *
     * @param string $devid  The device id.
     * @param string $user   The user id.
     * @param array $fields  An array of fields to return. Default is to return
     *                       the full cache. @since 2.9.0
     *
     * @return array  The current sync cache for the user/device combination.
     * @throws Horde_ActiveSync_Exception
     */
    public function getSyncCache($devid, $user, array $fields = null)
    {
        $this->_logger->info(sprintf(
            '[%s] Loading SyncCache from storage: %s',
            $this->_procid,
            serialize($fields)));

        $query = array(
            self::CACHE_DEVID => $devid,
            self::CACHE_USER => $user
        );
        $projection = array();
        if (!is_null($fields)) {
            foreach ($fields as $field) {
                $projection[] = 'cache_data.' . $field;
            }
        } else {
            $projection = array(self::CACHE_DATA);
        }
        try {
            $data = $this->_db->selectCollection(self::COLLECTION_CACHE)->findOne(
                $query,
                $projection
            );
        } catch (Exception $e) {
                $this->_logger->err(sprintf(
                    '[%s] %s',
                    $this->_procid,
                    $e->getMessage())
                );
            throw new Horde_ActiveSync_Exception($e);
        }

        if (is_null($fields) && (empty($data) || empty($data[self::CACHE_DATA]))) {
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
            return $data[self::CACHE_DATA];
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
        $this->_logger->info(
            sprintf('[%s] Saving SYNC_CACHE entry fields %s for user %s and device %s.',
                $this->_procid, serialize($dirty), $user, $devid));

        $cache['timestamp'] = strval($cache['timestamp']);
        $query = array(
            self::CACHE_DEVID => $devid,
            self::CACHE_USER => $user
        );
        $update = array();

        // Ensure the initial object is written for the collection data.
        if (empty($cache['collections'])) {
            $cache['collections'] = new stdClass();
            $update['cache.data.collections'] = $cache['collections'];
        }

        foreach ($dirty as $property => $value) {
            if ($property == 'collections' && is_array($value) && !empty($cache['collections'])) {
                foreach (array_keys($value) as $collection) {
                    $update['cache_data.collections.' . $collection] = $cache['collections'][$collection];
                }
            } else {
                $update['cache_data.' . $property] = $cache[$property];
            }
        }

        try {
            $this->_db->selectCollection(self::COLLECTION_CACHE)->update(
                $query,
                array('$set' => $update),
                array('upsert' => true)
            );
        } catch (Exception $e) {
                $this->_logger->err(sprintf(
                    '[%s] %s',
                    $this->_procid,
                    $e->getMessage())
                );
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
            $params[self::CACHE_DEVID] = $devid;
        }
        if (!empty($user)) {
            $params[self::CACHE_USER] = $user;
        }

        try {
            $this->_db->selectCollection(self::COLLECTION_CACHE)->remove($params);
        } catch (Exception $e) {
                $this->_logger->err(sprintf(
                    '[%s] %s',
                    $this->_procid,
                    $e->getMessage())
                );
            throw new Horde_ActiveSync_Exception($e);
        }
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
    protected function _getPIMChangeTS(array $changes)
    {
        // Get the allowed synckeys to include.
        $uuid = self::getSyncKeyUid($this->_syncKey);
        $cnt = self::getSyncKeyCounter($this->_syncKey);
        $keys = array();
        foreach (array($uuid . $cnt, $uuid . ($cnt - 1)) as $v) {
            $keys[] = $v;
        }
        $match = array(
            self::SYNC_DEVID => $this->_deviceInfo->id,
            self::SYNC_USER => $this->_deviceInfo->user,
            self::SYNC_KEY => array('$in' => $keys)
        );
        $uids = array();
        $match['$or'] = array();
        foreach ($changes as $change) {
            $match['$or'][] = array(
                '$and' => array(
                    array(self::MESSAGE_UID => $change['id']),
                    array(self::SYNC_DELETED => $change['type'] == Horde_ActiveSync::CHANGE_TYPE_DELETE)
                )
            );
        }
        try {
            $rows = $this->_db->selectCollection(self::COLLECTION_MAP)->aggregate(
                array('$match' => $match),
                array('$group' => array(self::MONGO_ID => '$message_uid', 'max' => array('$max' => '$sync_modtime')))

            );
        } catch (Exception $e) {
                $this->_logger->err(sprintf(
                    '[%s] %s',
                    $this->_procid,
                    $e->getMessage())
                );
            throw new Horde_ActiveSync_Exception($e);
        }
        if (empty($rows) || empty($rows['ok'])) {
            throw new Horde_ActiveSync_Exception(sprintf(
                'Error running aggregation: %s',
                empty($rows['errmsg']) ? '' : $rows['errmsg']));
        }
        $results = array();
        foreach ($rows['result'] as $row) {
            $results[$row[self::MONGO_ID]] = $row['max'];
        }

        return $results;
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
        if ($this->_collection['class'] == Horde_ActiveSync::CLASS_EMAIL) {
            return true;
        }

        $this->_logger->info(sprintf(
            '[%s] Horde_ActiveSync_State_Mongo::_havePIMChanges() for %s',
            $this->_procid, $this->_collection['serverid']));

        $c = $this->_db->selectCollection(self::COLLECTION_MAP);
        $query = array(
            self::SYNC_DEVID => $this->_deviceInfo->id,
            self::SYNC_USER => $this->_deviceInfo->user,
            self::SYNC_FOLDERID => $this->_collection['serverid']
        );
        try {
            return (bool)$c->find($query, array(self::MONGO_ID))->count();
        } catch (Exception $e) {
                $this->_logger->err(sprintf(
                    '[%s] %s',
                    $this->_procid,
                    $e->getMessage())
                );
            throw new Horde_ActiveSync_Exception($e);
        }
    }

    /**
     * Return all available mailMap changes for the current folder.
     *
     * @param  array  $changes  The chagnes array
     *
     * @return array  An array of hashes, each in the form of
     *   {uid} => array(
     *     Horde_ActiveSync::CHANGE_TYPE_FLAGS => true|false,
     *     Horde_ActiveSync::CHANGE_TYPE_DELETE => true|false
     *   )
     */
    protected function _getMailMapChanges(array $changes)
    {
        $ids = array();
        foreach ($changes as $change) {
            $ids[] = strval($change['id']);
        }
        $query = array(
            self::SYNC_FOLDERID => $this->_collection['serverid'],
            self::SYNC_DEVID => $this->_deviceInfo->id,
            self::SYNC_USER => $this->_deviceInfo->user,
            self::MESSAGE_UID => array('$in' => $ids)
        );
        Horde::debug($query);
        $rows = $this->_db->selectCollection(self::COLLECTION_MAILMAP)->find(
            $query,
            array(self::MESSAGE_UID, self::SYNC_READ, self::SYNC_FLAGGED, self::SYNC_DELETED, self::SYNC_CHANGED)
        );
Horde::debug($rows);
        $results = array();
        foreach ($rows as $row) {
            foreach ($changes as $change) {
                if ($change['id'] == $row[self::MESSAGE_UID]) {
                    switch ($change['type']) {
                    case Horde_ActiveSync::CHANGE_TYPE_FLAGS:
                        $results[$row[self::MESSAGE_UID]][$change['type']] =
                            (!is_null($row[self::SYNC_READ]) && $row[self::SYNC_READ] == $change['flags']['read']) ||
                            (!is_null($row[self::SYNC_FLAGGED] && $row[self::SYNC_FLAGGED] == $change['flags']['flagged']));
                        continue 3;
                    case Horde_ActiveSync::CHANGE_TYPE_DELETE:
                        $results[$row[self::MESSAGE_UID]][$change['type']] =
                            !is_null($row[self::SYNC_DELETED]) && $row[self::SYNC_DELETED] == true;
                        continue 3;
                    case Horde_ActiveSync::CHANGE_TYPE_CHANGE:
                        $results[$row[self::MESSAGE_UID]][$change['type']] =
                            !is_null($row[self::SYNC_CHANGED]) && $row[self::SYNC_CHANGED] == true;
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
            self::SYNC_DEVID => $this->_deviceInfo->id,
            self::SYNC_FOLDERID => !empty($this->_collection['id'])
                ? $this->_collection['id']
                : Horde_ActiveSync::CHANGE_TYPE_FOLDERSYNC,
            '$where' => $js
        );

        try {
            $this->_db->selectCollection(self::COLLECTION_STATE)->remove($query);
        } catch (Exception $e) {
                $this->_logger->err(sprintf(
                    '[%s] %s',
                    $this->_procid,
                    $e->getMessage())
                );
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
        foreach (array($this->_db->selectCollection(self::COLLECTION_MAP), $this->_db->selectCollection(self::COLLECTION_MAILMAP)) as $c) {
            $query = array(
                self::SYNC_DEVID => $this->_deviceInfo->id,
                self::SYNC_USER => $this->_deviceInfo->user,
                '$where' => $js
            );
            try {
                $c->remove($query);
            } catch (Exception $e) {
                $this->_logger->err(sprintf(
                    '[%s] %s',
                    $this->_procid,
                    $e->getMessage())
                );
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
