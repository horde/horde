<?php
/**
 * Test the operations of the list manipulator.
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
require_once __DIR__ . '/../../../Autoload.php';

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
class Horde_Kolab_Storage_Unit_List_Manipulation_BaseTest
extends PHPUnit_Framework_TestCase
{
    public function testCreateFolder()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $driver->expects($this->once())
            ->method('create')
            ->with('TEST');
        $manipulation = new Horde_Kolab_Storage_List_Manipulation_Base($driver);
        $manipulation->createFolder('TEST');
    }

    public function testCreateFolderWithType()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $driver->expects($this->once())
            ->method('create')
            ->with('TEST');
        $driver->expects($this->once())
            ->method('setAnnotation')
            ->with('TEST', '/shared/vendor/kolab/folder-type', 'event');
        $manipulation = new Horde_Kolab_Storage_List_Manipulation_Base($driver);
        $manipulation->createFolder('TEST', 'event');
    }

    public function testDeleteFolder()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $driver->expects($this->once())
            ->method('delete')
            ->with('TEST');
        $manipulation = new Horde_Kolab_Storage_List_Manipulation_Base($driver);
        $manipulation->deleteFolder('TEST');
    }

    public function testRenameFolder()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $driver->expects($this->once())
            ->method('rename')
            ->with('FOO', 'BAR');
        $manipulation = new Horde_Kolab_Storage_List_Manipulation_Base($driver);
        $manipulation->renameFolder('FOO', 'BAR');
    }

    public function testUpdateAfterCreateFolder()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $manipulation = new Horde_Kolab_Storage_List_Manipulation_Base($driver);
        $listener = $this->getMock('Horde_Kolab_Storage_List_Manipulation_Listener');
        $listener->expects($this->once())
            ->method('updateAfterCreateFolder')
            ->with('TEST');
        $manipulation->registerListener($listener);
        $manipulation->createFolder('TEST');
    }

    public function testUpdateAfterCreateFolderWithType()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $manipulation = new Horde_Kolab_Storage_List_Manipulation_Base($driver);
        $listener = $this->getMock('Horde_Kolab_Storage_List_Manipulation_Listener');
        $listener->expects($this->once())
            ->method('updateAfterCreateFolder')
            ->with('TEST', 'event');
        $manipulation->registerListener($listener);
        $manipulation->createFolder('TEST', 'event');
    }

    public function testUpdateAfterDeleteFolder()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $manipulation = new Horde_Kolab_Storage_List_Manipulation_Base($driver);
        $listener = $this->getMock('Horde_Kolab_Storage_List_Manipulation_Listener');
        $listener->expects($this->once())
            ->method('updateAfterDeleteFolder')
            ->with('TEST');
        $manipulation->registerListener($listener);
        $manipulation->deleteFolder('TEST');
    }

    public function testUpdateAfterRenameFolder()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $manipulation = new Horde_Kolab_Storage_List_Manipulation_Base($driver);
        $listener = $this->getMock('Horde_Kolab_Storage_List_Manipulation_Listener');
        $listener->expects($this->once())
            ->method('updateAfterRenameFolder')
            ->with('FOO', 'BAR');
        $manipulation->registerListener($listener);
        $manipulation->renameFolder('FOO', 'BAR');
    }
}