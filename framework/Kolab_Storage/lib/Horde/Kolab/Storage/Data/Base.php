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
implements Horde_Kolab_Storage_Data, Horde_Kolab_Storage_Data_Query
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
     * Return the ID of this data handler.
     *
     * @return string The ID.
     */
    public function getId()
    {
        $id = $this->_driver->getParameters();
        unset($id['user']);
        $id['owner'] = $this->_folder->getOwner();
        $id['folder'] = $this->_folder->getSubpath();
        $id['type'] = $this->_type;
        ksort($id);
        return md5(serialize($id));
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
     * Return the data version.
     *
     * @return string The data version.
     */
    public function getVersion()
    {
        return $this->_version;
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
            array('type' => $this->getType(), 'version' => $this->_version)
        );
    }

    /**
     * Return the backend ID for the given object ID.
     *
     * @param string $object_uid The object ID.
     *
     * @return string The backend ID for the object.
     */
    public function getBackendId($object_id)
    {
        $by_obid = $this->fetch($this->getStamp()->ids());
        foreach ($by_obid as $obid => $object) {
            if ($object['uid'] == $object_id) {
                return $obid;
            }
        }
        throw new Horde_Kolab_Storage_Exception(
            sprintf('Object ID %s does not exist!', $object_id)
        );
    }

    /**
     * Generate a unique object ID.
     *
     * @return string  The unique ID.
     */
    public function generateUid()
    {
        //@todo: implement
    }

    /**
     * Check if the given object ID exists.
     *
     * @param string $object_id The object ID.
     *
     * @return boolean True if the ID was found, false otherwise.
     */
    public function objectIdExists($object_id)
    {
        return array_key_exists(
            $object_id, $this->getObjects()
        );
    }

    /**
     * Return the specified object.
     *
     * @param string $object_id The object id.
     *
     * @return array The object data as an array.
     */
    public function getObject($object_id)
    {
        $objects = $this->getObjects();
        if (isset($objects[$object_id])) {
            return $objects[$object_id];
        } else {
            throw new Horde_Kolab_Storage_Exception(
                sprintf('Object ID %s does not exist!', $object_id)
            );
        }
    }

    /**
     * Return the specified attachment.
     *
     * @param string $attachment_id The attachment id.
     *
     * @return resource An open stream to the attachment data.
     */
    public function getAttachment($attachment_id)
    {
        //@todo: implement
    }

    /**
     * Retrieve all object ids in the current folder.
     *
     * @return array The object ids.
     */
    public function getObjectIds()
    {
        return array_keys($this->getObjects());
    }

    /**
     * Retrieve all objects in the current folder.
     *
     * @return array An array of all objects.
     */
    public function getObjects()
    {
        $by_oid  = array();
        $by_obid = $this->fetch($this->getStamp()->ids());
        foreach ($by_obid as $obid => $object) {
            $by_oid[$object['uid']] = $object;
        }
        return $by_oid;
    }

    /**
     * Register a query to be updated if the underlying data changes.
     *
     * @param string                    $name  The query name.
     * @param Horde_Kolab_Storage_Query $query The query to register.
     *
     * @return NULL
     */
    public function registerQuery($name, Horde_Kolab_Storage_Query $query)
    {
        if (!$query instanceOf Horde_Kolab_Storage_Data_Query) {
            throw new Horde_Kolab_Storage_Exception(
                'The provided query is no data query.'
            );
        }
        $this->_queries[$name] = $query;
    }

    /**
     * Synchronize the data information with the information from the backend.
     *
     * @return NULL
     */
    public function synchronize()
    {
        foreach ($this->_queries as $name => $query) {
            $query->synchronize();
        }
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
