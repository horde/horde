<?php
/**
 * Test the logging notification handler class.
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
 * Test the logging notification handler class.
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

class Horde_Notification_Class_Notification_Handler_Decorator_LogTest
extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!class_exists('Horde_Log_Logger')) {
            $this->markTestSkipped('The Horde_Log package is not installed!');
        }

        $this->handler = $this->getMock(
            'Horde_Notification_Handler_Base', array(), array(), '', false, false
        );
        $this->logger   = $this->getMock('Horde_Log_Logger');
        $this->logged_handler = new Horde_Notification_Handler_Decorator_Log(
            $this->handler, $this->logger
        );
    }

    public function testMethodPushHasPostconditionThattheEventGotLoggedIfTheEventWasAnError()
    {
        $exception = new Exception('test');
        $this->logger->expects($this->once())
            ->method('__call')
            ->with('debug', $this->isType('array'));
        $this->handler->expects($this->once())
            ->method('push')
            ->with($exception);
        $this->logged_handler->push($exception);
    }

    public function testMethodAttachGetsDelegated()
    {
        $this->handler->expects($this->once())
            ->method('attach')
            ->with('listener', array(), 'class')
            ->will($this->returnValue('instance'));
        $this->assertEquals(
            'instance',
            $this->logged_handler->attach('listener', array(), 'class')
        );
    }

    public function testMethodDetachGetsDelegated()
    {
        $this->handler->expects($this->once())
            ->method('detach')
            ->with('listener');
        $this->logged_handler->detach('listener');
    }

    public function testMethodReplaceGetsDelegated()
    {
        $this->handler->expects($this->once())
            ->method('replace')
            ->with('listener', array(), 'class')
            ->will($this->returnValue('instance'));
        $this->assertEquals(
            'instance',
            $this->logged_handler->replace('listener', array(), 'class')
        );
    }

    public function testMethodPushGetsDelegated()
    {
        $this->handler->expects($this->once())
            ->method('push')
            ->with('event', 'type', array());
        $this->logged_handler->push('event', 'type', array());
    }

    public function testMethodNotifyGetsDelegated()
    {
        $this->handler->expects($this->once())
            ->method('notify')
            ->with(array('listeners' => array('test')));
        $this->logged_handler->notify(array('listeners' => array('test')));
    }

    public function testMethodSetnotificationlistenersGetsDelegated()
    {
        $this->handler->expects($this->once())
            ->method('setNotificationListeners')
            ->with(array());
        $array = array();
        $this->logged_handler->setNotificationListeners($array);
    }

    public function testMethodNotifylistenersGetsDelegated()
    {
        $this->handler->expects($this->once())
            ->method('notifyListeners')
            ->with(array());
        $this->logged_handler->notifyListeners(array());
    }

    public function testMethodCountGetsDelegated()
    {
        $this->handler->expects($this->once())
            ->method('count')
            ->with('listener')
            ->will($this->returnValue(1));
        $this->assertEquals(1, $this->logged_handler->count('listener'));
    }

}
