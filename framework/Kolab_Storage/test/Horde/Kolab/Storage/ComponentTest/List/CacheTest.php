<?php
/**
 * Test the list cache.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the list cache.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_ComponentTest_List_CacheTest
extends Horde_Kolab_Storage_TestCase
{
    public function testListId()
    {
        $this->assertEquals(
            '933029c877625645eaa074e95727db52',
            $this->_getTestCache()->getListId()
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testMissingListId()
    {
        $cache = new Horde_Kolab_Storage_List_Cache($this->getMockCache());
        $cache->getListId();
    }

    public function testNotInitialized()
    {
        $this->assertFalse($this->_getTestCache()->isInitialized());
    }

    public function testInvalidVersion()
    {
        $cache = $this->getMockCache();
        $cache->storeList(
            'test', serialize(array('S' => time(), 'V' => '0'))
        );
        $this->assertFalse($this->_getTestCache($cache)->isInitialized());
    }

    public function testMissingSync()
    {
        $cache = $this->getMockCache();
        $cache->storeList(
            'test', serialize(
                array('V' => Horde_Kolab_Storage_List_Cache::VERSION)
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

    public function testID()
    {
        $cache = $this->getMockCache();
        $list_cache = new Horde_Kolab_Storage_List_Cache(
            $cache,
            array('host' => 'test', 'port' => '0', 'user' => 'test')
        );
        //$list_cache->setListId('test');
        $list_cache->store(array(), array());
        $list_cache->save();
        $data = unserialize($cache->loadList($list_cache->getListId()));
        $this->assertEquals(
            'a:3:{s:4:"host";s:4:"test";s:4:"port";s:1:"0";s:4:"user";s:4:"test";}',
            $data['I']
        );
    }

    public function testMissingStamp()
    {
        $cache = $this->_getTestCache();
        $this->assertEquals(0, $cache->getStamp());
    }

    public function testStamp()
    {
        $list_cache = $this->_getTestCache();
        $list_cache->store(array(), array());
        $list_cache->save();
        $this->assertEquals(
            6,
            strlen($list_cache->getStamp())
        );
    }

    public function testNewHost()
    {
        $this->assertNotSame(
            $this->_getTestCache(
                null,
                array('host' => 'a', 'port' => 1, 'user' => 'x')
            )->getListId(),
            $this->_getTestCache(
                null,
                array('host' => 'b', 'port' => 1, 'user' => 'x')
            )->getListId()
        );
    }

    public function testNewPort()
    {
        $this->assertNotSame(
            $this->_getTestCache(
                null,
                array('host' => 'a', 'port' => 1, 'user' => 'x')
            )->getListId(),
            $this->_getTestCache(
                null,
                array('host' => 'a', 'port' => 2, 'user' => 'x')
            )->getListId()
        );
    }

    public function testNewUser()
    {
        $this->assertNotSame(
            $this->_getTestCache(
                null,
                array('host' => 'a', 'port' => 1, 'user' => 'x')
            )->getListId(),
            $this->_getTestCache(
                null,
                array('host' => 'a', 'port' => 1, 'user' => 'y')
            )->getListId()
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testMissingHost()
    {
        $this->_getTestCache(null, array('port' => 1, 'user' => 'x'));
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testMissingPort()
    {
        $this->_getTestCache(null, array('host' => 'a', 'user' => 'x'));
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testMissingUser()
    {
        $this->_getTestCache(null, array('host' => 'a', 'port' => 1));
    }


    private function _getTestCache($cache = null, $params = null)
    {
        if ($cache === null) {
            $cache = $this->getMockCache();
        }
        if ($params === null) {
            $params = array('host' => 'a', 'port' => 1, 'user' => 'b');
        }
        $list_cache = new Horde_Kolab_Storage_List_Cache(
            $cache,
            $params
        );
        return $list_cache;
    }

}
