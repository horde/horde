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
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
            $this->getMockCache()
        );
        $this->assertType('array', $list->listFolders());
    }

    public function testListFolder()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getTwoFolderList(),
            $this->getMockCache()
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
            $this->getMockCache()
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
            $this->getMockCache()
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
            $this->getMockCache()
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
        $cache = $this->getMockCache();
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getMockDriverList(),
            $cache
        );
        $this->mockDriver->expects($this->once())
            ->method('getMailboxes') 
            ->will($this->returnValue(array('INBOX')));
        $this->mockDriver->expects($this->any())
            ->method('getId') 
            ->will($this->returnValue(array('A')));
        $mockDriver2 = $this->getMock('Horde_Kolab_Storage_Driver');
        $list2 = new Horde_Kolab_Storage_List_Decorator_Cache(
            new Horde_Kolab_Storage_List_Base(
                $mockDriver2,
                new Horde_Kolab_Storage_Factory()
            ),
            $cache
        );
        $mockDriver2->expects($this->once())
            ->method('getMailboxes') 
            ->will($this->returnValue(array('NOTHING')));
        $this->mockDriver->expects($this->any())
            ->method('getId') 
            ->will($this->returnValue(array('B')));

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
            $this->getMockCache()
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
            $this->getMockCache()
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
            $this->getMockCache()
        );
        $this->assertType('array', $list->listFolderTypes());
    }

    public function testFolderTypes()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getTwoFolderList(),
            $this->getMockCache()
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
            $this->getMockCache()
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
            $this->getMockCache()
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
            $this->getMockCache()
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
            $this->getMockCache()
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
            $this->getMockCache()
        );
        $this->mockDriver->expects($this->once())
            ->method('listAnnotation')
            ->will($this->returnValue(array('INBOX' => 'mail.default')));
        $list->synchronize();
        $list->listFolderTypes();
    }
}
