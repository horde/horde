<?php
/**
 * Test the basic folder list handler.
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
 * Test the basic folder list handler.
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
class Horde_Kolab_Storage_Unit_List_BaseTest
extends Horde_Kolab_Storage_TestCase
{
    public function testListReturnsArray()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $this->assertType('array', $list->listFolders());
    }

    public function testListReturnsFolders()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getTwoFolderMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $this->assertEquals(
            array('INBOX', 'INBOX/a'),
            $list->listFolders()
        );
    }

    public function testListFolderTypesReturnsArray()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $this->assertType('array', $list->listFolderTypes());
    }

    public function testGetNamespace()
    {
        $list = $this->getNullList();
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Folder_Namespace',
            $list->getNamespace()
        );
    }

    public function testListQueriable()
    {
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            new Horde_Kolab_Storage_Factory()
        );
        $this->assertTrue($list instanceOf Horde_Kolab_Storage_Queriable);
    }

    public function testQuerySynchronization()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            $factory
        );
        $query = $factory->createListQuery(
            'Horde_Kolab_Storage_Stub_ListQuery',
            $list
        );
        $list->registerQuery('stub', $query);
        $list->synchronize();
        $this->assertTrue($query->synchronized);
    }

    public function testGetQuery()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            $factory
        );
        $query = $factory->createListQuery(
            'Horde_Kolab_Storage_Stub_ListQuery',
            $list
        );
        $list->registerQuery('Horde_Kolab_Storage_Stub_ListQuery', $query);
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Stub_ListQuery',
            $list->getQuery('Horde_Kolab_Storage_Stub_ListQuery')
        );
    }

    public function testGetBaseQuery()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            $factory
        );
        $query = $factory->createListQuery(
            'Horde_Kolab_Storage_Stub_ListQuery',
            $list
        );
        $list->registerQuery(
            Horde_Kolab_Storage_List::QUERY_BASE, $query
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Stub_ListQuery',
            $list->getQuery()
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testRegisterInvalid()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getNullMock(),
            $factory
        );
        $query = $factory->createDataQuery(
            'Horde_Kolab_Storage_Stub_DataQuery',
            $this->getMock('Horde_Kolab_Storage_Data')
        );
        $list->registerQuery(
            Horde_Kolab_Storage_List::QUERY_BASE, $query
        );
    }

    public function testGetFolder()
    {
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Folder',
            $this->getAnnotatedQueriableList()->getFolder('INBOX/Calendar')
        );
    }

    public function testFolderPath()
    {
        $this->assertEquals(
            'INBOX/Calendar',
            $this->getAnnotatedQueriableList()
            ->getFolder('INBOX/Calendar')
            ->getPath()
        );
    }

    public function testFolderNamespace()
    {
        $this->assertEquals(
            'personal',
            $this->getAnnotatedQueriableList()
            ->getFolder('INBOX/Calendar')
            ->getNamespace()
        );
    }

    public function testFolderTitle()
    {
        $this->assertEquals(
            'Calendar',
            $this->getAnnotatedQueriableList()
            ->getFolder('INBOX/Calendar')
            ->getTitle()
        );
    }

    public function testFolderOwner()
    {
        $this->assertEquals(
            'test@example.com',
            $this->getAnnotatedQueriableList()
            ->getFolder('INBOX/Calendar')
            ->getOwner()
        );
    }

    public function testFolderSubpath()
    {
        $this->assertEquals(
            'Calendar',
            $this->getAnnotatedQueriableList()
            ->getFolder('INBOX/Calendar')
            ->getSubpath()
        );
    }

    public function testFolderDefault()
    {
        $this->assertTrue(
            $this->getAnnotatedQueriableList()
            ->getFolder('INBOX/Calendar')
            ->isDefault()
        );
    }

    public function testFolderType()
    {
        $this->assertEquals(
            'event',
            $this->getAnnotatedQueriableList()
            ->getFolder('INBOX/Calendar')
            ->getType()
        );
    }

    public function testCreateFolder()
    {
        $this->assertNull(
            $this->getAnnotatedQueriableList()->createFolder('INBOX/NewFolder')
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testCreateExistingFolder()
    {
        $list = $this->getAnnotatedQueriableList();
        $list->createFolder('INBOX/NewFolder');
        $list->createFolder('INBOX/NewFolder');
    }

    public function testCreateWithUmlaut()
    {
        $list = $this->getAnnotatedQueriableList();
        $list->createFolder('INBOX/NewFolderÄ');
        $this->assertEquals(
            'NewFolderÄ',
            $list->getFolder('INBOX/NewFolderÄ')
            ->getTitle()
        );
    }

    public function testCreateFolderCreatesFolder()
    {
        $list = $this->getAnnotatedQueriableList();
        $list->createFolder('INBOX/NewFolderÄ');
        $this->assertContains(
            'INBOX/NewFolderÄ',
            $list->listFolders()
        );
    }

    public function testCreateFolderWithAnnotation()
    {
        $list = $this->getAnnotatedQueriableList();
        $list->createFolder('INBOX/NewFolderÄ', 'event');
        $this->assertContains(
            'INBOX/NewFolderÄ',
            $list->getQuery()->listByType('event')
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testDeleteMissingFolder()
    {
        $this->getAnnotatedQueriableList()->deleteFolder('INBOX/ÄBC');
    }

    public function testDeleteFolder()
    {
        $list = $this->getAnnotatedQueriableList();
        $list->createFolder('INBOX/ÄBC');
        $this->assertNull(
            $list->deleteFolder('INBOX/ÄBC')
        );
    }

    public function testDeleteRemovesFolder()
    {
        $list = $this->getAnnotatedQueriableList();
        $list->deleteFolder('INBOX/Calendar');
        $this->assertNotContains(
            'INBOX/Calendar',
            $list->listFolders()
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testRenameMissingFolder()
    {
        $this->getAnnotatedQueriableList()->renameFolder('INBOX/ÄBC', 'INBOX/A');
    }

    /**
     * @expectedException Horde_Kolab_Storage_Exception
     */
    public function testRenameToExistingFolder()
    {
        $list = $this->getAnnotatedQueriableList();
        $list->createFolder('INBOX/ÜBC');
        $list->renameFolder('INBOX/ÄBC', 'INBOX/ÜBC');
    }

    public function testRenameFolder()
    {
        $list = $this->getAnnotatedQueriableList();
        $list->createFolder('INBOX/ÄBC');
        $this->assertNull(
            $list->renameFolder('INBOX/ÄBC', 'INBOX/ÜBC')
        );
    }

    public function testRenameRemovesFolder()
    {
        $list = $this->getAnnotatedQueriableList();
        $list->renameFolder('INBOX/Calendar', 'INBOX/ÄBC');
        $this->assertNotContains(
            'INBOX/Calendar',
            $list->listFolders()
        );
    }

    public function testRenameAddsFolder()
    {
        $list = $this->getAnnotatedQueriableList();
        $list->renameFolder('INBOX/Calendar', 'INBOX/ÄBC');
        $this->assertContains(
            'INBOX/ÄBC',
            $list->listFolders()
        );
    }
}
