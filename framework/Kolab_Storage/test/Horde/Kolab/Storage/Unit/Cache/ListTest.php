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
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the list cache.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Unit_Cache_ListTest
extends Horde_Kolab_Storage_TestCase
{
    public function testListId()
    {
        $this->assertEquals('test', $this->_getTestCache()->getListId());
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testMissingListId()
    {
        $cache = new Horde_Kolab_Storage_Cache_List($this->getMockCache());
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

    public function testID()
    {
        $cache = $this->getMockCache();
        $list_cache = new Horde_Kolab_Storage_Cache_List(
            $cache,
            array('host' => 'test', 'port' => '0', 'user' => 'test')
        );
        $list_cache->setListId('test');
        $list_cache->store(array(), array());
        $list_cache->save();
        $data = unserialize($cache->loadList($list_cache->getListId()));
        $this->assertEquals(
            'a:3:{s:4:"host";s:4:"test";s:4:"port";s:1:"0";s:4:"user";s:4:"test";}',
            $data['I']
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testMissingStamp()
    {
        $cache = $this->_getTestCache();
        $cache->getStamp();
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
