<?php
/**
 * This class provides cache storage in PHP memory.
 * It persists only during a script run and ignores the object lifetime
 * because of that.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Cache
 * @package  Cache
 */
class Horde_Cache_Storage_Mock extends Horde_Cache_Storage_Base
{
    /**
     * The storage location for this cache.
     *
     * @var array
     */
    private $_cache = array();

    /**
     */
    public function get($key, $lifetime = 0)
    {
        return isset($this->_cache[$key])
            ? $this->_cache[$key]
            : false;
    }

    /**
     */
    public function set($key, $data, $lifetime = 0)
    {
        $this->_cache[$key] = $data;
    }

    /**
     */
    public function exists($key, $lifetime = 0)
    {
        return isset($this->_cache[$key]);
    }

    /**
     */
    public function expire($key)
    {
        unset($this->_cache[$key]);
    }

    /**
     */
    public function clear()
    {
        $this->_cache = array();
    }

}
