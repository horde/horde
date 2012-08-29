<?php
/**
 * Describes a cache backend for Kolab storage list handlers.
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
 * Describes a cache backend for Kolab storage list handlers.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
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
abstract class Horde_Kolab_Storage_List_Cache
{
    /**
     * The ID for the list cache.
     *
     * @param string $list_id The unique ID for the list used when caching it.
     *
     * @return NULL
     */
    abstract public function setListId($list_id);

    /**
     * Return the ID for the list cache.
     *
     * @return string The unique ID for the list used when caching it.
     */
    abstract public function getListId();

    /**
     * Cache the list data.
     *
     * @return NULL
     */
    abstract public function save();

    /**
     * Check if the cache has been initialized.
     *
     * @return boolean True if cache data is available.
     */
    abstract public function isInitialized();

    /**
     * Returns the last sync stamp.
     *
     * @return string The last sync stamp.
     */
    abstract public function getStamp();

    /**
     * Returns the list of folders from the cache.
     *
     * @return array The list of folders, represented as a list of strings.
     */
    abstract public function getFolders();

    /**
     * Returns if the folder type annotation is stored in the cache.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @return boolean True if the type annotation is available.
     */
    abstract public function hasFolderTypes();

    /**
     * Returns the folder type annotation from the cache.
     *
     * @return array The list folder types with the folder names as key and the
     *               folder type as values.
     */
    abstract public function getFolderTypes();

    /**
     * Returns if the namespace information is available.
     *
     * @return boolean True if the information exists in the cache.
     */
    abstract public function hasNamespace();

    /**
     * Return namespace information.
     *
     * @return mixed The namespace data.
     */
    abstract public function getNamespace();

    /**
     * Set namespace information.
     *
     * @param mixed $data The namespace data.
     *
     * @return NULL
     */
    abstract public function setNamespace($data);

    /**
     * Has the capability support already been cached?
     *
     * @return boolean True if the value is already in the cache.
     */
    abstract public function issetSupport($capability);

    /**
     * Has the list support for the requested capability?
     *
     * @param string $capability The name of the requested capability.
     *
     * @return boolean True if the backend supports the requested capability.
     */
    abstract public function hasSupport($capability);

    /**
     * Set if the list supports the given capability.
     *
     * @param string  $capability The name of the requested capability.
     * @param boolean $flag       True if the capability is supported.
     *
     * @return NULL
     */
    abstract public function setSupport($capability, $flag);

    /**
     * Is the specified query data available in the cache?
     *
     * @param string $key The query key.
     *
     * @return boolean True in case cached data is available.
     */
    abstract public function hasQuery($key);

    /**
     * Return query information.
     *
     * @param string $key The query key.
     *
     * @return mixed The query data.
     */
    abstract public function getQuery($key);

    /**
     * Set query information.
     *
     * @param string $key  The query key.
     * @param mixed  $data The query data.
     *
     * @return NULL
     */
    abstract public function setQuery($key, $data);

    /**
     * Is the specified long term data available in the cache?
     *
     * @param string $key The long term key.
     *
     * @return boolean True in case cached data is available.
     */
    abstract public function hasLongTerm($key);

    /**
     * Return long term information.
     *
     * @param string $key The long term key.
     *
     * @return mixed The long term data.
     */
    abstract public function getLongTerm($key);

    /**
     * Set long term information.
     *
     * @param string $key  The long term key.
     * @param mixed  $data The long term data.
     *
     * @return NULL
     */
    abstract public function setLongTerm($key, $data);

    /**
     * Store the folder list and folder type annotations in the cache.
     *
     * @return NULL
     */
    abstract public function store(array $folders = null, array $types = null);
}
