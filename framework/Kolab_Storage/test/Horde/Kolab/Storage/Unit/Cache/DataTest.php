<?php
/**
 * Test the data cache.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the data cache.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Unit_Cache_DataTest
extends Horde_Kolab_Storage_TestCase
{
    public function testDataId()
    {
        $this->assertEquals('test', $this->_getTestCache()->getDataId());
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testMissingDataId()
    {
        $cache = new Horde_Kolab_Storage_Cache_Data($this->getMockCache());
        $cache->getDataId();
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





    public function testNotInitialized()
    {
        $this->assertFalse($this->_getTestCache()->isInitialized());
    }

    public function testInvalidVersion()
    {
        $cache = $this->getMockCache();
        $cache->storeListData(
            'test', serialize(array('S' => time(), 'V' => '0'))
        );
        $this->assertFalse($this->_getTestCache($cache)->isInitialized());
    }

    public function testMissingSync()
    {
        $cache = $this->getMockCache();
        $cache->storeListData(
            'test', serialize(
                array('V' => Horde_Kolab_Storage_Cache_List::VERSION)
            )
        );
        $this->assertFalse($this->_getTestCache($cache)->isInitialized());
    }

    public function testNamespace()
    {
        $cache = $this->_getTestCache();
        $cache->setNamespace('DUMMY');
        $this->assertEquals('DUMMY', $cache->getNamespace());
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testMissingNamespace()
    {
        $cache = $this->_getTestCache();
        $cache->getNamespace();
    }

    public function testUnsetSupport()
    {
        $cache = $this->_getTestCache();
        $this->assertFalse($cache->issetSupport('ACL'));
    }

    public function testLongterm()
    {
        $cache = $this->_getTestCache();
        $cache->setLongTerm('DUMMY', 'dummy');
        $this->assertEquals('dummy', $cache->getLongTerm('DUMMY'));
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testMissingLongterm()
    {
        $cache = $this->_getTestCache();
        $cache->getLongTerm('DUMMY');
    }

    public function testSetSupport()
    {
        $cache = $this->_getTestCache();
        $cache->setSupport('ACL', true);
        $this->assertTrue($cache->issetSupport('ACL'));
    }

    public function testSupport()
    {
        $cache = $this->_getTestCache();
        $cache->setSupport('ACL', true);
        $this->assertTrue($cache->hasSupport('ACL'));
    }

    public function testNoSupport()
    {
        $cache = $this->_getTestCache();
        $cache->setSupport('ACL', false);
        $this->assertFalse($cache->hasSupport('ACL'));
    }

    private function _getTestCache($cache = null)
    {
        if ($cache === null) {
            $cache = $this->getMockCache();
        }
        $list_cache = new Horde_Kolab_Storage_Cache_List($cache);
        $list_cache->setListId('test');
        return $list_cache;
    }
}
