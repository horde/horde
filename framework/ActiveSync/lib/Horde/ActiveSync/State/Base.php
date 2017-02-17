<?php
/**
 * Horde_ActiveSync_State_Base::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2017 Horde LLC (http://www.horde.org)
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
 * @copyright 2009-2017 Horde LLC (http://www.horde.org)
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
     * @var mixed Horde_ActiveSync_Folder_Base if request is not a FOLDERSYNC
     *            otherwise an array containing all FOLDERSYNC state.
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
     * The process id (used for logging).
     *
     * @var integer
     */
    protected $_procid;

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
     * The collection array for the collection we are currently syncing.
     * Keys include:
     *   - class:       The collection class Contacts, Calendar etc...
     *   - synckey:     The current synckey
     *   - newsynckey:  The new synckey sent back to the client
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
     * Device object.
     *
     * @var Horde_ActiveSync_Device
     */
    protected $_deviceInfo;

    /**
     * Local cache for changes to *send* to client.
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
     * A map of backend folderids to UIDs
     *
     * @var array
     */
    protected $_folderUidMap;

    /**
     * Const'r
     *
     * @param array $params  All configuration parameters.
     */
    public function __construct(array $params = array())
    {
        $this->_params = $params;
        if (empty($params['logger'])) {
            $this->_logger =  new Horde_ActiveSync_Log_Logger(new Horde_Log_Handler_Null());
        } else {
            $this->_logger = Horde_ActiveSync::_wrapLogger($params['logger']);
        }
        $this->_procid = getmypid();
    }

    /**
     * Update the $oldKey syncState to $newKey.
     *
     * @param string $newKey
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
     * @return integer
     */
    public function generatePolicyKey()
    {
            return mt_rand(1000000000, mt_getrandmax());
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
     * @param boolean $refresh  If true, reload the device's rwstatus flag.
     *        @since  2.31.0
     *
     * @return integer
     */
    public function getDeviceRWStatus($devId, $refresh = false)
    {
        /* See if we have it already */
        if (empty($this->_deviceInfo) || $this->_deviceInfo->id != $devId) {
            throw new Horde_ActiveSync_Exception('Device not loaded.');
        }

        /* Should we refresh? */
        if ($refresh) {
            $this->loadDeviceInfo(
                $this->_deviceInfo->id, $this->_deviceInfo->user, array('force' => true)
            );
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
        $this->_logger = Horde_ActiveSync::_wrapLogger($logger);
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
     * Determines if the server version of the message represented by $stat
     * conflicts with the client version of the message.  For this driver, this is
     * true whenever $lastSyncTime is older then $stat['mod']. Method is only
     * called from the Importer during an import of a non-new change from the
     * client.
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
     * Return the mapping of folder uids to backend folderids.
     *
     * @return array  An array of backend folderids -> uids.
     * @since 2.9.0
     */
    public function getFolderUidToBackendIdMap()
    {
        if (!isset($this->_folderUidMap)) {
            $this->_folderUidMap = array();
            $cache = $this->getSyncCache(
                $this->_deviceInfo->id,
                $this->_deviceInfo->user,
                array('folders'));
            foreach ($cache['folders'] as $id => $folder) {
                $this->_folderUidMap[$folder['serverid']] = $id;
            }
        }

        return $this->_folderUidMap;
    }

    /**
     * Get a EAS Folder Uid for the given backend server id.
     *
     * @param string $serverid  The backend server id. E.g., 'INBOX'.
     *
     * @return string|boolean  The EAS UID for the requested serverid, or false
     *                         if it is not found.
     * @since 2.4.0
     * @deprecated  Use self::getFolderUidToBackendIdMap() instead.
     */
    public function getFolderUidForBackendId($serverid)
    {
        $cache = $this->getSyncCache(
            $this->_deviceInfo->id,
            $this->_deviceInfo->user);

        $folders = $cache['folders'];
        foreach ($folders as $id => $folder) {
            if ($folder['serverid'] == $serverid) {
                $this->_logger->meta(sprintf(
                    'STORAGE: Found serverid for %s: %s',
                    $serverid, $id)
                );
                return $id;
            }
        }

        $this->_logger->meta(sprintf(
            'STORAGE: No folderid found for %s',
            $serverid)
        );

        return false;
    }

    /**
     * Get all items that have changed since the last sync time
     *
     * @param array $options  An options array:
     *      - ping: (boolean)  Only detect if there is a change, do not build
     *                         any messages.
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
            // How far back to sync for those collections that use this.
            $cutoffdate = self::_getCutOffDate(!empty($this->_collection['filtertype'])
                ? $this->_collection['filtertype']
                : 0);

            $this->_logger->meta(sprintf(
                'STATE: Initializing message diff engine for %s (%s)',
                $this->_collection['id'],
                $this->_folder->serverid())
            );

            // Check for previously found changes first.
            if (!empty($this->_changes)) {
                $this->_logger->meta('STATE: Returning previously found changes.');
                return $this->_changes;
            }

            // Get the current syncStamp from the backend.
            $this->_thisSyncStamp = $this->_backend->getSyncStamp(
                $this->_folder->serverid(),
                $this->_lastSyncStamp
            );

            if ($this->_thisSyncStamp === false) {
                throw new Horde_ActiveSync_Exception_StaleState(
                    'Detecting a change in timestamp or modification sequence. Reseting state.'
                );
            }

            $this->_logger->meta(sprintf(
                'STATE: Using SYNCSTAMP %s for %s.',
                $this->_thisSyncStamp,
                $this->_collection['id'])
            );

            // No existing changes, poll the backend
            $changes = $this->_backend->getServerChanges(
                $this->_folder,
                (int)$this->_lastSyncStamp,
                (int)$this->_thisSyncStamp,
                $cutoffdate,
                !empty($options['ping']),
                $this->_folder->haveInitialSync,
                !empty($options['maxitems']) ? $options['maxitems'] : 100,
                !empty($this->_collection['forcerefresh'])
            );

            // Only update the folderstate if we are not PINGing.
            if (empty($options['ping'])) {
                $this->_folder->updateState();
            }

            $this->_logger->meta(sprintf(
                'STATE: Found %d message changes in %s.',
                count($changes),
                $this->_collection['id'])
            );

            // Check for mirrored client changes.
            $this->_changes = array();
            if (count($changes) && $this->_havePIMChanges()) {
                $this->_logger->meta('STATE: Checking for client initiated changes.');
                switch ($this->_collection['class']) {
                case Horde_ActiveSync::CLASS_EMAIL:
                    // @todo Fix me with a changes object that transparently
                    // deals with different data structure for initial sync.
                    if (!empty($changes) && !is_array($changes[0])) {
                        $this->_changes = $changes;
                        break;
                    }

                    // Map of client-sourced changes
                    $mailmap = $this->_getMailMapChanges($changes);

                    // Map constants to more human readable/loggable text.
                    $flag_map = array(
                        Horde_ActiveSync::CHANGE_TYPE_FLAGS =>  'flag change',
                        Horde_ActiveSync::CHANGE_TYPE_DELETE => 'deletion',
                        Horde_ActiveSync::CHANGE_TYPE_CHANGE => 'move',
                        Horde_ActiveSync::CHANGE_TYPE_DRAFT => 'draft'
                    );

                    $cnt = count($changes);
                    for ($i = 0; $i < $cnt; $i++) {
                        if (empty($mailmap[$changes[$i]['id']][$changes[$i]['type']])) {
                            $this->_changes[] = $changes[$i];
                            continue;
                        }
                        // @todo For 3.0, create a Changes and
                        // ChangeFilter classes to abstract out a bunch of
                        // this stuff. (Needs BC breaking changes in
                        // storage/state classes).

                        // OL2013 is broken and duplicates the destination
                        // email during MOVEITEMS requests (instead it
                        // reassigns the existing email the new UID). Don't
                        // send the ADD command for these changes.
                        if ($changes[$i]['type'] == Horde_ActiveSync::CHANGE_TYPE_CHANGE &&
                            $changes[$i]['flags'] == Horde_ActiveSync::FLAG_NEWMESSAGE &&
                            $this->_deviceInfo->deviceType != 'WindowsOutlook15') {
                            $this->_changes[] = $changes[$i];
                            continue;
                        }
                        $changes[$i]['ignore'] = true;
                        $this->_changes[] = $changes[$i];
                        $this->_logger->meta(sprintf(
                            'STATE: Ignoring client initiated %s for %s',
                            $flag_map[$changes[$i]['type']],
                            $changes[$i]['id'])
                        );
                    }
                    break;

                default:
                    $client_timestamps = $this->_getPIMChangeTS($changes);
                    $cnt = count($changes);
                    for ($i = 0; $i < $cnt; $i++) {
                        if (empty($client_timestamps[$changes[$i]['id']])) {
                            $this->_changes[] = $changes[$i];
                            continue;
                        }
                        if ($changes[$i]['type'] == Horde_ActiveSync::CHANGE_TYPE_DELETE) {
                            // If we have a delete, don't bother stating the message,
                            // the entry should already be deleted on the client.
                            $stat['mod'] = 0;
                        } else {
                            // stat only returns MODIFY times, not deletion times,
                            // so will return (int)0 for ADD or DELETE.
                            $stat = $this->_backend->statMessage($this->_folder->serverid(), $changes[$i]['id']);
                        }
                        if ($client_timestamps[$changes[$i]['id']] >= $stat['mod']) {
                            $this->_logger->meta(sprintf(
                                'STATE: Ignoring client initiated change for %s (client TS: %s Stat TS: %s)',
                                $changes[$i]['id'], $client_timestamps[$changes[$i]['id']], $stat['mod'])
                            );
                        } else {
                            $this->_changes[] = $changes[$i];
                        }
                    }
                }
            } elseif (count($changes)) {
                $this->_logger->meta('STATE: No client changes, returning all messages.');
                $this->_changes = $changes;
            }
        } else {
            // FOLDERSYNC changes.
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
        $this->_logger->meta('STATE: Initializing folder diff engine');
        $folderlist = $this->_backend->getFolderList();
        if ($folderlist === false) {
            return false;
        }
        // @TODO Remove in H6. We need to ensure we have 'serverid' in the
        // returned stat data.
        foreach ($folderlist as &$folder) {
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
            $this->_logger->meta('STATE: No folder changes found.');
        } else {
            $this->_logger->meta(sprintf(
                'STATE: Found %d folder changes.',
                count($this->_changes))
            );
        }
    }

    /**
     * Non-static wrapper for getNewSyncKey.
     *
     * @param string $syncKey  The old syncKey
     *
     * @return string  The new synckey
     * @throws Horde_ActiveSync_Exception
     *
     * @todo  Remove/replace in H6 with Horde_ActiveSync_SyncKey
     */
    public function getNewSyncKeyWrapper($syncKey)
    {
        if ($this->checkCollision($newKey = self::getNewSyncKey($syncKey))) {
            $this->_logger->err(sprintf(
                'STATE: Found collision when generating synckey %s. Trying again.',
                $newKey)
            );
            return $this->getNewSyncKeyWrapper($synckey);
        }

        return $newKey;
    }

    /**
     * Check for the (rare) possibility of a synckey collision between
     * collections.
     *
     * @param string $syncKey  The synckey to check.
     *
     * @return boolean  True if there was a collision.
     */
    public function checkCollision($syncKey)
    {
        if (!preg_match('/^\{([0-9A-Za-z-]+)\}([0-9]+)$/', $syncKey, $matches)) {
            return false;
        }
        $guid = $matches[1];

        // We only need to check for collisions on the first save.
        if ($matches[2] != 1) {
            return false;
        }

        return $this->_checkCollision($guid);
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
    public static function getNewSyncKey($syncKey)
    {
        if (empty($syncKey)) {
            return '{' . new Horde_Support_Uuid() . '}' . '1';
        } else {
            if (preg_match('/^\{([a-fA-F0-9-]+)\}([0-9]+)$/', $syncKey, $matches)) {
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
     * @param string $syncKey  The synckey to obtain the counter for.
     *
     * @return mixed integer|boolean  The increment counter or false if failed.
     */
    public static function getSyncKeyCounter($syncKey)
    {
        if (preg_match('/^\{([a-fA-F0-9-]+)\}([0-9]+)$/', $syncKey, $matches)) {
            $n = $matches[2];
            return $n;
        }

        return false;
    }

    /**
     * Return the UID portion of a synckey.
     *
     * @param string $syncKey  The synckey
     *
     * @return string  The UID.
     */
    public static function getSyncKeyUid($syncKey)
    {
       if (preg_match('/^(\{[a-fA-F0-9-]+\})([0-9]+)$/', $syncKey, $matches)) {
            return $matches[1];
        }
    }

   /**
    * Returns the timestamp of the earliest modification time to consider
    *
    * @param integer $restrict  The time period to restrict to
    *
    * @return integer
    */
    protected static function _getCutOffDate($restrict)
    {
        // @todo Should just pass the filtertype to the driver instead
        // of parsing it here, let the driver figure out what to do with it.
        if ($restrict == Horde_ActiveSync::FILTERTYPE_INCOMPLETETASKS) {
            return $restrict;
        }
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

        if (isset($back)) {
            $date = time() - $back;
            return $date;
        } else {
            return 0; // unlimited
        }
    }

    /**
     * Helper function that performs the actual diff between client state and
     * server state FOLDERSYNC arrays.
     *
     * @param array $old  The client state
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
                    $change['id'] = $new[$inew]['id'];
                    $change['serverid'] = $new[$inew]['serverid'];
                    $changes[] = $change;
                }
                $inew++;
                $iold++;
            } else {
                if ($old[$iold]['id'] > $new[$inew]['id']) {
                    // Messesge in device state has disappeared
                    $change['type'] = Horde_ActiveSync::CHANGE_TYPE_DELETE;
                    $change['id'] = $old[$iold]['id'];
                    $change['serverid'] = $old[$iold]['serverid'];
                    $changes[] = $change;
                    $iold++;
                } else {
                    // Message in $new is new
                    $change['type'] = Horde_ActiveSync::CHANGE_TYPE_CHANGE;
                    $change['id'] = $new[$inew]['id'];
                    $change['serverid'] = $new[$inew]['serverid'];
                    $changes[] = $change;
                    $inew++;
                }
            }
        }
        while ($iold < count($old)) {
            // All data left in _syncstate have been deleted
            $change['type'] = Horde_ActiveSync::CHANGE_TYPE_DELETE;
            $change['id'] = $old[$iold]['id'];
            $change['serverid'] = $old[$iold]['serverid'];
            $changes[] = $change;
            $iold++;
        }

        // New folders added on server.
        while ($inew < count($new)) {
            // All data left in new have been added
            $change['type'] = Horde_ActiveSync::CHANGE_TYPE_CHANGE;
            $change['flags'] = Horde_ActiveSync::FLAG_NEWMESSAGE;
            $change['id'] = $new[$inew]['id'];
            $change['serverid'] = $new[$inew]['serverid'];
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
    public static function RowCmp($a, $b)
    {
        return $a['id'] < $b['id'] ? 1 : -1;
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
                '%s::loadState: clearing folder state.',
                __CLASS__)
            );
            if ($type == Horde_ActiveSync::REQUEST_TYPE_FOLDERSYNC) {
                $this->_folder = array();
            } else {
                // Create a new folder object.
                $this->_folder = ($this->_collection['class'] == Horde_ActiveSync::CLASS_EMAIL) ?
                    new Horde_ActiveSync_Folder_Imap($this->_collection['serverid'], Horde_ActiveSync::CLASS_EMAIL) :
                    ($this->_collection['serverid'] == 'RI' ? new Horde_ActiveSync_Folder_RI('RI', 'RI') : new Horde_ActiveSync_Folder_Collection($this->_collection['serverid'], $this->_collection['class']));
            }
            $this->_syncKey = '0';
            $this->_resetDeviceState($id);
            return;
        }

        $this->_logger->meta(sprintf('STATE: Loading state for synckey %s', $syncKey));

        // Check if synckey is allowed. Throw a StateGone exception if it
        // doesn't match to give the client a chance to reset it's internal
        // state.
        if (!preg_match('/^\{([0-9A-Za-z-]+)\}([0-9]+)$/', $syncKey, $matches)) {
            throw new Horde_ActiveSync_Exception_StateGone('Invalid synckey');
        }
        $this->_syncKey = $syncKey;

        // Cleanup older syncstates
        $this->_gc($syncKey);

        // Load the state
        $this->_loadState();
    }

    /**
     * Return the most recently seen synckey for the given collection.
     *
     * @param string $collection_id  The activesync collection id.
     *
     * @return string|integer  The synckey or 0 if none found.
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
     * Load the state represented by $syncKey from storage.
     *
     * @throws Horde_ActiveSync_Exception, Horde_ActiveSync_Exception_StateGone
     */
    protected function _loadState()
    {
        throw new Horde_ActiveSync_Exception('Must be implemented in concrete class.');
    }

    /**
     * Check for the existence of ANY entries in the map table for this device
     * and user.
     *
     * An extra database query for each sync, but the payoff is that we avoid
     * having to stat every message change we send to the client if there are no
     * client generated changes for this sync period.
     *
     * @return boolean
     * @throws Horde_ActiveSync_Exception
     */
    protected function _havePIMChanges()
    {
        throw new Horde_ActiveSync_Exception('Must be implemented in concrete class.');
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
        throw new Horde_ActiveSync_Exception('Must be implemented in concrete class.');
    }

    /**
     * Update the syncStamp in the collection state, outside of any other changes.
     * Used to prevent extremely large differences in syncStamps for clients
     * and collections that don't often have changes.
     */
    public function updateSyncStamp()
    {
        throw new Horde_ActiveSync_Exception('Not supported in this state driver.');
    }

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
     *    Horde_ActiveSync::CHANGE_ORIGIN_PIM - Change originated from client
     *
     * @param string $user      The current sync user, only needed if change
     *                          origin is CHANGE_ORIGIN_PIM
     * @param string $clientid  client clientid sent when adding a new message
     */
    abstract public function updateState(
        $type, array $change, $origin = Horde_ActiveSync::CHANGE_ORIGIN_NA,
        $user = null, $clientid = '');

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
     * @param string $devId    The device id.
     * @param string $status   A Horde_ActiveSync::RWSTATUS_* constant.
     *
     * @throws Horde_ActiveSync_Exception
     */
    abstract public function setDeviceRWStatus($devId, $status);

    /**
     * Obtain the device object.
     *
     * @param string $devId   The device id to obtain
     * @param string $user
     * @param array  $params  Additional parameters:
     *   - force: (boolean)  If true, reload the device info even if it's
     *     already loaded. Used to refresh values such as device_rwstatus that
     *     may have changed during a long running PING/SYNC. DEFAULT: false.
     *     @since  2.31.0
     *
     * @return Horde_ActiveSync_Device
     */
    abstract public function loadDeviceInfo($device, $user = null, $params = array());

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
     * @param object $data  The device information
     * @param array $dirty  Array of dirty properties. @since 2.9.0
     */
    abstract public function setDeviceInfo(Horde_ActiveSync_Device $data, array $dirty = array());

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
    abstract public function getSyncCache($devid, $user, array $fields = null);

    /**
     * Save the provided sync_cache.
     *
     * @param array $cache   The cache to save.
     * @param string $devid  The device id.
     * @param string $user   The userid.
     * @param array $dirty   An array of dirty properties. @since 2.9.0
     *
     * @throws Horde_ActiveSync_Exception
     */
    abstract public function saveSyncCache(array $cache, $devid, $user, array $dirty = array());

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
     * Check and see that we didn't already see the incoming change from the client.
     * This would happen e.g., if the client failed to receive the server response
     * after successfully importing new messages.
     *
     * @param string $id  The client id sent during message addition.
     *
     * @return string The UID for the given clientid, null if none found.
     * @throws Horde_ActiveSync_Exception
     */
     abstract public function isDuplicatePIMAddition($id);

     /**
      * Close the underlying backend storage connection.
      * To be used during PING or looping SYNC operations.
      */
     abstract public function disconnect();

     /**
      * (Re)open backend storage connection.
      */
     abstract public function connect();

}
