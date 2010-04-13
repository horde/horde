<?php
/**
 * Horde_History based state management.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
class Horde_ActiveSync_State_History extends Horde_ActiveSync_State_Base
{
    /**
     * Cache for ping state
     *
     * @var array
     */
    private $_pingState;

    /**
     * The timestamp for the last syncKey
     *
     * @var timestamp
     */
    private $_lastSyncTS;

    /**
     * The current sync timestamp
     *
     * @var timestamp
     */
    private $_thisSyncTS;


    /**
     * Local cache of changes that need to be sent
     *
     * @var array
     */
    private $_changes;

    /**
     * DB handle
     *
     * @var Horde_Db_Adapter_Base
     */
    protected $_db;

    /**
     * Const'r
     *
     * @param array  $params   Must contain:
     *      'db'  - Horde_Db
     *      'syncStateTable' - Name of table for storing syncstate
     *      'pingTable'      - Name of table for storing ping data
     *      'syncChangesTable'  - Name of table for remembering what changes
     *                            are due to PIM import so we don't mirror the
     *                            changes back to the PIM on next Sync
     *
     * @return Horde_ActiveSync_StateMachine_File
     */
    public function __construct($params = array())
    {
        parent::__construct($params);

        if (empty($this->_params['db']) || !($this->_params['db'] instanceof Horde_Db_Adapter_Base)) {
            throw new InvalidArgumentException('Missing or invalid Horde_Db parameter.');
        }
        $this->_params = $params['db'];
    }

    /**
     * Load the sync state
     *
     * @return void
     * @throws Horde_ActiveSync_Exception
     */
    public function loadState($syncKey, $username)
    {
        if (empty($syncKey)) {
            return;
        }

        // Check if synckey is allowed
        if (!preg_match('/^s{0,1}\{([0-9A-Za-z-]+)\}([0-9]+)$/', $syncKey, $matches)) {
            throw new Horde_ActiveSync_Exception('Invalid sync key');
        }
        $this->_syncKey = $syncKey;

        try {
            $results = $this->_db->selectOne('SELECT sync_data, sync_devId, sync_time FROM ' . $this->_syncStateTable . ' WHERE sync_key = ?', array($this->_syncKey));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }

        /* Load the previous syncState from storage */
        $this->_lastSyncTS = $results['sync_time'];
        $this->_devId = $results['sync_devId'];
        $this->_changes = unserialize(sync_data);
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
        // Update state table to remember this last synctime and key
        $sql = 'INSERT INTO ' . $this->_syncStateTable . ' (sync_key, sync_data, sync_devId, sync_time) VALUES (?, ?, ?, ?)';

        /* Remember any left over changes */
        $data = (isset($this->_changes) ? serialize($this->_changes) : serialize(array()));

        try {
            $this->_db->insert($sql, array($this->_syncKey, $data, $this->_devId, $this->_thisSyncTS));
        } catch (Horde_Db_Exception $e) {
            throw new Horde_ActiveSync_Exception($e);
        }
        
        return true;
    }

    /**
     * Update the state to reflect changes
     *
     * Notes: Since PIM changes are dealt with before Server changes, we can
     * use a null $_changes array to detect what we are updating for. If we
     * are importing PIM changes, need to update the syncChangesTable so we
     * don't mirror back the changes on next sync. If we are exporting server
     * changes, we need to track which changes have been sent (by removing them
     * from _changes) so we know which items to send on the next sync if a
     * MOREAVAILBLE response was needed.
     *
     * @param string $type   The type of change (change, delete, flags)
     * @param array $change  Array describing change
     *
     * @return void
     */
    public function updateState($type, $change)
    {
       if (!isset($this->_changes)) {
           /* We must be updating state during receiving changes from PIM */
           $sql = 'INSERT INTO ' . $this->_syncChangesTable . ' (message_uid, sync_mod_time, sync_key) VALUES (?, ?, ?)';
           try {
               $this->_db->insert($sql, array($change['id'], time(), $this->_syncKey));
           } catch (Horde_Db_Exception $e) {
               throw new Horde_ActiveSync_Exception($e);
           }
       } else {
           /* When sending server changes, $this->_changes will contain all
            * changes. Need to track which ones are sent since we might not
            * send all of them.
            */
           for ($i = 0; $i < count($this->_changes); $i++) {
               if ($this->_changes[$i]['id'] == $change['id']) {
                   unset($this->_changes[$i]);
               }
           }
       }
    }

    /**
     * Save folder data for a specific device
     *
     * @param string $devId  The device Id
     * @param array $folders The folder data
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
        /* Storage to SQL? */
