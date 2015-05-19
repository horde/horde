<?php
/**
 * The cache decorator for Kolab storage data handlers.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * The cache decorator for Kolab storage data handlers.
 *
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
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
    protected $_data_cache;

    /**
     * Has the cache already been loaded and validated?
     *
     * @var boolean
     */
    protected $_init = false;

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
     */
    protected function _init()
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
     * Returns the specified attachment.
     *
     * @param string $object_id      The object id. @since Kolab_Storage 2.1.0
     * @param string $attachment_id  The attachment id.
     *
     * @return resource An open stream to the attachment data.
     */
    public function getAttachment($object_id, $attachment_id)
    {
        $this->_init();
        return $this->_data_cache->getAttachment($object_id, $attachment_id)
            ?: parent::getAttachment($object_id, $attachment_id);
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
     * @see  Horde_Kolab_Storage_Query
     *
     * In addition to the parameters of the base class(es), the following may
     * be passed as well:
     *   - logger: (Horde_Log_Logger)  A logger instance.
     *
     * @return NULL
     */
    public function synchronize($params = array())
    {
        $this->_logger = !empty($this->_logger)
            ? $this->_logger
            : new Horde_Support_Stub();

        // For logging
        $user = $this->getAuth();
        $folder_path = $this->getPath();

        $current = $this->getStamp();

        if (!$this->_data_cache->isInitialized()) {
            $this->_logger->debug(sprintf(
                'Initial folder sync: user: %s, folder: %s',
                $user,
                $folder_path)
            );
            $this->_completeSynchronization($current);
            return;
        }

        $previous = unserialize($this->_data_cache->getStamp());

        // check if UIDVALIDITY changed
        if ($previous === false || $previous->isReset($current)) {
            $this->_logger->debug(sprintf("Complete folder sync: user: %s, folder: %s, is_reset: %d", $user, $folder_path, $is_reset));
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

            if (!empty($params['changes'][Horde_Kolab_Storage_Folder_Stamp::ADDED]) ||
                !empty($params['changes'][Horde_Kolab_Storage_Folder_Stamp::DELETED])) {
                $changes_to_log = array('add' => array(), 'del' => array());
                foreach ($params['changes'][Horde_Kolab_Storage_Folder_Stamp::ADDED] as $uid => $object) {
                    $changes_to_log['add'][$uid] = $object['uid'];
                }
                foreach ($params['changes'][Horde_Kolab_Storage_Folder_Stamp::DELETED] as $uid => $object_uid) {
                    $changes_to_log['del'][$uid] = $object_uid;
                }
                $this->_logger->debug(sprintf(
                    'Incremental folder sync: user: %s, folder: %s, last_sync: %d, current_sync: %d, changes: %s',
                    $user,
                    $folder_path,
                    $params['last_sync'],
                    $params['current_sync'],
                    print_r($changes_to_log, true))
                );
            }


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

        // logging
        $uids_to_log = array_keys($params['changes'][Horde_Kolab_Storage_Folder_Stamp::ADDED]);
        $this->_logger->debug(sprintf(
            'Full folder sync details: user: %s, folder: %s, uids: %s',
            $this->getAuth(),
            $this->getPath(),
            implode(', ', $uids_to_log))
        );

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
