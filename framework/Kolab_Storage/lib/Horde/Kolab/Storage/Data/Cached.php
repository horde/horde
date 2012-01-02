<?php
/**
 * The cache decorator for Kolab storage data handlers.
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
 * The cache decorator for Kolab storage data handlers.
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
class Horde_Kolab_Storage_Data_Cached
extends Horde_Kolab_Storage_Data_Base
{
    /**
     * The data cache.
     *
     * @var Horde_Kolab_Storage_Cache_Data
     */
    private $_data_cache;

    /**
     * Has the cache already been loaded and validated?
     *
     * @var boolean
     */
    private $_init = false;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Folder  $folder   The folder to retrieve the
     *                                              data from.
     * @param Horde_Kolab_Storage_Driver  $driver   The primary connection driver.
     * @param Horde_Kolab_Storage_Factory $factory  The factory.
     * @param Horde_Kolab_Storage_Cache   $cache    The cache storing data for
     *                                              this decorator.
     * @param string                      $type     The type of data we want to
     *                                              access in the folder.
     * @param int                         $version  Format version of the object
     *                                              data.
     */
    public function __construct(Horde_Kolab_Storage_Folder $folder,
                                Horde_Kolab_Storage_Driver $driver,
                                Horde_Kolab_Storage_Factory $factory,
                                Horde_Kolab_Storage_Cache $cache,
                                $type = null,
                                $version = 1)
    {
        parent::__construct($folder, $driver, $factory, $type, $version);
        $this->_data_cache = $cache->getDataCache($this->getIdParameters());
    }

    /**
     * Check if the cache has been initialized.
     *
     * @return NULL
     */
    private function _isInitialized()
    {
        return ($this->_init || $this->_data_cache->isInitialized());
    }

    /**
     * Check if the cache has been initialized at all and synchronize it if not.
     *
     * @return NULL
     */
    private function _init()
    {
        if (!$this->_isInitialized()) {
            $this->synchronize();
        }
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
        $this->_init();
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
     * Check if the given object ID exists.
     *
     * @param string $object_id The object ID.
     *
     * @return boolean True if the ID was found, false otherwise.
     */
    public function objectIdExists($object_id)
    {
        $this->_init();
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
        $this->_init();
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
        $this->_init();
        return array_keys($this->_data_cache->getObjects());
    }

    /**
     * Retrieve all objects in the current folder.
     *
     * @return array An array of all objects.
     */
    public function getObjects()
    {
        $this->_init();
        return $this->_data_cache->getObjects();
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
        $this->_init();
        return $this->_data_cache->getObjectToBackend();
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
        $this->_init();
        return $this->_data_cache->getDuplicates();
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
        $this->_init();
        return $this->_data_cache->getErrors();
    }

    /**
     * Synchronize the query data with the information from the backend.
     *
     * @param array $params Additional parameters.
     *
     * @return NULL
     */
    public function synchronize($params = array())
    {
        $current = $this->getStamp();
        if (!$this->_data_cache->isInitialized()) {
            $this->_completeSynchronization($current);
            return;
        }
        $previous = unserialize($this->_data_cache->getStamp());
        if ($previous === false || $previous->isReset($current)) {
            $this->_completeSynchronization($current);
            return;
        }
        if (!isset($params['changes'])) {
            $changes = $previous->getChanges($current);
            $params['changes'][Horde_Kolab_Storage_Folder_Stamp::ADDED] = $this->fetch(
                $changes[Horde_Kolab_Storage_Folder_Stamp::ADDED]
            );
            $params['changes'][Horde_Kolab_Storage_Folder_Stamp::DELETED] = $this->_data_cache->backendMap(
                $changes[Horde_Kolab_Storage_Folder_Stamp::DELETED]
            );
        }
        if ($params['changes'] !== false) {
            $params['last_sync'] = $this->_data_cache->getLastSync();
            $this->_data_cache->store(
                $params['changes'][Horde_Kolab_Storage_Folder_Stamp::ADDED],
                $current,
                $this->getVersion(),
                $params['changes'][Horde_Kolab_Storage_Folder_Stamp::DELETED]
            );
            $params['current_sync'] = $this->_data_cache->getLastSync();
            parent::synchronize($params);
            $this->_data_cache->save();
        }
        $this->_init = true;
    }

    /**
     * Perform a complete synchronization.
     *
     * @param Horde_Kolab_Storage_Folder_Stamp $stamp The current stamp.
     * @param array $params Additional parameters.
     *
     * @return NULL
     */
    private function _completeSynchronization(Horde_Kolab_Storage_Folder_Stamp $stamp,
                                              $params = array())
    {
        $this->_data_cache->reset();
        $ids = $stamp->ids();
        $params['last_sync'] = false;
        $params['changes'][Horde_Kolab_Storage_Folder_Stamp::ADDED] = empty($ids) ? array() : $this->fetch($ids);
        $this->_data_cache->store(
            $params['changes'][Horde_Kolab_Storage_Folder_Stamp::ADDED],
            $stamp,
            $this->getVersion()
        );
        $params['current_sync'] = $this->_data_cache->getLastSync();
        parent::synchronize($params);
        $this->_data_cache->save();
    }
}
