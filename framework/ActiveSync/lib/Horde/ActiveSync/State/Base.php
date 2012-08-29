<?php
/**
 * Horde_ActiveSync_State_Base::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Base class for managing everything related to device state
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
abstract class Horde_ActiveSync_State_Base
{
    /**
     * Configuration parameters
     *
     * @var array
     */
    protected $_params;

    /**
     * Caches the current state(s) in memory
     *
     * @var Horde_ActiveSync_Folder_Base
     */
    protected $_folder;

    /**
     * The syncKey for the current request.
     *
     * @var string
     */
    protected $_syncKey;

    /**
     * The backend driver
     *
     * @param Horde_ActiveSync_Driver_Base
     */
    protected $_backend;

    /**
     * The collection array for the collection we are currently syncing.
     * Keys include:
     *   - class:       The collection class Contacts, Calendar etc...
     *   - synckey:     The current synckey
     *   - newsynckey:  The new synckey sent back to the PIM
     *   - id:          Server folder id
     *   - filtertype:  Filter
     *   - conflict:    Conflicts
     *   - truncation:  Truncation
     *
     * @var array
     */
    protected $_collection;

    /**
     * Logger instance
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Device info structure. Contains the following properties:
     *  'rwstatus'   - Device RemoteWipe status.
     *  'deviceType' - The device type.
     *  'userAgent'  - The device's userAgent string.
     *  'id'         - Device id.
     *  'user'       - The user associated with the current account.
     *  'supported'  - The SUPPORTED response for the device's collections.
     *  'policykey'  - The device's current POLICYKEY.
     *
     * @var StdClass
     */
    protected $_deviceInfo;

    /**
     * Local cache for changes to *send* to PIM
     * (Will remain null until getChanges() is called)
     *
     * @var array
     */
    protected $_changes;

    /**
     * The type of request we are handling.
     *
     * @var string
     */
    protected $_type;

    /**
     * Const'r
     *
     * @param array $collection  A collection array
     * @param array $params  All configuration parameters, requirements.
     *
     * @return Horde_ActiveSync_State_Base
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
        if (empty($params['logger'])) {
            $this->_logger = new Horde_Support_Stub();
        }
    }

    public function __destruct()
    {
        unset ($this->_backend);
    }

    /**
     * Update the $oldKey syncState to $newKey.
     *
     * @param string $newKey
     *
     * @return void
     */
    public function setNewSyncKey($newKey)
    {
        $this->_syncKey = $newKey;
    }

    /**
     * Get the current synckey
     *
     * @return string  The synkey we last retrieved state for
     */
    public function getCurrentSyncKey()
    {
        return $this->_syncKey;
    }

    /**
     * Generate a random 10 digit policy key
     *
     * @return unknown
     */
    public function generatePolicyKey()
    {
        return mt_rand(1000000000, 9999999999);
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
        /* See if we have it already */
        if (empty($this->_deviceInfo) || $this->_deviceInfo->id != $devId) {
            throw new Horde_ActiveSync_Exception('Device not loaded.');
        }

        return $this->_deviceInfo->policykey;
    }

    /**
     * Return a device wipe status
     *
     * @param string $devId
     *
     * @return integer
     */
    public function getDeviceRWStatus($devId)
    {
        /* See if we have it already */
        if (empty($this->_deviceInfo) || $this->_deviceInfo->id != $devId) {
            throw new Horde_ActiveSync_Exception('Device not loaded.');
        }

        return $this->_deviceInfo->rwstatus;
    }

    /**
     * Set the backend driver
     * (should really only be called by a backend object when passing this
     * object to client code)
     *
     * @param Horde_ActiveSync_Driver_Base $backend  The backend driver
     *
     * @return void
     */
    public function setBackend(Horde_ActiveSync_Driver_Base $backend)
    {
        $this->_backend = $backend;
    }

    /**
     * Set the logger instance for this object.
     *
     * @param Horde_Log_Logger $logger
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;
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
     * Gets the new sync key for a specified sync key. You must save the new
     * sync state under this sync key when done sync'ing by calling
     * setNewSyncKey(), then save().
     *
     * @param string $syncKey  The old syncKey
     *
     * @return string  The new synckey
     * @throws Horde_ActiveSync_Exception
     */
    static public function getNewSyncKey($syncKey)
    {
        if (empty($syncKey)) {
            return '{' . new Horde_Support_Uuid() . '}' . '1';
        } else {
            if (preg_match('/^s{0,1}\{([a-fA-F0-9-]+)\}([0-9]+)$/', $syncKey, $matches)) {
                $n = $matches[2];
                $n++;

                return '{' . $matches[1] . '}' . $n;
            }
            throw new Horde_ActiveSync_Exception('Invalid SyncKey format passed to getNewSyncKey()');
        }
    }

    /**
     * Return the counter for the specified syncKey.
     *
     * @return mixed integer|boolean
     */
    static public function getSyncKeyCounter($syncKey)
    {
       if (preg_match('/^s{0,1}\{([a-fA-F0-9-]+)\}([0-9]+)$/', $syncKey, $matches)) {
            $n = $matches[2];
            return $n;
        }

        return false;
    }

   /**
    * Returns the timestamp of the earliest modification time to consider
    *
    * @param integer $restrict  The time period to restrict to
    *
    * @return integer
    */
    static protected function _getCutOffDate($restrict)
    {
        switch($restrict) {
        case Horde_ActiveSync::FILTERTYPE_1DAY:
            $back = 86400;
            break;
        case Horde_ActiveSync::FILTERTYPE_3DAYS:
            $back = 259200;
            break;
        case Horde_ActiveSync::FILTERTYPE_1WEEK:
            $back = 604800;
            break;
        case Horde_ActiveSync::FILTERTYPE_2WEEKS:
            $back = 1209600;
            break;
        case Horde_ActiveSync::FILTERTYPE_1MONTH:
            $back = 2419200;
            break;
        case Horde_ActiveSync::FILTERTYPE_3MONTHS:
            $back = 7257600;
            break;
        case Horde_ActiveSync::FILTERTYPE_6MONTHS:
            $back = 14515200;
            break;
        default:
            break;
        }

        if (isset($back))
        {
            $date = time() - $back;
            return $date;
        } else {
            return 0; // unlimited
        }
    }

    /**
     * Helper function that performs the actual diff between PIM state and
     * server state FOLDERSYNC arrays.
     *
     * @param array $old  The PIM state
     * @param array $new  The current server state
     *
     * @return unknown_type
     */
    protected function _getDiff($old, $new)
    {
        $changes = array();

        // Sort both arrays in the same way by ID
        usort($old, array(__CLASS__, 'RowCmp'));
        usort($new, array(__CLASS__, 'RowCmp'));

        $inew = 0;
        $iold = 0;

        // Get changes by comparing our list of folders with
        // our previous state
        while (1) {
            $change = array();
            if ($iold >= count($old) || $inew >= count($new)) {
                break;
            }
            // If ids are the same, but mod is different, a folder was
            // renamed on the client, but the server keeps it's id.
            if ($old[$iold]['id'] == $new[$inew]['id']) {
                // Both folders are still available compare mod
                if ($old[$iold]['mod'] != $new[$inew]['mod']) {
                    $change['type'] = Horde_ActiveSync::CHANGE_TYPE_CHANGE;
                    //$change['mod'] = $new[$inew]['mod'];
                    $change['id'] = $new[$inew]['id'];
                    $changes[] = $change;
                }
                $inew++;
                $iold++;
            } else {
                if ($old[$iold]['id'] > $new[$inew]['id']) {
                    // Messesge in device state has disappeared
                    $change['type'] = Horde_ActiveSync::CHANGE_TYPE_DELETE;
                    $change['id'] = $old[$iold]['id'];
                    $changes[] = $change;
                    $iold++;
                } else {
                    // Message in $new is new
                    $change['type'] = Horde_ActiveSync::CHANGE_TYPE_CHANGE;
                    $change['id'] = $new[$inew]['id'];
                    $changes[] = $change;
                    $inew++;
                }
            }
        }
        while ($iold < count($old)) {
            // All data left in _syncstate have been deleted
            $change['type'] = Horde_ActiveSync::CHANGE_TYPE_DELETE;
            $change['id'] = $old[$iold]['id'];
            $changes[] = $change;
            $iold++;
        }

        // New folders added on server.
        while ($inew < count($new)) {
            // All data left in new have been added
            $change['type'] = Horde_ActiveSync::CHANGE_TYPE_CHANGE;
            $change['flags'] = Horde_ActiveSync::FLAG_NEWMESSAGE;
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

    /**
     * Loads the state from storage for the specified syncKey and intializes the
     * object for use.
     *
     * @param array $collection  The collection array, if loading a collection.
     * @param string $syncKey    The key for the state to load.
     * @param string $type       Treat the loaded state data as this type of state.
     * @param string $id         The collection id this represents
     *
     * @return array The state array
     */
    abstract public function loadState(array $collection, $syncKey, $type = null, $id = null);

    /**
     * Get the list of known folders for the specified syncState
     *
     * @return array  An array of server folder ids
     */
    abstract public function getKnownFolders();

    /**
     * Save the current syncstate to storage
     */
    abstract public function save();

    /**
     * Update the state to reflect changes
     *
     * @param string $type      The type of change (change, delete, flags or
     *                          foldersync)
     * @param array $change     A stat/change hash describing the change.
     *  Contains:
     *    - id:      The message uid the change applies to
     *    - parent:  The parent of the message, normally the folder id.
     *    - flags:   If this is a flag change, the state of the read flag.
     *    - mod:     The modtime of this change for collections that use it.
     *
     * @param integer $origin   Flag to indicate the origin of the change:
     *    Horde_ActiveSync::CHANGE_ORIGIN_NA  - Not applicapble/not important
     *    Horde_ActiveSync::CHANGE_ORIGIN_PIM - Change originated from PIM
     *
     * @param string $user      The current sync user, only needed if change
     *                          origin is CHANGE_ORIGIN_PIM
     * @param string $clientid  PIM clientid sent when adding a new message
     */
    abstract public function updateState(
        $type, array $change, $origin = Horde_ActiveSync::CHANGE_ORIGIN_NA,
        $user = null, $clientid = '');

    /**
     * Get all items that have changed since the last sync time
     *
     * @param array $options  An options array:
     *      - ping:  (boolean)  Only detect if there is a change, do not build
     *                          any messages.
     *               DEFAULT: false (Build full change array).
     *
     * @return array
     */
    abstract public function getChanges(array $options = array());

    /**
     * Determines if the server version of the message represented by $stat
     * conflicts with the PIM version of the message according to the current
     * state.
     *
     * @param array $stat   A message stat array
     * @param string $type  The type of change (change, delete, add)
     *
     * @return boolean
     */
    abstract public function isConflict($stat, $type);

    /**
     * Save a new device policy key to storage.
     *
     * @param string $devId  The device id
     * @param integer $key   The new policy key
     */
    abstract public function setPolicyKey($devId, $key);

    /**
     * Reset ALL device policy keys. Used when server policies have changed
     * and you want to force ALL devices to pick up the changes. This will
     * cause all devices that support provisioning to be reprovisioned.
     *
     * @throws Horde_ActiveSync_Exception
     */
    abstract public function resetAllPolicyKeys();

    /**
     * Set a new remotewipe status for the device
     *
     * @param string $devid    The device id.
     * @param string $status   A Horde_ActiveSync::RWSTATUS_* constant.
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    abstract public function setDeviceRWStatus($devid, $status);

    /**
     * Obtain the device object.
     *
     * @param object $device
     * @param string $user
     *
     * @return StdClass
     */
    abstract public function loadDeviceInfo($device, $user = null);

    /**
     * Check that a given device id is known to the server. This is regardless
     * of Provisioning status.
     *
     * @param string $devId  The device id to check
     * @param string $user   The device should be owned by this user.
     *
     * @return boolean
     */
    abstract public function deviceExists($devId, $user = null);

    /**
     * Set new device info
     *
     * @param object $device  The device information
     *
     * @return boolean
     */
    abstract public function setDeviceInfo($data);

    /**
     * Set the device's properties as sent by a SETTINGS request.
     *
     * @param array $data       The device settings
     * @param string $deviceId  The device id.
     *
     * @throws Horde_ActiveSync_Exception
     */
    abstract public function setDeviceProperties(array $data, $deviceId);

    /**
     * Explicitly remove a state from storage.
     *
     * @param array $options  An options array containing:
     *   - synckey: (string)  Remove only the state associated with this synckey.
     *   - devId: (string)  Remove all information for this device.
     *   - user: (string)  When removing device info, restrict to removing data
     *                    for this user only.
     *   - id: (string)  When removing device state, restrict ro removing data
     *                   only for this collection.
     *
     * @throws Horde_ActiveSyncException
     */
    abstract public function removeState(array $options);

    /**
     * List all devices that we know about.
     *
     * @return array  An array of device hashes
     * @throws Horde_ActiveSync_Exception
     */
    abstract public function listDevices();

    /**
     * Get the last time the currently loaded device issued a SYNC request.
     *
     * @return integer  The timestamp of the last sync, regardless of collection
     * @throws Horde_ActiveSync_Exception
     */
    abstract public function getLastSyncTimestamp();

    /**
     * Return a sync cache for 12.1 SYNC requests.
     *
     * @param string $devid  The device id.
     * @param string $user   The user id.
     *
     * @return array  The current sync cache for the user/device combination.
     */
    abstract public function getSyncCache($devid, $user);

    /**
     * Save the provided sync_cache.
     *
     * @param array $cache   The cache to save.
     * @param string $devid  The device id.
     * @param string $user   The userid.
     *
     * @throws Horde_ActiveSync_Exception
     */
    abstract public function saveSyncCache(array $cache, $devid, $user);

    /**
     * Delete a complete sync cache
     *
     * @param string $devid  The device id
     * @param string $user   The user name.
     *
     * @throws Horde_ActiveSync_Exception
     */
    abstract public function deleteSyncCache($devid, $user);

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
     abstract public function isDuplicatePIMAddition($id);

}