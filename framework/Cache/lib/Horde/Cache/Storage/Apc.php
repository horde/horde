<?php
/**
 * This class provides cache storage in the Alternative PHP Cache.
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
class Horde_Cache_Storage_Apc extends Horde_Cache_Storage_Base
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
        return apc_fetch($key);
    }

    /**
     */
    public function set($key, $data, $lifetime = 0)
    {
        $key = $this->_params['prefix'] . $key;
        if (apc_store($key . '_expire', time(), $lifetime)) {
            apc_store($key, $data, $lifetime);
        }
    }

    /**
     */
    public function exists($key, $lifetime = 0)
    {
        $key = $this->_params['prefix'] . $key;
        $this->_setExpire($key, $lifetime);
        return (apc_fetch($key) !== false);
    }

    /**
     */
    public function expire($key)
    {
        $key = $this->_params['prefix'] . $key;
        apc_delete($key . '_expire');
        return apc_delete($key);
    }

    /**
     */
    public function clear()
    {
        if (!apc_clear_cache('user')) {
            throw new Horde_Cache_Exception('Clearing APC cache failed');
        }
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
