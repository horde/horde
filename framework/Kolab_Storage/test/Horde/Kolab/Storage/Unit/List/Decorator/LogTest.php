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
    public function testListLogsEntry()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Log(
            new Horde_Kolab_Storage_List_Base($this->getNullMock()),
            $this->getMockLogger()
        );
        $list->listFolders();
        $this->assertLogCount(2);
    }

    public function testListFolderCount()
    {
        $list = new Horde_Kolab_Storage_List_Decorator_Log(
            new Horde_Kolab_Storage_List_Base($this->getTwoFolderMock()),
            $this->getMockLogger()
        );
        $list->listFolders();
        $this->assertLogContains('List contained 2 folders.');
    }
}
