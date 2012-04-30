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
implements Horde_Kolab_Storage_List_Query_List
{
    /** The folder type list */
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
     * The queriable list.
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
     * The factory for generating additional resources.
     *
     * @var Horde_Kolab_Storage_Factory
     */
    private $_factory;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_List $list   The queriable list.
     * @param array                    $params Additional parameters.
     */
    public function __construct(Horde_Kolab_Storage_List $list,
                                $params)
    {
        $this->_list = $list;
        $this->_list_cache = $params['cache'];
        $this->_factory = $params['factory'];
    }

    /**
     * Ensure we have the query data.
     *
     * @param string $query The query data required.
     *
     * @return NULL
     */
    private function _init($query)
    {
        if (!$this->_list_cache->hasQuery($query)) {
            $this->synchronize();
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
        $this->_init(self::TYPES);
        return $this->_list_cache->getQuery(self::TYPES);
    }

    /**
     * Returns the folder type annotation as associative array.
     *
     * @return array The list folder types with the folder names as key and the
     *               type handler as values.
     */
    public function listFolderTypeAnnotations()
    {
        if (!$this->_list_cache->hasFolderTypes()) {
            $this->synchronize();
        }
        $result = array();
        $list = $this->_list_cache->getFolderTypes();
        foreach ($list as $folder => $annotation) {
            $result[$folder] = $this->_factory->createFolderType($annotation);
        }
        return $result;
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
        $this->_init(self::BY_TYPE);
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
        $this->_init(self::BY_TYPE);
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
        $this->_init(self::FOLDERS);
        $folders = $this->_list_cache->getQuery(self::FOLDERS);
        if (isset($folders[$folder])) {
            return $folders[$folder];
        } else {
            throw new Horde_Kolab_Storage_Exception(
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
        $this->_init(self::OWNERS);
        return $this->_list_cache->getQuery(self::OWNERS);
    }

    /**
     * Return the list of personal default folders.
     *
     * @return array An array that associates type (key) with the corresponding
     *               default folder name (value).
     */
    public function listPersonalDefaults()
    {
        $this->_init(self::PERSONAL_DEFAULTS);
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
        $this->_init(self::DEFAULTS);
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
        $this->_init(self::PERSONAL_DEFAULTS);
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
        $this->_init(self::DEFAULTS);
        $defaults = $this->_list_cache->getQuery(self::DEFAULTS);
        if (isset($defaults[$owner][$type])) {
            return $defaults[$owner][$type];
        } else {
            return false;
        }
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
        $this->synchronize();
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
        $this->synchronize();
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
        $this->synchronize();
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
     * Synchronize the query data with the information from the backend.
     *
     * @param array $params Additional parameters.
     *
     * @return NULL
     */
    public function synchronize($params = array())
    {
        $namespace = $this->_list->getNamespace();
        $annotations = $this->listFolderTypeAnnotations();
        $mail_type = $this->_factory->createFolderType('mail');

        $folders = array();
        $owners = array();
        $types = array();
        $by_type = array();
        $personal_defaults = array();
        $defaults = array();

        foreach ($this->_list->listFolders() as $folder) {
            $folder = strval($folder);
            if (!isset($annotations[$folder])) {
                $type = $mail_type;
            } else {
                $type = $annotations[$folder];
            }
            $folder_type = $type->getType();
            $owner = $namespace->getOwner($folder);

            $owners[$folder] = $owner;
            $folders[$folder] = array(
                'type' => $folder_type,
                'default' => $type->isDefault(),
                'namespace' => $namespace->matchNamespace($folder)->getType(),
                'prefix' => $namespace->matchNamespace($folder)->getName(),
                'owner' => $owner,
                'name' => $namespace->getTitle($folder),
                'subpath' => $namespace->getSubpath($folder),
                'parent' => $namespace->getParent($folder),
                'delimiter' => $namespace->matchNamespace($folder)->getDelimiter(),
            );

            $types[$folder] = $folders[$folder]['type'];
            $by_type[$folder_type][$folder] = array(
                'default' => $folders[$folder]['parent'],
                'owner' => $folders[$folder]['owner'],
                'name' => $folders[$folder]['name'],
                'prefix' => $folders[$folder]['prefix'],
                'parent' => $folders[$folder]['parent'],
                'delimiter' => $folders[$folder]['delimiter'],
                'subpath' => $folders[$folder]['subpath'],
                'folder' => $folder,
            );

            if ($folders[$folder]['default']) {
                if (!isset($defaults[$owner][$folder_type])) {
                    $defaults[$owner][$folder_type] = $folder;
                } else {
                    throw new Horde_Kolab_Storage_Exception(
                        sprintf(
                            'Both folders %s and %s are marked as default folder of type %s!',
                            $defaults[$owner][$folder_type],
                            $folder,
                            $folder_type
                        )
                    );
                }
                if ($folders[$folder]['namespace']
                    == Horde_Kolab_Storage_Folder_Namespace::PERSONAL) {
                    if (!isset($personal_defaults[$folder_type])) {
                        $personal_defaults[$folder_type] = $folder;
                    } else {
                        throw new Horde_Kolab_Storage_Exception(
                            sprintf(
                                'Both folders %s and %s are marked as default folder of type %s!',
                                $personal_defaults[$folder_type],
                                $folder,
                                $folder_type
                            )
                        );
                    }
                }
            }
        }

        $this->_list_cache->setQuery(self::FOLDERS, $folders);
        $this->_list_cache->setQuery(self::OWNERS, $owners);
        $this->_list_cache->setQuery(self::TYPES, $types);
        $this->_list_cache->setQuery(self::BY_TYPE, $by_type);
        $this->_list_cache->setQuery(self::DEFAULTS, $defaults);
        $this->_list_cache->setQuery(
            self::PERSONAL_DEFAULTS, $personal_defaults
        );
    }
}