<?php
/**
 * A cache for Kolab storage.
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
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Cache
{
    /**
     * The link to the horde cache.
     *
     * @var Horde_Cache
     */
    protected $horde_cache;

    /**
     * List cache instances.
     *
     * @var array
     */
    private $_list_caches;

    /**
     * Data cache instances.
     *
     * @var array
     */
    private $_data_caches;

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
     * Return a data cache.
     *
     * @param array $data_params Return the data cache for a data set
     *                           with these parameters.
     *
     * @return Horde_Kolab_Storage_Cache_Data The data cache.
     */
    public function getDataCache($data_params)
    {
        if (!isset($data_params['host'])) {
            throw new Horde_Kolab_Storage_Exception('Unable to determine the data cache key: The "host" parameter is missing!');
        }
        if (!isset($data_params['port'])) {
            throw new Horde_Kolab_Storage_Exception('Unable to determine the data cache key: The "port" parameter is missing!');
        }
        if (!isset($data_params['folder'])) {
            throw new Horde_Kolab_Storage_Exception('Unable to determine the data cache key: The "folder" parameter is missing!');
        }
        if (!isset($data_params['type'])) {
            throw new Horde_Kolab_Storage_Exception('Unable to determine the data cache key: The "type" parameter is missing!');
        }
        $data_id = sprintf(
            '%s/%s@%s:%s:DATA',
            $data_params['folder'],
            $data_params['type'],
            $data_params['host'],
            $data_params['port']
        );
        if (!isset($this->_data_caches[$data_id])) {
            $this->_data_caches[$data_id] = new Horde_Kolab_Storage_Cache_Data($this);
        }
        return $this->_data_caches[$data_id];
    }

    /**
     * Return a list cache.
     *
     * @param array $connection_params Return the list cache for a connection
     *                                 with these parameters.
     *
     * @return Horde_Kolab_Storage_Cache_List The list cache.
     */
    public function getListCache($connection_params)
    {
        $list_id = $this->_getListId($connection_params);
        if (!isset($this->_list_caches[$list_id])) {
            $this->_list_caches[$list_id] = new Horde_Kolab_Storage_Cache_List(
                $this
            );
            $this->_list_caches[$list_id]->setListId($list_id);
        }
        return $this->_list_caches[$list_id];
    }

    /**
     * Retrieve list data.
     *
     * @param string $list_id ID of the connection matching the list.
     *
     * @return mixed The data of the object.
     */
    public function loadList($list_id)
    {
        return $this->horde_cache->get($list_id, 0);
    }

    /**
     * Cache list data.
     *
     * @param string $list_id ID of the connection matching the list.
     * @param string $data          The data to be cached.
     *
     * @return boolean True if successfull.
     */
    public function storeList($list_id, $data)
    {
        $this->horde_cache->set($list_id, $data);
    }

    /**
     * Compose the list key.
     *
     * @param array $connection_params Return the list cache for a connection
     *                                 with these parameters.
     *
     * @return mixed The data of the object.
     */
    private function _getListId($connection_params)
    {
        if (!isset($connection_params['host'])) {
            throw new Horde_Kolab_Storage_Exception('Unable to determine the list cache key: The "host" parameter is missing!');
        }
        if (!isset($connection_params['port'])) {
            throw new Horde_Kolab_Storage_Exception('Unable to determine the list cache key: The "port" parameter is missing!');
        }
        if (!isset($connection_params['user'])) {
            throw new Horde_Kolab_Storage_Exception('Unable to determine the list cache key: The "user" parameter is missing!');
        }
        return sprintf(
            '%s@%s:%s:LIST',
            $connection_params['user'],
            $connection_params['host'],
            $connection_params['port']
        );
    }
}
