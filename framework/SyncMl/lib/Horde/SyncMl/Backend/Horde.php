<?php
/**
 * SyncML Backend for the Horde Application framework.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Karsten Fourmont <karsten@horde.org>
 * @package SyncMl
 */
class Horde_SyncMl_Backend_Horde extends Horde_SyncMl_Backend
{
    /**
     * A database instance.
     *
     * @var Horde_Db_Adapter_Base
     */
    protected $_db;

    /**
     * The session ID used in the Horde session.
     *
     * @var string
     */
    protected $_sessionId;

    /**
     * Constructor.
     *
     * Initializes the logger.
     *
     * @param array $params  Any parameters the backend might need.
     */
    public function __construct($params)
    {
        parent::__construct($params);

        $this->_db = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Db')->create();
    }

    /**
     * Sets the user used for this session.
     *
     * @param string $user  A user name.
     */
    public function setUser($user)
    {
        parent::setUser($user);

        if ($this->_backendMode == Horde_SyncMl_Backend::MODE_TEST) {
            /* After a session the user gets automatically logged out, so we
             * have to login again. */
            $GLOBALS['registry']->setAuth($this->_user, array());
        }
    }

    /**
     * Starts a PHP session.
     *
     * @param string $syncDeviceID  The device ID.
     * @param string $session_id    The session ID to use.
     * @param integer $backendMode  The backend mode, one of the
     *                              Horde_SyncMl_Backend::MODE_* constants.
     */
    public function sessionStart($syncDeviceID, $sessionId,
                                 $backendMode = Horde_SyncMl_Backend::MODE_SERVER)
    {
        $this->_backendMode = $backendMode;
        $this->_syncDeviceID = $syncDeviceID;
        $this->_sessionId = md5($syncDeviceID . $sessionId);

        /* Only the server needs to start a session. */
        if ($this->_backendMode == Horde_SyncMl_Backend::MODE_SERVER) {
            /* Reload the Horde SessionHandler if necessary. */
            $GLOBALS['session']->setup(true, null, $this->_sessionId);
            $this->state = $GLOBALS['session']->get('horde', 'syncml');
        }
    }

    public function close()
    {
        if ($this->state) {
            $GLOBALS['session']->set('horde', 'syncml', $this->state);
        }
        parent::close();
    }

    protected function _fastsync($databaseURI, $from_ts, $to_ts)
    {
        global $registry;

        $results = array(
            'adds' => array(),
            'mods' => array(),
            'dels' => array());

        $map = array(
            'adds' => 'add',
            'dels' => 'delete',
            'mods' => 'modify');

        $database = $this->normalize($databaseURI);

        // Get ALL server changes from backend
        try {
            $changes = $registry->{$database}->getChanges($from_ts, $to_ts);
        } catch (Horde_Exception $e) {
            $this->logMessage(
                sprintf(
                    ' %s getChanges() failed during _fastSync: %s', $database, $e->getMessage()),
                'ERR');
        }

        $add_ts = array();
        foreach (array_keys($results) as $type) {
            foreach ($changes[$map[$type]] as $suid) {
                // Only server needs to check for client sent entries:
                if ($this->_backendMode != Horde_SyncMl_Backend::MODE_SERVER) {
                    switch ($type) {
                    case 'adds':
                        $id = 0;
                        break;
                    case 'mods':
                    case 'dels':
                        $id = $suid;
                    }
                    $results[$type][$suid] = $id;
                    continue;
                }

                try {
                    $change_ts = $registry->{$database}->getActionTimestamp(
                        $suid, $map[$type], Horde_SyncMl_Backend::getParameter($databaseURI, 'source'));
                } catch (Horde_Exception $e) {
                    $this->logMessage($e->getMessage(), 'ERR');
                    return;
                }
                // If added, then deleted all since last sync, don't bother
                // sending change
                if ($type == 'adds') {
                    $add_ts[$suid] = $change_ts;
                } elseif ($type == 'dels') {
                    if (isset($results['adds'][$suid]) && $add_ts[$suid] < $change_ts) {
                        unset($results['adds'][$suid]);
                        continue;
                    }
                    if (isset($results['mods'][$suid])) {
                        unset($results['mods'][$suid]);
                    }
                }

                $sync_ts = $this->_getChangeTS($database, $suid);
                if ($sync_ts && $sync_ts >= $change_ts) {
                    // Change was done by us upon request of client.  Don't
                    // mirror that back to the client.
                    $this->logMessage(
                        "Added to server from client: $suid ignored", 'DEBUG');
                    continue;
                }

                // Sanity check and prepare list of changes
                if ($type != 'adds') {
                    $cuid = $this->_getCuid($database, $suid);
                    if (empty($cuid) && $type == 'mods') {
                        $this->logMessage(
                            "Unable to create change for server id $suid: client id not found in map, adding instead.", 'WARN');
                        $results['adds'][$suid] = 0;
                        continue;
                    } elseif (empty($cuid) && $type == 'dels') {
                         $this->logMessage(
                            "Unable to create delete for server id $suid: client id not found in map", 'WARN');
                        continue;
                    } else {
                        $id = $cuid;
                    }
                } else {
                    $id = 0;
                }
                $results[$type][$suid] = $id;
            }
        }

        return $results;
    }

