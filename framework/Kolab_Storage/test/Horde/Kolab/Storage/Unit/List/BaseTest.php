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
            'Horde_Kolab_Storage_Stub_FactoryQuery',
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
            'Horde_Kolab_Storage_Stub_FactoryQuery',
            $list
        );
        $list->registerQuery('Horde_Kolab_Storage_Stub_FactoryQuery', $query);
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Stub_FactoryQuery',
            $list->getQuery('Horde_Kolab_Storage_Stub_FactoryQuery')
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
            'Horde_Kolab_Storage_Stub_FactoryQuery',
            $list
        );
        $list->registerQuery(
            Horde_Kolab_Storage_List::QUERY_BASE, $query
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Stub_FactoryQuery',
            $list->getQuery()
        );
    }

    public function testGetFolder()
    {
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Folder',
            $this->_getList()->getFolder('INBOX/Calendar')
        );
    }

    public function testFolderPath()
    {
        $this->assertEquals(
            'INBOX/Calendar',
            $this->_getList()
            ->getFolder('INBOX/Calendar')
            ->getPath()
        );
    }

    public function testFolderNamespace()
    {
        $this->assertEquals(
            'personal',
            $this->_getList()
            ->getFolder('INBOX/Calendar')
            ->getNamespace()
        );
    }

    public function testFolderTitle()
    {
        $this->assertEquals(
            'Calendar',
            $this->_getList()
            ->getFolder('INBOX/Calendar')
            ->getTitle()
        );
    }

    public function testFolderOwner()
    {
        $this->assertEquals(
            'test@example.com',
            $this->_getList()
            ->getFolder('INBOX/Calendar')
            ->getOwner()
        );
    }

    public function testFolderSubpath()
    {
        $this->assertEquals(
            'Calendar',
            $this->_getList()
            ->getFolder('INBOX/Calendar')
            ->getSubpath()
        );
    }

    public function testFolderDefault()
    {
        $this->assertTrue(
            $this->_getList()
            ->getFolder('INBOX/Calendar')
            ->isDefault()
        );
    }

    public function testFolderType()
    {
        $this->assertEquals(
            'event',
            $this->_getList()
            ->getFolder('INBOX/Calendar')
            ->getType()
        );
    }

    public function testCreateFolder()
    {
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Folder',
            $this->_getList()->createFolder('INBOX/NewFolder')
        );
    }

    public function testCreateWithUmlaut()
    {
        $this->assertEquals(
            'NewFolderÄ',
            $this->_getList()
            ->createFolder('INBOX/NewFolderÄ')
            ->getTitle()
        );
    }

    public function testCreateFolderCreatesFolder()
    {
        $list = $this->_getList();
        $list->createFolder('INBOX/NewFolderÄ');
        $this->assertContains(
            'INBOX/NewFolderÄ',
            $list->listFolders()
        );
    }

    public function testCreateFolderWithAnnotation()
    {
        $list = $this->_getList();
        $list->createFolder('INBOX/NewFolderÄ', 'event');
        $this->assertContains(
            'INBOX/NewFolderÄ',
            $list->getQuery()->listByType('event')
        );
    }

    private function _getList()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $list = new Horde_Kolab_Storage_List_Base(
            $this->getAnnotatedMock(),
            $factory
        );
        $list->registerQuery(
            Horde_Kolab_Storage_List::QUERY_BASE,
            $factory->createListQuery(
                'Horde_Kolab_Storage_List_Query_Base',
                $list
            )
        );
        return $list;
    }
}
