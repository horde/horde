<?php
/**
 * Test the message container.
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
 * Test the message container.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_Unit_Data_Object_ContainerTest
extends PHPUnit_Framework_TestCase
{
    public function testStore()
    {
        $driver = $this->getMock('Horde_Kolab_Storage_Driver');
        $driver->expects($this->once())
            ->method('appendMessage')
            ->with('INBOX', '<event/>')
            ->will($this->returnValue(1));
        $container = new Horde_Kolab_Storage_Data_Object_Container(
            $driver, 'INBOX'
        );
        $mime = $this->getMock('Horde_Mime_Part', array(), array(), '', false, false);
        $mime->expects($this->once())
            ->method('toString')
            ->with(
                array(
                    'canonical' => true,
                    'stream' => true,
                    'headers' => array('headers')
                )
            )->will($this->returnValue('<event/>'));
        $message = $this->getMock('Horde_Kolab_Storage_Data_Object_Message', array(), array(), '', false, false);
        $message->expects($this->once())
            ->method('create')
            ->will($this->returnValue($mime));
        $message->expects($this->once())
            ->method('createEnvelopeHeaders')
            ->will($this->returnValue(array('headers')));
        $this->assertEquals('1', $container->store($message));
    }
}