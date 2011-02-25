<?php
/**
 * The basis for Kolab storage access.
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
 * The basis for Kolab storage access.
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
abstract class Horde_Kolab_Storage_Base
implements Horde_Kolab_Storage
{
    /**
     * The master Kolab storage system.
     *
     * @var Horde_Kolab_Storage_Driver
     */
    private $_master;

    /**
     * The query handler.
     *
     * @var Horde_Kolab_Storage_QuerySet
     */
    private $_query_set;

    /**
     * The factory for generating additional resources.
     *
     * @var Horde_Kolab_Storage_Factory
     */
    private $_factory;

    /**
     * List instances.
     *
     * @var array
     */
    private $_lists;

    /**
     * Data instances.
     *
     * @var array
     */
    private $_data;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Driver   $master     The primary connection
     *                                                 driver.
     * @param Horde_Kolab_Storage_QuerySet $query_set  The query handler.
     * @param Horde_Kolab_Storage_Factory  $factory    The factory.
.
     */
    public function __construct(
        Horde_Kolab_Storage_Driver $master,
        Horde_Kolab_Storage_QuerySet $query_set,
        Horde_Kolab_Storage_Factory $factory
    ) {
        $this->_master    = $master;
        $this->_query_set = $query_set;
        $this->_factory   = $factory;
    }

    /**
     * Get the folder list object.
     *
     * @return Horde_Kolab_Storage_List The handler for the list of folders
     *                                  present in the Kolab backend.
     */
    public function getList()
    {
        if (!isset($this->_lists[$this->_master->getId()])) {
            $list = $this->_createList($this->_master, $this->_factory);
            $this->_query_set->addListQuerySet($list);
            $this->_lists[$this->_master->getId()] = $list;
        }
        return $this->_lists[$this->_master->getId()];
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
    abstract protected function _createList(
        Horde_Kolab_Storage_Driver $master,
        Horde_Kolab_Storage_Factory $factory
    );

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
     * Return a data handler for accessing data in the specified folder.
     *
     * @param mixed  $folder       The name of the folder or an instance
     *                             representing the folder.
     * @param string $object_type  The type of data we want to access in the
     *                             folder.
     * @param int    $data_version Format version of the object data.
     *
     * @return Horde_Kolab_Data The data object.
     */
    public function getData($folder, $object_type = null, $data_version = 1)
    {
        if ($folder instanceOf Horde_Kolab_Storage_Folder) {
            $folder_key = $folder->getPath();
        } else {
            $folder_key = $folder;
        }
        $key = join(
            '@',
            array(
                $data_version,
                $object_type,
                $folder_key,
                $this->_master->getId()
            )
        );
        if (!isset($this->_data[$key])) {
            if (!$folder instanceOf Horde_Kolab_Storage_Folder) {
                $folder = $this->getList()->getFolder($folder);
            }
            $this->_data[$key] = $this->_createData(
                $folder,
                $this->_master,
                $this->_factory,
                $object_type,
                $data_version
            );
        }
        return $this->_data[$key];
    }

    /**
     * Return a data handler for accessing data in the specified folder.
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
    abstract protected function _createData(
        $folder,
        Horde_Kolab_Storage_Driver $master,
        Horde_Kolab_Storage_Factory $factory,
        $object_type = null,
        $data_version = 1
    );
}

