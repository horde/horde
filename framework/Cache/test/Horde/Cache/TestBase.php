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
 * This is the base test class to run all tests that the backend implementation
 * should support.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Cache
 */
abstract class Horde_Cache_TestBase extends Horde_Test_Case
{
    protected $reason = '';
    protected $cache;

    abstract protected function _getCache($params = array());

    public function setUp()
    {
        $this->cache = $this->_getCache();
        if (!$this->cache) {
            $this->markTestSkipped($this->reason);
        }
    }

    public function testReadWrite()
    {
        $this->assertTrue($this->cache->testReadWrite());
    }

    public function testSet()
    {
        $this->assertNull($this->cache->set('key1', 'data1'));
        $this->assertNull($this->cache->set('key2', 'data2', 0));
        $this->assertNull($this->cache->set('key3', 'data3', 1));
    }

    public function testExists()
    {
        $this->assertFalse($this->cache->exists('key1', 0));
        $this->assertFalse($this->cache->exists('key2', 0));
        $this->cache->set('key1', 'data1', 0);
        $this->cache->set('key2', 'data2', 0);
        $this->assertTrue($this->cache->exists('key1', 0));
        $this->assertFalse($this->cache->exists('key2', -10));
    }

    public function testGet()
    {
        $this->assertFalse($this->cache->get('key1', 0));
        $this->assertFalse($this->cache->get('key2', 0));
        $this->cache->set('key1', 'data1', 0);
        $this->cache->set('key2', 'data2', 0);
        $this->assertEquals('data1', $this->cache->get('key1', 0));
        $this->assertFalse($this->cache->get('key2', -10));
    }

    public function testOutput()
    {
        $this->assertFalse($this->cache->output('key1', 0));
        $this->cache->set('key1', 'data1', 0);
        ob_start();
        $this->assertTrue($this->cache->output('key1', 0));
        $this->assertEquals('data1', ob_get_contents());
        ob_end_clean();
    }

    public function testExpire()
    {
        $this->cache->set('key1', 'data1', 0);
        $this->assertEquals('data1', $this->cache->get('key1', 0));
        $this->cache->expire('key1');
        $this->assertFalse($this->cache->get('key1', 0));
    }

    public function testClear()
    {
        $this->cache->set('key1', 'data1', 0);
        $this->assertEquals('data1', $this->cache->get('key1', 0));
        $this->cache->clear();
        $this->assertFalse($this->cache->get('key1', 0));
    }

    public function testCompress()
    {
        if (!class_exists('Horde_Compress_Fast')) {
            $this->markTestSkipped('Horde_Compress_Fast not installed');
        }
        $this->tearDown();
        $this->cache = $this->_getCache(array('compress' => true));
        if (!$this->cache) {
            $this->markTestSkipped($this->reason);
        }
        $this->assertFalse($this->cache->get('key1', 0));
        $this->cache->set('key1', 'data1', 0);
        $this->assertEquals('data1', $this->cache->get('key1', 0));
    }

    public function tearDown()
    {
        if ($this->cache) {
            $this->cache->clear();
            unset($this->cache);
        }
    }
}
