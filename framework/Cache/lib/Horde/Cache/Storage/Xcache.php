<?php
/**
 * This class provides cache storage in Xcache.
 *
 * Copyright 2006-2007 Duck <duck@obala.net>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Duck <duck@obala.net>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Cache
 */
class Horde_Cache_Storage_Xcache extends Horde_Cache_Storage_Base
{
    /**
     * Constructor.
     *
     * @param array $params  Optional parameters:
     * <pre>
     * 'prefix' - (string) The prefix to use for the cache keys.
     *            DEFAULT: ''
     * </pre>
     */
    public function __construct(array $params = array())
    {
        parent::__construct(array_merge(array(
            'prefix' => '',
        ), $params));
    }

    /**
     */
    public function get($key, $lifetime = 0)
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
    public function set($key, $data, $lifetime = 0)
    {
        $key = $this->_params['prefix'] . $key;
        if (xcache_set($key . '_expire', time(), $lifetime)) {
            xcache_set($key, $data, $lifetime);
        }
    }

    /**
     */
    public function exists($key, $lifetime = 0)
    {
        $key = $this->_params['prefix'] . $key;
        $this->_setExpire($key, $lifetime);
        return xcache_isset($key);
    }

    /**
     */
    public function expire($key)
    {
        $key = $this->_params['prefix'] . $key;
        xcache_unset($key . '_expire');
        return xcache_unset($key);
    }

    /**
     */
    public function clear()
    {
        // xcache_clear_cache() won't work because it requires HTTP Auth.
        throw new Horde_Cache_Exception('Not supported');
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
