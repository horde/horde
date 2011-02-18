<?php
/**
 * This class provides the abstract implementation of the cache storage
 * driver.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Cache
 */
abstract class Horde_Cache_Storage_Base
{
    /**
     * Logger.
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters.
     */
    public function __construct(array $params = array())
    {
        $this->_params = array_merge($this->_params, $params);
    }

    /**
     * Set the logging object.
     *
     * @param Horde_Log_Logger $logger  Log object.
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Retrieve cached data.
     *
     * @param string $key        Object ID to query.
     * @param integer $lifetime  Lifetime of the object in seconds.
     *
     * @return mixed  Cached data, or false if none was found.
     */
    abstract public function get($key, $lifetime = 0);

    /**
     * Store an object in the cache.
     *
     * @param string $key        Object ID used as the caching key.
     * @param mixed $data        Data to store in the cache.
     * @param integer $lifetime  Object lifetime - i.e. the time before the
     *                           data becomes available for garbage
     *                           collection. If 0 will not be GC'd.
     */
    abstract public function set($key, $data, $lifetime = 0);

    /**
     * Checks if a given key exists in the cache, valid for the given
     * lifetime.
     *
     * @param string $key        Cache key to check.
     * @param integer $lifetime  Lifetime of the key in seconds.
     *
     * @return boolean  Existence.
     */
    abstract public function exists($key, $lifetime = 0);

    /**
     * Expire any existing data for the given key.
     *
     * @param string $key  Cache key to expire.
     *
     * @return boolean  Success or failure.
     */
    abstract public function expire($key);

    /**
     * Clears all data from the cache.
     *
     * @throws Horde_Cache_Exception
     */
    abstract public function clear();

}
