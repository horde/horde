<?php
/**
 * The Horde_Cache_Sql:: class provides a SQL implementation of the Horde
 * Caching system.
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
 *   'table'             The name of the cache table in 'database'.
 *                       Defaults to 'horde_cache'.
 *   'use_memorycache'   Use this Horde_Cache:: memory caching object to cache
 *                       the data (to avoid DB accesses).</pre>
 *
 * Optional values when using separate reading and writing servers, for example
 * in replication settings:<pre>
 *   'splitread'   Boolean, whether to implement the separation or not.
 *   'read'        Array containing the parameters which are different for
 *                 the read database connection, currently supported
 *                 only 'hostspec' and 'port' parameters.</pre>
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
 * @author  Michael Slusarz <slusarz@curecanti.org>
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Horde_Cache
 */
class Horde_Cache_Sql extends Horde_Cache_Base
{
    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    protected $_db;

    /**
     * Handle for the current database connection, used for writing. Defaults
     * to the same handle as $_db if a separate write database isn't required.
     *
     * @var DB
     */
    protected $_write_db;

    /**
     * Boolean indicating whether or not we're connected to the SQL server.
     *
     * @var boolean
     */
    protected $_connected = false;

    /**
     * The memory cache object to use, if configured.
     *
     * @var Horde_Cache
     */
    protected $_mc = null;

    /**
     * Constructs a new Horde_Cache_Sql object.
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
            $this->_params['table'] = 'horde_cache';
        }

        if (!empty($this->_params['use_memorycache'])) {
            $this->_mc = $this->_params['use_memorycache'];
        }

        parent::__construct($this->_params);
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

        try {
            $this->_connect();
        } catch (Horde_Exception $e) {
            if ($this->_logger) {
                $this->_logger->log($e, 'ERR');
            }
            return;
        }

        $query = 'DELETE FROM ' . $this->_params['table'] .
                 ' WHERE cache_expiration < ? AND cache_expiration <> 0';
        $values = array(time());

        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            if ($this->_logger) {
                $this->_logger->log($result, 'ERR');
            }
        }
    }

    /**
     * Attempts to retrieve cached data.
     *
     * @param string $key        Cache key to fetch.
     * @param integer $lifetime  Maximum age of the data in seconds or
     *                           0 for any object.
     *
     * @return mixed  Cached data, or false if none was found.
     */
    public function get($key, $lifetime = 1)
    {
        $okey = $key;
        $key = hash('md5', $key);

        if ($this->_mc) {
            $data = $this->_mc->get($key, $lifetime);
            if ($data !== false) {
                return $data;
            }
        }

        try {
            $this->_connect();
        } catch (Horde_Exception $e) {
            if ($this->_logger) {
                $this->_logger->log($e, 'ERR');
            }
            return false;
        }

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

        $result = $this->_db->getOne($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            if ($this->_logger) {
                $this->_logger->log($result, 'ERR');
            }
            return false;
        } elseif (is_null($result)) {
            /* No rows were found - cache miss */
            if ($this->_logger) {
                $this->_logger->log(sprintf('Cache miss: %s (Id %s newer than %d)', $okey, $key, $maxage), 'DEBUG');
            }
            return false;
        }

        if ($this->_mc) {
            $this->_mc->set($key, $result);
        }

        if ($this->_logger) {
            $this->_logger->log(sprintf('Cache hit: %s (Id %s newer than %d)', $okey, $key, $maxage), 'DEBUG');
        }

        return $result;
    }

