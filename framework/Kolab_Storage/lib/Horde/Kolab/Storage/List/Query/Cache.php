<?php
/**
 * The cached list query.
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
 * The cached list query.
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
class Horde_Kolab_Storage_List_Query_Cache
implements Horde_Kolab_Storage_List_Query
{
    /** The folder type list */
    const TYPES = 'TYPES';

    /** The folder list sorted by type */
    const BY_TYPE = 'BY_TYPE';

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
    public function __construct(
        Horde_Kolab_Storage_List $list,
        $params
    ) {
        $this->_list = $list;
        $this->_list_cache = $params['cache'];
        $this->_factory = $params['factory'];
    }

    /**
     * Returns the folder types as associative array.
     *
     * @return array The list folder types with the folder names as key and the
     *               type as values.
     */
    public function listTypes()
    {
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
        $by_type = $this->_list_cache->getQuery(self::BY_TYPE);
        if (isset($by_type[$type])) {
            return $by_type[$type];
        } else {
            return array();
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
        return $this->_list_cache->getQuery(self::OWNERS);
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
        $defaults = $this->_list_cache->getQuery(self::DEFAULTS);
        if (isset($defaults[$owner][$type])) {
            return $defaults[$owner][$type];
        } else {
            return false;
        }
    }

    /**
     * Synchronize the query data with the information from the backend.
     *
     * @return NULL
     */
    public function synchronize()
    {
        $types = array();
        $by_type = array();
        foreach ($this->listFolderTypeAnnotations() as $folder => $annotation) {
            $type = $annotation->getType();
            $types[$folder] = $type;
            $by_type[$type][] = $folder;
        }
        $this->_list_cache->setQuery(self::TYPES, $types);
        $this->_list_cache->setQuery(self::BY_TYPE, $by_type);

        $owners = array();
        $namespace = $this->_list->getNamespace();
        foreach ($this->_list->listFolders() as $folder) {
            $owners[$folder] = $namespace->getOwner($folder);
        }
        $this->_list_cache->setQuery(self::OWNERS, $owners);

        $personal_defaults = array();
        $defaults = array();
        $namespace = $this->_list->getNamespace();
        foreach ($this->listFolderTypeAnnotations() as $folder => $annotation) {
            if ($annotation->isDefault()) {
                $type = $annotation->getType();
                $owner = $namespace->getOwner($folder);
                if (!isset($defaults[$owner][$type])) {
                    $defaults[$owner][$type] = $folder;
                } else {
                    throw new Horde_Kolab_Storage_Exception(
                        sprintf(
                            'Both folders %s and %s are marked as default folder of type %s!',
                            $defaults[$owner][$type],
                            $folder,
                            $type
                        )
                    );
                }
                if ($namespace->matchNamespace($folder)->getType()
                    == Horde_Kolab_Storage_Folder_Namespace::PERSONAL) {
                    if (!isset($personal_defaults[$type])) {
                        $personal_defaults[$type] = $folder;
                    } else {
                        throw new Horde_Kolab_Storage_Exception(
                            sprintf(
                                'Both folders %s and %s are marked as default folder of type %s!',
                                $personal_defaults[$type],
                                $folder,
                                $type
                            )
                        );
                    }
                }
            }
        }
        $this->_list_cache->setQuery(self::DEFAULTS, $defaults);
        $this->_list_cache->setQuery(
            self::PERSONAL_DEFAULTS, $personal_defaults
        );
    }
}