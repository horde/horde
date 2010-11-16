<?php
/**
 * This class provides cache storage in a SQL databsae.
 *
 * The table structure for the cache is as follows:
 * <pre>
 * CREATE TABLE horde_cache (
 *     cache_id          VARCHAR(32) NOT NULL,
 *     cache_timestamp   BIGINT NOT NULL,
 *     cache_data        LONGBLOB,
 *     (Or on PostgreSQL:)
 *     cache_data        TEXT,
 *     (Or on some other DBMS systems:)
 *     cache_data        IMAGE,
 *
 *     PRIMARY KEY (cache_id)
 * );
 * </pre>
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Ben Klang <ben@alkaloid.net>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Cache
 */
class Horde_Cache_Storage_Sql extends Horde_Cache_Storage
{
    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     * 'db' - (Horde_Db_Adapter) [REQUIRED] The DB instance.
     * 'table' - (string) The name of the cache table.
     *           DEFAULT: 'horde_cache'
     * </pre>
     *
     * @throws Horde_Cache_Exception
     */
    public function __construct($params = array())
    {
        if (!isset($params['db'])) {
            throw new Horde_Cache_Exception('Missing db parameter.');
        }
        $this->_db = $params['db'];
        unset($params['db']);

        $params = array_merge(array(
            'table' => 'horde_cache',
        ), $params);

        parent::__construct($params);
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        /* Only do garbage collection 0.1% of the time we create an object. */
        if (rand(0, 999) != 0) {
            return;
        }

        $query = 'DELETE FROM ' . $this->_params['table'] .
                 ' WHERE cache_expiration < ? AND cache_expiration <> 0';
        $values = array(time());

        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {}
    }

    /**
     */
    public function get($key, $lifetime)
    {
        $okey = $key;
        $key = hash('md5', $key);

        $timestamp = time();
        $maxage = $timestamp - $lifetime;

        /* Build SQL query. */
        $query = 'SELECT cache_data FROM ' . $this->_params['table'] .
                 ' WHERE cache_id = ?';
        $values = array($key);

        // 0 lifetime checks for objects which have no expiration
        if ($lifetime != 0) {
            $query .= ' AND cache_timestamp >= ?';
            $values[] = $maxage;
        }

        try {
            $result = $this->_db->selectValue($query, $values);
        } catch (Horde_Db_Exception $e) {
            return false;
        }

        if (is_null($result)) {
            /* No rows were found - cache miss */
            if ($this->_logger) {
                $this->_logger->log(sprintf('Cache miss: %s (Id %s newer than %d)', $okey, $key, $maxage), 'DEBUG');
            }
            return false;
        }

        if ($this->_logger) {
            $this->_logger->log(sprintf('Cache hit: %s (Id %s newer than %d)', $okey, $key, $maxage), 'DEBUG');
        }

        return $result;
    }

    /**
     */
    public function _set($key, $data, $lifetime)
    {
        $okey = $key;
        $key = hash('md5', $key);

        $timestamp = time();

        // 0 lifetime indicates the object should not be GC'd.
        $expiration = ($lifetime === 0)
            ? 0
            : ($lifetime + $timestamp);

        if ($this->_logger) {
            $this->_logger->log(sprintf('Cache set: %s (Id %s set at %d expires at %d)', $okey, $key, $timestamp, $expiration), 'DEBUG');
        }

        // Remove any old cache data and prevent duplicate keys
        $query = 'DELETE FROM ' . $this->_params['table'] . ' WHERE cache_id=?';
        $values = array($key);
        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {}

        /* Build SQL query. */
        $query = 'INSERT INTO ' . $this->_params['table'] .
                 ' (cache_id, cache_timestamp, cache_expiration, cache_data)' .
                 ' VALUES (?, ?, ?, ?)';
        $values = array($key, $timestamp, $expiration, $data);

        try {
            $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Cache_Exception($e);
        }
    }

    /**
     */
    public function exists($key, $lifetime)
    {
        $okey = $key;
        $key = hash('md5', $key);

        /* Build SQL query. */
        $query = 'SELECT 1 FROM ' . $this->_params['table'] .
                 ' WHERE cache_id = ?';
        $values = array($key);

        // 0 lifetime checks for objects which have no expiration
        if ($lifetime != 0) {
            $query .= ' AND cache_timestamp >= ?';
            $values[] = time() - $lifetime;
        }

        try {
            $result = $this->_db->selectValue($query, $values);
        } catch (Horde_Db_Exception $e) {
            return false;
        }

        $timestamp = time();
        if (empty($result)) {
            if ($this->_logger) {
                $this->_logger->log(sprintf('Cache exists() miss: %s (Id %s newer than %d)', $okey, $key, $timestamp), 'DEBUG');
            }
            return false;
        }

        if ($this->_logger) {
            $this->_logger->log(sprintf('Cache exists() hit: %s (Id %s newer than %d)', $okey, $key, $timestamp), 'DEBUG');
        }

        return true;
    }

    /**
     */
    public function expire($key)
    {
        $key = hash('md5', $key);

        $query = 'DELETE FROM ' . $this->_params['table'] .
                 ' WHERE cache_id = ?';
        $values = array($key);

        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            return false;
        }

        return true;
    }

}
