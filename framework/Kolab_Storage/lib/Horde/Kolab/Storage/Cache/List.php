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
     * Check if the cache has been initialized.
     *
     * @return boolean True if cache data is available.
     */
    public function isInitialized()
    {
        $last_sync = $this->_cache->loadListData(
            $this->_list_id,
            self::SYNC
        );
        if (empty($last_sync)) {
            return false;
        }
        $version = $this->_cache->loadListData(
            $this->_list_id,
            self::VERSION
        );
        if ($version != self::FORMAT_VERSION) {
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
        return $this->_cache->loadListData(
            $this->_list_id,
            self::FOLDERS
        );
    }

    /**
     * Returns the folder type annotation from the cache.
     *
     * @return array The list folder types with the folder names as key and the
     *               folder type as values.
     */
    public function getFolderTypes()
    {
        return $this->_cache->loadListData(
            $this->_list_id,
            self::TYPES
        );
    }

    /**
     * Store the folder list and folder type annotations in the cache.
     *
     * @return NULL
     */
    public function store(array $folders = null, array $types = null)
    {
        $this->_cache->storeListData(
            $this->_list_id,
            self::QUERIES,
            array()
        );
        $this->_cache->storeListData(
            $this->_list_id,
            self::FOLDERS,
            $folders
        );
        $this->_cache->storeListData(
            $this->_list_id,
            self::TYPES,
            $types
        );
        $this->_cache->storeListData(
            $this->_list_id,
            self::VERSION,
            self::FORMAT_VERSION
        );
        $this->_cache->storeListData(
            $this->_list_id,
            self::SYNC,
            time()
        );
    }
}
