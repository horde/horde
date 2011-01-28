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
        $list_cache = new Horde_Kolab_Storage_Cache_List(
            $this->_cache
        );
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->_storage->getList(),
            $list_cache
        );
        $list->registerQuery(
            'Base',
            $this->_factory->createListQuery(
                'Horde_Kolab_Storage_List_Query_Cache',
                $list,
                array('cache' => $list_cache)
            )
        );
        return $list;
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
        return $this->_storage->getFolder($folder);
    }

    /**
     * Return a data handler for accessing data in the specified
     * folder.
     *
     * @param string $folder The name of the folder.
     * @param string $type   The type of data we want to
     *                       access in the folder.
     *
     * @return Horde_Kolab_Data The data object.
     */
    public function getData($folder, $type)
    {
        return $this->_storage->getData();
    }

}