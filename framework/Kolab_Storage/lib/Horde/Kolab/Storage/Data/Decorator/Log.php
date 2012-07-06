<?php
/**
 * A log decorator for the data handlers.
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
 * A log decorator for the data handlers.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @since Horde_Kolab_Storage 1.1.0
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Data_Decorator_Log
implements Horde_Kolab_Storage_Data, Horde_Kolab_Storage_Data_Query
{
    /**
     * Decorated data handler.
     *
     * @var Horde_Kolab_Storage_Data
     */
    private $_data;

    /**
     * A log handler.
     *
     * @var mixed
     */
    private $_logger;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Data $data   The original data handler.
     * @param mixed                    $logger The log handler. This instance
     *                                         must provide the debug() and 
     *                                         debug() methods.
     */
    public function __construct(Horde_Kolab_Storage_Data $data,
                                $logger)
    {
        $this->_data = $data;
        $this->_logger = $logger;
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
        return $this->_data->getPath();
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
        return $this->_data->getAuth();
    }

    /**
     * Return the ID of this data handler.
     *
     * @return string The ID.
     */
    public function getId()
    {
        return $this->_data->getId();
    }

    /**
     * Return the ID parameters for this data handler.
     *
     * @return array The ID parameters.
     */
    public function getIdParameters()
    {
        return $this->_data->getIdParameters();
    }

    /**
     * Return the data type represented by this object.
     *
     * @return string The type of data this instance handles.
     */
    public function getType()
    {
        return $this->_data->getType();
    }

    /**
     * Return the data version.
     *
     * @return string The data version.
     */
    public function getVersion()
    {
        return $this->_data->getVersion();
    }

    /**
     * Report the status of this folder.
     *
     * @return Horde_Kolab_Storage_Folder_Stamp The stamp that can be used for
     *                                          detecting folder changes.
     */
    public function getStamp()
    {
        return $this->_data->getStamp();
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
        $this->_logger->debug(
            sprintf('Creating new data object in %s.', $this->_data->getPath())
        );
        $result = $this->_data->create($object, $raw);
        $this->_logger->debug(
            sprintf(
                'Created data object %s in %s [backend: %s].',
                $object['uid'],
                $this->_data->getPath(),
                $result
            )
        );
        return $result;
    }

    /**
     * Modify an existing object.
     *
     * @param array   $object The array that holds the updated object data.
     * @param boolean $raw    True if the data to be stored has been provided in
     *                        raw format.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Storage_Exception In case an error occured while
     *                                       saving the data.
     */
    public function modify($object, $raw = false)
    {
        $this->_logger->debug(
            sprintf(
                'Modifying data object %s in %s.',
                $object['uid'],
                $this->_data->getPath()
            )
        );
        $this->_data->modify($object, $raw);
        $this->_logger->debug(
            sprintf(
                'Modified data object %s in %s.',
                $object['uid'],
                $this->_data->getPath()
            )
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
        $this->_logger->debug(
            sprintf(
                'Fetching data objects %s in %s.',
                join(',', $uids),
                $this->_data->getPath()
            )
        );
        return $this->_data->fetch($uids);
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
        $this->_logger->debug(
            sprintf(
                'Fetching complete message id %s in %s.',
                $uid,
                $this->_data->getPath()
            )
        );
        return $this->_data->fetchComplete($uid);
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
        $result = $this->_data->getBackendId($object_id);
        $this->_logger->debug(
            sprintf(
                'Backend id for object %s is %s in %s.',
                $object_id,
                $result,
                $this->_data->getPath()
            )
        );
        return $result;
    }

    /**
     * Generate a unique object ID.
     *
     * @return string  The unique ID.
     */
    public function generateUid()
    {
        $result = $this->_data->generateUid();
        $this->_logger->debug(
            sprintf(
                'Generated new uid %s for %s.',
                $result,
                $this->_data->getPath()
            )
        );
        return $result;
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
        return $this->_data->objectIdExists($object_id);
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
        return $this->_data->getObject($object_id);
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
        return $this->_data->getAttachment($attachment_id);
    }

    /**
     * Retrieve all object ids in the current folder.
     *
     * @return array The object ids.
     */
    public function getObjectIds()
    {
        $result = $this->_data->getObjectIds();
        if (count($result < 20)) {
            $ids = '[' . join(', ', $result) . ']';
        } else {
            $ids = '[too many to list]';
        }
        $this->_logger->debug(
            sprintf(
                '%s has %s objects %s.',
                $this->_data->getPath(),
                count($result),
                $ids
            )
        );
        return $result;
    }

    /**
     * Retrieve all objects in the current folder.
     *
     * @return array An array of all objects.
     */
    public function getObjects()
    {
        $result = $this->_data->getObjects();
        if (count($result < 20)) {
            $ids = '[' . join(', ', array_keys($result)) . ']';
        } else {
            $ids = '[too many to list]';
        }
        $this->_logger->debug(
            sprintf(
                '%s has %s objects %s.',
                $this->_data->getPath(),
                count($result),
                $ids
            )
        );
        return $result;
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
        $result = $this->_data->getObjectsByBackendId();
        if (count($result < 20)) {
            $ids = '[backend ids: ' . join(', ', array_keys($result)) . ']';
        } else {
            $ids = '[too many to list]';
        }
        $this->_logger->debug(
            sprintf(
                '%s has %s objects %s.',
                $this->_data->getPath(),
                count($result),
                $ids
            )
        );
        return $result;
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
        return $this->_data->getObjectByBackendId($uid);
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
        return $this->_data->getObjectToBackend();
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
        return $this->_data->getDuplicates();
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
        return $this->_data->getErrors();
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
        $this->_logger->debug(
            sprintf(
                'Moving data object %s in %s to %s.',
                $object_id,
                $this->_data->getPath(),
                $new_folder
            )
        );
        $this->_data->move($object_ids, $new_folder);
        $this->_logger->debug(
            sprintf(
                'Moved data object %s in %s to %s.',
                $object_id,
                $this->_data->getPath(),
                $new_folder
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
        if (is_array($object_ids)) {
            $ids = join(', ', $object_ids);
        } else {
            $ids = $object_ids;
        }
        $this->_logger->debug(
            sprintf(
                'Deleting data object(s) %s in %s.',
                $ids,
                $this->_data->getPath()
            )
        );
        $this->_data->delete($object_ids);
        $this->_logger->debug(
            sprintf(
                'Deleted data object(s) %s in %s.',
                $ids,
                $this->_data->getPath()
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
        $this->_logger->debug(
            sprintf(
                'Deleting all data objects in %s.',
                $this->_data->getPath()
            )
        );
        $this->_data->deleteAll();
        $this->_logger->debug(
            sprintf(
                'Deleted all data objects in %s.',
                $this->_data->getPath()
            )
        );
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
        if (is_array($uids)) {
            $ids = join(', ', $uids);
        } else {
            $ids = $uids;
        }
        $this->_logger->debug(
            sprintf(
                'Deleting backend data object(s) %s in %s.',
                $ids,
                $this->_data->getPath()
            )
        );
        $this->_data->deleteBackendIds($uids);
        $this->_logger->debug(
            sprintf(
                'Deleted backend data object(s) %s in %s.',
                $ids,
                $this->_data->getPath()
            )
        );
    }

    /**
     * Synchronize the list information with the information from the backend.
     *
     * @param array $params Additional parameters.
     *
     * @return NULL
     */
    public function synchronize($params = array())
    {
        $this->_data->synchronize();
        $this->_logger->debug(
            sprintf(
                'Synchronized data cache for %s.',
                $this->_data->getPath()
            )
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
    public function registerQuery($name, Horde_Kolab_Storage_Query $query)
    {
        $this->_data->registerQuery($name, $query);
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
        return $this->_data->getQuery($name);
    }
}