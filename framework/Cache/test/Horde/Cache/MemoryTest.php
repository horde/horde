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
 * This class tests the memory backend.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Cache
 */
class Horde_Cache_MemoryTest extends Horde_Cache_TestBase
{
    protected function _getCache($params = array())
    {
        return new Horde_Cache(
            new Horde_Cache_Storage_Memory()
        );
    }

    /**
     * The Memory backend doesn't support lifetimes, so cannot test these like
     * in the TestBase class.
     */
    public function testExists()
    {
        $this->assertFalse($this->cache->exists('key1', 0));
        $this->cache->set('key1', 'data1', 0);
        $this->assertTrue($this->cache->exists('key1', 0));
    }

    /**
     * The Memory backend doesn't support lifetimes, so cannot test these like
     * in the TestBase class.
     */
    public function testGet()
    {
        $this->assertFalse($this->cache->get('key1', 0));
        $this->cache->set('key1', 'data1', 0);
        $this->assertEquals('data1', $this->cache->get('key1', 0));
    }
}
