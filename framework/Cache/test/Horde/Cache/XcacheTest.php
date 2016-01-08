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
 * This class tests the XCache backend.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Cache
 */
class Horde_Cache_XcacheTest extends Horde_Cache_TestBase
{
    protected function _getCache($params = array())
    {
        if (!extension_loaded('xcache')) {
            $this->reason = 'XCache extension not loaded';
            return;
        }
        return new Horde_Cache(
            new Horde_Cache_Storage_Xcache(array(
                'prefix' => 'horde_cache_test'
            ))
        );
    }

    public function testClear()
    {
        $this->markTestSkipped('Not supported');
    }

    public function tearDown()
    {
        if ($this->cache) {
            $this->cache->expire('key1');
            $this->cache->expire('key2');
            unset($this->cache);
        }
    }
}
