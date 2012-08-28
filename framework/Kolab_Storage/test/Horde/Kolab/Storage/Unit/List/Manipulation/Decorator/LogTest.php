<?php
/**
 * Test the operations of the list manipulation log decorator.
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
require_once __DIR__ . '/../../../../Autoload.php';

/**
 * Test the operations of the list manipulation log decorator.
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
class Horde_Kolab_Storage_Unit_List_Manipulation_Decorator_LogTest
extends PHPUnit_Framework_TestCase
{
    public function testCreateFolder()
    {
        $base = $this->getMock('Horde_Kolab_Storage_List_Manipulation');
        $base->expects($this->once())
            ->method('createFolder')
            ->with('TEST');
        $manipulation = new Horde_Kolab_Storage_List_Manipulation_Decorator_Log(
            $base, $this->getMock('Horde_Log_Logger')
        );
        $manipulation->createFolder('TEST');
    }

    public function testDeleteFolder()
    {
        $base = $this->getMock('Horde_Kolab_Storage_List_Manipulation');
        $base->expects($this->once())
            ->method('deleteFolder')
            ->with('TEST');
        $manipulation = new Horde_Kolab_Storage_List_Manipulation_Decorator_Log(
            $base, $this->getMock('Horde_Log_Logger')
        );
        $manipulation->deleteFolder('TEST');
    }

    public function testRenameFolder()
    {
        $base = $this->getMock('Horde_Kolab_Storage_List_Manipulation');
        $base->expects($this->once())
            ->method('renameFolder')
            ->with('FOO', 'BAR');
        $manipulation = new Horde_Kolab_Storage_List_Manipulation_Decorator_Log(
            $base, $this->getMock('Horde_Log_Logger')
        );
        $manipulation->renameFolder('FOO', 'BAR');
    }

    public function testRegisterListener()
    {
        $base = $this->getMock('Horde_Kolab_Storage_List_Manipulation');
        $base->expects($this->once())
            ->method('registerListener');
        $manipulation = new Horde_Kolab_Storage_List_Manipulation_Decorator_Log(
            $base, $this->getMock('Horde_Log_Logger')
        );
        $listener = $this->getMock('Horde_Kolab_Storage_List_Manipulation_Listener');
        $manipulation->registerListener($listener);
    }

    public function testCreateFolderLog()
    {
        $base = $this->getMock('Horde_Kolab_Storage_List_Manipulation');
        $logger = $this->getMock('Horde_Log_Logger', array('debug'));
        $logger->expects($this->exactly(2))
            ->method('debug')
            ->with(
                $this->logicalOr(
                    'Creating folder TEST.',
                    'Successfully created folder TEST [type: ].'
                )
            );
        $manipulation = new Horde_Kolab_Storage_List_Manipulation_Decorator_Log(
            $base, $logger
        );
        $manipulation->createFolder('TEST');
    }

    public function testDeleteFolderLog()
    {
        $base = $this->getMock('Horde_Kolab_Storage_List_Manipulation');
        $logger = $this->getMock('Horde_Log_Logger', array('debug'));
        $logger->expects($this->exactly(2))
            ->method('debug')
            ->with(
                $this->logicalOr(
                    'Deleting folder TEST.',
                    'Successfully deleted folder TEST.'
                )
            );
        $manipulation = new Horde_Kolab_Storage_List_Manipulation_Decorator_Log(
            $base, $logger
        );
        $manipulation->deleteFolder('TEST');
    }

    public function testRenameFolderLog()
    {
        $base = $this->getMock('Horde_Kolab_Storage_List_Manipulation');
        $logger = $this->getMock('Horde_Log_Logger', array('debug'));
        $logger->expects($this->exactly(2))
            ->method('debug')
            ->with(
                $this->logicalOr(
                    'Renaming folder FOO.',
                    'Successfully renamed folder FOO to BAR.'
                )
            );
        $manipulation = new Horde_Kolab_Storage_List_Manipulation_Decorator_Log(
            $base, $logger
        );
        $manipulation->renameFolder('FOO', 'BAR');
    }
}