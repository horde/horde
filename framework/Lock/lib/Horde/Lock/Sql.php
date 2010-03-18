<?php
/**
 * The Horde_Lock_Sql driver implements a storage backend for the Horde_Lock
 * API.
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
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-license.php.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Horde_Lock
 */
class Horde_Lock_Sql extends Horde_Lock_Driver
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
        $this->_params = array_merge(array(
            'database' => '',
            'hostspec' => '',
            'password' => '',
            'table' => 'horde_locks',
            'username' => ''
        ), $params);

        /* Only do garbage collection if asked for, and then only 0.1% of the
         * time we create an object. */
        if (rand(0, 999) == 0) {
            register_shutdown_function(array($this, '_doGC'));
        }

        parent::__construct($this->_params);
    }

    /**
     * Return an array of information about the requested lock.
     *
     * @see Horde_Lock_Driver::getLockInfo()
     */
    public function getLockInfo($lockid)
    {
        $this->_connect();

        $now = time();
        $sql = 'SELECT lock_id, lock_owner, lock_scope, lock_principal, ' .
               'lock_origin_timestamp, lock_update_timestamp, ' .
               'lock_expiry_timestamp, lock_type FROM ' . $this->_params['table'] .
               ' WHERE lock_id = ? AND lock_expiry_timestamp >= ?';
        $values = array($lockid, $now);

        if ($this->_logger) {
            $this->_logger->log('SQL Query by Horde_Lock_sql::getLockInfo(): ' . $sql, 'DEBUG');
        }

        $result = $this->_db->query($sql, $values);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Lock_Exception($result);
        }

        $locks = array();
        $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        if ($row instanceof PEAR_Error) {
            return false;
        }

        $result->free();
        return $row;
    }

    /**
     * Return a list of valid locks with the option to limit the results
     * by principal, scope and/or type.
     *
     * @see Horde_Lock_Driver::getLocks()
     */
    public function getLocks($scope = null, $principal = null, $type = null)
    {
        $this->_connect();

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
        if (!empty($scope)) {
            $sql .= ' AND lock_scope = ?';
            $values[] = $scope;
        }
        if (!empty($type)) {
            $sql .= ' AND lock_type = ?';
            $values[] = $type;
        }

        if ($this->_logger) {
            $this->_logger->log('SQL Query by Horde_Lock_sql::getLocks(): ' . $sql, 'DEBUG');
        }

        $result = $this->_db->query($sql, $values);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Lock_Exception($result);
        }

        $locks = array();
        $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
        while ($row && !($row instanceof PEAR_Error)) {
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
     * @see Horde_Lock_Driver::resetLock()
     */
    public function resetLock($lockid, $extend)
    {
        $this->_connect();

        $now = time();

        if (!$this->getLockInfo($lockid)) {
            return false;
        }

        $expiry = $now + $extend;
        $sql = 'UPDATE ' . $this->_params['table'] . ' SET ' .
               'lock_update_timestamp = ?, lock_expiry_timestamp = ? ' .
               'WHERE lock_id = ?';
        $values = array($now, $expiry, $lockid);

        if ($this->_logger) {
            $this->_logger->log('SQL Query by Horde_Lock_sql::resetLock(): ' . $sql, 'DEBUG');
        }

        $result = $this->_write_db->query($sql, $values);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Lock_Exception($result);
        }

        if ($this->_logger) {
            $this->_logger->log(sprintf('Lock %s reset successfully.', $lockid), 'DEBUG');
        }
        return true;
    }

    /**
     * Sets a lock on the requested principal and returns the generated lock
     * ID.
     *
     * @see Horde_Lock_Driver::setLock()
     */
    public function setLock($requestor, $scope, $principal,
                            $lifetime = 1, $type = Horde_Lock::TYPE_SHARED)
    {
        $this->_connect();

        $oldlocks = $this->getLocks($scope, $principal, Horde_Lock::TYPE_EXCLUSIVE);

        if (count($oldlocks) != 0) {
            // An exclusive lock exists.  Deny the new request.
            if ($this->_logger) {
                $this->_logger->log(sprintf('Lock requested for %s denied due to existing exclusive lock.', $principal), 'NOTICE');
            }
            return false;
        }

        $lockid = (string)new Horde_Support_Uuid();

        $now = time();
        $expiration = $now + $lifetime;
        $sql = 'INSERT INTO ' . $this->_params['table'] . ' (lock_id, lock_owner, lock_scope, lock_principal, lock_origin_timestamp, lock_update_timestamp, lock_expiry_timestamp, lock_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $values = array($lockid, $requestor, $scope, $principal, $now, $now,
                        $expiration, $type);

        if ($this->_logger) {
            $this->_logger->log('SQL Query by Horde_Lock_sql::setLock(): ' . $sql, 'DEBUG');
        }

        $result = $this->_write_db->query($sql, $values);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Lock_Exception($result);
        }

        if ($this->_logger) {
            $this->_logger->log(sprintf('Lock %s set successfully by %s in scope %s on "%s"', $lockid, $requestor, $scope, $principal), 'DEBUG');
        }

        return $lockid;
    }

    /**
     * Removes a lock given the lock ID.
     *
     * @see Horde_Lock_Driver::clearLock()
     */
    public function clearLock($lockid)
    {
        $this->_connect();

        if (empty($lockid)) {
            throw new Horde_Lock_Exception('Must supply a valid lock ID.');
        }

        // Since we're trying to clear the lock we don't care
        // whether it is still valid or not.  Unconditionally
        // remove it.
        $sql = 'DELETE FROM ' . $this->_params['table'] . ' WHERE lock_id = ?';
        $values = array($lockid);

        if ($this->_logger) {
            $this->_logger->log('SQL Query by Horde_Lock_sql::clearLock(): ' . $sql, 'DEBUG');
        }

        $result = $this->_write_db->query($sql, $values);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Lock_Exception($result);
        }

        if ($this->_logger) {
            $this->_logger->log(sprintf('Lock %s cleared successfully.', $lockid), 'DEBUG');
        }

        return true;
    }

    /**
     * Opens a connection to the SQL server.
     */
    private function _connect()
    {
        if ($this->_connected) {
            return;
        }

        try {
            Horde_Util::assertDriverConfig($this->_params, array('phptype'), 'Lock SQL');
        } catch (Horde_Exception $e) {
            if ($this->_logger) {
                $this->_logger->log($e, 'ERR');
            }
            throw new Horde_Lock_Exception($e);
        }

        $this->_write_db = DB::connect(
            $this->_params,
            array('persistent' => !empty($this->_params['persistent']),
                  'ssl' => !empty($this->_params['ssl']))
        );
        if ($this->_write_db instanceof PEAR_Error) {
            if ($this->_logger) {
                $this->_logger->log($this->_write_db, 'ERR');
            }
            throw new Horde_Lock_Exception($this->_write_db);
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
            $this->_db = DB::connect(
                $params,
                array('persistent' => !empty($params['persistent']),
                      'ssl' => !empty($params['ssl']))
            );
            if ($this->_db instanceof PEAR_Error) {
                if ($this->_logger) {
                    $this->_logger->log($this->_db, 'ERR');
                }
                throw new Horde_Lock_Exception($this->_db);
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
    }

    /**
     * Do garbage collection needed for the driver.
     */
    private function _doGC()
    {
        try {
            $this->_connect();
        } catch (Horde_Lock_Exception $e) {
            return;
        }

        $now = time();
        $query = 'DELETE FROM ' . $this->_params['table'] . ' WHERE ' .
                 'lock_expiry_timestamp < ? AND lock_expiry_timestamp != 0';
        $values = array($now);

        $result = $this->_write_db->query($query, $values);
        if ($this->_logger) {
            $this->_logger->log('SQL Query by Horde_Lock_sql::_doGC(): ' .  $sql, 'DEBUG');
            if ($result instanceof PEAR_Error) {
                $this->_logger->log($result, 'ERR');
            } else {
                $this->_logger->log(sprintf('Lock garbage collection cleared %d locks.', $this->_write_db->affectedRows()), 'DEBUG');
            }
        }
    }

}
