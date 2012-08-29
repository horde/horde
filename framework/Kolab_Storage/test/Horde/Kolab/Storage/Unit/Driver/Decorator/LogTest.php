<?php
/**
 * Test the log decorator for the backend drivers.
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
 * Test the log decorator for the backend drivers.
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
class Horde_Kolab_Storage_Unit_Driver_Decorator_LogTest
extends Horde_Kolab_Storage_TestCase
{
    public function testGetMailboxesLogsEntry()
    {
        $driver = new Horde_Kolab_Storage_Driver_Decorator_Log(
            $this->getNullMock(),
            $this->getMockLogger()
        );
        $driver->listFolders();
        $this->assertLogCount(2);
    }

    public function testGetMailboxesFolderCount()
    {
        $driver = new Horde_Kolab_Storage_Driver_Decorator_Log(
            $this->getTwoFolderMock(),
            $this->getMockLogger()
        );
        $driver->listFolders();
        $this->assertLogContains('Driver "Horde_Kolab_Storage_Driver_Mock": List contained 2 folders.');
    }

    public function testListAnnotationLogsEntry()
    {
        $driver = new Horde_Kolab_Storage_Driver_Decorator_Log(
            $this->getNullMock(),
            $this->getMockLogger()
        );
        $driver->listAnnotation('/shared/vendor/kolab/folder-type');
        $this->assertLogCount(2);
    }

    public function testListAnnotationFolderCount()
    {
        $driver = new Horde_Kolab_Storage_Driver_Decorator_Log(
            $this->getAnnotatedMock(),
            $this->getMockLogger()
        );
        $driver->listAnnotation('/shared/vendor/kolab/folder-type');
        $this->assertLogContains('Driver "Horde_Kolab_Storage_Driver_Mock": List contained 4 folder annotations.');
    }

    public function testGetNamespaceLogsEntry()
    {
        $driver = new Horde_Kolab_Storage_Driver_Decorator_Log(
            $this->getNullMock(),
            $this->getMockLogger()
        );
        $driver->getNamespace();
        $this->assertLogCount(2);
    }

    public function testGetNamespaceType()
    {
        $driver = new Horde_Kolab_Storage_Driver_Decorator_Log(
            $this->getNullMock(),
            $this->getMockLogger()
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Folder_Namespace',
            $driver->getNamespace()
        );
    }

    public function testCreateFolderLog()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $logger = $this->getMock('Horde_Log_Logger', array('debug'));
        $logger->expects($this->exactly(2))
            ->method('debug')
            ->with(
                $this->logicalOr(
                    'Driver "' . get_class($driver) . '": Creating folder INBOX/Test.',
                    'Driver "' . get_class($driver) . '": Successfully created folder INBOX/Test.'
                )
            );
        $log = new Horde_Kolab_Storage_Driver_Decorator_Log($driver, $logger);
        $log->create('INBOX/Test');
    }

    public function testDeleteFolderLog()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $logger = $this->getMock('Horde_Log_Logger', array('debug'));
        $logger->expects($this->exactly(2))
            ->method('debug')
            ->with(
                $this->logicalOr(
                    'Driver "' . get_class($driver) . '": Deleting folder INBOX/Test.',
                    'Driver "' . get_class($driver) . '": Successfully deleted folder INBOX/Test.'
                )
            );
        $log = new Horde_Kolab_Storage_Driver_Decorator_Log($driver, $logger);
        $log->delete('INBOX/Test');
    }

    public function testRenameFolderLog()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $logger = $this->getMock('Horde_Log_Logger', array('debug'));
        $logger->expects($this->exactly(2))
            ->method('debug')
            ->with(
                $this->logicalOr(
                    'Driver "' . get_class($driver) . '": Renaming folder INBOX/Foo.',
                    'Driver "' . get_class($driver) . '": Successfully renamed folder INBOX/Foo to INBOX/Bar.'
                )
            );
        $log = new Horde_Kolab_Storage_Driver_Decorator_Log($driver, $logger);
        $log->rename('INBOX/Foo', 'INBOX/Bar');
    }

    public function testCreateFolder()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $driver->expects($this->once())
            ->method('create')
            ->with('INBOX/Test');
        $logger = $this->getMock('Horde_Log_Logger', array('debug'));
        $log = new Horde_Kolab_Storage_Driver_Decorator_Log($driver, $logger);
        $log->create('INBOX/Test');
    }

    public function testDeleteFolder()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $driver->expects($this->once())
            ->method('delete')
            ->with('INBOX/Test');
        $logger = $this->getMock('Horde_Log_Logger', array('debug'));
        $log = new Horde_Kolab_Storage_Driver_Decorator_Log($driver, $logger);
        $log->delete('INBOX/Test');
    }

    public function testRenameFolder()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $driver->expects($this->once())
            ->method('rename')
            ->with('INBOX/Test', 'FOO');
        $logger = $this->getMock('Horde_Log_Logger', array('debug'));
        $log = new Horde_Kolab_Storage_Driver_Decorator_Log($driver, $logger);
        $log->rename('INBOX/Test', 'FOO');
    }

}
