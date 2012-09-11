<?php
/**
 * Tests the modification of existing Kolab mime messages.
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
 * Tests the modification of existing Kolab mime messages.
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
class Horde_Kolab_Storage_Unit_Data_Object_Message_ModifiedTest
extends PHPUnit_Framework_TestCase
{
    public function testStore()
    {
        $content = $this->getMock('Horde_Kolab_Storage_Data_Object_Content_Modified', array(), array(), '', false, false);
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $body = $this->getMock('Horde_Mime_Part');
        $body->expects($this->once())
            ->method('contentTypeMap')
            ->will($this->returnValue(array()));
        $original = $this->getMock('Horde_Mime_Part');
        $body->expects($this->once())
            ->method('getPart')
            ->will($this->returnValue($original));
        $imap = array(
            $this->getMock('Horde_Mime_Headers'),
            $body
        );
        $driver->expects($this->once())
            ->method('fetchComplete')
            ->with('INBOX', 1)
            ->will($this->returnValue($imap));
        $driver->expects($this->once())
            ->method('appendMessage')
            ->will($this->returnValue(true));
        $message = new Horde_Kolab_Storage_Data_Object_Message_Modified(
            $content, $driver, 'INBOX', 1
        );
        $message->store();
    }

    /**
     * @expectedException Horde_Kolab_Storage_Data_Exception
     */
    public function testStoreException()
    {
        $content = $this->getMock('Horde_Kolab_Storage_Data_Object_Content_Modified', array(), array(), '', false, false);
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $body = $this->getMock('Horde_Mime_Part');
        $body->expects($this->once())
            ->method('contentTypeMap')
            ->will($this->returnValue(array()));
        $original = $this->getMock('Horde_Mime_Part');
        $body->expects($this->once())
            ->method('getPart')
            ->will($this->returnValue($original));
        $imap = array(
            $this->getMock('Horde_Mime_Headers'),
            $body
        );
        $driver->expects($this->once())
            ->method('fetchComplete')
            ->with('INBOX', 1)
            ->will($this->returnValue($imap));
        $driver->expects($this->once())
            ->method('appendMessage')
            ->will($this->returnValue(false));
        $message = new Horde_Kolab_Storage_Data_Object_Message_Modified(
            $content, $driver, 'INBOX', 1
        );
        $message->store();
    }
}
