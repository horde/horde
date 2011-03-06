<?php
/**
 * The cache decorator for Kolab storage data handlers.
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
 * The cache decorator for Kolab storage data handlers.
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
class Horde_Kolab_Storage_Data_Decorator_Cache
implements Horde_Kolab_Storage_Data, Horde_Kolab_Storage_Data_Query
{
    /**
     * Decorated data handler.
     *
     * @var Horde_Kolab_Storage_Data
     */
    private $_data;

    /**
     * The data cache.
     *
     * @var Horde_Kolab_Storage_Cache_Data
     */
    private $_data_cache;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Data       $data  The original data handler.
     * @param Horde_Kolab_Storage_Cache_Data $cache The cache storing data for
     *                                              this decorator.
     */
    public function __construct(
        Horde_Kolab_Storage_Data $data,
        Horde_Kolab_Storage_Cache_Data $cache
    ) {
        $this->_data = $data;
        $this->_data_cache = $cache;
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
     * Retrieves the body part for the given UID and mime part ID.
     *
     * @param string $uid The message UID.
     * @param string $id  The mime part ID.
     *
     * @return @TODO
     */
    public function fetchPart($uid, $id)
    {
        return $this->_data->fetchPart($uid, $id);
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
        return $this->_data->fetch($uids);
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
        $mapping = $this->_data_cache->getObjectToBackend();
        if (isset($mapping[$object_id])) {
            return $mapping[$object_id];
        } else {
            throw new Horde_Kolab_Storage_Exception(
                sprintf('Object ID %s does not exist!', $object_id)
            );
        }
    }

    /**
     * Generate a unique object ID.
     *
     * @return string  The unique ID.
     */
    public function generateUid()
    {
        //@todo
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
            $object_id, $this->_data_cache->getObjects()
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
        $objects = $this->_data_cache->getObjects();
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
        //@todo
    }

    /**
     * Retrieve all object ids in the current folder.
     *
     * @return array The object ids.
     */
    public function getObjectIds()
    {
        return array_keys($this->_data_cache->getObjects());
    }

    /**
     * Retrieve all objects in the current folder.
     *
     * @return array An array of all objects.
     */
    public function getObjects()
    {
        return $this->_data_cache->getObjects();
    }

    /**
     * Synchronize the query data with the information from the backend.
     *
     * @return NULL
     */
    public function synchronize()
    {
        $this->_data->synchronize();
        $current = $this->_data->getStamp();
        if (!$this->_data_cache->isInitialized()) {
            $this->_completeSynchronization($current);
            return;
        }
        $previous = unserialize($this->_data_cache->getStamp());
        if ($previous === false || $previous->isReset($current)) {
            $this->_completeSynchronization($current);
            return;
        }
        $changes = $previous->getChanges($current);
        if ($changes) {
            $this->_data_cache->store(
                $this->_data->fetch(
                    $changes[Horde_Kolab_Storage_Folder_Stamp::ADDED]
                ),
                $current,
                $this->_data->getVersion(),
                $changes[Horde_Kolab_Storage_Folder_Stamp::DELETED]
            );
            $this->_data_cache->save();
        }
    }

    /**
     * Perform a complete synchronization.
     *
     * @param Horde_Kolab_Storage_Folder_Stamp $stamp The current stamp.
     *
     * @return NULL
     */
    private function _completeSynchronization(
        Horde_Kolab_Storage_Folder_Stamp $stamp
    ) {
        $this->_data_cache->reset();
        $ids = $stamp->ids();
        $this->_data_cache->store(
            empty($ids) ? array() : $this->_data->fetch($ids),
            $stamp,
            $this->_data->getVersion()
        );
        $this->_data_cache->save();
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
