<?php
/**
 * A cache backend for Kolab storage list handlers.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * A cache backend for Kolab storage list handlers.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_List_Cache
{
    /** Key for the folder list. */
    const FOLDERS = 'F';

    /** Key for the type list. */
    const TYPES = 'T';

    /** Key for the namespace data. */
    const NAME_SPACE = 'N';

    /** Key for the backend capabilities. */
    const SUPPORT = 'C';

    /** Holds query results. */
    const QUERIES = 'Q';

    /** Holds long term cache data. */
    const LONG_TERM = 'L';

    /** Key for the last time the list was synchronized. */
    const SYNC = 'S';

    /** Key for the cache format version. */
    const VERSION = 'V';

    /** Key for the connection ID associated with this list cache. */
    const ID = 'I';

    /** Holds the version number of the cache format. */
    const FORMAT_VERSION = '1';

    /**
     * The core cache driver.
     *
     * @var Horde_Kolab_Storage_Cache
     */
    private $_cache;

    /**
     * List parameters that will be recorded in the cache.
     *
     * @var array
     */
    private $_parameters;

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
     * @param Horde_Kolab_Storage_Cache $cache      The core cache driver.

     * @param array                     $parameters Connection parameters that
     *                                              are only recorded and have
     *                                              no further impact.
     */
    public function __construct(Horde_Kolab_Storage_Cache $cache,
                                $parameters = array())
    {
        $this->_cache = $cache;
        $this->_parameters = $parameters;
        $this->_setListId();
    }

    /**
     * Compose the list key.
     */
    private function _setListId()
    {
        foreach (array('host', 'port', 'user') as $key) {
            $this->_cache->requireParameter($this->_parameters, 'list', $key);
        }
        ksort($this->_parameters);
        $this->_list_id = md5(serialize($this->_parameters));
    }

    /**
     * Return the ID for the list cache.
     *
     * @return string The unique ID for the list used when caching it.
     */
    public function getListId()
    {
        if ($this->_list_id === null) {
            throw new Horde_Kolab_Storage_Exception(
                'You must set the ID of the list cache!'
            );
        }
        return $this->_list_id;
    }

    /**
     * Retrieve the cached list data.
     *
     * @return mixed The data of the object.
     */
    private function _load()
    {
        if ($this->_data === false) {
            $this->_data = unserialize($this->_cache->loadList($this->getListId()));
            if (!is_array($this->_data)
                || !isset($this->_data[self::SYNC])
                || !isset($this->_data[self::VERSION])
                || $this->_data[self::VERSION] != self::FORMAT_VERSION) {
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
        $this->_cache->storeList($this->getListId(), serialize($this->_data));
    }

    /**
     * Check if the cache has been initialized.
     *
     * @return boolean True if cache data is available.
     */
    public function isInitialized()
    {
        $this->_load();
        return !empty($this->_data);
    }

    /**
     * Returns the last sync stamp.
     *
     * @return string The last sync stamp.
     */
    public function getStamp()
    {
        $this->_load();
        if (isset($this->_data[self::SYNC])) {
            return $this->_data[self::SYNC];
        }
        return 0;
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
     * Returns if the folder type annotation is stored in the cache.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @return boolean True if the type annotation is available.
     */
    public function hasFolderTypes()
    {
        $this->_load();
        if (isset($this->_data[self::TYPES])) {
            return true;
        }
        return false;
    }

    /**
     * Returns the folder type annotation from the cache.
     *
     * @return array The list folder types with the folder names as key and the
     *               folder type as values.
     */
    public function getFolderTypes()
    {
        if ($this->hasFolderTypes()) {
            return $this->_data[self::TYPES];
        } else {
            throw new Horde_Kolab_Storage_Exception(
                sprintf('Missing cache data (Key: %s). Synchronize first!', self::TYPES)
            );
        }
    }

    /**
     * Returns if the namespace information is available.
     *
     * @return boolean True if the information exists in the cache.
     */
    public function hasNamespace()
    {
        $this->_load();
        return isset($this->_data[self::NAME_SPACE]);
    }

    /**
     * Return namespace information.
     *
     * @return mixed The namespace data.
     */
    public function getNamespace()
    {
        if ($this->hasNamespace()) {
            return $this->_data[self::NAME_SPACE];
        } else {
            throw new Horde_Kolab_Storage_Exception(
                'Missing namespace data. Synchronize first!'
            );
        }
    }

    /**
     * Set namespace information.
     *
     * @param mixed $data The namespace data.
     *
     * @return NULL
     */
    public function setNamespace($data)
    {
        $this->_load();
        $this->_data[self::NAME_SPACE] = $data;
    }

    /**
     * Has the capability support already been cached?
     *
     * @return boolean True if the value is already in the cache.
     */
    public function issetSupport($capability)
    {
        $this->_load();
        return isset($this->_data[self::SUPPORT][$capability]);
    }

    /**
     * Has the list support for the requested capability?
     *
     * @param string $capability The name of the requested capability.
     *
     * @return boolean True if the backend supports the requested capability.
     */
    public function hasSupport($capability)
    {
        if ($this->issetSupport($capability)) {
            return $this->_data[self::SUPPORT][$capability];
        } else {
            throw new Horde_Kolab_Storage_Exception(
                'Missing support data. Synchronize first!'
            );
        }
    }

    /**
     * Set if the list supports the given capability.
     *
     * @param string  $capability The name of the requested capability.
     * @param boolean $flag       True if the capability is supported.
     *
     * @return NULL
     */
    public function setSupport($capability, $flag)
    {
        $this->_load();
        $this->_data[self::SUPPORT][$capability] = $flag;
    }

    /**
     * Is the specified query data available in the cache?
     *
     * @param string $key The query key.
     *
     * @return boolean True in case cached data is available.
     */
    public function hasQuery($key)
    {
        $this->_load();
        return isset($this->_data[self::QUERIES][$key]);
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
        if ($this->hasQuery($key)) {
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
     * Is the specified long term data available in the cache?
     *
     * @param string $key The long term key.
     *
     * @return boolean True in case cached data is available.
     */
    public function hasLongTerm($key)
    {
        $this->_load();
        return isset($this->_data[self::LONG_TERM][$key]);
    }

    /**
     * Return long term information.
     *
     * @param string $key The long term key.
     *
     * @return mixed The long term data.
     */
    public function getLongTerm($key)
    {
        if ($this->hasLongTerm($key)) {
            return $this->_data[self::LONG_TERM][$key];
        } else {
            throw new Horde_Kolab_Storage_Exception(
                sprintf('Missing long term cache data (Key: %s). Synchronize first!', $key)
            );
        }
    }

    /**
     * Set long term information.
     *
     * @param string $key  The long term key.
     * @param mixed  $data The long term data.
     *
     * @return NULL
     */
    public function setLongTerm($key, $data)
    {
        $this->_load();
        $this->_data[self::LONG_TERM][$key] = $data;
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
        $this->_data[self::ID] = serialize($this->_parameters);
        $this->_data[self::SYNC] = pack('Nn', time(), mt_rand());
    }
}
