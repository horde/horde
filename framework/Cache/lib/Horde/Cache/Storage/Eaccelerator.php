<?php
/**
 * This class provides cache storage in eAccelerator (version 0.9.5+).
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
class Horde_Cache_Storage_Eaccelerator extends Horde_Cache_Storage_Base
{
    /**
     * Constructor.
     *
     * @param array $params  Optional parameters:
     * <pre>
     * 'prefix' - (string) The prefix to use for the cache keys.
     *            DEFAULT: ''
     * </pre>
     *
     * @throws Horde_Cache_Exception
     */
    public function __construct(array $params = array())
    {
        if (!function_exists('eaccelerator_gc')) {
            throw new Horde_Cache_Exception('eAccelerator must be compiled with support for shared memory to use as caching backend.');
        }

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
        return eaccelerator_get($key);
    }

    /**
     */
    public function set($key, $data, $lifetime = 0)
    {
        $key = $this->_params['prefix'] . $key;
        if (eaccelerator_put($key . '_expire', time(), $lifetime)) {
            eaccelerator_put($key, $data, $lifetime);
        }
    }

    /**
     */
    public function exists($key, $lifetime = 0)
    {
        $key = $this->_params['prefix'] . $key;
        $this->_setExpire($key, $lifetime);
        return eaccelerator_get($key) !== false;
    }

    /**
     */
    public function expire($key)
    {
        $key = $this->_params['prefix'] . $key;
        eaccelerator_rm($key . '_expire');
        return eaccelerator_rm($key);
    }

    /**
     */
    public function clear()
    {
        eaccelerator_clear();
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
