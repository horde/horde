<?php
/**
 * The basis for Kolab storage access.
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
 * The basis for Kolab storage access.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
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
     * The cache.
     *
     * @var Horde_Kolab_Storage_Cache
     */
    protected $_cache;

    /**
     * A logger.
     *
     * @var Horde_Log_Logger
     */
    private $_logger;

    /**
     * Additional parameters.
     *
     * @var array
     */
    private $_params;

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
     * @param Horde_Kolab_Storage_Cache    $cache      The cache.
     * @param Horde_Log_Logger             $logger  A logger.
     * @param array                        $params     Additional parameters.
     */
    public function __construct(Horde_Kolab_Storage_Driver $master,
                                Horde_Kolab_Storage_QuerySet $query_set,
                                Horde_Kolab_Storage_Factory $factory,
                                Horde_Kolab_Storage_Cache $cache,
                                $logger,
                                array $params = array())
    {
        $this->_master    = $master;
        $this->_query_set = $query_set;
        $this->_factory   = $factory;
        $this->_cache     = $cache;
        $this->_logger    = $logger;
        $this->_params    = $params;
    }

    /**
     * Get a folder list object for a "system" user.
     *
     * @param string $type The type of system user.
     *
     * @return Horde_Kolab_Storage_List The handler for the list of folders
     *                                  present in the Kolab backend.
     */
    public function getSystemList($type)
    {
        if (!isset($this->_params['system'][$type])) {
            if (!isset($this->_params['system'][''])) {
                throw new Horde_Kolab_Storage_Exception(
                    'No system users are available!'
                );
            } else {
                $params = $this->_params['system'][''];
            }
        } else {
            $params = $this->_params['system'][$type];
        }

        return $this->getList(
            $this->_factory->createDriver(array('params' => $params))
        );
    }

    /**
     * Get the folder list object.
     *
     * @params Horde_Kolab_Storage_Driver $driver Optional driver as backend
     *                                            for the list.
     *
     * @return Horde_Kolab_Storage_List The handler for the list of folders
     *                                  present in the Kolab backend.
     */
    public function getList(Horde_Kolab_Storage_Driver $driver = null)
    {
        if ($driver === null) {
            $driver = $this->_master;
        }
        if (!isset($this->_lists[$driver->getId()])) {
            $this->_lists[$driver->getId()] = new Horde_Kolab_Storage_List_Tools(
                $driver, $this->_cache, $this->_logger, $this->_params
            );
        }
        return $this->_lists[$driver->getId()];
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
        return new Horde_Kolab_Storage_Folder_Base(
            $this->getList()->getQuery(
                Horde_Kolab_Storage_List_Tools::QUERY_BASE
            ),
            $folder
        );
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
     * @return Horde_Kolab_Storage_Data The data object.
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
                $folder = $this->getFolder($folder);
            }
            $this->_data[$key] = $this->_createData(
                $folder,
                $this->_master,
                $this->_factory,
                $object_type,
                $data_version
            );
            if (isset($this->_params['logger'])) {
                $this->_data[$key] = new Horde_Kolab_Storage_Data_Decorator_Log(
                    $this->_data[$key], $this->_params['logger']
                );
            }
            $this->_query_set->addDataQuerySet($this->_data[$key]);
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

