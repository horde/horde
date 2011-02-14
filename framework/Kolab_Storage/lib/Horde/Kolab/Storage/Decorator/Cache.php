<?php
/**
 * A cache decorator for the Kolab storage handler.
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
 * A cache decorator for the Kolab storage handler.
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Decorator_Cache
implements Horde_Kolab_Storage
{
    /**
     * The decorated storage handler.
     *
     * @var Horde_Kolab_Storage
     */
    private $_storage;

    /**
     * The cache.
     *
     * @var Horde_Kolab_Storage_Cache
     */
    private $_cache;

    /**
     * The factory for generating additional resources.
     *
     * @var Horde_Kolab_Storage_Factory
     */
    private $_factory;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage       $storage The storage handler.
     * @param Horde_Kolab_Storage_Cache $cache   The cache.
     * @param Horde_Kolab_Storage_Factory $factory The factory.
     */
    public function __construct(
        Horde_Kolab_Storage $storage, 
        Horde_Kolab_Storage_Cache $cache,
        Horde_Kolab_Storage_Factory $factory
    ) {
        $this->_storage = $storage;
        $this->_cache = $cache;
        $this->_factory = $factory;
    }

    /**
     * Get the folder list object.
     *
     * @return Horde_Kolab_Storage_List The handler for the list of folders
     *                                  present in the Kolab backend.
     */
    public function getList()
    {
        $decorated_list = $this->_storage->getList();
        $list_cache = $this->_cache->getListCache(
            $decorated_list->getConnectionParameters()
        );
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->_storage->getList(),
            $list_cache
        );
        $this->addListQuery($list, Horde_Kolab_Storage_List::QUERY_BASE);
        $this->addListQuery($list, Horde_Kolab_Storage_List::QUERY_ACL);
        return $list;
    }

    /**
     * Add a list query.
     *
     * @param Horde_Kolab_Storage_List $list   The list.
     * @param string                   $type   The query type.
     * @param array                    $params Additional query parameters.
     *
     * @return NULL
     */
    public function addListQuery(Horde_Kolab_Storage_List $list, $type, $params = array())
    {
        switch ($type) {
        case Horde_Kolab_Storage_List::QUERY_SHARE:
            $class = 'Horde_Kolab_Storage_List_Query_Share_Cache';
            break;
        case Horde_Kolab_Storage_List::QUERY_BASE:
            $class = 'Horde_Kolab_Storage_List_Query_List_Cache';
            break;
        case Horde_Kolab_Storage_List::QUERY_ACL:
            $class = 'Horde_Kolab_Storage_List_Query_Acl_Cache';
            break;
        default:
            $this->_storage->addListQuery($list, $type, $params);
            return;
        }
        $params = array_merge(
            $params,
            array(
                'cache' => $this->_cache->getListCache(
                    $list->getConnectionParameters()
                )
            )
        );
        $list->registerQuery(
            $type, $this->_factory->createListQuery($class, $list, $params)
        );
    }

    /**
     * Get a Folder object.
     *
     * @param string $folder The folder name.
     *
     * @return Horde_Kolab_Storage_Folder The Kolab folder object.
     */
    public function getFolder($folder)
    {
        return $this->getList()->getFolder($folder);
    }

    /**
     * Return a data handler for accessing data in the specified
     * folder.
     *
     * @param string $folder       The name of the folder.
     * @param string $object_type  The type of data we want to
     *                             access in the folder.
     * @param int    $data_version Format version of the object data.
     *
     * @return Horde_Kolab_Data The data object.
     */
    public function getData($folder, $object_type = null, $data_version = 1)
    {
        return $this->_storage->getData($folder, $object_type, $data_version);
    }
}