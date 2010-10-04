<?php
/**
 * The Horde_Cache_Eaccelerator:: class provides a eAccelerator content cache
 * (version 0.9.5+) implementation of the Horde caching system.
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
class Horde_Cache_Eaccelerator extends Horde_Cache
{
    /**
     * Construct a new Horde_Cache object.
     *
     * @param array $params  Parameter array.
     *
     * @throws Horde_Cache_Exception
     */
    public function __construct($params = array())
    {
        if (!function_exists('eaccelerator_gc')) {
            throw new Horde_Cache_Exception('eAccelerator must be compiled with support for shared memory to use as caching backend.');
        }

        parent::__construct($params);
    }

    /**
     */
    protected function _get($key, $lifetime)
    {
        $key = $this->_params['prefix'] . $key;
        $this->_setExpire($key, $lifetime);
        return eaccelerator_get($key);
    }

    /**
     */
    protected function _set($key, $data, $lifetime)
    {
        $key = $this->_params['prefix'] . $key;
        $lifetime = $this->_getLifetime($lifetime);
        if (eaccelerator_put($key . '_expire', time(), $lifetime)) {
            eaccelerator_put($key, $data, $lifetime);
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
        return eaccelerator_get($key) !== false;
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
        eaccelerator_rm($key . '_expire');
        return eaccelerator_rm($key);
    }

    /**
     * Set expire time on each call since eAccelerator sets it on
     * cache creation.
     *
     * @param string $key        Cache key to expire.
     * @param integer $lifetime  Lifetime of the data in seconds.
     */
    protected function _setExpire($key, $lifetime)
    {
        if ($lifetime == 0) {
            // Don't expire.
            return;
        }

        $key = $this->_params['prefix'] . $key;
        $expire = eaccelerator_get($key . '_expire');

        // Set prune period.
        if ($expire + $lifetime < time()) {
            // Expired
            eaccelerator_rm($key);
            eaccelerator_rm($key . '_expire');
        }
    }

}
