<?php
/**
 * SyncML Backend for the Horde Application framework.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Karsten Fourmont <karsten@horde.org>
 * @package SyncML
 */
class SyncML_Backend_Horde extends SyncML_Backend {

    /**
     * A PEAR DB instance.
     *
     * @var DB
     */
    var $_db;

    /**
     * Constructor.
     *
     * Initializes the logger.
     *
     * @param array $params  Any parameters the backend might need.
     */
    function __construct($params)
    {
        parent::__construct($params);

        $this->_db = $GLOBALS['injector']->getInstance('Horde_Core_Factory_DbPear')->create();
    }

    /**
     * Sets the user used for this session.
     *
     * @param string $user  A user name.
     */
    function setUser($user)
    {
        parent::setUser($user);

        if ($this->_backendMode == SYNCML_BACKENDMODE_TEST) {
            /* After a session the user gets automatically logged out, so we
             * have to login again. */
            Horde_Auth::setAuth($this->_user, array());
        }
    }

    /**
     * Starts a PHP session.
     *
     * @param string $syncDeviceID  The device ID.
     * @param string $session_id    The session ID to use.
     * @param integer $backendMode  The backend mode, one of the
     *                              SYNCML_BACKENDMODE_* constants.
     */
    function sessionStart($syncDeviceID, $sessionId,
                          $backendMode = SYNCML_BACKENDMODE_SERVER)
    {
        $this->_backendMode = $backendMode;

        /* Only the server needs to start a session. */
        if ($this->_backendMode == SYNCML_BACKENDMODE_SERVER) {
            /* Reload the Horde SessionHandler if necessary. */
            $GLOBALS['session'] = new Horde_Session(false);
        }

        parent::sessionStart($syncDeviceID, $sessionId, $backendMode);
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
     * @return mixed  True on success or a PEAR_Error object.
     */
    function getServerChanges($databaseURI, $from_ts, $to_ts, &$adds, &$mods,
                              &$dels)
    {
        global $registry;

        $adds = $mods = $dels = array();
        $database = $this->_normalize($databaseURI);
        $slowsync = $from_ts == 0;

        // Handle additions:
        try {
            if ($slowsync) {
                // Return all db entries directly rather than bother history. But
                // first check if we only want to sync data from a given start
                // date:
                $start = trim(SyncML_Backend::getParameter($databaseURI, 'start'));
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
                                SyncML_Backend::getParameter($databaseURI, 'source'), $start);
                } else {
                    $data = $registry->{$database}->listUids(
                                SyncML_Backend::getParameter($databaseURI, 'source'));
                }
            } else {
                $data = $registry->{$database}->listBy(
                    'add', $from_ts, SyncML_Backend::getParameter($databaseURI, 'source'), $to_ts);
            }
        } catch (Horde_Exception $e) {
            $this->logMessage("$database/list or $database/listBy failed while retrieving server additions:"
                              . $e->getMessage(), 'ERR');
            return;
        }

        $add_ts = array();
        foreach ($data as $suid) {
            // Only server needs to check for client sent entries:
            if ($this->_backendMode != SYNCML_BACKENDMODE_SERVER) {
                $adds[$suid] = 0;
                continue;
            }

            if ($slowsync) {
                // SlowSync: Ignore all entries where there already in a
                // map entry.
                $cuid = $this->_getCuid($database, $suid);
                if ($cuid) {
                    $this->logMessage(
                        "Added to server from client during SlowSync: $suid ignored", 'DEBUG');
                    continue;
                }
            }
            try {
                $add_ts[$suid] = $registry->{$database}->getActionTimestamp(
                    $suid, 'add', SyncML_Backend::getParameter($databaseURI, 'source'));
            } catch (Horde_Exception $e) {
                $this->logMessage($e->getMessage(), 'ERR');
                return;
            }

            $sync_ts = $this->_getChangeTS($database, $suid);
            if ($sync_ts && $sync_ts >= $add_ts[$suid]) {
                // Change was done by us upon request of client.  Don't mirror
                // that back to the client.
                $this->logMessage("Added to server from client: $suid ignored", 'DEBUG');
                continue;
            }
            $this->logMessage(
                "Adding to client from db $database, server id $suid", 'DEBUG');

            $adds[$suid] = 0;
        }

