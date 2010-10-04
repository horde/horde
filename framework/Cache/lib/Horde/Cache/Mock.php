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
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Cache
 * @package  Cache
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
     */
    protected function _get($key, $lifetime)
    {
        return isset($this->_cache[$key])
            ? $this->_cache[$key]
            : false;
    }

    /**
     */
    protected function _set($key, $data, $lifetime)
    {
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
