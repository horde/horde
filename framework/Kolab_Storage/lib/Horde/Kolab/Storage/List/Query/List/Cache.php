<?php
/**
 * The cached list query.
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
 * The cached list query.
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
class Horde_Kolab_Storage_List_Query_List_Cache
extends Horde_Kolab_Storage_List_Query_List
implements Horde_Kolab_Storage_List_Manipulation_Listener,
Horde_Kolab_Storage_List_Synchronization_Listener
{
    /** The list of folder types */
    const TYPES = 'TYPES';

    /** The folder list sorted by type */
    const BY_TYPE = 'BY_TYPE';

    /** The list of folder data */
    const FOLDERS = 'FOLDERS';

    /** The folder owner list */
    const OWNERS = 'OWNERS';

    /** The default folder list for the current user */
    const PERSONAL_DEFAULTS = 'PERSONAL_DEFAULTS';

    /** The default folder list */
    const DEFAULTS = 'DEFAULTS';

    /**
     * The synchronization handler.
     *
     * @var Horde_Kolab_Storage_List_Query_List_Cache_Synchronization
     */
    private $_sync;

    /**
     * The list cache.
     *
     * @var Horde_Kolab_Storage_List_Cache
     */
    private $_list_cache;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_List_Query_List_Cache_Synchronization $sync The synchronization handler..
     * @param Horde_Kolab_Storage_List_Cache $cache The list cache.
     */
    public function __construct(Horde_Kolab_Storage_List_Query_List_Cache_Synchronization $sync,
                                Horde_Kolab_Storage_List_Cache $cache)
    {
        $this->_sync = $sync;
        $this->_list_cache = $cache;
        $this->_sync->setCache($cache);
    }

    /**
     * Ensure we have the query data.
     *
     * @param string $query The query data required.
     *
     * @return NULL
     */
    private function _initQuery($query)
    {
        if (!$this->_list_cache->hasQuery($query)) {
            $this->_sync->synchronize($this->_list_cache);
        }
    }

    /**
     * Returns the folder types as associative array.
     *
     * @return array The list folder types with the folder names as key and the
     *               type as values.
     */
    public function listTypes()
    {
        $this->_initQuery(self::TYPES);
        return $this->_list_cache->getQuery(self::TYPES);
    }

    /**
     * List all folders of a specific type.
     *
     * @param string $type The folder type the listing should be limited to.
     *
     * @return array The list of folders.
     */
    public function listByType($type)
    {
        $this->_initQuery(self::BY_TYPE);
        $by_type = $this->_list_cache->getQuery(self::BY_TYPE);
        if (isset($by_type[$type])) {
            return array_keys($by_type[$type]);
        } else {
            return array();
        }
    }

    /**
     * List basic folder data for the folders of a specific type.
     *
     * @param string $type The folder type the listing should be limited to.
     *
     * @return array The list of folders.
     */
    public function dataByType($type)
    {
        $this->_initQuery(self::BY_TYPE);
        $data_by_type = $this->_list_cache->getQuery(self::BY_TYPE);
        if (isset($data_by_type[$type])) {
            return $data_by_type[$type];
        } else {
            return array();
        }
    }

    /**
     * List basic folder data for the specified folder.
     *
     * @param string $folder The folder path.
     *
     * @return array The folder data.
     */
    public function folderData($folder)
    {
        $this->_initQuery(self::FOLDERS);
        $folders = $this->_list_cache->getQuery(self::FOLDERS);
        if (isset($folders[$folder])) {
            return $folders[$folder];
        } else {
            throw new Horde_Kolab_Storage_List_Exception(
                sprintf('Folder %s does not exist!', $folder)
            );
        }
    }

    /**
     * Get the folder owners.
     *
     * @return array The folder owners with the folder names as key and the
     *               owner as values.
     */
    public function listOwners()
    {
        $this->_initQuery(self::OWNERS);
        return $this->_list_cache->getQuery(self::OWNERS);
    }

    /**
     * Set the specified folder as default for its current type.
     *
     * @param string $folder The folder name.
     */
    public function setDefault($folder)
    {
        $data = $this->folderData($folder);
        $this->_sync->setDefault($data, $this->getDefault($data['type']));
    }

    /**
     * Return the list of personal default folders.
     *
     * @return array An array that associates type (key) with the corresponding
     *               default folder name (value).
     */
    public function listPersonalDefaults()
    {
        $this->_initQuery(self::PERSONAL_DEFAULTS);
        return $this->_list_cache->getQuery(self::PERSONAL_DEFAULTS);
    }

    /**
     * Return the list of default folders.
     *
     * @return array An array with owners as keys and another array as
     *               value. The second array associates type (key) with the
     *               corresponding default folder (value).
     */
    public function listDefaults()
    {
        $this->_initQuery(self::DEFAULTS);
        return $this->_list_cache->getQuery(self::DEFAULTS);
    }

    /**
     * Get the default folder for a certain type.
     *
     * @param string $type The type of the share/folder.
     *
     * @return string|boolean The name of the default folder, false if there is no default.
     */
    public function getDefault($type)
    {
        $this->_initQuery(self::PERSONAL_DEFAULTS);
        $defaults = $this->_list_cache->getQuery(self::PERSONAL_DEFAULTS);
        if (isset($defaults[$type])) {
            return $defaults[$type];
        } else {
            return false;
        }
    }

    /**
     * Get the default folder for a certain type from a different owner.
     *
     * @param string $owner The folder owner.
     * @param string $type  The type of the share/folder.
     *
     * @return string|boolean The name of the default folder, false if there is no default.
     */
    public function getForeignDefault($owner, $type)
    {
        $this->_initQuery(self::DEFAULTS);
        $defaults = $this->_list_cache->getQuery(self::DEFAULTS);
        if (isset($defaults[$owner][$type])) {
            return $defaults[$owner][$type];
        } else {
            return false;
        }
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
        $this->_sync->updateAfterCreateFolder($folder, $type);
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
        $this->_sync->updateAfterDeleteFolder($folder);
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
        $this->_sync->updateAfterRenameFolder($old, $new);
    }

    /**
     * Return the last sync stamp.
     *
     * @return string The stamp.
     */
    public function getStamp()
    {
        return $this->_list_cache->getStamp();
    }

    /**
     * Return any default folder duplicates.
     *
     * @return array The list of duplicate default folders accessible to the current user.
     */
    public function getDuplicateDefaults()
    {
        return $this->_sync->getDuplicateDefaults();
    }

    /**
     * Synchronize the listener.
     */
    public function synchronize()
    {
        $this->_sync->synchronize();
    }
}