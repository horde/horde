<?php
/**
 * The Horde_Lock_sql driver implements a storage backend for the Horde_Lock API
 *
 * Required parameters:<pre>
 *   'phptype'      The database type (ie. 'pgsql', 'mysql', etc.).</pre>
 *
 * Required by some database implementations:<pre>
 *   'database'     The name of the database.
 *   'hostspec'     The hostname of the database server.
 *   'username'     The username with which to connect to the database.
 *   'password'     The password associated with 'username'.
 *   'options'      Additional options to pass to the database.
 *   'tty'          The TTY on which to connect to the database.
 *   'port'         The port on which to connect to the database.</pre>
 *
 * Optional parameters:<pre>
 *   'table'               The name of the lock table in 'database'.
 *                         Defaults to 'horde_locks'.
 *
 * Optional values when using separate reading and writing servers, for example
 * in replication settings:<pre>
 *   'splitread'   Boolean, whether to implement the separation or not.
 *   'read'        Array containing the parameters which are different for
 *                 the read database connection, currently supported
 *                 only 'hostspec' and 'port' parameters.</pre>
 *
 * The table structure for the locks is as follows:
 * <pre>
 * CREATE TABLE horde_locks (
 *     lock_id                  VARCHAR(36) NOT NULL,
 *     lock_owner               VARCHAR(32) NOT NULL,
 *     lock_scope               VARCHAR(32) NOT NULL,
 *     lock_principal           VARCHAR(255) NOT NULL,
 *     lock_origin_timestamp    BIGINT NOT NULL,
 *     lock_update_timestamp    BIGINT NOT NULL,
 *     lock_expiry_timestamp    BIGINT NOT NULL,
 *     lock_type                TINYINT NOT NULL,
 *
 *     PRIMARY KEY (lock_id)
 * );
 * </pre>
 *
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-license.php.
 *
 * $Horde: framework/Lock/Lock/sql.php,v 1.19 2009/06/09 23:23:40 slusarz Exp $
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @since   Horde 3.2
 * @package Horde_Lock
 */
class Horde_Lock_Sql extends Horde_Lock
{
    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    private $_db;

    /**
     * Handle for the current database connection, used for writing. Defaults
     * to the same handle as $_db if a separate write database isn't required.
     *
     * @var DB
     */
    private $_write_db;

    /**
     * Boolean indicating whether or not we're connected to the SQL server.
     *
     * @var boolean
     */
    private $_connected = false;

    /**
     * Constructs a new Horde_Lock_sql object.
     *
     * @param array $params  A hash containing configuration parameters.
     */
    public function __construct($params = array())
    {
        $options = array(
            'database' => '',
            'username' => '',
            'password' => '',
            'hostspec' => '',
            'table' => '',
        );
        $this->_params = array_merge($options, $params);
        if (empty($this->_params['table'])) {
            $this->_params['table'] = 'horde_locks';
        }

        /* Only do garbage collection if asked for, and then only 0.1% of the
         * time we create an object. */
        if (rand(0, 999) == 0) {
            register_shutdown_function(array(&$this, '_doGC'));
        }

        parent::__construct($this->_params);
    }

    /**
     * Return an array of information about the requested lock.
     *
     * @see Horde_Lock::getLockInfo
     */
    public function getLockInfo($lockid)
    {
       if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Lock_Exception(_("Internal database error.  Details have been logged for the administrator."));
        }

        $now = time();
        $sql = 'SELECT lock_id, lock_owner, lock_scope, lock_principal, ' .
               'lock_origin_timestamp, lock_update_timestamp, ' .
               'lock_expiry_timestamp, lock_type FROM ' . $this->_params['table'] .
               ' WHERE lock_id = ? AND lock_expiry_timestamp >= ?';
        $values = array($lockid, $now);

        Horde::logMessage('SQL Query by Horde_Lock_sql::getLockInfo(): ' . $sql,
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            throw new Horde_Lock_Exception($result->getMessage());
        }

        $locks = array();
        $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        if (is_a($row, 'PEAR_Error')) {
            return false;
        }