    protected function _slowsync($databaseURI, $from_ts, $to_ts)
    {
        global $registry;

        $results = array(
            'adds' => array(),
            'dels' => array(),
            'mods' => array());

        $database = $this->normalize($databaseURI);

        // Return all db entries directly rather than bother history. But
        // first check if we only want to sync data from a given start
        // date:
        $start = trim(Horde_SyncMl_Backend::getParameter($databaseURI, 'start'));
        try {
            if (!empty($start)) {
                if (strlen($start) == 4) {
                    $start .= '0101000000';
                } elseif (strlen($start) == 6) {
                    $start .= '01000000';
                } elseif (strlen($start) == 8) {
                    $start .= '000000';
                }
                $start = new Horde_Date($start);
                $this->logMessage('Slow-syncing all events starting from ' . (string)$start, 'DEBUG');
                $data = $registry->{$database}->listUids(
                            Horde_SyncMl_Backend::getParameter($databaseURI, 'source'), $start);
            } else {
                $data = $registry->{$database}->listUids(
                            Horde_SyncMl_Backend::getParameter($databaseURI, 'source'));
            }
        } catch (Horde_Exception $e) {
            $this->logMessage(
                "$database/list or $database/listBy failed while retrieving server additions:"
                    . $e->getMessage(), 'ERR');
            return;
        }

        foreach ($data as $suid) {
            // Only server needs to check for client sent entries:
            if ($this->_backendMode != Horde_SyncMl_Backend::MODE_SERVER) {
                $results['adds'][$suid] = 0;
                continue;
            }

            // Ignore if a map entry is present
            $cuid = $this->_getCuid($database, $suid);
            if ($cuid) {
                $this->logMessage(
                    "Added to server from client during SlowSync: $suid ignored", 'DEBUG');
                continue;
            }

            try {
                $add_ts  = $registry->{$database}->getActionTimestamp(
                    $suid,
                    'add',
                    Horde_SyncMl_Backend::getParameter($databaseURI, 'source'));
            } catch (Horde_Exception $e) {
                $this->logMessage($e->getMessage(), 'ERR');
                return;
            }

            $sync_ts = $this->_getChangeTS($database, $suid);
            if ($sync_ts && $sync_ts >= $add_ts) {
                // Change was done by us upon request of client.  Don't mirror
                // that back to the client.
                $this->logMessage("Added to server from client: $suid ignored", 'DEBUG');
                continue;
            }
            $this->logMessage(
                "Adding to client from db $database, server id $suid", 'DEBUG');

            $results['adds'][$suid] = 0;
        }

        return $results;
    }

