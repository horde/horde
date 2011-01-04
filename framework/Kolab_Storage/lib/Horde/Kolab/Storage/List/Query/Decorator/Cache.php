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
class Horde_Kolab_Storage_List_Query_Decorator_Cache
implements Horde_Kolab_Storage_List_Query
{
    /** The folder type list */
    const TYPES = 'TYPES';

    /** The folder list sorted by type */
    const BY_TYPE = 'BY_TYPE';

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
     * @param Horde_Kolab_Storage_List       $list       The queriable list.
     * @param Horde_Kolab_Storage_Factory    $factory    The factory.
     * @param Horde_Kolab_Storage_Cache_List $list_cache The list cache.
     */
    public function __construct(
        Horde_Kolab_Storage_List $list,
        Horde_Kolab_Storage_Factory $factory,
        Horde_Kolab_Storage_Cache_List $list_cache
    ) {
        $this->_list = $list;
        $this->_list_cache = $list_cache;
        $this->_factory = $factory;
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
    }
}