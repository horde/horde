<?php
/**
 * Test the stop watch decorator for the backend drivers.
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
 * Test the stop watch decorator for the backend drivers.
 *
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_Unit_Driver_Decorator_TimerTest
extends Horde_Kolab_Storage_TestCase
{
    public function setUp()
    {
        parent::setUp();
        if (!class_exists('Horde_Support_Timer')) {
            $this->markTestSkipped('The "Horde_Support" package seems to be missing!');
        }
    }

    public function testGetMailboxesLogsEntry()
    {
        $driver = new Horde_Kolab_Storage_Driver_Decorator_Timer(
            $this->getNullMock(),
            new Horde_Support_Timer(),
            $this->getMockLogger()
        );
        $driver->listFolders();
        $this->assertLogCount(1);
    }

    public function testGetMailboxesFolderCount()
    {
        $driver = new Horde_Kolab_Storage_Driver_Decorator_Timer(
            $this->getTwoFolderMock(),
            new Horde_Support_Timer(),
            $this->getMockLogger()
        );
        $driver->listFolders();
        $this->assertLogRegExp('/REQUEST OUT IMAP:.*listFolders.*/');
    }

    public function testListAnnotationLogsEntry()
    {
        $driver = new Horde_Kolab_Storage_Driver_Decorator_Timer(
            $this->getNullMock(),
            new Horde_Support_Timer(),
            $this->getMockLogger()
        );
        $driver->listAnnotation('/shared/vendor/kolab/folder-type');
        $this->assertLogCount(1);
    }

    public function testListAnnotationFolderCount()
    {
        $driver = new Horde_Kolab_Storage_Driver_Decorator_Timer(
            $this->getTwoFolderMock(),
            new Horde_Support_Timer(),
            $this->getMockLogger()
        );
        $driver->listAnnotation('/shared/vendor/kolab/folder-type');
        $this->assertLogRegExp('/REQUEST OUT IMAP:.*listAnnotation.*/');
    }

    public function testGetNamespaceLogsEntry()
    {
        $driver = new Horde_Kolab_Storage_Driver_Decorator_Timer(
            $this->getTwoFolderMock(),
            new Horde_Support_Timer(),
            $this->getMockLogger()
        );
        $driver->getNamespace();
        $this->assertLogRegExp('/REQUEST OUT IMAP:.*getNamespace.*/');
    }

    public function testGetNamespaceType()
    {
        $driver = new Horde_Kolab_Storage_Driver_Decorator_Timer(
            $this->getTwoFolderMock(),
            new Horde_Support_Timer(),
            $this->getMockLogger()
        );
        $this->assertInstanceOf(
            'Horde_Kolab_Storage_Folder_Namespace',
            $driver->getNamespace()
        );
    }

    public function testCreateLogsEntry()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $driver->expects($this->once())
            ->method('create')
            ->with('a');
        $logger = new Horde_Kolab_Storage_Driver_Decorator_Timer(
            $driver,
            new Horde_Support_Timer(),
            $this->getMockLogger()
        );
        $logger->create('a');
        $this->assertLogRegexp('/REQUEST OUT IMAP:.*createFolder.*/');
    }

    public function testSetAclLogsEntry()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $driver->expects($this->once())
            ->method('setAcl')
            ->with('a', 'b', 'c');
        $logger = new Horde_Kolab_Storage_Driver_Decorator_Timer(
            $driver,
            new Horde_Support_Timer(),
            $this->getMockLogger()
        );
        $logger->setAcl('a', 'b', 'c');
        $this->assertLogRegexp('/REQUEST OUT IMAP:.*setAcl.*/');
    }

    public function testDeleteAclLogsEntry()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $driver->expects($this->once())
            ->method('deleteAcl')
            ->with('a', 'b');
        $logger = new Horde_Kolab_Storage_Driver_Decorator_Timer(
            $driver,
            new Horde_Support_Timer(),
            $this->getMockLogger()
        );
        $logger->deleteAcl('a', 'b');
        $this->assertLogRegexp('/REQUEST OUT IMAP:.*deleteAcl.*/');
    }
}
