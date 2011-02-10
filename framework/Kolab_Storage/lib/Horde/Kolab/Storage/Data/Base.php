<?php
/**
 * The basic handler for data objects in a Kolab storage folder.
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
 * The basic handler for data objects in a Kolab storage folder.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Data_Base
implements Horde_Kolab_Storage_Data
{
    /**
     * The link to the parent folder object.
     *
     * @var Horde_Kolab_Folder
     */
    private $_folder;

    /**
     * The driver for accessing the Kolab storage system.
     *
     * @var Horde_Kolab_Storage_Driver
     */
    private $_driver;

    /**
     * The factory for generating additional resources.
     *
     * @var Horde_Kolab_Storage_Factory
     */
    private $_factory;

    /**
     * The folder type.
     *
     * @var string
     */
    private $_type;

    /**
     * The version of the data.
     *
     * @var int
     */
    private $_version;

    /**
     * The list of registered queries.
     *
     * @var array
     */
    private $_queries = array();

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Folder  $folder  The folder to retrieve the
     *                                             data from.
     * @param Horde_Kolab_Storage_Driver  $driver  The primary connection driver.
     * @param Horde_Kolab_Storage_Factory $factory The factory.
     * @param string                      $type     The type of data we want to
     *                                              access in the folder.
     * @param int                         $version Format version of the object
     *                                             data.
     */
    public function __construct(
        Horde_Kolab_Storage_Folder $folder,
        Horde_Kolab_Storage_Driver $driver,
        Horde_Kolab_Storage_Factory $factory,
        $type = null,
        $version = 1
    ) {
        $this->_folder  = $folder;
        $this->_driver  = $driver;
        $this->_factory = $factory;
        $this->_type    = $type;
        $this->_version = $version;
    }

    /**
     * Return the data type represented by this object.
     *
     * @return string The type of data this instance handles.
     */
    public function getType()
    {
        if ($this->_type === null) {
            $this->_type = $this->_folder->getType();
        }
        return $this->_type;
    }

    /**
     * Report the status of this folder.
     *
     * @return Horde_Kolab_Storage_Folder_Stamp The stamp that can be used for
     *                                          detecting folder changes.
     */
    public function getStamp()
    {
        return $this->_driver->getStamp($this->_folder->getPath());
    }

    /**
     * Retrieves the body part for the given UID and mime part ID.
     *
     * @param string $uid The message UID.
     * @param string $id  The mime part ID.
     *
     * @return @TODO
     */
    public function fetchPart($uid, $id)
    {
        if (!method_exists($this->_driver, 'fetchBodypart')) {
            throw new Horde_Kolab_Storage_Exception(
                'The backend does not support the "fetchBodypart" method!'
            );
        }
        return $this->_driver->fetchBodypart(
            $this->_folder->getPath(), $uid, $id
        );
    }

    /**
     * Retrieves the objects for the given UIDs.
     *
     * @param array $uids The message UIDs.
     *
     * @return array An array of objects.
     */
    public function fetch($uids)
    {
        return $this->_driver->fetch(
            $this->_folder->getPath(),
            $uids,
            array('type' => $this->_type, 'version' => $this->_version)
        );
    }

    /**
     * Register a query to be updated if the underlying data changes.
     *
     * @param string                    $name  The query name.
     * @param Horde_Kolab_Storage_Query $query The query to register.
     *
     * @return NULL
     */
    public function registerQuery($name, Horde_Kolab_Storage_List_Query $query)
    {
        $this->_queries[$name] = $query;
    }

    /**
     * Return a registered query.
     *
     * @param string $name The query name.
     *
     * @return Horde_Kolab_Storage_Query The requested query.
     *
     * @throws Horde_Kolab_Storage_Exception In case the requested query does
     *                                       not exist.
     */
    public function getQuery($name = null)
    {
        if ($name === null) {
            $name = self::QUERY_BASE;
        }
        if (isset($this->_queries[$name])) {
            return $this->_queries[$name];
        } else {
            throw new Horde_Kolab_Storage_Exception('No such query!');
        }
    }
}
