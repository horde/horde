<?php
/**
 * A cache backend for Kolab storage list handlers.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * A cache backend for Kolab storage list handlers.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Cache_List
{
    /** Key for the folder list. */
    const FOLDERS = 'F';

    /** Key for the type list. */
    const TYPES = 'T';

    /** Key for the last time the list was synchronized. */
    const SYNC = 'S';

    /** Holds query cache results. */
    const QUERIES = 'Q';

    /** Key for the cache format version. */
    const VERSION = 'V';

    /** Holds the version number of the cache format. */
    const FORMAT_VERSION = '1';

    /**
     * The core cache driver.
     *
     * @var Horde_Kolab_Storage_Cache
     */
    private $_cache;

    /**
     * List ID.
     *
     * @var string
     */
    private $_list_id;

    /**
     * The list data.
     *
     * @var array
     */
    private $_data = false;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Cache $cache   The core cache driver.
     */
    public function __construct(Horde_Kolab_Storage_Cache $cache)
    {
        $this->_cache = $cache;
    }

    /**
     * The the list ID.
     *
     * @param string                    $list_id The unique ID for the list
     *                                           used when caching it.
     *
     * @return NULL
     */
    public function setListId($list_id)
    {
        $this->_list_id = $list_id;
    }

    /**
     * Retrieve the cached list data.
     *
     * @return mixed The data of the object.
     */
    private function _load()
    {
        if ($this->_data === false) {
            $this->_data = unserialize($this->_cache->loadListData($this->_list_id));
            if (!is_array($this->_data)) {
                $this->_data = array();
            }
        }
    }

    /**
     * Cache the list data.
     *
     * @return NULL
     */
    public function save()
    {
        $this->_cache->storeListData($this->_list_id, serialize($this->_data));
    }

    /**
     * Check if the cache has been initialized.
     *
     * @return boolean True if cache data is available.
     */
    public function isInitialized()
    {
        $this->_load();
        if (!isset($this->_data[self::SYNC])) {
            return false;
        }
        if (!isset($this->_data[self::VERSION])
            || $this->_data[self::VERSION] != self::FORMAT_VERSION) {
            return false;
        }
        return true;
    }

    /**
     * Returns the list of folders from the cache.
     *
     * @return array The list of folders, represented as a list of strings.
     */
    public function getFolders()
    {
        $this->_load();
        if (isset($this->_data[self::FOLDERS])) {
            return $this->_data[self::FOLDERS];
        } else {
            throw new Horde_Kolab_Storage_Exception(
                sprintf('Missing cache data (Key: %s). Synchronize first!', self::FOLDERS)
            );
        }
    }

    /**
     * Returns the folder type annotation from the cache.
     *
     * @return array The list folder types with the folder names as key and the
     *               folder type as values.
     */
    public function getFolderTypes()
    {
        $this->_load();
        if (isset($this->_data[self::TYPES])) {
            return $this->_data[self::TYPES];
        } else {
            throw new Horde_Kolab_Storage_Exception(
                sprintf('Missing cache data (Key: %s). Synchronize first!', self::TYPES)
            );
        }
    }

    /**
     * Return query information.
     *
     * @param string $key The query key.
     *
     * @return mixed The query data.
     */
    public function getQuery($key)
    {
        $this->_load();
        if (isset($this->_data[self::QUERIES][$key])) {
            return $this->_data[self::QUERIES][$key];
        } else {
            throw new Horde_Kolab_Storage_Exception(
                sprintf('Missing query cache data (Key: %s). Synchronize first!', $key)
            );
        }
    }

    /**
     * Set query information.
     *
     * @param string $key  The query key.
     * @param mixed  $data The query data.
     *
     * @return NULL
     */
    public function setQuery($key, $data)
    {
        $this->_load();
        $this->_data[self::QUERIES][$key] = $data;
    }

    /**
     * Store the folder list and folder type annotations in the cache.
     *
     * @return NULL
     */
    public function store(array $folders = null, array $types = null)
    {
        $this->_load();
        $this->_data[self::QUERIES] = array();
        $this->_data[self::FOLDERS] = $folders;
        $this->_data[self::TYPES] = $types;
        $this->_data[self::VERSION] = self::FORMAT_VERSION;
        $this->_data[self::SYNC] = time();
    }
}
