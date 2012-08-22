<?php
/**
 * Test the operations of the list modifier.
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
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the operations of the list modifier.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_Unit_List_ModifierTest
extends PHPUnit_Framework_TestCase
{
    public function testCreateFolder()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $driver->expects($this->once())
            ->method('create')
            ->with('TEST');
        $list = new Horde_Kolab_Storage_List_Base(
            $driver, $this->getMock('Horde_Kolab_Storage_Factory')
        );
        $list->createFolder('TEST');
    }

    public function testDeleteFolder()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $driver->expects($this->once())
            ->method('delete')
            ->with('TEST');
        $list = new Horde_Kolab_Storage_List_Base(
            $driver, $this->getMock('Horde_Kolab_Storage_Factory')
        );
        $list->deleteFolder('TEST');
    }

    public function testRenameFolder()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $driver->expects($this->once())
            ->method('rename')
            ->with('FOO', 'BAR');
        $list = new Horde_Kolab_Storage_List_Base(
            $driver, $this->getMock('Horde_Kolab_Storage_Factory')
        );
        $list->renameFolder('FOO', 'BAR');
    }

}