        // On SlowSync: everything is sent as add, no need to send
        // modifications or deletions. So we are finished here:
        if ($slowsync) {
            return true;
        }

        // Handle changes:
        try {
            $data = $registry->$database->listBy(
               'modify', $from_ts, SyncML_Backend::getParameter($databaseURI,'source'), $to_ts);
        } catch (Horde_Exception $e) {
            $this->logMessage(
                "$database/listBy failed while retrieving server modifications:"
                . $e->getMessage(), 'WARN');
            return;
        }

        $mod_ts = array();
        foreach ($data as $suid) {
            // Check if the entry has been added after the last sync.
            if (isset($adds[$suid])) {
                continue;
            }

            // Only server needs to check for client sent entries and update
            // map.
            if ($this->_backendMode == SYNCML_BACKENDMODE_SERVER) {
                $mod_ts[$suid] = $registry->$database->getActionTimestamp(
                                     $suid, 'modify', SyncML_Backend::getParameter($databaseURI,'source'));
                $sync_ts = $this->_getChangeTS($database, $suid);
                if ($sync_ts && $sync_ts >= $mod_ts[$suid]) {
                    // Change was done by us upon request of client.  Don't
                    // mirror that back to the client.
                    $this->logMessage("Changed on server after sent from client: $suid ignored", 'DEBUG');
                    continue;
                }
                $cuid = $this->_getCuid($database, $suid);
                if (!$cuid) {
                    $this->logMessage(
                        "Unable to create change for server id $suid: client id not found in map, adding instead.", 'WARN');
                    $adds[$suid] = 0;
                    continue;
                } else {
                    $mods[$suid] = $cuid;
                }
            } else {
                $mods[$suid] = $suid;
            }
            $this->logMessage(
                "Modifying on client from db $database, client id $cuid -> server id $suid", 'DEBUG');
        }

        // Handle deletions.
        try {
            $data = $registry->$database->listBy(
                        'delete', $from_ts, SyncML_Backend::getParameter($databaseURI, 'source'), $to_ts);
        } catch (Horde_Exception $e) {
            $this->logMessage(
                "$database/listBy failed while retrieving server deletions:"
                . $e->getMessage(), 'WARN');
            return;
        }

