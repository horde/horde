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
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the stop watch decorator for the backend drivers.
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
class Horde_Kolab_Storage_Unit_Driver_Decorator_TimerTest
extends Horde_Kolab_Storage_TestCase
{
    public function setUp()
    {
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
        $driver->getMailboxes();
        $this->assertLogCount(1);
    }

    public function testGetMailboxesFolderCount()
    {
        $driver = new Horde_Kolab_Storage_Driver_Decorator_Timer(
            $this->getTwoFolderMock(),
            new Horde_Support_Timer(),
            $this->getMockLogger()
        );
        $driver->getMailboxes();
        $this->assertLogRegExp('/REQUEST OUT IMAP:.*getMailboxes.*/');
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
}
