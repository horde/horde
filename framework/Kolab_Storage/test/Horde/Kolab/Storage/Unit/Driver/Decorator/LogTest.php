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
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the log decorator for the backend drivers.
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
class Horde_Kolab_Storage_Unit_Driver_Decorator_LogTest
extends Horde_Kolab_Storage_TestCase
{
    public function testGetMailboxesLogsEntry()
    {
        $driver = new Horde_Kolab_Storage_Driver_Decorator_Log(
            $this->getNullMock(),
            $this->getMockLogger()
        );
        $driver->getMailboxes();
        $this->assertLogCount(2);
    }

    public function testGetMailboxesFolderCount()
    {
        $driver = new Horde_Kolab_Storage_Driver_Decorator_Log(
            $this->getTwoFolderMock(),
            $this->getMockLogger()
        );
        $driver->getMailboxes();
        $this->assertLogContains('Driver "Horde_Kolab_Storage_Driver_Mock": List contained 2 folders.');
    }
}
