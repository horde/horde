<?php
/**
 * Interface representing an autoloader caching backend.
 *
 * PHP 5
 *
 * @category Horde
 * @package  Autoloader_Cache
 * @author   Gunnar Wrobel <wrobel@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Autoloader_Cache
 */

/**
 * Interface representing an autoloader caching backend.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Autoloader_Cache
 * @author   Gunnar Wrobel <wrobel@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Autoloader_Cache
 */
interface Horde_Autoloader_Cache_Backend
{
    /**
     * Determines if the caching backend is supported.
     *
     * @return boolean True if the caching backend can be used.
     */
    static public function isSupported();

    /**
     * Store the class to file mapping in the cache.
     *
     * @param array $mapping The mapping to be stored.
     *
     * @return NULL
     */
    public function store($mapping);

    /**
     * Fetch the class to file mapping from the cache.
     *
     * @return array The mapping as fetched from the cache.
     */
    public function fetch();

    /**
     * Delete the class to file mapping from the cache.
     *
     * @return boolean True if pruning succeeded.
     */
    public function prune();
}