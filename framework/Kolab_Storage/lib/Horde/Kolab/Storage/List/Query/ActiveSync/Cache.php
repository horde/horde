<?php
/**
 * Caches active sync parameters.
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
 * Caches active sync parameters.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @since Horde_Kolab_Storage 1.1.0
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_List_Query_ActiveSync_Cache
extends Horde_Kolab_Storage_List_Query_ActiveSync_Base
{
    /** The active sync information */
    const ACTIVE_SYNC = 'ACTIVE_SYNC';

    /**
     * The list cache.
     *
     * @var Horde_Kolab_Storage_Cache_List
     */
    private $_list_cache;

    /**
     * The cached active sync data.
     *
     * @var array
     */
    private $_active_sync;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_List $list   The queriable list.
     * @param array                    $params Additional parameters.
     */
    public function __construct(Horde_Kolab_Storage_List $list,
                                $params)
    {
        parent::__construct($list, $params);
        $this->_list_cache = $params['cache'];
        if ($this->_list_cache->hasQuery(self::ACTIVE_SYNC)) {
            $this->_active_sync = $this->_list_cache->getQuery(self::ACTIVE_SYNC);
        } else {
            $this->_active_sync = array();
        }
    }

    /**
     * Returns the active sync settings.
     *
     * @param string $folder The folder name.
     *
     * @return array The folder active sync parameters.
     */
    public function getActiveSync($folder)
    {
        if (!isset($this->_active_sync[$folder])) {
            $this->_active_sync[$folder] = parent::getActiveSync($folder);
            $this->_list_cache->setQuery(self::ACTIVE_SYNC, $this->_active_sync);
            $this->_list_cache->save();
        }
        return $this->_active_sync[$folder];
    }

    /**
     * Set the active sync settings.
     *
     * @param string $folder The folder name.
     * @param array  $data   The active sync settings.
     *
     * @return string The encoded share parameters.
     */
    public function setActiveSync($folder, array $data)
    {
        parent::setActiveSync($folder, $data);
        $this->_active_sync[$folder] = $data;
        $this->_list_cache->setQuery(self::ACTIVE_SYNC, $this->_active_sync);
        $this->_list_cache->save();
    }

    /**
     * Create a new folder.
     *
     * @param string $folder The path of the folder to create.
     * @param string $type   An optional type for the folder.
     *
     * @return NULL
     */
    public function createFolder($folder, $type = null)
    {
    }

    /**
     * Delete a folder.
     *
     * @param string $folder The path of the folder to delete.
     *
     * @return NULL
     */
    public function deleteFolder($folder)
    {
        unset($this->_active_sync[$folder]);
        $this->_list_cache->setQuery(self::ACTIVE_SYNC, $this->_active_sync);
        $this->_list_cache->save();
    }

    /**
     * Rename a folder.
     *
     * @param string $old The old path of the folder.
     * @param string $new The new path of the folder.
     *
     * @return NULL
     */
    public function renameFolder($old, $new)
    {
        if (isset($this->_active_sync[$old])) {
            $this->_active_sync[$new] = $this->_active_sync[$old];
            unset($this->_active_sync[$old]);
            $this->_list_cache->setQuery(self::ACTIVE_SYNC, $this->_active_sync);
        }
        $this->_list_cache->save();
    }

    /**
     * Return the last sync stamp.
     *
     * @return string The stamp.
     */
    public function getStamp()
    {
        return $this->_list->getStamp();
    }

    /**
     * Synchronize the ACL information with the information from the backend.
     *
     * @param array $params Additional parameters.
     *
     * @return NULL
     */
    public function synchronize($params = array())
    {
        $this->_active_sync = array();
    }
}