<?php
/**
 * The Horde_Cache_Apc:: class provides an Alternative PHP Cache
 * implementation of the Horde caching system.
 *
 * Copyright 2006-2007 Duck <duck@obala.net>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Duck <duck@obala.net>
 * @category Horde
 * @package  Cache
 */
class Horde_Cache_Apc extends Horde_Cache_Base
{
    /**
     * Attempts to retrieve a piece of cached data and return it to
     * the caller.
     *
     * @param string $key        Cache key to fetch.
     * @param integer $lifetime  Lifetime of the key in seconds.
     *
     * @return mixed  Cached data, or false if none was found.
     */
    public function get($key, $lifetime = 1)
    {
        $key = $this->_params['prefix'] . $key;
        $this->_setExpire($key, $lifetime);
        return apc_fetch($key);
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
        $key = $this->_params['prefix'] . $key;
        $lifetime = $this->_getLifetime($lifetime);
        if (apc_store($key . '_expire', time(), $lifetime)) {
            apc_store($key, $data, $lifetime);
        }
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
        $key = $this->_params['prefix'] . $key;
        $this->_setExpire($key, $lifetime);
        return (apc_fetch($key) === false) ? false : true;
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
        $key = $this->_params['prefix'] . $key;
        apc_delete($key . '_expire');
        return apc_delete($key);
    }

    /**
     * Set expire time on each call since APC sets it on cache creation.
     *
     * @param string $key        Cache key to expire.
     * @param integer $lifetime  Lifetime of the data in seconds.
     */
    protected function _setExpire($key, $lifetime)
    {
        $key = $this->_params['prefix'] . $key;
        if ($lifetime == 0) {
            // Don't expire.
            return;
        }

        $expire = apc_fetch($key . '_expire');

        // Set prune period.
        if ($expire + $lifetime < time()) {
            // Expired
            apc_delete($key);
            apc_delete($key . '_expire');
        }
    }

}
