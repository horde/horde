<?php
/**
 * Test the folder list log decorator.
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
 * Test the folder list log decorator.
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
class Horde_Kolab_Storage_Unit_List_Decorator_LogTest
extends Horde_Kolab_Storage_TestCase
{
    public function testListFolderCount()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Log(
            $this->getNullList(),
            $this->getMockLogger()
        );
        $list->listFolders();
        $this->assertLogCount(2);
    }

    public function testListLogsEntry()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Log(
            $this->getTwoFolderList(),
            $this->getMockLogger()
        );
        $list->listFolders();
        $this->assertLogContains('List for test@example.com@mock:0 contained 2 folders.');
    }

    public function testListAnnotationsLogsEntry()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Log(
            $this->getAnnotatedList(),
            $this->getMockLogger()
        );
        $list->listFolderTypes();
        $this->assertLogContains('List for test@example.com@mock:0 contained 4 folders and annotations.');
    }

    public function testGetNamespace()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Log(
            $this->getNullList(),
            $this->getMockLogger()
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Folder_Namespace',
            $list->getNamespace()
        );
    }

    public function testGetQuery()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $list = new Horde_Kolab_Storage_List_Decorator_Log(
            $this->getNullList($factory),
            $this->getMockLogger()
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

    public function testGetFolder()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $list = new Horde_Kolab_Storage_List_Decorator_Log(
            $this->getAnnotatedList($factory),
            $this->getMockLogger()
        );
        $list->registerQuery(
            Horde_Kolab_Storage_List::QUERY_BASE,
            $factory->createListQuery(
                'Horde_Kolab_Storage_List_Query_Base',
                $list
            )
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Folder',
            $list->getFolder('INBOX/Calendar')
        );
    }

    public function testCreateFolder()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Log(
            $this->getAnnotatedQueriableList(),
            $this->getMockLogger()
        );
        $list->createFolder('INBOX/NewFolderÄ');
        $this->assertContains(
            'INBOX/NewFolderÄ',
            $list->listFolders()
        );
    }

    public function testCreateFolderLogOne()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Log(
            $this->getAnnotatedQueriableList(),
            $this->getMockLogger()
        );
        $list->createFolder('INBOX/NewFolderÄ');
        $this->assertLogContains('Creating folder INBOX/NewFolderÄ.');
    }

    public function testCreateFolderLogTwo()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Log(
            $this->getAnnotatedQueriableList(),
            $this->getMockLogger()
        );#
        $list->createFolder('INBOX/NewFolderÄ');
        $this->assertLogContains('Successfully created folder INBOX/NewFolderÄ [type: mail, owner: test@example.com, namespace: personal, title: NewFolderÄ].');
    }
}
