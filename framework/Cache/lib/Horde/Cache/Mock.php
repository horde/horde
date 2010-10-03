<?php
/**
 * The Horde_Cache_Mock:: class provides a memory based implementation of the
 * Horde caching system. It persists only during a script run and ignores the
 * object lifetime because of that.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Cache
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Cache
 */
class Horde_Cache_Mock extends Horde_Cache
{
    /**
     * The storage location for this cache.
     *
     * @var array
     */
    private $_cache = array();

    /**
     * Construct a new Horde_Cache_Mock object.
     *
     * @param array $params  Configuration parameters:
     */
    public function __construct($params = array())
    {
        parent::__construct($params);
    }

    /**
     * Attempts to retrieve a piece of cached data and return it to the caller.
     *
     * @param string $key        Cache key to fetch.
     * @param integer $lifetime  Lifetime of the key in seconds.
     *
     * @return mixed  Cached data, or false if none was found.
     */
    public function get($key, $lifetime = 1)
    {
        return isset($this->_cache[$key])
            ? $this->_cache[$key]
            : false;
    }

    /**
     * Attempts to store an object to the cache.
     *
     * @param string $key        Cache key (identifier).
     * @param string $data       Data to store in the cache.
     * @param integer $lifetime  Data lifetime.
     *
     * @throws Horde_Cache_Exception
     */
    public function set($key, $data, $lifetime = null)
    {
        if (!is_string($data)) {
            throw new Horde_Cache_Exception('Data must be a string.');
        }
        $this->_cache[$key] = $data;
    }

    /**
     * Checks if a given key exists in the cache, valid for the given
     * lifetime.
     *
     * @param string $key        Cache key to check.
     * @param integer $lifetime  Lifetime of the key in seconds.
     *
     * @return boolean  Existence.
     */
    public function exists($key, $lifetime = 1)
    {
        return isset($this->_cache[$key]);
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
        unset($this->_cache[$key]);
    }
}
