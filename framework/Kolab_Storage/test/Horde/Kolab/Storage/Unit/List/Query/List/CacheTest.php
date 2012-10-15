<?php
/**
 * Test the cached list query.
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
require_once __DIR__ . '/../../../../Autoload.php';

/**
 * Test the cached list query.
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
class Horde_Kolab_Storage_Unit_List_Query_List_CacheTest
extends PHPUnit_Framework_TestCase
{
    public function testListTypes()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('getQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::TYPES)
            ->will($this->returnValue(array('foo' => 'BAR')));
        $this->assertEquals(array('foo' => 'BAR'), $list->listTypes());
    }

    public function testUnitializedListTypes()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('hasQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::TYPES)
            ->will($this->returnValue(false));
        $this->sync->expects($this->once())
            ->method('synchronize');
        $list->listTypes('foo');
    }

    public function testInitializedListTypes()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('hasQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::TYPES)
            ->will($this->returnValue(true));
        $this->sync->expects($this->never())
            ->method('synchronize');
        $list->listTypes('foo');
    }

    public function testListByType()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('getQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::BY_TYPE)
            ->will($this->returnValue(array('foo' => array('BAR' => array('a' => 'b')))));
        $this->assertEquals(
            array('BAR'), $list->listByType('foo')
        );
    }

    public function testUnitializedListByType()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('hasQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::BY_TYPE)
            ->will($this->returnValue(false));
        $this->sync->expects($this->once())
            ->method('synchronize');
        $list->listByType('foo');
    }

    public function testItializedListByType()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('hasQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::BY_TYPE)
            ->will($this->returnValue(true));
        $this->sync->expects($this->never())
            ->method('synchronize');
        $list->listByType('foo');
    }

    public function testDataByType()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('getQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::BY_TYPE)
            ->will($this->returnValue(array('foo' => array('BAR' => array('a' => 'b')))));
        $this->assertEquals(
            array('BAR' => array('a' => 'b')), $list->dataByType('foo')
        );
    }

    public function testUnitializedDataByType()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('hasQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::BY_TYPE)
            ->will($this->returnValue(false));
        $this->sync->expects($this->once())
            ->method('synchronize');
        $list->dataByType('foo');
    }

    public function testItializedDataByType()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('hasQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::BY_TYPE)
            ->will($this->returnValue(true));
        $this->sync->expects($this->never())
            ->method('synchronize');
        $list->dataByType('foo');
    }

    public function testFolderData()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('getQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::FOLDERS)
            ->will($this->returnValue(array('BAR' => array('a' => 'b'))));
        $this->assertEquals(array('a' => 'b'), $list->folderData('BAR'));
    }

    public function testUninitializedFolderData()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('hasQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::FOLDERS)
            ->will($this->returnValue(false));
        $this->sync->expects($this->once())
            ->method('synchronize');
        $this->cache->expects($this->once())
            ->method('getQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::FOLDERS)
            ->will($this->returnValue(array('BAR' => array('a' => 'b'))));
        $list->folderData('BAR');
    }

    public function testInitializedFolderData()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('hasQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::FOLDERS)
            ->will($this->returnValue(true));
        $this->sync->expects($this->never())
            ->method('synchronize');
        $this->cache->expects($this->once())
            ->method('getQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::FOLDERS)
            ->will($this->returnValue(array('BAR' => array('a' => 'b'))));
        $list->folderData('BAR');
    }

    /**
     * @expectedException Horde_Kolab_Storage_List_Exception
     */
    public function testMissingFolderData()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('getQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::FOLDERS)
            ->will($this->returnValue(array('BAR' => array('a' => 'b'))));
        $list->folderData('INBOX/NO');
    }

    public function testListOwners()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('getQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::OWNERS)
            ->will($this->returnValue(array('FOO' => 'bar')));
        $this->assertEquals(
            array('FOO' => 'bar'),
            $list->listOwners()
        );
    }

    public function testUninitializedListOwners()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('hasQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::OWNERS)
            ->will($this->returnValue(false));
        $this->sync->expects($this->once())
            ->method('synchronize');
        $list->listOwners();
    }

    public function testInitializedListOwners()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('hasQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::OWNERS)
            ->will($this->returnValue(true));
        $this->sync->expects($this->never())
            ->method('synchronize');
        $list->listOwners();
    }

    public function testSetDefault()
    {
        $list = $this->_getList();
        $this->cache->expects($this->exactly(2))
            ->method('getQuery')
            ->with(
                $this->logicalOr(
                    Horde_Kolab_Storage_List_Query_List_Cache::FOLDERS,
                    Horde_Kolab_Storage_List_Query_List_Cache::PERSONAL_DEFAULTS
                )
            )
            ->will($this->returnValue(array('INBOX/Foo' => array('folder' => 'INBOX/Foo', 'type' => 'event'))));
        $this->sync->expects($this->once())
            ->method('setDefault')
            ->with(array('folder' => 'INBOX/Foo', 'type' => 'event'), null);
        $list->setDefault('INBOX/Foo');
    }

    public function testListPersonalDefaults()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('getQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::PERSONAL_DEFAULTS)
            ->will($this->returnValue(array('FOO' => 'bar')));
        $this->assertEquals(
            array('FOO' => 'bar'),
            $list->listPersonalDefaults()
        );
    }

    public function testUninitializedListPersonalDefaults()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('hasQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::PERSONAL_DEFAULTS)
            ->will($this->returnValue(false));
        $this->sync->expects($this->once())
            ->method('synchronize');
        $list->listPersonalDefaults();
    }

    public function testInitializedListPersonalDefaults()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('hasQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::PERSONAL_DEFAULTS)
            ->will($this->returnValue(true));
        $this->sync->expects($this->never())
            ->method('synchronize');
        $list->listPersonalDefaults();
    }

    public function testListDefaults()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('getQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::DEFAULTS)
            ->will($this->returnValue(array('FOO' => 'bar')));
        $this->assertEquals(
            array('FOO' => 'bar'),
            $list->listDefaults()
        );
    }

    public function testUninitializedListDefaults()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('hasQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::DEFAULTS)
            ->will($this->returnValue(false));
        $this->sync->expects($this->once())
            ->method('synchronize');
        $list->listDefaults();
    }

    public function testInitializedListDefaults()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('hasQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::DEFAULTS)
            ->will($this->returnValue(true));
        $this->sync->expects($this->never())
            ->method('synchronize');
        $list->listDefaults();
    }

    public function testGetDefault()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('getQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::PERSONAL_DEFAULTS)
            ->will($this->returnValue(array('bar' => 'FOO')));
        $this->assertEquals('FOO', $list->getDefault('bar'));
    }

    public function testMissingDefault()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('getQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::PERSONAL_DEFAULTS)
            ->will($this->returnValue(array('bar' => 'FOO')));
        $this->assertFalse($list->getDefault('foo'));
    }

    public function testGetForeignDefault()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('getQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::DEFAULTS)
            ->will($this->returnValue(array('owner' => array('bar' => 'FOO'))));
        $this->assertEquals(
            'FOO', $list->getForeignDefault('owner', 'bar')
        );
    }

    public function testMissingForeignDefault()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('getQuery')
            ->with(Horde_Kolab_Storage_List_Query_List_Cache::DEFAULTS)
            ->will($this->returnValue(array('owner' => array('bar' => 'FOO'))));
        $this->assertFalse($list->getForeignDefault('owner', 'foo'));
    }

    public function testGetStamp()
    {
        $list = $this->_getList();
        $this->cache->expects($this->once())
            ->method('getStamp')
            ->will($this->returnValue('STAMP'));
        $this->assertEquals(
            'STAMP', $list->getStamp()
        );
    }

    public function testUpdateAfterCreateFolder()
    {
        $list = $this->_getList();
        $this->sync->expects($this->once())
            ->method('updateAfterCreateFolder')
            ->with('INBOX/Calendar', 'event.default');
        $list->updateAfterCreateFolder('INBOX/Calendar', 'event.default');
    }

    public function testUpdateAfterDeleteFolder()
    {
        $list = $this->_getList();
        $this->sync->expects($this->once())
            ->method('updateAfterDeleteFolder')
            ->with('INBOX/Calendar');
        $list->updateAfterDeleteFolder('INBOX/Calendar');
    }

    public function testUpdateAfterRenameFolder()
    {
        $list = $this->_getList();
        $this->sync->expects($this->once())
            ->method('updateAfterRenameFolder')
            ->with('INBOX/Calendar', 'INBOX/Kalender');
        $list->updateAfterRenameFolder('INBOX/Calendar', 'INBOX/Kalender');
    }

    public function testSynchronize()
    {
        $list = $this->_getList();
        $this->sync->expects($this->once())
            ->method('synchronize')
            ->with();
        $list->synchronize();
    }

    public function testDuplicateDefaults()
    {
        $duplicates = array('a' => 'b');
        $list = $this->_getList();
        $this->sync->expects($this->once())
            ->method('getDuplicateDefaults')
            ->will($this->returnValue($duplicates));
        $this->assertEquals($duplicates, $list->getDuplicateDefaults());
    }

    private function _getList()
    {
        $this->cache = $this->getMock('Horde_Kolab_Storage_List_Cache', array(), array(), '', false, false);
        $this->sync = $this->getMock('Horde_Kolab_Storage_List_Query_List_Cache_Synchronization', array(), array(), '', false, false);
        return new Horde_Kolab_Storage_List_Query_List_Cache(
            $this->sync, $this->cache
        );
    }
}
