<?php
/**
 * The Horde_Cache_Xcache:: class provides an XCache implementation of
 * the Horde caching system.
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
class Horde_Cache_Xcache extends Horde_Cache
{
    /**
     */
    protected function _get($key, $lifetime)
    {
        $key = $this->_params['prefix'] . $key;
        $this->_setExpire($key, $lifetime);
        $result = xcache_get($key);

        return empty($result)
            ? false
            : $result;
    }

    /**
     */
    protected function _set($key, $data, $lifetime)
    {
        $key = $this->_params['prefix'] . $key;
        $lifetime = $this->_getLifetime($lifetime);
        if (xcache_set($key . '_expire', time(), $lifetime)) {
            xcache_set($key, $data, $lifetime);
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
        return xcache_isset($key);
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
        xcache_unset($key . '_expire');
        return xcache_unset($key);
    }

    /**
     * Set expire time on each call since memcache sets it on cache creation.
     *
     * @param string $key        Cache key to expire.
     * @param integer $lifetime  Lifetime of the data in seconds.
     */
    protected function _setExpire($key, $lifetime)
    {
        if ($lifetime == 0) {
            // don't expire
            return;
        }
        $key = $this->_params['prefix'] . $key;
        $expire = xcache_get($key . '_expire');

        // set prune period
        if ($expire + $lifetime < time()) {
            // Expired
            xcache_unset($key . '_expire');
            xcache_unset($key);
        }
    }

}
