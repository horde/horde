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
    /** Key for the folder list. */
    const FOLDERS = 'F';

    /** Key for the type list. */
    const TYPES = 'T';

    /** Key for the namespace data. */
    const NAME_SPACE = 'N';

    /** Key for the backend capabilities. */
    const SUPPORT = 'C';

    /** Holds query results. */
    const QUERIES = 'Q';

    /** Holds long term cache data. */
    const LONG_TERM = 'L';

    /** Key for the last time the list was synchronized. */
    const SYNC = 'S';

    /** Key for the cache format version. */
    const VERSION = 'V';

    /** Holds the version number of the cache format. */
    const FORMAT_VERSION = '1';

    /**
     * The core cache driver.
     *
     * @var Horde_Kolab_Storage_Cache
     */
    private $_cache;

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
     * @param Horde_Kolab_Storage_Cache $cache   The core cache driver.
     */
    public function __construct(Horde_Kolab_Storage_Cache $cache)
    {
        $this->_cache = $cache;
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
            $this->_data = unserialize($this->_cache->loadListData($this->getListId()));
            if (!is_array($this->_data)
                || !isset($this->_data[self::SYNC])
                || !isset($this->_data[self::VERSION])
                || $this->_data[self::VERSION] != self::FORMAT_VERSION) {
                $this->_data = array();
            }
        }
    }

    /**
     * Cache the list data.
     *
     * @return NULL
     */
    public function save()
    {
        $this->_cache->storeListData($this->getListId(), serialize($this->_data));
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
     * Is the specified query data available in the cache?
     *
     * @param string $key The query key.
     *
     * @return boolean True in case cached data is available.
     */
    public function hasQuery($key)
    {
        $this->_load();
        return isset($this->_data[self::QUERIES][$key]);
    }

    /**
     * Return query information.
     *
     * @param string $key The query key.
     *
     * @return mixed The query data.
     */
    public function getQuery($key)
    {
        if ($this->hasQuery($key)) {
            return $this->_data[self::QUERIES][$key];
        } else {
            throw new Horde_Kolab_Storage_Exception(
                sprintf('Missing query cache data (Key: %s). Synchronize first!', $key)
            );
        }
    }

    /**
     * Set query information.
     *
     * @param string $key  The query key.
     * @param mixed  $data The query data.
     *
     * @return NULL
     */
    public function setQuery($key, $data)
    {
        $this->_load();
        $this->_data[self::QUERIES][$key] = $data;
    }

    /**
     * Store the folder list and folder type annotations in the cache.
     *
     * @return NULL
     */
    public function store(array $folders = null, array $types = null)
    {
        $this->_load();
        $this->_data[self::QUERIES] = array();
        $this->_data[self::FOLDERS] = $folders;
        $this->_data[self::TYPES] = $types;
        $this->_data[self::VERSION] = self::FORMAT_VERSION;
        $this->_data[self::SYNC] = time();
    }

    /**
     * Load the cached share data identified by $key.
     *
     * @param string $key          Access key to the cached data.
     * @param int    $data_version A version identifier provided by
     *                             the storage manager.
     * @param bool   $force        Force loading the cache.
     *
     * @return NULL
     */
    public function load($key, $data_version, $force = false)
    {
        if (!$force && $this->key == $key
            && $this->data_version == $data_version) {
            return;
        }

        $this->key           = $key;
        $this->data_version  = $data_version;
        $this->cache_version = ($data_version << 8) | $this->base_version;

        $this->reset();

        $cache = $this->horde_cache->get($this->key, 0);

        if (!$cache) {
            return;
        }

        $data = unserialize($cache);

        // Delete disc cache if it's from an old version
        if ($data['version'] != $this->cache_version) {
            $this->horde_cache->expire($this->key);
            $this->reset();
        } else {
            $this->version  = $data['version'];
            $this->validity = $data['uidvalidity'];
            $this->nextid   = $data['uidnext'];
            $this->objects  = $data['objects'];
            $this->uids     = $data['uids'];
        }
    }

    /**
     * Load a cached attachment.
     *
     * @param string $key Access key to the cached data.
     *
     * @return mixed The data of the object.
     */
    public function loadAttachment($key)
    {
        return $this->horde_cache->get($key, 0);
    }

    /**
     * Cache an attachment.
     *
     * @param string $key  Access key to the cached data.
     * @param string $data The data to be cached.
     *
     * @return boolean True if successfull.
     */
    public function storeAttachment($key, $data)
    {
        $this->horde_cache->set($key, $data);
        return true;
    }

    /**
     * Initialize the cache structure.
     *
     * @return NULL
     */
    public function reset()
    {
        $this->version  = $this->cache_version;
        $this->validity = -1;
        $this->nextid   = -1;
        $this->objects  = array();
        $this->uids     = array();
    }

    /**
     * Save the share data in the cache.
     *
     * @return boolean True on success.
     */
    public function _save()
    {
        $data = array('version' => $this->version,
                      'uidvalidity' =>  $this->validity,
                      'uidnext' =>  $this->nextid,
                      'objects' =>  $this->objects,
                      'uids' =>  $this->uids);

        return $this->horde_cache->set($this->key,
                                       serialize($data));
    }

    /**
     * Store an object in the cache.
     *
     * @param int    $id        The storage ID.
     * @param string $object_id The object ID.
     * @param array  &$object   The object data.
     *
     * @return NULL
     */
    public function _store($id, $object_id, &$object)
    {
        $this->uids[$id]           = $object_id;
        $this->objects[$object_id] = $object;
    }

    /**
     * Mark the ID as invalid (cannot be correctly parsed).
     *
     * @param int $id The ID of the storage item to ignore.
     *
     * @return NULL
     */
    public function ignore($id)
    {
        $this->uids[$id] = false;
    }

    /**
     * Deliberately expire a cache.
     *
     * @return NULL
     */
    public function expire()
    {
        $this->version = -1;
        $this->save();
        $this->load($this->key, $this->data_version, true);
    }

    /**
     * The version of the cache we loaded.
     *
     * @var int
     */
    protected $version;

    /**
     * The internal version of the cache format represented by the
     * code.
     *
     * @var int
     */
    protected $base_version = 1;

    /**
     * The version of the data format provided by the storage handler.
     *
     * @var int
     */
    protected $data_version;

    /**
     * The version of the cache format that includes the data version.
     *
     * @var int
     */
    protected $cache_version = -1;

    /**
     * A validity marker for a share in the cache. This allows the
     * storage handler to invalidate the cache for this share.
     *
     * @var int
     */
    public $validity;

    /**
     * A nextid marker for a share in the cache. This allows the
     * storage handler to invalidate the cache for this share.
     *
     * @var int
     */
    public $nextid;

    /**
     * The objects of the current share.
     *
     *   | objects: key is uid (GUID)
     *   | ----------- hashed object data
     *                 |----------- uid: object id (GUID)
     *   |             |----------- all fields from kolab specification
     *
     * @var array
     */
    public $objects;

    /**
     * The uid<->object mapping of the current share.
     *
     *   | uids   Mapping between imap uid and object uids: imap uid -> object uid
     *            Special: A value of "false" means we've seen the uid
     *                     but we deciced to ignore it in the future
     *
     * @var array
     */
    public $uids;

    /**
     * The unique key for the currently loaded data.
     *
     * @var string
     */
    protected $key;

}