    /**
     * Attempts to store data.
     *
     * @param string $key        Cache key.
     * @param mixed $data        Data to store in the cache. (MUST BE A STRING)
     * @param integer $lifetime  Maximum data life span or 0 for a
     *                           non-expiring object.
     *
     * @return boolean  True on success, false on failure.
     */
    public function set($key, $data, $lifetime = null)
    {
        $okey = $key;
        $key = hash('md5', $key);

        if ($this->_mc) {
            $this->_mc->set($key, $data);
        }

        try {
            $this->_connect();
        } catch (Horde_Exception $e) {
            if ($this->_logger) {
                $this->_logger->log($e, 'ERR');
            }
            return false;
        }

        $timestamp = time();

        // 0 lifetime indicates the object should not be GC'd.
        $expiration = ($lifetime === 0)
            ? 0
            : $this->_getLifetime($lifetime) + $timestamp;

            if ($this->_logger) {
                $this->_logger->log(sprintf('Cache set: %s (Id %s set at %d expires at %d)', $okey, $key, $timestamp, $expiration), 'DEBUG');
            }

        // Remove any old cache data and prevent duplicate keys
        $query = 'DELETE FROM ' . $this->_params['table'] . ' WHERE cache_id=?';
        $values = array($key);
        $this->_write_db->query($query, $values);

        /* Build SQL query. */
        $query = 'INSERT INTO ' . $this->_params['table'] .
                 ' (cache_id, cache_timestamp, cache_expiration, cache_data)' .
                 ' VALUES (?, ?, ?, ?)';
        $values = array($key, $timestamp, $expiration, $data);

        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            if ($this->_logger) {
                $this->_logger->log($result, 'ERR');
            }
            return false;
        }

        return true;
    }

    /**
     * Checks if a given key exists in the cache, valid for the given
     * lifetime.
     *
     * @param string $key        Cache key to check.
     * @param integer $lifetime  Maximum age of the key in seconds or 0 for
     *                           any object.
     *
     * @return boolean  Existence.
     */
    public function exists($key, $lifetime = 1)
    {
        $okey = $key;
        $key = hash('md5', $key);

        if ($this->_mc && $this->_mc->exists($key, $lifetime)) {
            return true;
        }

        try {
            $this->_connect();
        } catch (Horde_Exception $e) {
            if ($this->_logger) {
                $this->_logger->log($e, 'ERR');
            }
            return false;
        }

        /* Build SQL query. */
        $query = 'SELECT 1 FROM ' . $this->_params['table'] .
                 ' WHERE cache_id = ?';
        $values = array($key);

        // 0 lifetime checks for objects which have no expiration
        if ($lifetime != 0) {
            $query .= ' AND cache_timestamp >= ?';
            $values[] = time() - $lifetime;
        }

        $result = $this->_db->getRow($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            if ($this->_logger) {
                $this->_logger->log($result, 'ERR');
            }
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
     * Expire any existing data for the given key.
     *
     * @param string $key  Cache key to expire.
     *
     * @return boolean  Success or failure.
     */
    public function expire($key)
    {
        $key = hash('md5', $key);

        if ($this->_mc) {
            $this->_mc->expire($key);
        }

        try {
            $this->_connect();
        } catch (Horde_Exception $e) {
            if ($this->_logger) {
                $this->_logger->log($e, 'ERR');
            }
            return false;
        }

        $query = 'DELETE FROM ' . $this->_params['table'] .
                 ' WHERE cache_id = ?';
        $values = array($key);

        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            if ($this->_logger) {
                $this->_logger->log($result, 'ERR');
            }
            return false;
        }

        return true;
    }

    /**
     * Opens a connection to the SQL server.
     *
     * @throws Horde_Exception
     */
    protected function _connect()
    {
        if ($this->_connected) {
            return true;
        }

        Horde_Util::assertDriverConfig($this->_params, array('phptype'), 'cache SQL');

        $this->_write_db = DB::connect(
            $this->_params,
            array('persistent' => !empty($this->_params['persistent']),
                  'ssl' => !empty($this->_params['ssl']))
        );
        if (is_a($this->_write_db, 'PEAR_Error')) {
            throw new Horde_Exception($this->_write_db->getMessage());
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
            if (is_a($this->_db, 'PEAR_Error')) {
                throw new Horde_Exception($this->_db->getMessage());
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

}
