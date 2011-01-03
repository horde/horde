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
    /**
     * Decorated list handler.
     *
     * @var Horde_Kolab_Storage_List
     */
    private $_list;

    /**
     * The list cache.
     *
     * @var Horde_Kolab_Storage_Cache_List
     */
    private $_list_cache;

    /**
     * Has the cache already been loaded and validated?
     *
     * @var boolean
     */
    private $_init = false;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_List       $list  The original list handler.
     * @param Horde_Kolab_Storage_Cache_List $cache The cache storing data for
     *                                              this decorator.
     */
    public function __construct(
        Horde_Kolab_Storage_List $list,
        Horde_Kolab_Storage_Cache_List $cache
    ) {
        $this->_list = $list;
        $this->_list_cache = $cache;
        $this->_list_cache->setListId($this->_list->getConnectionId());
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
        if ($this->_init) {
            return;
        }
        if (!$this->_list_cache->isInitialized()) {
            $this->synchronize();
        }
    }

    /**
     * Returns the list of folders visible to the current user.
     *
     * @return array The list of folders, represented as a list of strings.
     */
    public function listFolders()
    {
        $this->_init();
        return $this->_list_cache->getFolders();
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
        return $this->_list_cache->getFolderTypes();
    }

    /**
     * Synchronize the list information with the information from the backend.
     *
     * @return NULL
     */
    public function synchronize()
    {
        $this->_list->synchronize();

        $this->_list_cache->store(
            $this->_list->listFolders(),
            $this->_list->listFolderTypes()
        );

        $this->_list_cache->save();

        $this->_init = true;
    }
}