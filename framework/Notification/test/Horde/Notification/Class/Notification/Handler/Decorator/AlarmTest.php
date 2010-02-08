<?php
/**
 * Test the alarm notification handler class.
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
require_once dirname(__FILE__) . '/../../../../Autoload.php';

/**
 * Test the alarm notification handler class.
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

class Horde_Notification_Class_Notification_Handler_Decorator_AlarmTest
extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!class_exists('Horde_Alarm')) {
            $this->markTestSkipped('The Horde_Alarm package is not installed!');
        }

        $this->handler = $this->getMock(
            'Horde_Notification_Handler_Base', array(), array(), '', false, false
        );
        $this->alarm   = $this->getMock('Horde_Alarm');
        $this->alarm_handler = new Horde_Notification_Handler_Decorator_Alarm(
            $this->handler, $this->alarm
        );
    }

    public function testMethodNotifyHasPostconditionThatTheAlarmSystemGotNotifiedIfTheStatusListenerShouldBeNotified()
    {
        $this->alarm->expects($this->once())
            ->method('notify')
            ->with('');
        $this->handler->expects($this->once())
            ->method('setNotificationListeners')
            ->with(array('listeners' => array('status')))
            ->will($this->returnValue(array('listeners' => array('status'))));
        $this->handler->expects($this->once())
            ->method('notifyListeners')
            ->with(array('listeners' => array('status')));
        $this->alarm_handler->notify(array('listeners' => array('status')));
    }

    public function testMethodAttachGetsDelegated()
    {
        $this->handler->expects($this->once())
            ->method('attach')
            ->with('listener', array(), 'class')
            ->will($this->returnValue('instance'));
        $this->assertEquals(
            'instance',
            $this->alarm_handler->attach('listener', array(), 'class')
        );
    }

    public function testMethodDetachGetsDelegated()
    {
        $this->handler->expects($this->once())
            ->method('detach')
            ->with('listener');
        $this->alarm_handler->detach('listener');
    }

    public function testMethodReplaceGetsDelegated()
    {
        $this->handler->expects($this->once())
            ->method('replace')
            ->with('listener', array(), 'class')
            ->will($this->returnValue('instance'));
        $this->assertEquals(
            'instance',
            $this->alarm_handler->replace('listener', array(), 'class')
        );
    }

    public function testMethodPushGetsDelegated()
    {
        $this->handler->expects($this->once())
            ->method('push')
            ->with('event', 'type', array());
        $this->alarm_handler->push('event', 'type', array());
    }

    public function testMethodNotifyGetsDelegated()
    {
        $this->handler->expects($this->once())
            ->method('setNotificationListeners')
            ->with(array('listeners' => array('test')))
            ->will($this->returnValue(array('listeners' => array('test'))));
        $this->handler->expects($this->once())
            ->method('notifyListeners')
            ->with(array('listeners' => array('test')));
        $this->alarm_handler->notify(array('listeners' => array('test')));
    }

    public function testMethodSetnotificationlistenersGetsDelegated()
    {
        $this->handler->expects($this->once())
            ->method('setNotificationListeners')
            ->with(array());
        $array = array();
        $this->alarm_handler->setNotificationListeners($array);
    }

    public function testMethodNotifylistenersGetsDelegated()
    {
        $this->handler->expects($this->once())
            ->method('notifyListeners')
            ->with(array());
        $this->alarm_handler->notifyListeners(array());
    }

    public function testMethodCountGetsDelegated()
    {
        $this->handler->expects($this->once())
            ->method('count')
            ->with('listener')
            ->will($this->returnValue(1));
        $this->assertEquals(1, $this->alarm_handler->count('listener'));
    }

}