        $result->free();
        return $row;
    }

    /**
     * Return a list of valid locks with the option to limit the results
     * by principal, scope and/or type.
     *
     * @see Horde_Lock::getLocks
     */
    public function getLocks($scope = null, $principal = null, $type = null)
    {
        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Lock_Exception(_("Internal database error.  Details have been logged for the administrator."));
        }

        $now = time();
        $sql = 'SELECT lock_id, lock_owner, lock_scope, lock_principal, ' .
               'lock_origin_timestamp, lock_update_timestamp, ' .
               'lock_expiry_timestamp, lock_type FROM ' .
               $this->_params['table'] . ' WHERE lock_expiry_timestamp >= ?';
        $values = array($now);

        // Check to see if we need to filter the results
        if (!empty($principal)) {
            $sql .= ' AND lock_principal = ?';
            $values[] = $principal;
        }
        if(!empty($scope)) {
            $sql .= ' AND lock_scope = ?';
            $values[] = $scope;
        }
        if (!empty($type)) {
            $sql .= ' AND lock_type = ?';
            $values[] = $type;
        }

        Horde::logMessage('SQL Query by Horde_Lock_sql::getLocks(): ' . $sql,
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            throw new Horde_Lock_Exception($result->getMessage());
        }

        $locks = array();
        $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        while ($row && !is_a($row, 'PEAR_Error')) {
            $locks[$row['lock_id']] = $row;
            /* Advance to the new row in the result set. */
            $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        }
        $result->free();
        return $locks;
    }

    /**
     * Extend the valid lifetime of a valid lock to now + $newtimeout.
     *
     * @see Horde_Lock::resetLock
     */
    public function resetLock($lockid, $extend)
    {
       if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Lock_Exception(_("Internal database error.  Details have been logged for the administrator."));
        }

        $now = time();

        $lockinfo = $this->getLockInfo($lockid);
        if ($lockinfo === true) {
            $expiry = $now + $extend;
            $sql = 'UPDATE ' . $this->_params['table'] . ' SET ' .
                   'lock_update_timestamp = ?, lock_expiry_timestamp = ? ' .
                   'WHERE lock_id = ?';
            $values = array($now, $expiry, $lockid);

            Horde::logMessage('SQL Query by Horde_Lock_sql::resetLock(): ' .
                              $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);
            $result = $this->_write_db->query($sql, $values);
            if (is_a($result, 'PEAR_Error')) {
                throw new Horde_Lock_Exception($result->getMessage());
            }
            Horde::logMessage(sprintf('Lock %s reset successfully.', $lockid), __FILE__, __LINE__, PEAR_LOG_INFO);
            return true;
        } elseif (is_a($lockinfo, 'PEAR_Error')) {
            throw new Horde_Lock_Exception($lockinfo->getMessage());
        } else {
            // $lockinfo is false indicating the lock is no longer valid.
            return false;
        }
    }

    /**
     * Sets a lock on the requested principal and returns the generated lock ID.
     * NOTE: No security checks are done in the Horde_Lock API.  It is expected
     * that the calling application has done all necessary security checks
     * before requesting a lock be granted.
     *
     * @see Horde_Lock::setLock
     */
    public function setLock($requestor, $scope, $principal,
                     $lifetime = 1, $type = HORDE_LOCK_TYPE_SHARED)
    {
       if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Lock_Exception(_("Internal database error.  Details have been logged for the administrator."));
        }

        $oldlocks = $this->getLocks($scope, $principal,  HORDE_LOCK_TYPE_EXCLUSIVE);
        if (is_a($oldlocks, 'PEAR_Error')) {
            throw new Horde_Lock_Exception($oldlocks->getMessage());
        }

        if (count($oldlocks) != 0) {
            // An exclusive lock exists.  Deny the new request.
            Horde::logMessage(sprintf('Lock requested for %s denied due to existing exclusive lock.', $principal), __FILE__, __LINE__, PEAR_LOG_NOTICE);
            return false;
        }

        $lockid = (string)new Horde_Support_Uuid();

        $now = time();
        $expiration = $now + $lifetime;
        $sql = 'INSERT INTO ' . $this->_params['table'] . ' (lock_id, lock_owner, lock_scope, lock_principal, lock_origin_timestamp, lock_update_timestamp, lock_expiry_timestamp, lock_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $values = array($lockid, $requestor, $scope, $principal, $now, $now,
                        $expiration, $type);

        Horde::logMessage('SQL Query by Horde_Lock_sql::setLock(): ' . $sql,
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_write_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            throw new Horde_Lock_Exception($result->getMessage());
        }

        Horde::logMessage(sprintf('Lock %s set successfully by %s in scope %s on "%s"', $lockid, $requestor, $scope, $principal), __FILE__, __LINE__, PEAR_LOG_INFO);
        return $lockid;
    }

    /**
     * Removes a lock given the lock ID.
     * NOTE: No security checks are done in the Horde_Lock API.  It is expected
     * that the calling application has done all necessary security checks
     * before requesting a lock be cleared.
     *
     * @see Horde_Lock::clearLock
     */
    public function clearLock($lockid)
    {
        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Lock_Exception(_("Internal database error.  Details have been logged for the administrator."));
        }

        if (empty($lockid)) {
            throw new Horde_Lock_Exception(_("Must supply a valid lock ID."));
        }

        // Since we're trying to clear the lock we don't care
        // whether it is still valid or not.  Unconditionally
        // remove it.
        $sql = 'DELETE FROM ' . $this->_params['table'] . ' WHERE lock_id = ?';
        $values = array($lockid);

        Horde::logMessage('SQL Query by Horde_Lock_sql::clearLock(): ' . $sql,
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_write_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            throw new Horde_Lock_Exception($result->getMessage());
        }

        Horde::logMessage(sprintf('Lock %s cleared successfully.', $lockid), __FILE__, __LINE__, PEAR_LOG_INFO);
        return true;
    }

    /**
     * Opens a connection to the SQL server.
     *
     * @return boolean  True on success, a PEAR_Error object on failure.
     */
    private function _connect()
    {
        if ($this->_connected) {
            return true;
        }

        $result = Horde_Util::assertDriverConfig($this->_params, array('phptype'),
                                           'Lock SQL', array('driver' => 'lock'));
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Lock_Exception($result->getMessage());
        }

        require_once 'DB.php';
        $this->_write_db = &DB::connect(
            $this->_params,
            array('persistent' => !empty($this->_params['persistent']),
                  'ssl' => !empty($this->_params['ssl']))
        );
        if (is_a($this->_write_db, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Lock_Exception($this->_write_db->getMessage());
        }

        // Set DB portability options.
        $portability = DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS;
        if ($this->_write_db->phptype) {
            $portability |= DB_PORTABILITY_RTRIM;
        }
        $this->_write_db->setOption('portability', $portability);

        /* Check if we need to set up the read DB connection
         * seperately. */
        if (!empty($this->_params['splitread'])) {
            $params = array_merge($this->_params, $this->_params['read']);
            $this->_db = &DB::connect(
                $params,
                array('persistent' => !empty($params['persistent']),
                      'ssl' => !empty($params['ssl']))
            );
            if (is_a($this->_db, 'PEAR_Error')) {
                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                throw new Horde_Lock_Exception($this->_db->getMessage());
            }

            // Set DB portability options.
            $portability = DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS;
            if ($this->_db->phptype) {
                $portability |= DB_PORTABILITY_RTRIM;
            }
            $this->_db->setOption('portability', $portability);
        } else {
            /* Default to the same DB handle for read. */
            $this->_db = $this->_write_db;
        }

        $this->_connected = true;
        return true;
    }

    /**
     * Do garbage collection needed for the driver.
     *
     * @access private
     */
    private function _doGC()
    {
        if (is_a(($result = $this->_connect()), 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return false;
        }

        $now = time();

        $query = 'DELETE FROM ' . $this->_params['table'] . ' WHERE ' .
                 'lock_expiry_timestamp < ? AND lock_expiry_timestamp != 0';
        $values = array($now);

        Horde::logMessage('SQL Query by Horde_Lock_sql::_doGC(): ' .  $sql,
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return false;
        }

        Horde::logMessage(sprintf('Lock garbage collection cleared %d locks.', $this->_write_db->affectedRows()), __FILE__, __LINE__, PEAR_LOG_INFO);
        return true;
    }

}
