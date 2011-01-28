<?php
/**
 * Test the folder list cache decorator.
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
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the folder list cache decorator.
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
class Horde_Kolab_Storage_Unit_List_Decorator_CacheTest
extends Horde_Kolab_Storage_TestCase
{
    public function testListFolderIsArray()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getNullList(),
            $this->getMockListCache()
        );
        $this->assertType('array', $list->listFolders());
    }

    public function testListFolder()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getTwoFolderList(),
            $this->getMockListCache()
        );
        $this->assertEquals(
            array('INBOX', 'INBOX/a'),
            $list->listFolders()
        );
    }

    public function testLongerList()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getAnnotatedList(),
            $this->getMockListCache()
        );
        $this->assertEquals(
            array('INBOX', 'INBOX/a', 'INBOX/Calendar', 'INBOX/Contacts', 'INBOX/Notes', 'INBOX/Tasks'),
            $list->listFolders()
        );
    }

    public function testMockedList()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getMockDriverList(),
            $this->getMockListCache()
        );
        $this->mockDriver->expects($this->once())
            ->method('getMailboxes') 
            ->will($this->returnValue(array('INBOX')));
        $this->assertEquals(
            array('INBOX'),
            $list->listFolders()
        );
    }

    public function testCachedList()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getMockDriverList(),
            $this->getMockListCache()
        );
        $this->mockDriver->expects($this->once())
            ->method('getMailboxes') 
            ->will($this->returnValue(array('INBOX')));
        $list->listFolders();
        $this->assertEquals(
            array('INBOX'),
            $list->listFolders()
        );
    }

    public function testTwoCachedLists()
    {
        $decorated = $this->getMockDriverList();
        $this->mockDriver->expects($this->once())
            ->method('getMailboxes') 
            ->will($this->returnValue(array('INBOX')));
        $this->mockDriver->expects($this->once())
            ->method('getId') 
            ->will($this->returnValue('A'));
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $decorated,
            $this->getMockListCache()
        );

        $mockDriver2 = $this->getMock('Horde_Kolab_Storage_Driver');
        $mockDriver2->expects($this->once())
            ->method('getMailboxes') 
            ->will($this->returnValue(array('NOTHING')));
        $mockDriver2->expects($this->once())
            ->method('getId') 
            ->will($this->returnValue('B'));
        $list2 = new Horde_Kolab_Storage_List_Decorator_Cache(
            new Horde_Kolab_Storage_List_Base(
                $mockDriver2,
                new Horde_Kolab_Storage_Factory()
            ),
            $this->getMockListCache()
        );

        $list->listFolders();
        $list2->listFolders();
        $this->assertEquals(
            array('NOTHING'),
            $list2->listFolders()
        );
    }

    public function testSynchronizeFolders()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getMockDriverList(),
            $this->getMockListCache()
        );
        $this->mockDriver->expects($this->once())
            ->method('getMailboxes') 
            ->will($this->returnValue(array('INBOX')));
        $list->synchronize();
    }

    public function testSynchronizeFolderCache()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getMockDriverList(),
            $this->getMockListCache()
        );
        $this->mockDriver->expects($this->once())
            ->method('getMailboxes') 
            ->will($this->returnValue(array('INBOX')));
        $list->synchronize();
        $list->listFolders();
    }

    public function testTypeListIsArray()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getNullList(),
            $this->getMockListCache()
        );
        $this->assertType('array', $list->listFolderTypes());
    }

    public function testFolderTypes()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getTwoFolderList(),
            $this->getMockListCache()
        );
        $this->assertEquals(
            array(),
            $list->listFolderTypes()
        );
    }

    public function testMoreTypes()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getAnnotatedList(),
            $this->getMockListCache()
        );
        $this->assertEquals(
            array(
                'INBOX/Calendar' => 'event.default',
                'INBOX/Contacts' => 'contact.default',
                'INBOX/Notes' => 'note.default',
                'INBOX/Tasks' => 'task.default'
            ),
            $list->listFolderTypes()
        );
    }

    public function testMockedTypes()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getMockDriverList(),
            $this->getMockListCache()
        );
        $this->mockDriver->expects($this->once())
            ->method('listAnnotation')
            ->will($this->returnValue(array('INBOX' => 'mail.default')));
        $this->assertEquals(
            array('INBOX' => 'mail.default'),
            $list->listFolderTypes()
        );
    }

    public function testCachedTypes()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getMockDriverList(),
            $this->getMockListCache()
        );
        $this->mockDriver->expects($this->once())
            ->method('listAnnotation')
            ->will($this->returnValue(array('INBOX' => 'mail.default')));
        $list->listFolderTypes();
        $this->assertEquals(
            array('INBOX' => 'mail.default'),
            $list->listFolderTypes()
        );
    }

    public function testSynchronizeTypes()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getMockDriverList(),
            $this->getMockListCache()
        );
        $this->mockDriver->expects($this->once())
            ->method('listAnnotation')
            ->will($this->returnValue(array('INBOX' => 'mail.default')));
        $list->synchronize();
    }

    public function testSynchronizeTypeCache()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getMockDriverList(),
            $this->getMockListCache()
        );
        $this->mockDriver->expects($this->once())
            ->method('listAnnotation')
            ->will($this->returnValue(array('INBOX' => 'mail.default')));
        $list->synchronize();
        $list->listFolderTypes();
    }

    public function testSynchronizeIfEmpty()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getMockDriverList(),
            $this->getMockListCache()
        );
        $this->mockDriver->expects($this->once())
            ->method('getMailboxes') 
            ->will($this->returnValue(array('INBOX')));
        $this->mockDriver->expects($this->once())
            ->method('listAnnotation')
            ->will($this->returnValue(array('INBOX' => 'mail.default')));
        $list->listFolders();
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testExceptionIfFooled()
    {
        $cache = $this->getMockCache();
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getMockDriverList(),
            new Horde_Kolab_Storage_Cache_List(
                $cache
            )
        );
        $cache->storeListData($list->getConnectionId(), serialize(array('S' => time(), 'V' => '1')));
        $this->mockDriver->expects($this->never())
            ->method('getMailboxes') 
            ->will($this->returnValue(array('INBOX')));
        $list->listFolders();
    }

    public function testInvalidVersion()
    {
        $cache = $this->getMockCache();
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getMockDriverList(),
            new Horde_Kolab_Storage_Cache_List(
                $cache
            )
        );
        $cache->storeListData($list->getConnectionId(), serialize(array('S' => time(), 'V' => '2')));
        $this->mockDriver->expects($this->once())
            ->method('getMailboxes') 
            ->will($this->returnValue(array('INBOX')));
        $this->mockDriver->expects($this->once())
            ->method('listAnnotation')
            ->will($this->returnValue(array('INBOX' => 'mail.default')));
        $list->listFolders();
    }

    public function testInitialization()
    {
        $cache = $this->getMockCache();
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getMockDriverList(),
            new Horde_Kolab_Storage_Cache_List(
                $cache
            )
        );
        $this->mockDriver->expects($this->once())
            ->method('getMailboxes') 
            ->will($this->returnValue(array('INBOX')));
        $list->listFolders();
        $cache->storeListData($list->getConnectionId(), 'V', '2');
        $list->listFolders();
    }

    public function testGetNamespace()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getNullList(),
            $this->getMockListCache()
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Folder_Namespace',
            $list->getNamespace()
        );
    }

    public function testGetQuery()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $cache = $this->getMockCache();
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getMockDriverList(),
            new Horde_Kolab_Storage_Cache_List(
                $cache
            )
        );
        $query = $factory->createListQuery(
            'Horde_Kolab_Storage_List_Query_Base', $list
        );
        $list->registerQuery('Base', $query);
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Query',
            $list->getQuery('Base')
        );
    }
}
