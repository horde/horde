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
    public function __construct(
        Horde_Kolab_Storage_Folder $folder,
        Horde_Kolab_Storage_Driver $driver,
        Horde_Kolab_Storage_Factory $factory,
        Horde_Kolab_Storage_Cache $cache,
        $type = null,
        $version = 1
    ) {
        parent::__construct($folder, $driver, $factory, $type, $version);
        $this->_data_cache = $cache->getDataCache($this->getIdParameters());
    }

    /**
     * Create a new object.
     *
     * @param array   $object The array that holds the object data.
     * @param boolean $raw    True if the data to be stored has been provided in
     *                        raw format.
     *
     * @return string The ID of the new object or true in case the backend does
     *                not support this return value.
     *
     * @throws Horde_Kolab_Storage_Exception In case an error occured while
     *                                       saving the data.
     */
    public function create($object, $raw = false)
    {
        $result = parent::create($object, $raw);
        if ($result === true) {
            $this->synchronize();
        } else {
            $this->_data_cache->store(
                array($result => $object),
                $this->getStamp(),
                $this->getVersion()
            );
            $this->_data_cache->save();
        }
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
            $old_obid = $this->getBackendId($object['uid']);
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
        $result = parent::modify($object, $raw);
        if ($result === true) {
            $this->synchronize();
        } else {
            $this->_data_cache->store(
                array($result => $object),
                $this->getStamp(),
                $this->getVersion(),
                array($old_obid)
            );
            $this->_data_cache->save();
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
        parent::synchronize();
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
        $changes = $previous->getChanges($current);
        if ($changes) {
            $this->_data_cache->store(
                $this->fetch(
                    $changes[Horde_Kolab_Storage_Folder_Stamp::ADDED]
                ),
                $current,
                $this->getVersion(),
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
            empty($ids) ? array() : $this->fetch($ids),
            $stamp,
            $this->getVersion()
        );
        $this->_data_cache->save();
    }
}
