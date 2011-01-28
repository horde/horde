<?php
/**
 * Test the Kolab cache.
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
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the Kolab cache.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Unit_CacheTest
extends Horde_Kolab_Storage_TestCase
{
    public function setUp()
    {
        $this->cache = new Horde_Cache_Storage_Mock();
    }

    /**
     * Test cleaning the cache.
     *
     * @return NULL
     */
    public function testReset()
    {
        $cache = new Horde_Kolab_Storage_Cache($this->cache);
        $cache->reset();
        $this->assertEquals(-1, $cache->validity);
        $this->assertEquals(-1, $cache->nextid);
        $this->assertTrue(empty($cache->objects));
        $this->assertTrue(empty($cache->uids));
    }

    /**
     * Test storing data.
     *
     * @return NULL
     */
    public function testStore()
    {
        $cache = new Horde_Kolab_Storage_Cache($this->cache);
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
     *
     * @return NULL
     */
    public function testIgnore()
    {
        $cache = new Horde_Kolab_Storage_Cache($this->cache);
        $cache->reset();
        $cache->ignore(11);
        $this->assertEquals(false, $cache->uids[11]);
    }

    public function testLoadAttachment()
    {
        $cache = new Horde_Kolab_Storage_Cache($this->cache);
        $cache->storeAttachment('a', 'attachment');
        $this->assertEquals('attachment', $cache->loadAttachment('a'));
    }

    public function testLoadSecondAttachment()
    {
        $cache = new Horde_Kolab_Storage_Cache($this->cache);
        $cache->storeAttachment('a', 'attachment');
        $cache->storeAttachment('b', 'b');
        $this->assertEquals('b', $cache->loadAttachment('b'));
    }

    public function testOverrideAttachment()
    {
        $cache = new Horde_Kolab_Storage_Cache($this->cache);
        $cache->storeAttachment('a', 'attachment');
        $cache->storeAttachment('a', 'a');
        $this->assertEquals('a', $cache->loadAttachment('a'));
    }

    public function testCachingListData()
    {
        $cache = new Horde_Kolab_Storage_Cache($this->cache);
        $cache->storeListData('user@example.com:143', array('folders' => array('a', 'b')));
        $this->assertEquals(array('folders' => array('a', 'b')), $cache->loadListData('user@example.com:143'));
    }

    /**
     * Test loading/saving the cache.
     *
     * @return NULL
     */
    public function testLoadSave()
    {
        $cache = new Horde_Kolab_Storage_Cache($this->cache);
        $cache->load('test', 1);
        /**
         * Loading a second time should return immediately (see code
         * coverage)
         */
        $cache->load('test', 1);
        $cache->expire();
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
        $cache->load('test', 1, true);
        $this->assertTrue(isset($cache->objects[1]));
        $this->assertTrue(isset($cache->uids[10]));
        $this->assertEquals(1, $cache->uids[10]);
        $this->assertEquals($item1, $cache->objects[1]);
        $cache->expire();
        $this->assertEquals(-1, $cache->validity);
        $this->assertEquals(-1, $cache->nextid);
        $this->assertTrue(empty($cache->objects));
        $this->assertTrue(empty($cache->uids));
    }

}
