<?php
/**
 * Handles synchronization of the list query cache.
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
 * Handles synchronization of the list query cache.
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
class Horde_Kolab_Storage_List_Query_List_Cache_Synchronization
{
    /**
     * The IMAP driver to query the backend.
     *
     * @var Horde_Kolab_Storage_Driver
     */
    private $_driver;

    /**
     * The factory for folder types.
     *
     * @var Horde_Kolab_Storage_Folder_Types
     */
    private $_folder_types;

    /**
     * Handles default folders.
     *
     * @var Horde_Kolab_Storage_List_Query_List_Defaults
     */
    private $_defaults;

    /**
     * The list cache.
     *
     * @var Horde_Kolab_Storage_List_Cache
     */
    private $_cache;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Driver $driver The driver to access the backend.
     * @param Horde_Kolab_Storage_Folder_Types $types Handler of folder types.
     */
    public function __construct(Horde_Kolab_Storage_Driver $driver,
                                Horde_Kolab_Storage_Folder_Types $types,
                                Horde_Kolab_Storage_List_Query_List_Defaults $defaults)
    {
        $this->_driver = $driver;
        $this->_folder_types = $types;
        $this->_defaults = $defaults;
    }

    /**
     * Set the list cache.
     *
     * @param Horde_Kolab_Storage_List_Cache $cache The reference to the cache
     *                                              that should reveive any updates.
     */
    public function setCache($cache)
    {
        $this->_cache = $cache;
    }

    /**
     * Synchronize the query data with the information from the backend.
     */
    public function synchronize()
    {
        $this->_synchronize(
            $this->_driver->getNamespace(),
            $this->_driver->listFolders(),
            $this->_driver->listAnnotation(
                Horde_Kolab_Storage_List_Query_List::ANNOTATION_FOLDER_TYPE
            )
        );
    }

    /**
     * Synchronize based on the given folder list.
     *
     * @param Horde_Kolab_Storage_List_Cache $cache The reference to the cache
     *                                              that should reveive the update.
     * @param Horde_Kolab_Storage_Folder_Namespace $namespace The namespace handler
     * @param array $folder_list The list of folders.
     * @param array $annotation The list of folder annotations.
     *
     * @return NULL
     */
    public function _synchronize(Horde_Kolab_Storage_Folder_Namespace $namespace,
                                 $folder_list,
                                 $annotations)
    {
        $folders = array();
        $owners = array();
        $types = array();
        $by_type = array();
        $mail_type = $this->_folder_types->create('mail');

        $this->_defaults->reset();

        foreach ($folder_list as $folder) {
            $folder = strval($folder);
            if (!isset($annotations[$folder])) {
                $type = $mail_type;
            } else {
                $type = $this->_folder_types->create($annotations[$folder]);
            }
            $folder_type = $type->getType();
            $owner = $namespace->getOwner($folder);

            $owners[$folder] = $owner;
            $types[$folder] = $type->getType();

            $data = new Horde_Kolab_Storage_Folder_Data(
                $folder, $type, $namespace
            );
            $dataset = $data->toArray();

            $folders[$folder] = $dataset;
            $by_type[$folder_type][$folder] = $dataset;

            if ($folders[$folder]['default']) {
                $this->_defaults->rememberDefault(
                    $folder,
                    $folder_type,
                    $owner,
                    $folders[$folder]['namespace'] == Horde_Kolab_Storage_Folder_Namespace::PERSONAL
                );
            }
        }

        $this->_cache->store($folder_list, $annotations);

        if (!$this->_cache->hasNamespace()) {
            $this->_cache->setNamespace(serialize($namespace));
        }

        $this->_cache->setQuery(Horde_Kolab_Storage_List_Query_List_Cache::TYPES, $types);
        $this->_cache->setQuery(Horde_Kolab_Storage_List_Query_List_Cache::FOLDERS, $folders);
        $this->_cache->setQuery(Horde_Kolab_Storage_List_Query_List_Cache::OWNERS, $owners);
        $this->_cache->setQuery(Horde_Kolab_Storage_List_Query_List_Cache::BY_TYPE, $by_type);
        $this->_cache->setQuery(
            Horde_Kolab_Storage_List_Query_List_Cache::DEFAULTS,
            $this->_defaults->getDefaults()
        );
        $this->_cache->setQuery(
            Horde_Kolab_Storage_List_Query_List_Cache::PERSONAL_DEFAULTS,
            $this->_defaults->getPersonalDefaults()
        );

        $this->_cache->save();
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
        if (!$this->_cache->hasNamespace()) {
            // Cache not synchronized yet.
            return;
        }
        $folder_list = $this->_cache->getFolders();
        $folder_list[] = $folder;
        $annotations = $this->_cache->getFolderTypes();
        if ($type !== null) {
            $annotations[$folder] = $type;
        }
        $namespace = unserialize($this->_cache->getNamespace());
        $this->_synchronize($namespace, $folder_list, $annotations);
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
        if (!$this->_cache->hasNamespace()) {
            // Cache not synchronized yet.
            return;
        }
        $folder_list = $this->_cache->getFolders();
        $folder_list = array_diff($folder_list, array($folder));
        $annotations = $this->_cache->getFolderTypes();
        if (isset($annotations[$folder])) {
            unset($annotations[$folder]);
        }
        $namespace = unserialize($this->_cache->getNamespace());
        $this->_synchronize($namespace, $folder_list, $annotations);
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
        if (!$this->_cache->hasNamespace()) {
            // Cache not synchronized yet.
            return;
        }
        $folder_list = $this->_cache->getFolders();
        $folder_list = array_diff($folder_list, array($old));
        $folder_list[] = $new;
        $annotations = $this->_cache->getFolderTypes();
        if (isset($annotations[$old])) {
            $annotations[$new] = $annotations[$old];
            unset($annotations[$old]);
        }
        $namespace = unserialize($this->_cache->getNamespace());
        $this->_synchronize($namespace, $folder_list, $annotations);
    }

    /**
     * Set the specified folder as default for its current type.
     *
     * @param array  $folder   The folder data.
     * @param string|boolean $previous The previous default folder or false if there was none.
     */
    public function setDefault($folder, $previous = false)
    {
        if (!$this->_cache->hasNamespace()) {
            // Cache not synchronized yet.
            return;
        }
        if ($folder['namespace'] !== Horde_Kolab_Storage_Folder_Namespace::PERSONAL) {
            throw new Horde_Kolab_Storage_List_Exception(
                sprintf(
                    "Unable to mark %s as a default folder. It is not within your personal namespace!",
                    $folder
                )
            );
        }
        $annotations = $this->_cache->getFolderTypes();
        if (!isset($annotations[$folder['folder']])) {
            throw new Horde_Kolab_Storage_List_Exception(
                sprintf(
                    "The folder %s has no Kolab type. It cannot be marked as 'default' folder!",
                    $folder
                )
            );
        }
        if ($previous) {
            $this->_driver->setAnnotation(
                $previous,
                Horde_Kolab_Storage_List_Query_List::ANNOTATION_FOLDER_TYPE,
                $folder['type']
            );
            $annotations[$previous] = $folder['type'];
        }
        $this->_driver->setAnnotation(
            $folder['folder'],
            Horde_Kolab_Storage_List_Query_List::ANNOTATION_FOLDER_TYPE,
            $folder['type'] . '.default'
        );
        $annotations[$folder['folder']] = $folder['type'] . '.default';

        $folder_list = $this->_cache->getFolders();
        $namespace = unserialize($this->_cache->getNamespace());
        $this->_synchronize($namespace, $folder_list, $annotations);
    }

    /**
     * Return any default folder duplicates.
     *
     * @return array The list of duplicate default folders accessible to the current user.
     */
    public function getDuplicateDefaults()
    {
        return $this->_defaults->getDuplicates();
    }
}