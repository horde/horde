<?php
/**
 * Temporary file based autoloader caching backend.
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
 * Temporary file based autoloader caching backend.
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
class Horde_Autoloader_Cache_Backend_Tempfile
implements Horde_Autoloader_Cache_Backend
{
    /**
     * Path to the cache file.
     *
     * @var string
     */
    private $_tempfile;

    /**
     * Constructor.
     *
     * @param string $cachekey The key for the data stored within the cache.
     */
    public function __construct($cachekey)
    {
        $this->_tempfile = sys_get_temp_dir() . '/' . hash('md5', $cachekey);
    }

    /**
     * Determines if the caching backend is supported.
     *
     * @return boolean True if the caching backend can be used.
     */
    static public function isSupported()
    {
        return is_readable(sys_get_temp_dir());
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
        file_put_contents($this->_tempfile, json_encode($mapping));
    }

    /**
     * Fetch the class to file mapping from the cache.
     *
     * @return array The mapping as fetched from the cache.
     */
    public function fetch()
    {
        if (file_exists($this->_tempfile)
            && ($data = file_get_contents($this->_tempfile)) !== false) {
            return @json_decode($data, true);
        }
        return array();
    }

    /**
     * Delete the class to file mapping from the cache.
     *
     * @return boolean True if pruning succeeded.
     */
    public function prune()
    {
        if (file_exists($this->_tempfile)) {
            return unlink($this->_tempfile);
        }
    }

    /**
     * Return the path to the temporary cache file.
     *
     * @return string Path to the cache file.
     */
    public function getTempfile()
    {
        return $this->_tempfile;
    }
}