    /**
     * Returns entries that have been modified in the server database.
     *
     * @param string $databaseURI  URI of Database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     * @param integer $from_ts     Start timestamp.
     * @param integer $to_ts       Exclusive end timestamp. Not yet
     *                             implemented.
     * @param array &$adds         Output array: hash of adds suid => 0
     * @param array &$mods         Output array: hash of modifications
     *                             suid => cuid
     * @param array &$dels         Output array: hash of deletions suid => cuid
     *
     * @return boolean true
     */
    public function getServerChanges($databaseURI, $from_ts, $to_ts, &$adds, &$mods,
                              &$dels)
    {
        global $registry;

        $slowsync = $from_ts == 0;

        if ($slowsync) {
            $results = $this->_slowsync($databaseURI, $from_ts, $to_ts);
        } else {
            $results = $this->_fastSync($databaseURI, $from_ts, $to_ts);
        }

        $adds = $results['adds'];
        $mods = $results['mods'];
        $dels = $results['dels'];

        // @TODO: No need to return true, since errors are now thrown. H5 should
        //        remove this.
        return true;
    }

    /**
     * Retrieves an entry from the backend.
     *
     * @param string $databaseURI  URI of Database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     * @param string $suid         Server unique id of the entry: for horde
     *                             this is the guid.
     * @param string $contentType  Content-Type: the MIME type in which the
     *                             public function should return the data.
     * @param array $fields        Hash of field names and Horde_SyncMl_Property
     *                             properties with the requested fields.
     *
     * @return mixed  A string with the data entry or a PEAR_Error object.
     */
    public function retrieveEntry($databaseURI, $suid, $contentType, $fields)
    {
        try {
            return $GLOBALS['registry']->call(
                $this->normalize($databaseURI) . '/export',
                array('guid' => $suid, 'contentType' => $contentType, 'dummy' => null, 'fields' => $fields));
        } catch (Horde_Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }
    }

    /**
     * Adds an entry into the server database.
     *
     * @param string $databaseURI  URI of Database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     * @param string $content      The actual data.
     * @param string $contentType  MIME type of the content.
     * @param string $cuid         Client ID of this entry.
     *
     * @return array  PEAR_Error or suid (Horde guid) of new entry
     */
    public function addEntry($databaseURI, $content, $contentType, $cuid = null)
    {
        global $registry;

        $database = $this->normalize($databaseURI);

        try {
            $suid = $registry->call(
                $database . '/import',
                array($content,
                      $contentType,
                      Horde_SyncMl_Backend::getParameter($databaseURI, 'source')));

            $this->logMessage(
                "Added to server db $database client id $cuid -> server id $suid", 'DEBUG');
            $ts = $registry->call(
                $database . '/getActionTimestamp',
                array($suid,
                      'add',
                      Horde_SyncMl_Backend::getParameter($databaseURI, 'source')));
            if (!$ts) {
                $this->logMessage(
                    "Unable to find addition timestamp for server id $suid at $ts", 'ERR');
            }
            // Only server needs to do a cuid<->suid map
            if ($this->_backendMode == Horde_SyncMl_Backend::MODE_SERVER) {
                $this->createUidMap($database, $cuid, $suid, $ts);
            }
        } catch (Horde_Exception $e) {
            // Failed import. Maybe the entry is already there. Check if a
            // guid is returned:
            /* Not working with exceptions
            if ($suid->getDebugInfo()) {
                $suid = $suid->getDebugInfo();
                $this->logMessage(
                    'Adding client entry to server: already exists with server id ' . $suid, 'NOTICE');
                if ($this->_backendMode == Horde_SyncMl_Backend::MODE_SERVER) {
                    $this->createUidMap($database, $cuid, $suid, 0);
                }
            }
            */

        }

        return $suid;
    }

