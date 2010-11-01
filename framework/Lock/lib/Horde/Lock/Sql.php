<?php
/**
 * The Horde_Lock_Sql driver implements a storage backend for the Horde_Lock
 * API.
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
 * Copyright 2008-2010 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-license.php.
 *
 * @author   Ben Klang <bklang@horde.org>
 * @category Horde
 * @package  Lock
 */
class Horde_Lock_Sql extends Horde_Lock
{
    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    private $_db;

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     * 'db' - (Horde_Db_Adapter) [REQUIRED] The DB instance.
     * 'table' - (string) The name of the lock table in 'database'.
     *           DEFAULT: 'horde_locks'
     * </pre>
     *
     * @throws Horde_Lock_Exception
     */
    public function __construct($params = array())
    {
        if (!isset($params['db'])) {
            throw new Horde_Lock_Exception('Missing db parameter.');
        }
        $this->_db = $params['db'];
        unset($params['db']);

        $params = array_merge(array(
            'table' => 'horde_locks'
        ), $params);

        parent::__construct($params);

        /* Only do garbage collection if asked for, and then only 0.1% of the
         * time we create an object. */
        if (rand(0, 999) == 0) {
            register_shutdown_function(array($this, 'doGC'));
        }
    }

    /**
     * Return an array of information about the requested lock.
     *
     * @see Horde_Lock_Base::getLockInfo()
     */
    public function getLockInfo($lockid)
    {
        $now = time();
        $sql = 'SELECT lock_id, lock_owner, lock_scope, lock_principal, ' .
               'lock_origin_timestamp, lock_update_timestamp, ' .
               'lock_expiry_timestamp, lock_type FROM ' . $this->_params['table'] .
               ' WHERE lock_id = ? AND lock_expiry_timestamp >= ?';
        $values = array($lockid, $now);

        try {
            return $this->_db->selectOne($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Lock_Exception($e);
        }
    }

    /**
     * Return a list of valid locks with the option to limit the results
     * by principal, scope and/or type.
     *
     * @see Horde_Lock_Base::getLocks()
     */
    public function getLocks($scope = null, $principal = null, $type = null)
    {
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

        try {
            $result = $this->_db->selectAll($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Lock_Exception($e);
        }

        $locks = array();
        foreach ($result as $row) {
            $locks[$row['lock_id']] = $row;
        }

        return $locks;
    }

    /**
     * Extend the valid lifetime of a valid lock to now + $newtimeout.
     *
     * @see Horde_Lock_Base::resetLock()
     */
    public function resetLock($lockid, $extend)
    {
        $now = time();

        if (!$this->getLockInfo($lockid)) {
            return false;
        }

        $expiry = $now + $extend;
        $sql = 'UPDATE ' . $this->_params['table'] . ' SET ' .
               'lock_update_timestamp = ?, lock_expiry_timestamp = ? ' .
               'WHERE lock_id = ?';
        $values = array($now, $expiry, $lockid);

        try {
            $this->_db->update($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Lock_Exception($e);
        }

        return true;
    }

    /**
     * Sets a lock on the requested principal and returns the generated lock
     * ID.
     *
     * @see Horde_Lock_Base::setLock()
     */
    public function setLock($requestor, $scope, $principal,
                            $lifetime = 1, $type = Horde_Lock::TYPE_SHARED)
    {
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

        try {
            $this->_db->insert($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Lock_Exception($e);
        }

        if ($this->_logger) {
            $this->_logger->log(sprintf('Lock %s set successfully by %s in scope %s on "%s"', $lockid, $requestor, $scope, $principal), 'DEBUG');
        }

        return $lockid;
    }

    /**
     * Removes a lock given the lock ID.
     *
     * @see Horde_Lock_Base::clearLock()
     */
    public function clearLock($lockid)
    {
        if (empty($lockid)) {
            throw new Horde_Lock_Exception('Must supply a valid lock ID.');
        }

        // Since we're trying to clear the lock we don't care
        // whether it is still valid or not.  Unconditionally
        // remove it.
        $sql = 'DELETE FROM ' . $this->_params['table'] . ' WHERE lock_id = ?';
        $values = array($lockid);

        try {
            $this->_db->delete($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Lock_Exception($e);
        }

        if ($this->_logger) {
            $this->_logger->log(sprintf('Lock %s cleared successfully.', $lockid), 'DEBUG');
        }

        return true;
    }

    /**
     * Do garbage collection needed for the driver.
     */
    public function doGC()
    {
        $now = time();
        $query = 'DELETE FROM ' . $this->_params['table'] . ' WHERE ' .
                 'lock_expiry_timestamp < ? AND lock_expiry_timestamp != 0';
        $values = array($now);

        try {
            $result = $this->_db->delete($query, $values);
            if ($this->_logger) {
                $this->_logger->log(sprintf('Lock garbage collection cleared %d locks.', $result), 'DEBUG');
            }
        } catch (Horde_Db_Exception $e) {}
    }

}
