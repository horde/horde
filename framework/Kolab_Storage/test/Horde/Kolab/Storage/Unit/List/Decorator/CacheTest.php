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
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the folder list cache decorator.
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
class Horde_Kolab_Storage_Unit_List_Decorator_CacheTest
extends Horde_Kolab_Storage_TestCase
{
    public function testListFolderIsArray()
    {
        $list = $this->_setupMockList();
        $this->assertInternalType('array', $list->listFolders());
    }

    public function testListFolder()
    {
        $list = $this->_getCachedList($this->getTwoFolderList());
        $this->assertEquals(
            array('INBOX', 'INBOX/a'),
            $list->listFolders()
        );
    }

    public function testLongerList()
    {
        $list = $this->_getCachedList($this->getAnnotatedList());
        $this->assertEquals(
            array('INBOX', 'INBOX/a', 'INBOX/Calendar', 'INBOX/Contacts', 'INBOX/Notes', 'INBOX/Tasks'),
            $list->listFolders()
        );
    }

    public function testMockedList()
    {
        $list = $this->_setupMockList();
        $this->assertEquals(
            array('INBOX'),
            $list->listFolders()
        );
    }

    public function testCachedList()
    {
        $list = $this->_setupMockList();
        $list->listFolders();
        $this->assertEquals(
            array('INBOX'),
            $list->listFolders()
        );
    }

    private function _twoListCaches($cache, $n1, $n2)
    {
        $lc1 = new Horde_Kolab_Storage_Cache_List($cache);
        $lc1->setListId($n1);
        $lc2 = new Horde_Kolab_Storage_Cache_List($cache);
        $lc2->setListId($n2);
        return array($lc1, $lc2);
    }

    public function testTwoDifferentCachedLists()
    {
        $cache = $this->getMockCache();
        list($lc1, $lc2) = $this->_twoListCaches($cache, 'lc1', 'lc2');

        $decorated = $this->getMockDriverList();
        $this->mockDriver->expects($this->once())
            ->method('listFolders') 
            ->will($this->returnValue(array('INBOX')));
        $list = new Horde_Kolab_Storage_List_Decorator_Cache($decorated, $lc1);

        $mockDriver2 = $this->getMock('Horde_Kolab_Storage_Driver');
        $mockDriver2->expects($this->once())
            ->method('listFolders') 
            ->will($this->returnValue(array('NOTHING')));
        $list2 = new Horde_Kolab_Storage_List_Decorator_Cache(
            new Horde_Kolab_Storage_List_Base(
                $mockDriver2,
                new Horde_Kolab_Storage_Factory()
            ),
            $lc2
        );
        $list->listFolders();
        $list2->listFolders();

        list($lc1, $lc2) = $this->_twoListCaches($cache, 'lc1', 'lc2');
        $list = new Horde_Kolab_Storage_List_Decorator_Cache($decorated, $lc1);
        $list2 = new Horde_Kolab_Storage_List_Decorator_Cache(
            new Horde_Kolab_Storage_List_Base(
                $mockDriver2,
                new Horde_Kolab_Storage_Factory()
            ),
            $lc2
        );

        $this->assertEquals(array('INBOX'), $list->listFolders());
        $this->assertEquals(array('NOTHING'), $list2->listFolders());
    }

    public function testTwoEqualCachedLists()
    {
        $cache = $this->getMockCache();
        list($lc1, $lc2) = $this->_twoListCaches($cache, 'lc1', 'lc1');

        $decorated = $this->getMockDriverList();
        $this->mockDriver->expects($this->once())
            ->method('listFolders') 
            ->will($this->returnValue(array('INBOX')));
        $list = new Horde_Kolab_Storage_List_Decorator_Cache($decorated, $lc1);

        $mockDriver2 = $this->getMock('Horde_Kolab_Storage_Driver');
        $mockDriver2->expects($this->never())
            ->method('listFolders') 
            ->will($this->returnValue(array('NOTHING')));
        $list2 = new Horde_Kolab_Storage_List_Decorator_Cache(
            new Horde_Kolab_Storage_List_Base(
                $mockDriver2,
                new Horde_Kolab_Storage_Factory()
            ),
            $lc2
        );
        $list->listFolders();
        $list2->listFolders();

        list($lc1, $lc2) = $this->_twoListCaches($cache, 'lc1', 'lc1');
        $list = new Horde_Kolab_Storage_List_Decorator_Cache($decorated, $lc1);
        $list2 = new Horde_Kolab_Storage_List_Decorator_Cache(
            new Horde_Kolab_Storage_List_Base(
                $mockDriver2,
                new Horde_Kolab_Storage_Factory()
            ),
            $lc2
        );

        $this->assertEquals(array('INBOX'), $list->listFolders());
        $this->assertEquals(array('INBOX'), $list2->listFolders());
    }

    public function testSynchronizeFolders()
    {
        $list = $this->_setupMockList();
        $list->synchronize();
    }

    public function testSynchronizeFolderCache()
    {
        $list = $this->_setupMockList();
        $list->synchronize();
        $list->listFolders();
    }

    public function testTypeListIsArray()
    {
        $list = $this->_getCachedList($this->getNullList());
        $this->assertInternalType('array', $list->listFolderTypes());
    }

    public function testFolderTypes()
    {
        $list = $this->_getCachedList($this->getTwoFolderList());
        $this->assertEquals(
            array(),
            $list->listFolderTypes()
        );
    }

    public function testMoreTypes()
    {
        $list = $this->_getCachedList($this->getAnnotatedList());
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
        $list = $this->_setupMockList();
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
        $list = $this->_setupMockList();
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
        $list = $this->_setupMockList();
        $this->mockDriver->expects($this->once())
            ->method('listAnnotation')
            ->will($this->returnValue(array('INBOX' => 'mail.default')));
        $list->synchronize();
    }

    public function testSynchronizeTypeCache()
    {
        $list = $this->_setupMockList();
        $this->mockDriver->expects($this->once())
            ->method('listAnnotation')
            ->will($this->returnValue(array('INBOX' => 'mail.default')));
        $list->synchronize();
        $list->listFolderTypes();
    }

    public function testSynchronizeIfEmpty()
    {
        $list = $this->_setupMockList();
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
        $cache->storeList($list->getId(), serialize(array('S' => time(), 'V' => '1')));
        $this->mockDriver->expects($this->never())
            ->method('listFolders') 
            ->will($this->returnValue(array('INBOX')));
        $list->listFolders();
    }

    public function testInvalidVersion()
    {
        $cache = $this->getMockCache();
        $list_cache = new Horde_Kolab_Storage_Cache_List($cache);
        $list_cache->setListId('test');
        $list = $this->_setupMockList($list_cache);
        $cache->storeList($list->getId(), serialize(array('S' => time(), 'V' => '2')));
        $this->mockDriver->expects($this->once())
            ->method('listAnnotation')
            ->will($this->returnValue(array('INBOX' => 'mail.default')));
        $list->listFolders();
    }

    public function testInitialization()
    {
        $cache = $this->getMockCache();
        $list_cache = new Horde_Kolab_Storage_Cache_List($cache);
        $list_cache->setListId('test');
        $list = $this->_setupMockList($list_cache);
        $list->listFolders();
        $cache->storeList($list->getId(), 'V', '2');
        $list->listFolders();
    }

    public function testGetNamespace()
    {
        $list = $this->_getCachedList($this->getNullList());
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Folder_Namespace',
            $list->getNamespace()
        );
    }

    public function testCachedNamespace()
    {
        $list = $this->_setupBareMockList();
        $this->mockDriver->expects($this->once())
            ->method('getNamespace') 
            ->will(
                $this->returnValue(
                    new Horde_Kolab_Storage_Folder_Namespace_Fixed('test')
                )
            );
        $list->getNamespace();
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Folder_Namespace',
            $list->getNamespace()
        );
    }

    public function testGetQuery()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $cache = $this->getMockCache();
        $list_cache = new Horde_Kolab_Storage_Cache_List($cache);
        $list_cache->setListId('test');
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $this->getMockDriverList($factory), $list_cache
        );
        $query = new Horde_Kolab_Storage_List_Query_List_Base(
            $list, array('factory' => $factory)
        );
        $list->registerQuery('Base', $query);
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_List_Query',
            $list->getQuery('Base')
        );
    }

    public function testGetFolder()
    {
        $list = $this->getCachedAnnotatedQueriableList();
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Folder',
            $list->getFolder('INBOX/Calendar')
        );
    }

    public function testCreateFolder()
    {
        $list = $this->getCachedAnnotatedQueriableList();
        $list->createFolder('INBOX/NewFolderÄ');
        $this->assertContains(
            'INBOX/NewFolderÄ',
            $list->listFolders()
        );
    }

    public function testCacheUpdateAfterCreate()
    {
        $list = $this->getCachedAnnotatedQueriableList();
        $list->listFolders();
        $list->createFolder('INBOX/NewFolderÄ');
        $this->assertContains(
            'INBOX/NewFolderÄ',
            $list->listFolders()
        );
    }

    public function testTypeAfterCreate()
    {
        $list = $this->getCachedAnnotatedQueriableList();
        $list->listFolders();
        $list->createFolder('INBOX/NewFolderÄ');
        $this->assertEquals(
            'mail',
            $list->getFolder('INBOX/NewFolderÄ')->getType()
        );
    }

    public function testNewFolderNotCachedTwice()
    {
        $list = $this->getCachedAnnotatedQueriableList();
        $list->createFolder('INBOX/NewFolderÄ');
        $count = 0;
        foreach ($list->listFolders() as $folder) {
            if ($folder == 'INBOX/NewFolderÄ') {
                $count++;
            }
        }
        $this->assertEquals(1, $count);
    }

    public function testDeleteFolder()
    {
        $list = $this->getCachedAnnotatedQueriableList();
        $list->deleteFolder('INBOX/Calendar');
        $this->assertNotContains(
            'INBOX/Calendar',
            $list->listFolders()
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testCacheUpdateAfterDelete()
    {
        $list = $this->getCachedAnnotatedQueriableList();
        $list->listFolders();
        $list->deleteFolder('INBOX/Calendar');
        $list->getFolder('INBOX/Calendar')->getType();
    }

    public function testRenameFolder()
    {
        $list = $this->getCachedAnnotatedQueriableList();
        $list->renameFolder('INBOX/Calendar', 'INBOX/Ä');
        $this->assertNotContains(
            'INBOX/Calendar',
            $list->listFolders()
        );
    }

    public function testRenameFolderTarget()
    {
        $list = $this->getCachedAnnotatedQueriableList();
        $list->renameFolder('INBOX/Calendar', 'INBOX/Ä');
        $this->assertContains(
            'INBOX/Ä',
            $list->listFolders()
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testCacheUpdateAfterRename()
    {
        $list = $this->getCachedAnnotatedQueriableList();
        $list->listFolders();
        $list->renameFolder('INBOX/Calendar', 'INBOX/Ä');
        $list->getFolder('INBOX/Calendar')->getType();
    }


    private function _setupMockList($cache = null)
    {
        $list = $this->_setupBareMockList($cache);
        $this->mockDriver->expects($this->once())
            ->method('listFolders') 
            ->will($this->returnValue(array('INBOX')));
        return $list;
    }

    private function _setupBareMockList($cache = null)
    {
        if ($cache === null) {
            $cache = $this->getMockListCache();
        }
        $mock_list = $this->getMockDriverList();
        $this->mockDriver->expects($this->any())
            ->method('getId') 
            ->will($this->returnValue('test'));
        $list = new Horde_Kolab_Storage_List_Decorator_Cache(
            $mock_list,
            $cache
        );
        return $list;
    }

    private function _getCachedList($list)
    {
        return new Horde_Kolab_Storage_List_Decorator_Cache(
            $list,
            $this->getMockListCache(),
            new Horde_Kolab_Storage_Factory()
        );
    }
}