    /**
     * Replaces an entry in the server database.
     *
     * @param string $databaseURI  URI of Database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     * @param string $content      The actual data.
     * @param string $contentType  MIME type of the content.
     * @param string $cuid         Client ID of this entry.
     *
     * @return string  PEAR_Error or server ID (Horde GUID) of modified entry.
     */
    public function replaceEntry($databaseURI, $content, $contentType, $cuid)
    {
        global $registry;

        $database = $this->normalize($databaseURI);

        // Only server needs to do a cuid<->suid map
        if ($this->_backendMode == Horde_SyncMl_Backend::MODE_SERVER) {
            $suid = $this->_getSuid($database, $cuid);
        } else {
            $suid = $cuid;
        }

        if (!$suid) {
            return PEAR::raiseError("No map entry found for client id $cuid replacing on server");
        }

        // Entry exists: replace current one.
        try {
            $ok = $registry->call($database . '/replace',
                                  array($suid, $content, $contentType));
        } catch (Horde_Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }
        $this->logMessage(
            "Replaced in server db $database client id $cuid -> server id $suid", 'DEBUG');
        $ts = $registry->call(
            $database . '/getActionTimestamp',
            array($suid,
                  'modify',
                  Horde_SyncMl_Backend::getParameter($databaseURI,'source')));
        // Only server needs to do a cuid<->suid map
        if ($this->_backendMode == Horde_SyncMl_Backend::MODE_SERVER) {
            $this->createUidMap($database, $cuid, $suid, $ts);
        }

        return $suid;
    }

    /**
     * Deletes an entry from the server database.
     *
     * @param string $databaseURI  URI of Database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     * @param string $cuid         Client ID of the entry.
     *
     * @return boolean  True on success or false on failed (item not found).
     */
    public function deleteEntry($databaseURI, $cuid)
    {
        global $registry;

        $database = $this->normalize($databaseURI);
        // Find server ID for this entry:
        // Only server needs to do a cuid<->suid map
        if ($this->_backendMode == Horde_SyncMl_Backend::MODE_SERVER) {
            try {
                $suid = $this->_getSuid($database, $cuid);
            } catch (Horde_Exception $e) {
                return false;
            }
        } else {
            $suid = $cuid;
        }
        if (empty($suid) || is_a($suid, 'PEAR_Error')) {
            return false;
        }

        try {
            $registry->call($database. '/delete', array($suid));
        } catch (Horde_Exception $e) {
            return false;
        }

        $this->logMessage(
            "Deleted in server db $database client id $cuid -> server id $suid", 'DEBUG');
        $ts = $registry->call($database . '/getActionTimestamp',
                              array($suid, 'delete'));
        // We can't remove the mapping entry as we need to keep the timestamp
        // information.
        // Only server needs to do a cuid<->suid map
        if ($this->_backendMode == Horde_SyncMl_Backend::MODE_SERVER) {
            $this->createUidMap($database, $cuid, $suid, $ts);
        }

        return true;
    }

    /**
     * Authenticates the user at the backend.
     *
     * @param string $username    A user name.
     * @param string $password    A password.
     *
     * @return boolean|string  The user name if authentication succeeded, false
     *                         otherwise.
     */
    protected function _checkAuthentication($username, $password)
    {
        $auth = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_Auth')
            ->create()
            ->authenticate($username, array('password' => $password))
            ? $GLOBALS['registry']->getAuth()
            : false;

        /* Horde is regenerating the session id at login, but we need to keep
         * our own, predictable session to not lose state. */
        session_id($this->_sessionId);

        return $auth;
    }

    /**
     * Sets a user as being authenticated at the backend.
     *
     * @abstract
     *
     * @param string $username    A user name.
     * @param string $credData    Authentication data provided by <Cred><Data>
     *                            in the <SyncHdr>.
     *
     * @return string  The user name.
     */
    public function setAuthenticated($username, $credData)
    {
        global $registry;

        $registry->setAuth($username, $credData);
        return $registry->getAuth();
    }

