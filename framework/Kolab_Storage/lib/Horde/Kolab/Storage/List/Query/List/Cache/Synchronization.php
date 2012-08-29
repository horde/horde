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
     * Synchronize the query data with the information from the backend.
     *
     * @param Horde_Kolab_Storage_List_Cache $cache The reference to the cache
     *                                              that should reveive the update.
     *
     * @return NULL
     */
    public function synchronize(Horde_Kolab_Storage_List_Cache $cache)
    {
        $namespace = $this->_driver->getNamespace();
        if (!$cache->hasNamespace()) {
            $cache->setNamespace(serialize($namespace));
        }

        $folder_list = $this->_driver->listFolders();
        $annotations = $this->_driver->listAnnotation(Horde_Kolab_Storage_List_Query_List::ANNOTATION_FOLDER_TYPE);

        $folders = array();
        $owners = array();
        $types = array();
        $by_type = array();
        $mail_type = $this->_folder_types->create('mail');

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

        $cache->store($folder_list, $types);

        $cache->setQuery(Horde_Kolab_Storage_List_Query_List_Cache::FOLDERS, $folders);
        $cache->setQuery(Horde_Kolab_Storage_List_Query_List_Cache::OWNERS, $owners);
        $cache->setQuery(Horde_Kolab_Storage_List_Query_List_Cache::BY_TYPE, $by_type);
        $cache->setQuery(
            Horde_Kolab_Storage_List_Query_List_Cache::DEFAULTS,
            $this->_defaults->getDefaults()
        );
        $cache->setQuery(
            Horde_Kolab_Storage_List_Query_List_Cache::PERSONAL_DEFAULTS,
            $this->_defaults->getPersonalDefaults()
        );

        $cache->save();
    }
}