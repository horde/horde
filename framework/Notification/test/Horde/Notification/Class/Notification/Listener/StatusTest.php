<?php
/**
 * Test the status listener class.
 *
 * @category Horde
 * @package  Notification
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Notification
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the status listener class.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Notification
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Notification
 */
class Horde_Notification_Class_Notification_Listener_StatusTest extends PHPUnit_Extensions_OutputTestCase
{
    public function setUp()
    {
        if (!class_exists('Horde_Perms')) {
            $this->markTestSkipped('The Horde_Perms package is not installed!');
        }

        /**
         * The listener pulls the registry from global scope to get the image
         * directory.
         */
        $GLOBALS['registry'] = $this->getMock(
            'Horde_Registry', array(), array(), '', false, false
        );
    }

    public function testMethodHandleHasResultBooleanTrueForHordeMessages()
    {
        $listener = new Horde_Notification_Listener_Status();
        $this->assertTrue($listener->handles('horde.message'));
    }

    public function testMethodGetnameHasResultStringStatus()
    {
        $listener = new Horde_Notification_Listener_Status();
        $this->assertEquals('status', $listener->getName());
    }

    public function testMethodNotifyHasNoOutputIfTheMessageStackIsEmpty()
    {
        $listener = new Horde_Notification_Listener_Status();
        $messages = array();
        $listener->notify($messages);
    }

    public function testMethodNotifyHasOutputEventMessagesEmbeddedInUlElement()
    {
        $this->markTestIncomplete('This is untestable without mocking half of the Horde framework.');
        $listener = new Horde_Notification_Listener_Status();
        $event = new Horde_Notification_Event('test');
        $messages = array($event);
        $this->expectOutputString(
            '<ul class="notices"><li>test</li></ul>'
        );
        $listener->notify($messages);
    }

    public function testMethodGetstackHasNoOutputIfNotifyWasAskedToAvoidDirectOutput()
    {
        $listener = new Horde_Notification_Listener_Status();
        $event = new Horde_Notification_Event('test');
        $event->flags = array('content.raw' => true);
        $messages = array($event);
        $listener->notify($messages, array('store' => true));
        $this->expectOutputString('');
    }

    public function testMethodGetstackHasOutputEventMessagesIfNotifyWasAskedToAvoidDirectOutput()
    {
        $listener = new Horde_Notification_Listener_Status();
        $event = new Horde_Notification_Event('test');
        $event->flags = array('content.raw' => true);
        $messages = array($event);
        $listener->notify($messages, array('store' => true));
        $this->assertEquals(
            $event,
            $listener->getStack()
        );
    }
}
