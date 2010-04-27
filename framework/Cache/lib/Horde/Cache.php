<?php
/**
 * The Horde_Cache:: class provides a common abstracted interface into
 * the various caching backends.  It also provides functions for
 * checking in, retrieving, and flushing a cache.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Anil Madhavapeddy <anil@recoil.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Cache
 */
interface Horde_Cache
{
    /**
     * Attempts to retrieve a cached object and return it to the
     * caller.
     *
     * @param string $key        Object ID to query.
     * @param integer $lifetime  Lifetime of the object in seconds.
     *
     * @return mixed  Cached data, or false if none was found.
     */
    public function get($key, $lifetime = 1);

    /**
     * Attempts to store an object in the cache.
     *
     * @param string $key        Object ID used as the caching key.
     * @param mixed $data        Data to store in the cache.
     * @param integer $lifetime  Object lifetime - i.e. the time before the
     *                           data becomes available for garbage
     *                           collection.  If null use the default Horde GC
     *                           time.  If 0 will not be GC'd.
     *
     * @return boolean  True on success, false on failure.
     */
    public function set($key, $data, $lifetime = null);

    /**
     * Checks if a given key exists in the cache, valid for the given
     * lifetime.
     *
     * @param string $key        Cache key to check.
     * @param integer $lifetime  Lifetime of the key in seconds.
     *
     * @return boolean  Existence.
     */
    public function exists($key, $lifetime = 1);

    /**
     * Expire any existing data for the given key.
     *
     * @param string $key  Cache key to expire.
     *
     * @return boolean  Success or failure.
     */
    public function expire($key);
}
