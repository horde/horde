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

    public function testGetQuery()
    {
        $factory = new Horde_Kolab_Storage_Factory();
        $list = new Horde_Kolab_Storage_List_Decorator_Log(
            $this->getNullList($factory),
            $this->getMockLogger()
        );
        $factory->createListQuery('Base', $list);
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Query',
            $list->getQuery('Base')
        );
    }
}
