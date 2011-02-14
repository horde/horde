<?php
/**
 * The cached data query.
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
 * The cached data query.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Data_Query_Data_Cache
implements Horde_Kolab_Storage_Data_Query_Data
{
    /**
     * The queriable data.
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
     * The factory for generating additional resources.
     *
     * @var Horde_Kolab_Storage_Factory
     */
    private $_factory;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Data $data   The queriable data.
     * @param array                    $params Additional parameters.
     */
    public function __construct(
        Horde_Kolab_Storage_Data $data,
        $params
    ) {
        $this->_data = $data;
        $this->_data_cache = $params['cache'];
        $this->_factory = $params['factory'];
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
                sprintf(
                    'Kolab cache: Object ID %s does not exist in the cache!',
                    $object_id
                )
            );
        }
    }

    /**
     * Generate a unique object ID.
     *
     * @return string  The unique ID.
     */
    public function generateUID()
    {
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
                sprintf(
                    'Kolab cache: Object ID %s does not exist in the cache!',
                    $object_id
                )
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
        $this->_data_cache->store(
            $this->_data->fetch($stamp->ids()),
            $stamp,
            $this->_data->getVersion()
        );
    }
}