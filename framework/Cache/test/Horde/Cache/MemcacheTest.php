<?php
/**
 * Copyright 2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Cache
 */

/**
 * This class tests the Memcache backend.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Cache
 */
class Horde_Cache_MemcacheTest extends Horde_Cache_TestBase
{
    protected function _getCache($params = array())
    {
        if (!class_exists('Horde_Memcache')) {
            $this->reason = 'Horde_Memcache not installed';
            return;
        }
        if (!(extension_loaded('memcache') || extension_loaded('memcached'))) {
            $this->reason = 'Memcache extension not loaded';
            return;
        }
        if (!($config = self::getConfig('CACHE_MEMCACHE_TEST_CONFIG')) ||
            !isset($config['cache']['memcache'])) {
            $this->reason = 'Memcache configuration not available.';
            return;
        }
        return new Horde_Cache(
            new Horde_Cache_Storage_Memcache(array(
                'memcache' => new Horde_Memcache($config['cache']['memcache']),
                'prefix' => 'horde_cache_test'
            ))
        );
    }
}
