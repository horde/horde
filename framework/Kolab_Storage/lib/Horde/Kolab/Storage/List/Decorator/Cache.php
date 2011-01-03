<?php
/**
 * The cache decorator for folder lists from Kolab storage.
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
 * The cache decorator for folder lists from Kolab storage.
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
class Horde_Kolab_Storage_List_Decorator_Cache
implements Horde_Kolab_Storage_List
{
    /** Marks the folder list. */
    const FOLDERS = 'F';

    /** Marks the type list. */
    const TYPES = 'T';

    /**
     * Decorated list handler.
     *
     * @var Horde_Kolab_Storage_List
     */
    private $_list;

    /**
     * The cache.
     *
     * @var Horde_Kolab_Storage_Cache
     */
    private $_cache;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_List  $list  The original list handler.
     * @param Horde_Kolab_Storage_Cache $cache The cache storing data for this
     *                                         decorator.
     */
    public function __construct(
        Horde_Kolab_Storage_List $list,
        Horde_Kolab_Storage_Cache $cache
    ) {
        $this->_list = $list;
        $this->_cache = $cache;
    }

    /**
     * Return the ID of the underlying connection.
     *
     * @return string The connection ID.
     */
    public function getConnectionId()
    {
        return $this->_list->getConnectionId();
    }

    /**
     * Check if the cache has been initialized at all and synchronize it if not.
     *
     * @return NULL
     */
    private function _init()
    {
    }

    /**
     * Returns the list of folders visible to the current user.
     *
     * @return array The list of folders, represented as a list of strings.
     */
    public function listFolders()
    {
        $this->_init();
        $a = $this->_cache->loadListData(
            $this->_list->getConnectionId(),
            self::FOLDERS
        );
        if (empty($a)) {
            $a = $this->_cacheFolders();
        }
        return $a;
    }

    /**
     * Caches and returns the list of folders visible to the current user.
     *
     * @return array The list of folders, represented as a list of strings.
     */
    private function _cacheFolders()
    {
        $list = $this->_list->listFolders();
        $this->_cache->storeListData(
            $this->_list->getConnectionId(),
            self::FOLDERS,
            $list
        );
        return $list;
    }

    /**
     * Returns the folder type annotation as associative array.
     *
     * @return array The list folder types with the folder names as key and the
     *               folder type as values.
     */
    public function listFolderTypes()
    {
        $this->_init();
        $a = $this->_cache->loadListData(
            $this->_list->getConnectionId(),
            self::TYPES
        );
        if (empty($a)) {
            $a = $this->_cacheFolderTypes();
        }
        return $a;
    }

    /**
     * Cache and returns the folder type annotation as associative array.
     *
     * @return array The list folder types with the folder names as key and the
     *               folder type as values.
     */
    private function _cacheFolderTypes()
    {
        $types = $this->_list->listFolderTypes();
        $this->_cache->storeListData(
            $this->_list->getConnectionId(),
            self::TYPES,
            $types
        );
        return $types;
    }

    /**
     * Synchronize the list information with the information from the backend.
     *
     * @return NULL
     */
    public function synchronize()
    {
        $this->_cacheFolders();
        $this->_cacheFolderTypes();
    }
}