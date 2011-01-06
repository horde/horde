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
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Stub_FactoryQuery',
            $list->getQuery('Horde_Kolab_Storage_Stub_FactoryQuery')
        );
    }

}
