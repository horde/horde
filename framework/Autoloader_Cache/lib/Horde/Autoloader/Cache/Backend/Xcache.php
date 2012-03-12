<?php
/**
 * Xcache based autoloader caching backend.
 *
 * PHP 5
 *
 * @category Horde
 * @package  Autoloader_Cache
 * @author   Jan Schneider <jan@horde.org>
 * @author   Gunnar Wrobel <wrobel@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Autoloader_Cache
 */

/**
 * Xcache based autoloader caching backend.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Autoloader_Cache
 * @author   Jan Schneider <jan@horde.org>
 * @author   Gunnar Wrobel <wrobel@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Autoloader_Cache
 */
class Horde_Autoloader_Cache_Backend_Xcache
implements Horde_Autoloader_Cache_Backend
{
    /**
     * Cache key name.
     *
     * @var string
     */
    private $_cachekey;

    /**
     * Constructor.
     *
     * @param string $cachekey The key for the data stored within the cache.
     */
    public function __construct($cachekey)
    {
        $this->_cachekey = $cachekey;
    }

    /**
     * Determines if the caching backend is supported.
     *
     * @return boolean True if the caching backend can be used.
     */
    static public function isSupported()
    {
        return extension_loaded('xcache');
    }

    /**
     * Store the class to file mapping in the cache.
     *
     * @param array $mapping The mapping to be stored.
     *
     * @return NULL
     */
    public function store($mapping)
    {
        xcache_set($this->_cachekey, $mapping);
    }

    /**
     * Fetch the class to file mapping from the cache.
     *
     * @return array The mapping as fetched from the cache.
     */
    public function fetch()
    {
        return xcache_get($this->_cachekey);
    }

    /**
     * Delete the class to file mapping from the cache.
     *
     * @return boolean True if pruning succeeded.
     */
    public function prune()
    {
        return xcache_unset($this->_cachekey);
    }

    /**
     * Return the cache key.
     *
     * @return string Key of the cached data.
     */
    public function getKey()
    {
        return $this->_cachekey;
    }
}