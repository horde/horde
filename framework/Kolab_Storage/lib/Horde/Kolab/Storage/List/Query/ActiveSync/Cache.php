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
extends Horde_Kolab_Storage_List_Query_ActiveSync
implements Horde_Kolab_Storage_List_Manipulation_Listener,
Horde_Kolab_Storage_List_Synchronization_Listener
{
    /** The active sync information */
    const ACTIVE_SYNC = 'ACTIVE_SYNC';

    /**
     * The underlying ActiveSync query.
     *
     * @param Horde_Kolab_Storage_List_Query_ActiveSync
     */
    private $_query;

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
     * @param Horde_Kolab_Storage_List_Query_ActiveSync $query The underlying ActiveSync query.
     * @param Horde_Kolab_Storage_List_Cache $cache The list cache.
     */
    public function __construct(Horde_Kolab_Storage_List_Query_ActiveSync $query,
                                Horde_Kolab_Storage_List_Cache $cache)
    {
        $this->_query = $query;
        $this->_list_cache = $cache;
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
            $this->_active_sync[$folder] = $this->_query->getActiveSync($folder);
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
        $this->_query->setActiveSync($folder, $data);
        $this->_active_sync[$folder] = $data;
        $this->_list_cache->setQuery(self::ACTIVE_SYNC, $this->_active_sync);
        $this->_list_cache->save();
    }

    /**
     * Update the listener after creating a new folder.
     *
     * @param string $folder The path of the folder that has been created.
     * @param string $type   An optional type for the folder.
     *
     * @return NULL
     */
    public function updateAfterCreateFolder($folder, $type = null)
    {
    }

    /**
     * Update the listener after deleting folder.
     *
     * @param string $folder The path of the folder that has been deleted.
     *
     * @return NULL
     */
    public function updateAfterDeleteFolder($folder)
    {
        unset($this->_active_sync[$folder]);
        $this->_list_cache->setQuery(self::ACTIVE_SYNC, $this->_active_sync);
        $this->_list_cache->save();
    }

    /**
     * Update the listener after renaming a folder.
     *
     * @param string $old The old path of the folder.
     * @param string $new The new path of the folder.
     *
     * @return NULL
     */
    public function updateAfterRenameFolder($old, $new)
    {
        if (isset($this->_active_sync[$old])) {
            $this->_active_sync[$new] = $this->_active_sync[$old];
            unset($this->_active_sync[$old]);
            $this->_list_cache->setQuery(self::ACTIVE_SYNC, $this->_active_sync);
        }
        $this->_list_cache->save();
    }

    /**
     * Purge all ActiveSync data and restart querying the backend.
     *
     * @return NULL
     */
    public function synchronize()
    {
        $this->_active_sync = array();
    }
}