    /**
     * Stores Sync anchors after a successful synchronization to allow two-way
     * synchronization next time.
     *
     * The backend has to store the parameters in its persistence engine
     * where user, syncDeviceID and database are the keys while client and
     * server anchor ar the payload. See readSyncAnchors() for retrieval.
     *
     * @param string $databaseURI       URI of database to sync. Like calendar,
     *                                  tasks, contacts or notes. May include
     *                                  optional parameters:
     *                                  tasks?options=ignorecompleted.
     * @param string $clientAnchorNext  The client anchor as sent by the
     *                                  client.
     * @param string $serverAnchorNext  The anchor as used internally by the
     *                                  server.
     */
    public function writeSyncAnchors($databaseURI, $clientAnchorNext,
                              $serverAnchorNext)
    {
        $database = $this->normalize($databaseURI);

        $values = array($clientAnchorNext, $serverAnchorNext,
                        $this->_syncDeviceID, $database, $this->_user);
        if (!$this->readSyncAnchors($databaseURI)) {
            $query = 'INSERT INTO horde_syncml_anchors '
                . '(syncml_clientanchor, syncml_serveranchor, '
                . 'syncml_syncpartner, syncml_db, syncml_uid) '
                . 'VALUES (?, ?, ?, ?, ?)';
            $this->_db->insert($query, $values);
        } else {
            $query = 'UPDATE horde_syncml_anchors '
                . 'SET syncml_clientanchor = ?, syncml_serveranchor = ? '
                . 'WHERE syncml_syncpartner = ? AND syncml_db = ? AND '
                . 'syncml_uid = ?';
            $this->_db->update($query, $values);
        }
    }

    /**
     * Reads the previously written sync anchors from the database.
     *
     * @param string $databaseURI  URI of database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     *
     * @return mixed  Two-element array with client anchor and server anchor as
     *                stored in previous writeSyncAnchor() calls. False if no
     *                data found.
     */
    public function readSyncAnchors($databaseURI)
    {
        $database = $this->normalize($databaseURI);
        $query = 'SELECT syncml_clientanchor, syncml_serveranchor '
            . 'FROM horde_syncml_anchors '
            . 'WHERE syncml_syncpartner = ? AND syncml_db = ? AND '
            . 'syncml_uid = ?';
        $values = array($this->_syncDeviceID, $database, $this->_user);
        try {
            if ($res = $this->_db->selectOne($query, $values)) {
                return array(
                    $res['syncml_clientanchor'],
                    $res['syncml_serveranchor']
                );
            }
        } catch (Horde_Db_Exception $e) {}

        return false;
    }

    /**
     * Returns all previously written sync anchors for a user.
     *
     * @param string $user  A user name.
     *
     * @return array  A hash tree with all devices, databases and sync anchors
     *                from the specified user.
     */
    public function getUserAnchors($user)
    {
        $query = 'SELECT syncml_syncpartner, syncml_db, syncml_clientanchor, '
            . 'syncml_serveranchor FROM horde_syncml_anchors '
            . 'WHERE syncml_uid = ?';
        $values = array($user);
        return $this->_db->selectAll($query, $values);
    }

    /**
     * Deletes previously written sync anchors for a user.
     *
     * If no device or database are specified, anchors for all devices and/or
     * databases will be deleted.
     *
     * @param string $user      A user name.
     * @param string $device    The ID of the client device.
     * @param string $database  Normalized URI of database to delete. Like
     *                          calendar, tasks, contacts or notes.
     *
     * @return array
     */
    public function removeAnchor($user, $device = null, $database = null)
    {
        $query = 'DELETE FROM horde_syncml_anchors WHERE syncml_uid = ?';
        $values = array($user);
        if (strlen($device)) {
            $query .= ' AND syncml_syncpartner = ?';
            $values[] = $device;
        }
        if (strlen($database)) {
            $query .= ' AND syncml_db = ?';
            $values[] = $database;
        }

        $this->_db->delete($query, $values);
    }