//
//        if (!file_put_contents($this->_stateDir . '/compat-' . $devId, serialize($unique_folders))) {
//            $this->logError('_saveFolderData: Data could not be saved!');
//            throw new Horde_ActiveSync_Exception('Folder data could not be saved');
//        }
    }

    /**
     * Get the folder data for a specific device
     *
     * @param string $devId  The device id
     * @param string $class  The folder class to fetch (Calendar, Contacts etc.)
     *
     * @return mixed  Either an array of folder data || false
     */
    public function getFolderData($devId, $class)
    {
//        $filename = $this->_stateDir . '/compat-' . $devId;
//        if (file_exists($filename)) {
//            $arr = unserialize(file_get_contents($filename));
//            if ($class == "Calendar") {
//                return $arr[Horde_ActiveSync::FOLDER_TYPE_APPOINTMENT];
//            }
//            if ($class == "Contacts") {
//                return $arr[Horde_ActiveSync::FOLDER_TYPE_CONTACT];
//            }
//        }
//
//        return false;
    }

    public function getKnownFolders($syncKey)
    {

        $sql = 'SELECT state_data from ' . $this->_table . ' WHERE state_syncKey = ?';
        //
        //

    }

    public function setKnownFolders($syncKey, $folders)
    {
        $sql = 'INSERT INTO ' . $this->_table . '....';

        // Need to GC the table, delete all but the *two* most recent synckeys
        // for this devId. Need the latest one, but also the previous one in
        // case the device did not correctly receive the response - it will
        // continue to send the previous syncKey, so we need to remember the
        // state.

    }

    /**
     * Perform any initialization needed to deal with pingStates
     * For this driver, it loads the device's state file.
     *
     * @param string $devId  The device id of the PIM to load PING state for
     *
     * @return The $collection array
     */
    public function initPingState($devId)
    {
        $this->_devId = $devId;

        $sql = 'SELECT ping_state FROM ' . $this->_pingTable . ' WHERE ping_devid = ?';

        $this->_pingState = unserialize($results);
        // Try to get pingstate from SQL (need lifetime and last synctime)
        //$this->_pingState = unserialize($sqlResults);

        // If no existing state - initialize
        //        $this->_pingState = array(
        //            'lifetime' => 0,
        //            'collections' => array());

        return $this->_pingState['collections'];
    }

    /**
     * Load a specific collection's ping state
     *
     * @param array $pingCollection  The collection array from the PIM request
     *
     * @return void
     * @throws Horde_ActiveSync_Exception
     */
    public function loadCollectionPingState($pingCollection)
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
            //$this->_stateCache = $this->_collection['state'];
            $haveState = true;
        }

        /* Initialize state for this collection */
        if (!$haveState) {
            $this->_logger->debug('Empty state for '. $pingCollection['class']);

            /* Start with empty state cache */
            //$this->_stateCache[$pingCollection['id']] = array();

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
        $state = serialize(array('lifetime' => $this->_pingState['lifetime'], 'collections' => $this->_pingState['collections']));

        // Need to write to DB
        return ;//file_put_contents($this->_stateDir . '/' . $this->_devId, $state);
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
     * Get all items that have changed since the last sync time
     *
     * @param integer $flags
     *
     * @return array
     */
    public function getChanges($flags = 0)
    {
        $cutoffdate = self::_getCutOffDate(!empty($this->_collection['filtertype']) ? $this->_collection['filtertype'] : 0);

        if (!empty($this->_collection['id'])) {
            $folderId = $this->_collection['id'];
            $this->_logger->debug('Initializing message diff engine');

            //do nothing if it is a dummy folder
            if ($folderId != Horde_ActiveSync::FOLDER_TYPE_DUMMY) {
                /* First, need to see if we have exising changes left over
                 * from a previous sync that resulted in a MORE_AVAILABLE */
                if (!$empty($this->_changes)) {
                    return $this->_changes;
                }

                /* No existing changes, poll the backend */
                $this->_thisSyncTS = time();
                $this->_changes = $this->_backend->getServerChanges($folderId, $this->_lastSyncTS, $this->_thisSyncTS);
            }
            $this->_logger->debug('Found ' . count($this->_changes) . ' message changes');

        } else {

            $this->_logger->debug('Initializing folder diff engine');
            $this->_thisSyncTS = time();
            $folderlist = $this->_backend->getFolderList();
            if ($folderlist === false) {
                return false;
            }

            if (!isset($syncState) || !$syncState) {
                $syncState = array();
            }

            $this->_changes = $this->_getDiff($syncState, $folderlist);
            $this->_logger->debug('Config: Found ' . count($this->_changes) . ' folder changes');
        }

        return $this->_changes;
    }

    public function getChangeCount()
    {
        if (!isset($this->_changes)) {
            $this->getChanges();
            //throw new Horde_ActiveSync_Exception('Changes not yet retrieved. Must call getChanges() first');
        }
        return count($this->_changes);
    }

    /**
     * Garbage collector - clean up from previous sync
     * requests.
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

        $dir = opendir($this->_stateDir);
        if (!$dir) {
            return false;
        }
        while ($entry = readdir($dir)) {
            if (preg_match('/^s{0,1}\{([0-9A-Za-z-]+)\}([0-9]+)$/', $entry, $matches)) {
                if ($matches[1] == $guid && $matches[2] < $n) {
                    unlink($this->_stateDir . '/' . $entry);
                }
            }
        }

        return true;
    }

    /**
     *
     * @param $old
     * @param $new
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
                    $change['flags'] = Horde_ActiveSync::FLAG_NEWMESSAGE;
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
            $change['flags'] = Horde_ActiveSync::FLAG_NEWMESSAGE;
            $change['id'] = $new[$inew]['id'];
            $changes[] = $change;
            $inew++;
        }

        return $changes;
    }

     /**
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