<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://www.horde.org/libraries/Horde_Autoloader_Cache
 * @package   Autoloader_Cache
 */

/**
 * Abstract class representing an autoloader caching backend.
 *
 * @author    Gunnar Wrobel <wrobel@horde.org>
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://www.horde.org/libraries/Horde_Autoloader_Cache
 * @package   Autoloader_Cache
 *
 * @property-read string $key  Cache key.
 */
abstract class Horde_Autoloader_Cache_Backend
{
    /**
     * Cache key name.
     *
     * @var string
     */
    protected $_key = null;

    /**
     * Constructor.
     *
     * @param string $key  The key for the data stored within the cache.
     */
    public function __construct($key)
    {
        $this->_key = $key;
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'key':
            return $this->_key;
        }
    }

    /**
     * Determines if the caching backend is supported.
     *
     * @return boolean  True if the caching backend can be used.
     */
    static public function isSupported()
    {
        return false;
    }

    /**
     * Store the class to file mapping in the cache.
     *
     * @param array $mapping  The mapping data to be stored.
     */
    public function store($mapping)
    {
        $data = json_encode($mapping);
        if (extension_loaded('horde_lz4')) {
            $data = horde_lz4_compress($data);
        } elseif (extension_loaded('lzf')) {
            $data = lzf_compress($data);
        }
        $this->_store($data);
    }

    /**
     * Store the class to file mapping in the cache.
     *
     * @param string $data  The mapping data to be stored.
     */
    abstract protected function _store($data);

    /**
     * Fetch the class to file mapping from the cache.
     *
     * @return array  The mapping data as fetched from the cache.
     */
    public function fetch()
    {
        if ($data = $this->_fetch()) {
            if (extension_loaded('horde_lz4')) {
                $data = @horde_lz4_uncompress($data);
            } elseif (extension_loaded('lzf')) {
                $data = @lzf_decompress($data);
            }

            if ($data !== false) {
                $data = @json_decode($data, true);
                if (is_array($data)) {
                    return $data;
                } else {
                    $this->prune();
                }
            }
        }

        return array();
    }

    /**
     * Fetch the class to file mapping from the cache.
     *
     * @return string  The mapping data as fetched from the cache.
     */
    abstract protected function _fetch();

    /**
     * Delete the class to file mapping from the cache.
     *
     * @return boolean  True if pruning succeeded.
     */
    abstract public function prune();

}