    /**
     * Deletes previously written sync maps for a user.
     *
     * If no device or database are specified, maps for all devices and/or
     * databases will be deleted.
     *
     * @param string $user      A user name.
     * @param string $device    The ID of the client device.
     * @param string $database  Normalized URI of database to delete. Like
     *                          calendar, tasks, contacts or notes.
     *
     * @return array
     */
    public function removeMaps($user, $device = null, $database = null)
    {
        $query = 'DELETE FROM horde_syncml_map WHERE syncml_uid = ?';
        $values = array($user);
        if (strlen($device)) {
            $query .= ' AND syncml_syncpartner = ?';
            $values[] = $device;
        }
        if (strlen($database)) {
            $query .= ' AND syncml_db = ?';
            $values[] = $database;
        }

        $this->_db->delete($query, $values);
    }

    /**
     * Creates a map entry to map between server and client IDs.
     *
     * If an entry already exists, it is overwritten.
     *
     * @param string $databaseURI  URI of database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     * @param string $cuid         Client ID of the entry.
     * @param string $suid         Server ID of the entry.
     * @param integer $timestamp   Optional timestamp. This can be used to
     *                             'tag' changes made in the backend during the
     *                             sync process. This allows to identify these,
     *                             and ensure that these changes are not
     *                             replicated back to the client (and thus
     *                             duplicated). See key concept "Changes and
     *                             timestamps".
     */
    public function createUidMap($databaseURI, $cuid, $suid, $timestamp = 0)
    {
        $database = $this->normalize($databaseURI);

        $values = array($suid, (int)$timestamp, $this->_syncDeviceID,
                        $database, $this->_user, $cuid);
        // Check if entry exists. If not insert, otherwise update.
        if (!$this->_getSuid($databaseURI, $cuid)) {
            $query = 'INSERT INTO horde_syncml_map '
                . '(syncml_suid, syncml_timestamp, syncml_syncpartner, '
                . 'syncml_db, syncml_uid, syncml_cuid) '
                . 'VALUES (?, ?, ?, ?, ?, ?)';
            $this->_db->insert($query, $values);
        } else {
            $query = 'UPDATE horde_syncml_map '
                . 'SET syncml_suid = ?, syncml_timestamp = ? '
                . 'WHERE syncml_syncpartner = ? AND syncml_db = ? AND '
                . 'syncml_uid = ? AND syncml_cuid = ?';
            $this->_db->update($query, $values);
        }
    }

    /**
     * Retrieves the Server ID for a given Client ID from the map.
     *
     * @param string $databaseURI  URI of database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     * @param string $cuid         The client ID.
     *
     * @return mixed  The server ID string or false if no entry is found.
     */
    protected function _getSuid($databaseURI, $cuid)
    {
        $database = $this->normalize($databaseURI);
        $query = 'SELECT syncml_suid FROM horde_syncml_map '
            . 'WHERE syncml_syncpartner = ? AND syncml_db = ? AND '
            . 'syncml_uid = ? AND syncml_cuid = ?';
        $values = array($this->_syncDeviceID, $database, $this->_user, $cuid);
        return $this->_db->selectValue($query, $values);
    }

    /**
     * Retrieves the Client ID for a given Server ID from the map.
     *
     * @param string $databaseURI  URI of database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     * @param string $suid         The server ID.
     *
     * @return mixed  The client ID string or false if no entry is found.
     */
    protected function _getCuid($databaseURI, $suid)
    {
        $database = $this->normalize($databaseURI);

        $query = 'SELECT syncml_cuid FROM horde_syncml_map '
            . 'WHERE syncml_syncpartner = ? AND syncml_db = ? AND '
            . 'syncml_uid = ? AND syncml_suid = ?';
        $values = array($this->_syncDeviceID, $database, $this->_user, $suid);
        return $this->_db->selectValue($query, $values);
    }