        foreach ($data as $suid) {
            // Only server needs to check for client sent entries.
            if ($this->_backendMode == SYNCML_BACKENDMODE_SERVER) {
                $suid_ts = $registry->$database->getActionTimestamp(
                    $suid, 'delete', SyncML_Backend::getParameter($databaseURI,'source'));

                // Check if the entry has been added or modified after the
                // last sync.
                if (isset($adds[$suid]) && $add_ts[$suid] < $suid_ts) {
                    unset($adds[$suid]);
                    continue;
                }
                if (isset($mods[$suid])) {
                    unset($mods[$suid]);
                }

                $sync_ts = $this->_getChangeTS($database, $suid);
                if ($sync_ts && $sync_ts >= $suid_ts) {
                    // Change was done by us upon request of client.  Don't
                    // mirror that back to the client.
                    $this->logMessage("Deleted on server after request from client: $suid ignored", 'DEBUG');
                    continue;
                }
                $cuid = $this->_getCuid($database, $suid);
                if (!$cuid) {
                    $this->logMessage(
                        "Unable to create delete for server id $suid: client id not found in map", 'WARN');
                    continue;
                }
                $dels[$suid] = $cuid;
            } else {
                $dels[$suid] = $suid;
            }
            $this->logMessage(
                "Deleting on client from db $database, client id $cuid -> server id $suid", 'DEBUG');
        }

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
     *                             function should return the data.
     * @param array $fields        Hash of field names and SyncML_Property
     *                             properties with the requested fields.
     *
     * @return mixed  A string with the data entry or a PEAR_Error object.
     */
    function retrieveEntry($databaseURI, $suid, $contentType, $fields)
    {
        return $GLOBALS['registry']->call(
            $this->_normalize($databaseURI) . '/export',
            array('guid' => $suid, 'contentType' => $contentType, 'dummy' => null, 'fields' => $fields));
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
    function addEntry($databaseURI, $content, $contentType, $cuid = null)
    {
        global $registry;

        $database = $this->_normalize($databaseURI);

        $suid = $registry->call(
            $database . '/import',
            array($content,
                  $contentType,
                  SyncML_Backend::getParameter($databaseURI, 'source')));

        if (!is_a($suid, 'PEAR_Error')) {
            $this->logMessage(
                "Added to server db $database client id $cuid -> server id $suid", 'DEBUG');
            $ts = $registry->call(
                $database . '/getActionTimestamp',
                array($suid,
                      'add',
                      SyncML_Backend::getParameter($databaseURI, 'source')));
            if (!$ts) {
                $this->logMessage(
                    "Unable to find addition timestamp for server id $suid at $ts", 'ERR');
            }
            // Only server needs to do a cuid<->suid map
            if ($this->_backendMode == SYNCML_BACKENDMODE_SERVER) {
                $this->createUidMap($database, $cuid, $suid, $ts);
            }
        } else {
            // Failed import. Maybe the entry is already there. Check if a
            // guid is returned:
            if ($suid->getDebugInfo()) {
                $suid = $suid->getDebugInfo();
                $this->logMessage(
                    'Adding client entry to server: already exists with server id ' . $suid, 'NOTICE');
                if ($this->_backendMode == SYNCML_BACKENDMODE_SERVER) {
                    $this->createUidMap($database, $cuid, $suid, 0);
                }
            }

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
    function replaceEntry($databaseURI, $content, $contentType, $cuid)
    {
        global $registry;

        $database = $this->_normalize($databaseURI);

        // Only server needs to do a cuid<->suid map
        if ($this->_backendMode == SYNCML_BACKENDMODE_SERVER) {
            $suid = $this->_getSuid($database, $cuid);
        } else {
            $suid = $cuid;
        }

        if (!$suid) {
            return PEAR::raiseError("No map entry found for client id $cuid replacing on server");
        }

        // Entry exists: replace current one.
        $ok = $registry->call($database . '/replace',
                              array($suid, $content, $contentType));
        if (is_a($ok, 'PEAR_Error')) {
            return $ok;
        }
        $this->logMessage(
            "Replaced in server db $database client id $cuid -> server id $suid", 'DEBUG');
        $ts = $registry->call(
            $database . '/getActionTimestamp',
            array($suid,
                  'modify',
                  SyncML_Backend::getParameter($databaseURI,'source')));
        // Only server needs to do a cuid<->suid map
        if ($this->_backendMode == SYNCML_BACKENDMODE_SERVER) {
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
    function deleteEntry($databaseURI, $cuid)
    {
        global $registry;

        $database = $this->_normalize($databaseURI);
        // Find server ID for this entry:
        // Only server needs to do a cuid<->suid map
        if ($this->_backendMode == SYNCML_BACKENDMODE_SERVER) {
            $suid = $this->_getSuid($database, $cuid);
        } else {
            $suid = $cuid;
        }
        if (is_a($suid, 'PEAR_Error')) {
            return false;
        }

        $r = $registry->call($database. '/delete', array($suid));
        if (is_a($r, 'PEAR_Error')) {
            return false;
        }

        $this->logMessage(
            "Deleted in server db $database client id $cuid -> server id $suid", 'DEBUG');
        $ts = $registry->call($database . '/getActionTimestamp',
                              array($suid, 'delete'));
        // We can't remove the mapping entry as we need to keep the timestamp
        // information.
        // Only server needs to do a cuid<->suid map
        if ($this->_backendMode == SYNCML_BACKENDMODE_SERVER) {
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
    function _checkAuthentication($username, $password)
    {
        $auth = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create();
        return $auth->authenticate($username, array('password' => $password))
            ? $GLOBALS['registry']->getAuth()
            : false;
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
    function setAuthenticated($username, $credData)
    {
        Horde_Auth::setAuth($username, $credData);
        return $GLOBALS['registry']->getAuth();
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
    function writeSyncAnchors($databaseURI, $clientAnchorNext,
                              $serverAnchorNext)
    {
        $database = $this->_normalize($databaseURI);

        if (!$this->readSyncAnchors($databaseURI)) {
            $query = 'INSERT INTO horde_syncml_anchors '
                . '(syncml_clientanchor, syncml_serveranchor, '
                . 'syncml_syncpartner, syncml_db, syncml_uid) '
                . 'VALUES (?, ?, ?, ?, ?)';
        } else {
            $query = 'UPDATE horde_syncml_anchors '
                . 'SET syncml_clientanchor = ?, syncml_serveranchor = ? '
                . 'WHERE syncml_syncpartner = ? AND syncml_db = ? AND '
                . 'syncml_uid = ?';
        }
        $values = array($clientAnchorNext, $serverAnchorNext,
                        $this->_syncDeviceID, $database, $this->_user);

        $this->logMessage(
            'SQL Query by SyncML_Backend_Horde::writeSyncAnchors(): '
            . $query . ', values: ' . implode(', ', $values), 'DEBUG');

        return $this->_db->query($query, $values);
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
    function readSyncAnchors($databaseURI)
    {
        $database = $this->_normalize($databaseURI);

        $query = 'SELECT syncml_clientanchor, syncml_serveranchor '
            . 'FROM horde_syncml_anchors '
            . 'WHERE syncml_syncpartner = ? AND syncml_db = ? AND '
            . 'syncml_uid = ?';
        $values = array($this->_syncDeviceID, $database, $this->_user);

        $this->logMessage(
            'SQL Query by SyncML_Backend_Horde::readSyncAnchors(): '
            . $query . ', values: ' . implode(', ', $values), 'DEBUG');
        $result = $this->_db->getRow($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            $this->logMessage($result, 'ERR');
        }

        return $result;
    }

    /**
     * Returns all previously written sync anchors for a user.
     *
     * @param string $user  A user name.
     *
     * @return array  A hash tree with all devices, databases and sync anchors
     *                from the specified user.
     */
    function getUserAnchors($user)
    {
        $query = 'SELECT syncml_syncpartner, syncml_db, syncml_clientanchor, '
            . 'syncml_serveranchor FROM horde_syncml_anchors '
            . 'WHERE syncml_uid = ?';
        $values = array($user);

        $this->logMessage(
            'SQL Query by SyncML_Backend_Horde::getUserAnchors(): '
            . $query . ', values: ' . implode(', ', $values), 'DEBUG');
        $result = $this->_db->getAssoc($query, false, $values,
                                       DB_FETCHMODE_ASSOC, true);
        if (is_a($result, 'PEAR_Error')) {
            $this->logMessage($result, 'ERR');
        }

        return $result;
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
    function removeAnchor($user, $device = null, $database = null)
    {
        $query = 'DELETE FROM horde_syncml_anchors '
            . 'WHERE syncml_uid = ?';
        $values = array($user);
        if (strlen($device)) {
            $query .= ' AND syncml_syncpartner = ?';
            $values[] = $device;
        }
        if (strlen($database)) {
            $query .= ' AND syncml_db = ?';
            $values[] = $database;
        }

        $this->logMessage(
            'SQL Query by SyncML_Backend_Horde::removeAnchor(): '
            . $query . ', values: ' . implode(', ', $values), 'DEBUG');
        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            $this->logMessage($result, 'ERR');
        }

        return $result;
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
    function createUidMap($databaseURI, $cuid, $suid, $timestamp = 0)
    {
        $database = $this->_normalize($databaseURI);

        // Check if entry exists. If not insert, otherwise update.
        if (!$this->_getSuid($databaseURI, $cuid)) {
            $query = 'INSERT INTO horde_syncml_map '
                . '(syncml_suid, syncml_timestamp, syncml_syncpartner, '
                . 'syncml_db, syncml_uid, syncml_cuid) '
                . 'VALUES (?, ?, ?, ?, ?, ?)';
        } else {
            $query = 'UPDATE horde_syncml_map '
                . 'SET syncml_suid = ?, syncml_timestamp = ? '
                . 'WHERE syncml_syncpartner = ? AND syncml_db = ? AND '
                . 'syncml_uid = ? AND syncml_cuid = ?';
        }
        $values = array($suid, (int)$timestamp, $this->_syncDeviceID,
                        $database, $this->_user, $cuid);

        $this->logMessage('SQL Query by SyncML_Backend_Horde::createUidMap(): '
                          . $query . ', values: ' . implode(', ', $values), 'DEBUG');
        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            $this->logMessage($result, 'ERR');
            return $result;
        }

        return true;
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
    function _getSuid($databaseURI, $cuid)
    {
        $database = $this->_normalize($databaseURI);

        $query = 'SELECT syncml_suid FROM horde_syncml_map '
            . 'WHERE syncml_syncpartner = ? AND syncml_db = ? AND '
            . 'syncml_uid = ? AND syncml_cuid = ?';
        $values = array($this->_syncDeviceID, $database, $this->_user, $cuid);

        $this->logMessage('SQL Query by SyncML_Backend_Horde::_getSuid(): '
                          . $query . ', values: ' . implode(', ', $values), 'DEBUG');
        $result = $this->_db->getOne($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            $this->logMessage($result, 'ERR');
        }

        return $result;
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
    function _getCuid($databaseURI, $suid)
    {
        $database = $this->_normalize($databaseURI);

        $query = 'SELECT syncml_cuid FROM horde_syncml_map '
            . 'WHERE syncml_syncpartner = ? AND syncml_db = ? AND '
            . 'syncml_uid = ? AND syncml_suid = ?';
        $values = array($this->_syncDeviceID, $database, $this->_user, $suid);

        $this->logMessage('SQL Query by SyncML_Backend_Horde::_getCuid(): '
                          . $query . ', values: ' . implode(', ', $values), 'DEBUG');
        $result = $this->_db->getOne($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            $this->logMessage($result, 'ERR');
        }

        return $result;
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
    function _getChangeTS($databaseURI, $suid)
    {
        $database = $this->_normalize($databaseURI);

        $query = 'SELECT syncml_timestamp FROM horde_syncml_map '
            . 'WHERE syncml_syncpartner = ? AND syncml_db = ? AND '
            . 'syncml_uid = ? AND syncml_suid = ?';
        $values = array($this->_syncDeviceID, $database, $this->_user, $suid);

        $this->logMessage('SQL Query by SyncML_Backend_Horde::_getChangeTS(): '
                          . $query . ', values: ' . implode(', ', $values), 'DEBUG');
        $result = $this->_db->getOne($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            $this->logMessage($result, 'ERR');
        }

        return $result;
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
    function eraseMap($databaseURI)
    {
        $database = $this->_normalize($databaseURI);

        $query = 'DELETE FROM horde_syncml_map '
            . 'WHERE syncml_syncpartner = ? AND syncml_db = ? AND '
            . 'syncml_uid = ?';
        $values = array($this->_syncDeviceID, $database, $this->_user);

        $this->logMessage('SQL Query by SyncML_Backend_Horde::eraseMap(): '
                          . $query . ', values: ' . implode(', ', $values), 'DEBUG');
        $result = $this->_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            $this->logMessage($result, 'ERR');
        }

        return $result;
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
    function logMessage($message, $priority = 'INFO')
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
    function testSetup($user, $pwd)
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
            /* We need to be logged in to call removeUserData, otherwise we
             * run into permission issues. */
            Horde_Auth::setAuth($user, array());
            try {
                Horde_Auth::removeUserData($user);
            } catch (Horde_Exception $e) {
                // TODO
            }
            $auth->removeUser($user);
        }

        $auth->addUser($user, array('password' => $pwd));
    }

    /**
     * Prepares the test start.
     *
     * @param string $user This user accout has to be created in the backend.
     */
    function testStart($user)
    {
        $this->_user = $user;

        /* Make this user an admin for the time beeing to allow deletion of
         * user data. */
        $GLOBALS['conf']['auth']['admins'][] = $user;

        Horde_Auth::setAuth($user, array());
    }

    /**
     * Tears down the test environment after the test is run.
     *
     * Should remove the testuser created during testSetup and all its data.
     */
    function testTearDown()
    {
        /* Get an Auth object. */
        try {
            $auth = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create();
        } catch (Horde_Exception $e) {
            // TODO
        }

        /* We need to be logged in to call removeUserData, otherwise we run
         * into permission issues. */
        Horde_Auth::setAuth($this->_user, array());

        print "\nCleaning up: removing test user data and test user...";
        Horde_Auth::removeUserData($this->_user);
        $auth->removeUser($this->_user);

        print "OK\n";
    }

}
