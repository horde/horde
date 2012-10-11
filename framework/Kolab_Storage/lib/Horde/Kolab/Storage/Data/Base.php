<?php
/**
 * The basic handler for data objects in a Kolab storage folder.
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
 * The basic handler for data objects in a Kolab storage folder.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_Data_Base
implements Horde_Kolab_Storage_Data, Horde_Kolab_Storage_Data_Query
{
    /**
     * The link to the parent folder object.
     *
     * @var Horde_Kolab_Storage_Folder
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
    public function __construct(Horde_Kolab_Storage_Folder $folder,
                                Horde_Kolab_Storage_Driver $driver,
                                Horde_Kolab_Storage_Factory $factory,
                                $type = null,
                                $version = 1)
    {
        $this->_folder  = $folder;
        $this->_driver  = $driver;
        $this->_factory = $factory;
        $this->_type    = $type;
        $this->_version = $version;
    }

    /**
     * Return the folder path for this data handler.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @return string The folder path.
     */
    public function getPath()
    {
        return $this->_folder->getPath();
    }

    /**
     * Return the ID of the current user.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @return string The current user.
     */
    public function getAuth()
    {
        return $this->_driver->getAuth();
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
        $id['prefix'] = $this->_folder->getPrefix();
        $id['folder'] = $this->_folder->getSubpath();
        $id['type'] = $this->getType();
        ksort($id);
        return md5(serialize($id));
    }

    /**
     * Return the ID parameters for this data handler.
     *
     * @return array The ID parameters.
     */
    public function getIdParameters()
    {
        $id = $this->_driver->getParameters();
        unset($id['user']);
        $id['owner'] = $this->_folder->getOwner();
        $id['prefix'] = $this->_folder->getPrefix();
        $id['folder'] = $this->_folder->getSubpath();
        $id['type'] = $this->getType();
        return $id;
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
     * Create a new object.
     *
     * @param array   &$object The array that holds the object data.
     * @param boolean $raw     True if the data to be stored has been provided in
     *                         raw format.
     *
     * @return string The ID of the new object or true in case the backend does
     *                not support this return value.
     *
     * @throws Horde_Kolab_Storage_Exception In case an error occured while
     *                                       saving the data.
     */
    public function create(&$object, $raw = false)
    {
        if ($raw === false) {
            $writer = new Horde_Kolab_Storage_Object_Writer_Format(
                new Horde_Kolab_Format_Factory(),
                array('version' => $this->_version)
            );
        } else {
            $writer = new Horde_Kolab_Storage_Object_Writer_Raw();
        }
        $storage_object = new Horde_Kolab_Storage_Object();
        $storage_object->setDriver($this->_driver);
        $storage_object->setData($object);
        $result = $storage_object->create($this->_folder, $writer, $this->getType());

        if ($result === true) {
            $params = array();
        } else {
            $params = array(
                'changes' => array(
                    Horde_Kolab_Storage_Folder_Stamp::ADDED => array(
                        $result => $storage_object
                    ),
                    Horde_Kolab_Storage_Folder_Stamp::DELETED => array()
                )
            );
        }
        $this->synchronize($params);
        return $result;
    }

    /**
     * Modify an existing object.
     *
     * @param array   $object The array that holds the updated object data.
     * @param boolean $raw    True if the data to be stored has been provided in
     *                        raw format.
     *
     * @return string The new backend ID of the modified object or true in case
     *                the backend does not support this return value.
     *
     * @throws Horde_Kolab_Storage_Exception In case an error occured while
     *                                       saving the data.
     */
    public function modify($object, $raw = false)
    {
        if (!isset($object['uid'])) {
            throw new Horde_Kolab_Storage_Exception(
                'The provided object data contains no ID value!'
            );
        }
        try {
            $obid = $this->getBackendId($object['uid']);
        } catch (Horde_Kolab_Storage_Exception $e) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        'The message with ID %s does not exist. This probably means that the Kolab object has been modified by somebody else since you retrieved the object from the backend. Original error: %s'
                    ),
                    $object['uid'],
                    0,
                    $e
                )
            );
        }

        if ($raw === false) {
            $writer = new Horde_Kolab_Storage_Object_Writer_Format(
                new Horde_Kolab_Format_Factory(),
                array('version' => $this->_version)
            );
        } else {
            $writer = new Horde_Kolab_Storage_Object_Writer_Raw();
        }
        if (!$object instanceOf Horde_Kolab_Storage_Object) {
            $object_array = $object;
            $object = $this->getObject($object_array['uid']);
            $object->setData($object_array);
        }
        $object->setDriver($this->_driver);
        $result = $object->save($writer);

        if ($result === true) {
            $params = array();
        } else {
            $params = array(
                'changes' => array(
                    Horde_Kolab_Storage_Folder_Stamp::ADDED => array(
                        $result => $object
                    ),
                    Horde_Kolab_Storage_Folder_Stamp::DELETED => array()
                )
            );
        }
        $this->synchronize($params);
        return $result;
    }

    /**
     * Retrieves the complete message for the given UID.
     *
     * @param string $uid The message UID.
     *
     * @return array The message encapsuled as an array that contains a
     *               Horde_Mime_Headers and a Horde_Mime_Part object.
     */
    public function fetchComplete($uid)
    {
        return $this->_driver->fetchComplete($this->_folder->getPath(), $uid);
    }

    /**
     * Retrieves the body part for the given UID and mime part ID.
     *
     * @param string $uid The message UID.
     * @param string $id  The mime part ID.
     *
     * @return resource The message part as stream resource.
     */
    public function fetchPart($uid, $id)
    {
        return $this->_driver->fetchBodypart(
            $this->_folder->getPath(), $uid, $id
        );
    }

    /**
     * Retrieves the objects for the given UIDs.
     *
     * @param array   $uids The message UIDs.
     * @param boolean $raw  True if the raw format should be returned rather than
     *                      the parsed data.
     *
     * @return array An array of objects.
     */
    public function fetch($uids, $raw = false)
    {
        if (empty($uids)) {
            return array();
        }

        if ($raw === false) {
            $writer = new Horde_Kolab_Storage_Object_Writer_Format(
                new Horde_Kolab_Format_Factory(),
                array('version' => $this->_version)
            );
        } else {
            $writer = new Horde_Kolab_Storage_Object_Writer_Raw();
        }

        $objects = array();
        $structures = $this->_driver->fetchStructure($this->_folder->getPath(), $uids);
        foreach ($structures as $uid => $structure) {
            if (!isset($structure['structure'])) {
                throw new Horde_Kolab_Storage_Exception(
                    'Backend returned a structure without the expected "structure" element.'
                );
            }
            $object = new Horde_Kolab_Storage_Object();
            $object->setDriver($this->_driver);
            $object->load($uid, $this->_folder, $writer, $structure['structure']);
            $objects[$uid] = $object;
        }
        return $objects;
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
     * Retrieve all objects in the current folder by backend id.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @return array An array of all objects.
     */
    public function getObjectsByBackendId()
    {
        return $this->fetch($this->getStamp()->ids());
    }

    /**
     * Retrieve an object in the current folder by backend id.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @param string $uid Backend id of the object to be returned.
     *
     * @return array An array of all objects.
     */
    public function getObjectByBackendId($uid)
    {
        $fetched = $this->fetch(array($uid));
        return array_pop($fetched);
    }

    /**
     * Return the mapping of object IDs to backend IDs.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @return array The object to backend mapping.
     */
    public function getObjectToBackend()
    {
        $bid  = array();
        $by_obid = $this->fetch($this->getStamp()->ids());
        foreach ($by_obid as $obid => $object) {
            $bid[$object['uid']] = $obid;
        }
        return $bid;
    }

    /**
     * Retrieve the list of object duplicates.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @return array The list of duplicates.
     */
    public function getDuplicates()
    {
        $existing = array();
        $duplicates = array();
        $by_obid = $this->fetch($this->getStamp()->ids());
        foreach ($by_obid as $obid => $object) {
            if (isset($existing[$object['uid']])) {
                if (!isset($duplicates[$object['uid']])) {
                    $duplicates[$object['uid']][] = $existing[$object['uid']];
                }
                $duplicates[$object['uid']][] = $obid;
            } else {
                $existing[$object['uid']] = $obid;
            }
        }
        return $duplicates;
    }

    /**
     * Retrieve the list of object errors.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @return array The list of errors.
     */
    public function getErrors()
    {
        $errors = array();
        $by_obid = $this->fetch($this->getStamp()->ids());
        foreach ($by_obid as $obid => $object) {
            if ($object->hasParseErrors()) {
                $errors[$obid] = $object;
            }
        }
        return $errors;
    }

    /**
     * Move the specified message from the current folder into a new
     * folder.
     *
     * @param string $object_id  ID of the message to be moved.
     * @param string $new_folder Target folder.
     *
     * @return NULL
     */
    public function move($object_id, $new_folder)
    {
        if ($this->objectIdExists($object_id)) {
            $uid = $this->getBackendId($object_id);
        } else {
            throw new Horde_Kolab_Storage_Exception(
                sprintf('No such object %s!', $object_id)
            );
        }
        $this->_driver->moveMessage(
            $uid, $this->_folder->getPath(), $new_folder
        );
        $this->synchronize(
            array(
                'changes' => array(
                    Horde_Kolab_Storage_Folder_Stamp::ADDED => array(),
                    Horde_Kolab_Storage_Folder_Stamp::DELETED => array($object_id)
                )
            )
        );
    }

    /**
     * Delete the specified objects from this data set.
     *
     * @param array|string $object_ids Id(s) of the object to be deleted.
     *
     * @return NULL
     */
    public function delete($object_ids)
    {
        if (!is_array($object_ids)) {
            $object_ids = array($object_ids);
        }

        $uids = array();
        foreach ($object_ids as $id) {
            if ($this->objectIdExists($id)) {
                $uids[$this->getBackendId($id)] = $id;
            } else {
                throw new Horde_Kolab_Storage_Exception(
                    sprintf('No such object %s!', $id)
                );
            }
        }
        $this->deleteBackendIds(array_keys($uids));
        $this->synchronize(
            array(
                'changes' => array(
                    Horde_Kolab_Storage_Folder_Stamp::ADDED => array(),
                    Horde_Kolab_Storage_Folder_Stamp::DELETED => $uids
                )
            )
        );
    }

    /**
     * Delete all objects from this data set.
     *
     * @return NULL
     */
    public function deleteAll()
    {
        $this->delete($this->getObjectIds());
    }

    /**
     * Delete the specified messages from this folder.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @param array|string $uids Backend id(s) of the message to be deleted.
     *
     * @return NULL
     */
    public function deleteBackendIds($uids)
    {
        if (!is_array($uids)) {
            $uids = array($uids);
        }
        $this->_driver->deleteMessages($this->_folder->getPath(), $uids);
        $this->_driver->expunge($this->_folder->getPath());
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
     * @param array $params Additional parameters.
     *
     * @return NULL
     */
    public function synchronize($params = array())
    {
        foreach ($this->_queries as $name => $query) {
            $query->synchronize($params);
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
        if (isset($this->_queries[$name])) {
            return $this->_queries[$name];
        } else {
            throw new Horde_Kolab_Storage_Exception('No such query!');
        }
    }

    /**
     * Generate a unique object ID.
     *
     * @return string  The unique ID.
     */
    public function generateUid()
    {
        return strval(new Horde_Support_Uuid());
    }
}
