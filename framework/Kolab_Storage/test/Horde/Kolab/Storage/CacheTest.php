<?php
/**
 * Test the Kolab cache.
 *
 * $Horde: framework/Kolab_Storage/test/Horde/Kolab/Storage/CacheTest.php,v 1.4 2009/03/20 23:44:38 wrobel Exp $
 *
 * @package Kolab_Storage
 */

/**
 *  We need the unit test framework 
 */
require_once 'PHPUnit/Framework.php';

require_once 'Horde.php';
require_once 'Horde/Kolab/Storage/Cache.php';

/**
 * Test the Kolab cache.
 *
 * $Horde: framework/Kolab_Storage/test/Horde/Kolab/Storage/CacheTest.php,v 1.4 2009/03/20 23:44:38 wrobel Exp $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kolab_Storage
 */
class Horde_Kolab_Storage_CacheTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test cache construction.
     */
    public function testConstruct()
    {
        $cache = &new Kolab_Cache();
        $this->assertEquals('Horde_Cache_File', get_class($cache->_horde_cache));
    }

    /**
     * Test cleaning the cache.
     */
    public function testReset()
    {
        $cache = &new Kolab_Cache();
        $cache->reset();
        $this->assertEquals(-1, $cache->validity);
        $this->assertEquals(-1, $cache->nextid);
        $this->assertTrue(empty($cache->objects));
        $this->assertTrue(empty($cache->uids));
    }

    /**
     * Test storing data.
     */
    public function testStore()
    {
        $cache = &new Kolab_Cache();
        $cache->reset();
        $item = array(1);
        $cache->store(10, 1, $item);
        $this->assertTrue(isset($cache->objects[1]));
        $this->assertTrue(isset($cache->uids[10]));
        $this->assertEquals(1, $cache->uids[10]);
        $this->assertSame($item, $cache->objects[1]);
    }

    /**
     * Test ignoring objects.
     */
    public function testIgnore()
    {
        $cache = &new Kolab_Cache();
        $cache->reset();
        $cache->ignore(11);
        $this->assertEquals(false, $cache->uids[11]);
    }

    /**
     * Test loading/saving the cache.
     */
    public function testLoadSave()
    {
        $cache = &new Kolab_Cache();
        $cache->load('test', 1);
        $cache->expire();
        $this->assertEquals(1, $cache->_data_version);
        $this->assertEquals('test', $cache->_key);
        $this->assertEquals(-1, $cache->validity);
        $this->assertEquals(-1, $cache->nextid);
        $this->assertTrue(empty($cache->objects));
        $this->assertTrue(empty($cache->uids));
        $item1 = array(1);
        $item2 = array(2);
        $cache->store(10, 1, $item1);
        $cache->store(12, 2, $item2);
        $cache->ignore(11);
        $this->assertTrue(isset($cache->objects[1]));
        $this->assertTrue(isset($cache->uids[10]));
        $this->assertEquals(1, $cache->uids[10]);
        $this->assertEquals($item1, $cache->objects[1]);
        $cache->save();
        $this->assertEquals(false, $cache->uids[11]);
        $cache->ignore(10);
        $cache->ignore(12);
        $this->assertEquals(false, $cache->uids[10]);
        $this->assertEquals(false, $cache->uids[12]);
        /** Allow us to reload the cache */
        $cache->_key = null;
        $cache->load('test', 1);
        $this->assertEquals((1 << 8) | 1, $cache->_cache_version);
        $this->assertTrue(isset($cache->objects[1]));
        $this->assertTrue(isset($cache->uids[10]));
        $this->assertEquals(1, $cache->uids[10]);
        $this->assertEquals($item1, $cache->objects[1]);
        $cache->expire();
        $this->assertEquals((1 << 8) | 1, $cache->_cache_version);
        $this->assertEquals(1, $cache->_data_version);
        $this->assertEquals('test', $cache->_key);
        $this->assertEquals(-1, $cache->validity);
        $this->assertEquals(-1, $cache->nextid);
        $this->assertTrue(empty($cache->objects));
        $this->assertTrue(empty($cache->uids));
    }

}
