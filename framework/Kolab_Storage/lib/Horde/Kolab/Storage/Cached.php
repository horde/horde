<?php
/**
 * The cached variant for the Kolab storage handler [the default].
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
 * The cached variant for the Kolab storage handler [the default].
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
class Horde_Kolab_Storage_Cached
extends Horde_Kolab_Storage_Base
{
    /**
     * The cache.
     *
     * @var Horde_Kolab_Storage_Cache
     */
    private $_cache;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Driver   $master     The primary connection
     *                                                 driver.
     * @param Horde_Kolab_Storage_QuerySet $query_set  The query handler.
     * @param Horde_Kolab_Storage_Factory  $factory    The factory.
.
     * @param Horde_Kolab_Storage_Cache    $cache      The cache.
     * @param array                        $params     Additional parameters.
     */
    public function __construct(
        Horde_Kolab_Storage_Driver $master,
        Horde_Kolab_Storage_QuerySet $query_set,
        Horde_Kolab_Storage_Factory $factory,
        Horde_Kolab_Storage_Cache $cache,
        array $params = array()
    ) {
        parent::__construct($master, $query_set, $factory, $params);
        $this->_cache = $cache;
    }

    /**
     * Create the folder list object.
     *
     * @param Horde_Kolab_Storage_Driver  $master  The primary connection driver.
     * @param Horde_Kolab_Storage_Factory $factory The factory.
     *
     * @return Horde_Kolab_Storage_List The handler for the list of folders
     *                                  present in the Kolab backend.
     */
    protected function _createList(
        Horde_Kolab_Storage_Driver $master,
        Horde_Kolab_Storage_Factory $factory
    ) {
        $decorated_list = new Horde_Kolab_Storage_List_Base($master, $factory);
        $list_cache = $this->_cache->getListCache(
            $decorated_list->getIdParameters()
        );
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $decorated_list,
            $list_cache
        );
        return $list;
    }

    /**
     * Return a data handler for accessing data in the specified
     * folder.
     *
     * @param mixed                       $folder       The name of the folder or
     *                                                  an instance representing
     *                                                  the folder.
     * @param Horde_Kolab_Storage_Driver  $master       The primary connection
     *                                                  driver.
     * @param Horde_Kolab_Storage_Factory $factory      The factory.
     * @param string                      $object_type  The type of data we want
     *                                                  to access in the folder.
     * @param int                         $data_version Format version of the
     *                                                  object data.
     *
     * @return Horde_Kolab_Data The data object.
     */
    protected function _createData(
        $folder,
        Horde_Kolab_Storage_Driver $master,
        Horde_Kolab_Storage_Factory $factory,
        $object_type = null,
        $data_version = 1
    ) {
        $data = new Horde_Kolab_Storage_Data_Base(
            $folder,
            $master,
            $factory,
            $object_type,
            $data_version
        );
        return new Horde_Kolab_Storage_Data_Decorator_Cache(
            $data, $this->_cache->getDataCache($data->getIdParameters())
        );
    }
}