    /**
     * Returns a timestamp stored in the map for a given Server ID.
     *
     * The timestamp is the timestamp of the last change to this server ID
     * that was done inside a sync session (as a result of a change received
     * by the server). It's important to distinguish changes in the backend a)
     * made by the user during normal operation and b) changes made by SyncML
     * to reflect client updates.  When the server is sending its changes it
     * is only allowed to send type a). However the history feature in the
     * backend my not know if a change is of type a) or type b). So the
     * timestamp is used to differentiate between the two.
     *
     * @param string $databaseURI  URI of database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     * @param string $suid         The server ID.
     *
     * @return mixed  The previously stored timestamp or false if no entry is
     *                found.
     */
    protected function _getChangeTS($databaseURI, $suid)
    {
        $database = $this->normalize($databaseURI);
        $query = 'SELECT syncml_timestamp FROM horde_syncml_map '
            . 'WHERE syncml_syncpartner = ? AND syncml_db = ? AND '
            . 'syncml_uid = ? AND syncml_suid = ?';
        $values = array($this->_syncDeviceID, $database, $this->_user, $suid);
        return $this->_db->selectValue($query, $values);
    }

    /**
     * Erases all mapping entries for one combination of user, device ID.
     *
     * This is used during SlowSync so that we really sync everything properly
     * and no old mapping entries remain.
     *
     * @param string $databaseURI  URI of database to sync. Like calendar,
     *                             tasks, contacts or notes. May include
     *                             optional parameters:
     *                             tasks?options=ignorecompleted.
     */
    public function eraseMap($databaseURI)
    {
        $database = $this->normalize($databaseURI);
        $query = 'DELETE FROM horde_syncml_map '
            . 'WHERE syncml_syncpartner = ? AND syncml_db = ? AND '
            . 'syncml_uid = ?';
        $values = array($this->_syncDeviceID, $database, $this->_user);
        $this->_db->delete($query, $values);
    }

    /**
     * Logs a message in the backend.
     *
     * @param mixed $message    Either a string or a PEAR_Error object.
     * @param string $priority  The priority of the message. One of:
     *                           - EMERG
     *                           - ALERT
     *                           - CRIT
     *                           - ERR
     *                           - WARN
     *                           - NOTICE
     *                           - INFO
     *                           - DEBUG
     */
    public function logMessage($message, $priority = 'INFO')
    {
        $trace = debug_backtrace();
        $trace = $trace[1];

        // Internal logging to $this->_logtext.
        parent::logMessage($message, $priority);

        // Logging to Horde log:
        Horde::logMessage($message, $priority, array('file' => $trace['file'], 'line' => $trace['line']));
    }

    /**
     * Creates a clean test environment in the backend.
     *
     * Ensures there's a user with the given credentials and an empty data
     * store.
     *
     * @param string $user This user accout has to be created in the backend.
     * @param string $pwd  The password for user $user.
     *
     * @throws Horde_Exception
     */
    public function testSetup($user, $pwd)
    {
        $this->_user = $user;
        if (empty($pwd)) {
            $pwd = rand() . rand();
        }

        /* Get an Auth object. */
        $auth = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create();

        /* Make this user an admin for the time beeing to allow deletion of
         * user data. */
        $GLOBALS['conf']['auth']['admins'][] = $user;

        /* Always remove test user first. */
        if ($auth->exists($user)) {
            $GLOBALS['registry']->removeUser($user);
        }

        $auth->addUser($user, array('password' => $pwd));
    }

    /**
     * Prepares the test start.
     *
     * @param string $user This user accout has to be created in the backend.
     */
    public function testStart($user)
    {
        $this->_user = $user;

        /* Make this user an admin for the time beeing to allow deletion of
         * user data. */
        $GLOBALS['conf']['auth']['admins'][] = $user;

        $GLOBALS['registry']->setAuth($user, array());
    }

    /**
     * Tears down the test environment after the test is run.
     *
     * Should remove the testuser created during testSetup and all its data.
     */
    public function testTearDown()
    {
        /* Get an Auth object. */
        try {
            $auth = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create();
        } catch (Horde_Exception $e) {
            // TODO
        }

        /* We need to be logged in to call removeUserData, otherwise we run
         * into permission issues. */
        $GLOBALS['registry']->setAuth($this->_user, array());

        print "\nCleaning up: removing test user data and test user...";
        $registry->removeUser($this->_user);

        print "OK\n";
    }
}
