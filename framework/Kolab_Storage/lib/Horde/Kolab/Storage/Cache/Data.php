<?php
/**
 * A cache backend for Kolab storage data handlers.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * A cache backend for Kolab storage data handlers.
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Cache_Data
{
    /** Key for the backend ID to object ID mapping. */
    const B2O = 'M';

    /** Key for the object ID to backend ID mapping. */
    const O2B = 'B';

    /** Key for the objects. */
    const OBJECTS = 'O';

    /** Key for the stamp. */
    const STAMP = 'P';

    /** Key for the data format version. */
    const DATA_VERSION = 'D';

    /** Key for the last time the data was synchronized. */
    const SYNC = 'S';

    /** Key for the cache format version. */
    const VERSION = 'V';

    /** Key for the data set parameters associated with this cache. */
    const ID = 'I';

    /** Holds the version number of the cache format. */
    const FORMAT_VERSION = '1';

    /**
     * The core cache driver.
     *
     * @var Horde_Kolab_Storage_Cache
     */
    private $_cache;

    /**
     * Data parameters that will be recorded in the cache.
     *
     * @var array
     */
    private $_parameters;

    /**
     * Data ID.
     *
     * @var string
     */
    private $_data_id;

    /**
     * The cache data.
     *
     * @var array
     */
    private $_data = false;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Cache $cache      The core cache driver.

     * @param array                     $parameters Data set parameters that
     *                                              are only recorded and have
     *                                              no further impact.
     */
    public function __construct(
        Horde_Kolab_Storage_Cache $cache,
        $parameters = null
    ) {
        $this->_cache = $cache;
        $this->_parameters = $parameters;
    }

    /**
     * The ID for the data cache.
     *
     * @param string $data_id The unique ID for the data used when caching it.
     *
     * @return NULL
     */
    public function setDataId($data_id)
    {
        $this->_data_id = $data_id;
    }

    /**
     * Return the ID for the data cache.
     *
     * @return string The unique ID for the data used when caching it.
     */
    public function getDataId()
    {
        if ($this->_data_id === null) {
            throw new Horde_Kolab_Storage_Exception(
                'You must set the ID of the data cache!'
            );
        }
        return $this->_data_id;
    }

    /**
     * Retrieve the cached list data.
     *
     * @return mixed The data of the object.
     */
    private function _load()
    {
        if ($this->_data === false) {
            $this->_data = unserialize($this->_cache->loadData($this->getDataId()));
            if (!is_array($this->_data)
                || !isset($this->_data[self::SYNC])
                || !isset($this->_data[self::VERSION])
                || $this->_data[self::VERSION] != self::FORMAT_VERSION) {
                $this->_data = array();
            }
        }
    }

    /**
     * Cache the data.
     *
     * @return NULL
     */
    public function save()
    {
        $this->_cache->storeData($this->getDataId(), serialize($this->_data));
    }

    /**
     * Check if the cache has been initialized.
     *
     * @return boolean True if cache data is available.
     */
    public function isInitialized()
    {
        $this->_load();
        return !empty($this->_data);
    }

    /**
     * Retrieve the object list from the cache.
     *
     * @return array The list of objects.
     */
    public function getObjects()
    {
        return $this->_fetchCacheEntry(self::OBJECTS);
    }

    /**
     * Retrieve the specified object from the cache.
     *
     * @param string $obid The object ID to fetch.
     *
     * @return array The list of objects.
     */
    public function getObjectByBackendId($obid)
    {
        $obids = $this->getBackendToObject();
        if (isset($obids[$obid])) {
            $objects = $this->getObjects();
            return $objects[$obids[$obid]];
        } else {
            throw new Horde_Kolab_Storage_Exception(
                sprintf ('No such object %s!', $obid)
            );
        }
    }

    /**
     * Return the object ID to backend ID mapping.
     *
     * @return array The mapping.
     */
    public function getObjectToBackend()
    {
        return $this->_fetchCacheEntry(self::O2B);
    }

    /**
     * Return the backend ID to object ID mapping.
     *
     * @return array The mapping.
     */
    public function getBackendToObject()
    {
        return $this->_fetchCacheEntry(self::B2O);
    }

    /**
     * Retrieve the last stamp.
     *
     * @return Horde_Kolab_Storage_Folder_Stamp The last recorded stamp.
     */
    public function getStamp()
    {
        $this->_checkInit(self::STAMP);
        return $this->_data[self::STAMP];
    }

    /**
     * Retrieve the data version.
     *
     * @return string The version of the stored data.
     */
    public function getVersion()
    {
        $this->_checkInit(self::DATA_VERSION);
        return $this->_data[self::DATA_VERSION];
    }

    /**
     * Retrieve an attachment.
     *
     * @param string $obid          Object backend id.
     * @param string $attachment_id Attachment ID.
     *
     * @return resource A stream opened to the attachement data.
     */
    public function getAttachment($obid, $attachment_id)
    {
        return $this->_cache->loadAttachment(
            $this->getDataId(), $obid, $attachment_id
        );
    }

    /**
     * Retrieve an attachment by name.
     *
     * @param string $obid          Object backend id.
     * @param string $attachment_id Attachment ID.
     *
     * @return array An array of attachment resources.
     */
    public function getAttachmentByName($obid, $name)
    {
        $object = $this->getObjectByBackendId($obid);
        if (!isset($object['_attachments']['name'][$name])) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    'No attachment named "%s" for object id %s!',
                    $name,
                    $obid
                )
            );
        }
        $result = array();
        foreach ($object['_attachments']['name'][$name] as $attachment_id) {
            $result[$attachment_id] = $this->_cache->loadAttachment(
                $this->getDataId(), $obid, $attachment_id
            );
        }
        return $result;
    }

    /**
     * Retrieve an attachment by name.
     *
     * @param string $obid          Object backend id.
     * @param string $attachment_id Attachment ID.
     *
     * @return array An array of attachment resources.
     */
    public function getAttachmentByType($obid, $type)
    {
        $object = $this->getObjectByBackendId($obid);
        if (!isset($object['_attachments']['type'][$type])) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    'No attachment with type "%s" for object id %s!',
                    $type,
                    $obid
                )
            );
        }
        $result = array();
        foreach ($object['_attachments']['type'][$type] as $attachment_id) {
            $result[$attachment_id] = $this->_cache->loadAttachment(
                $this->getDataId(), $obid, $attachment_id
            );
        }
        return $result;
    }

    /**
     * Fetch the specified cache entry in case it is present. Returns an empty
     * array otherwise.
     *
     * @param string $key The key in the cached data array.
     *
     * @return array The cache entry.
     */
    private function _fetchCacheEntry($key)
    {
        $this->_checkInit($key);
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        } else {
            return array();
        }
    }

    /**
     * Verify that the data cache is initialized.
     *
     * @param string $key The key in the cached data array.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Storage_Exception In case the cache has not been
     *                                       initialized.
     */
    private function _checkInit($key)
    {
        if (!$this->isInitialized()) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf('Missing cache data (Key: %s). Synchronize first!', $key)
            );
        }
    }

    /**
     * Store the objects list in the cache.
     *
     * @param array                            $object  The object data to store.
     * @param Horde_Kolab_Storage_Folder_Stamp $stamp   The current stamp.
     * @param string                           $version The format version of
     *                                                  the provided data.
     * @param array                            $delete  Object IDs that were removed.
     *
     * @return NULL
     */
    public function store(
        array $objects,
        Horde_Kolab_Storage_Folder_Stamp $stamp,
        $version,
        array $delete = array()
    ) {
        $this->_load();
        foreach ($objects as $obid => $object) {
            if (!empty($object) && isset($object['uid'])) {
                //@todo: exception on double object id?
                $this->_data[self::B2O][$obid] = $object['uid'];
                $this->_data[self::O2B][$object['uid']] = $obid;
                if (isset($object['_attachments'])) {
                    $attachments = array();
                    foreach ($object['_attachments'] as $id => $attachment) {
                        $attachments['id'][] = $id;
                        if (isset($attachment['name'])) {
                            $attachments['name'][$attachment['name']][] = $id;
                        }
                        if (isset($attachment['type'])) {
                            $attachments['type'][$attachment['type']][] = $id;
                        }
                        $this->_cache->storeAttachment($this->getDataId(), $obid, $id, $attachment['content']);
                    }
                    $object['_attachments'] = $attachments;
                }
                $this->_data[self::OBJECTS][$object['uid']] = $object;
            } else {
                $this->_data[self::B2O][$obid] = false;
            }
        }
        if (!empty($delete)) {
            foreach ($delete as $item) {
                $object_id = $this->_data[self::B2O][$item];
                $object = $this->_data[self::OBJECTS][$object_id];
                if (isset($object['_attachments'])) {
                    foreach ($object['_attachments']['id'] as $id) {
                        $this->_cache->deleteAttachment(
                            $this->getDataId(), $item, $id
                        );
                    }
                }
                unset($this->_data[self::O2B][$object_id]);
                unset($this->_data[self::OBJECTS][$object_id]);
                unset($this->_data[self::B2O][$item]);
            }
        }
        $this->_data[self::STAMP] = serialize($stamp);
        $this->_data[self::DATA_VERSION] = $version;
        $this->_data[self::VERSION] = self::FORMAT_VERSION;
        $this->_data[self::ID] = serialize($this->_parameters);
        $this->_data[self::SYNC] = time();
    }

    /**
     * Initialize the cache structure.
     *
     * @return NULL
     */
    public function reset()
    {
        $this->_data = array();
    }
}
