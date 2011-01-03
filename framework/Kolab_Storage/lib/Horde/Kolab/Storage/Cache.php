<?php
/**
 * A cache for Kolab storage.
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
 * The Kolab_Cache class provides a cache for Kolab groupware objects.
 *
 * The Horde_Kolab_Storage_Cache singleton instance provides caching for all
 * storage folders. So before operating on the cache data it is necessary to
 * load the desired folder data. Before switching the folder the cache data
 * should be saved.
 *
 * This class does not offer a lot of safeties and is primarily intended to be
 * used within the Horde_Kolab_Storage_Data class.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Cache
{
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

    /**
     * The link to the horde cache.
     *
     * @var Horde_Cache
     */
    protected $horde_cache;

    /**
     * Constructor.
     *
     * @param Horde_Cache $cache The global cache for temporary data storage.
     */
    public function __construct($cache)
    {
        $this->horde_cache = $cache;
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
     * Retrieve list data.
     *
     * @param string $connection_id ID of the connection matching the list.
     *
     * @return mixed The data of the object.
     */
    public function loadListData($connection_id)
    {
        return $this->horde_cache->get($this->_getListKey($connection_id), 0);
    }

    /**
     * Cache list data.
     *
     * @param string $connection_id ID of the connection matching the list.
     * @param string $data          The data to be cached.
     *
     * @return boolean True if successfull.
     */
    public function storeListData($connection_id, $data)
    {
        $this->horde_cache->set($this->_getListKey($connection_id), $data);
    }

    /**
     * Retrieve list data.
     *
     * @param string $connection_id ID of the connection matching the list.
     * @param string $key           Access key to the cached data.
     *
     * @return mixed The data of the object.
     */
    private function _getListKey($connection_id)
    {
        return $connection_id . ':LIST';
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
    public function save()
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
    public function store($id, $object_id, &$object)
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
}
