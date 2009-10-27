<?php
/**
 * Test the notification class.
 *
 * PHP version 5
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
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the notification class.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
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

class Horde_Notification_Class_NotificationTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        @include_once 'Log.php';
        if (!defined('PEAR_LOG_DEBUG')) {
            $this->markTestSkipped('The PEAR_LOG_DEBUG constant is not available!');
        }
    }

    public function testMethodSingletonAlwaysReturnsTheSameInstanceForTheSameStackName()
    {
        $notification1 = Horde_Notification::singleton('test');
        $notification2 = Horde_Notification::singleton('test');
        $this->assertSame($notification1, $notification2);
    }

    public function testMethodConstructHasPostconditionThatTheSessionStackGotInitializedAsArray()
    {
        $notification = Horde_Notification_Instance::newInstance('test');
        $this->assertEquals(array(), $_SESSION['test']);
    }

    public function testMethodAttachHasResultNotificationlistener()
    {
        $notification = Horde_Notification_Instance::newInstance('test');
        $this->assertType(
            'Horde_Notification_Listener_Audio',
            $notification->attach('audio')
        );
    }

    public function testMethodAttachHasResultNotificationlistenerClassAsSpecifiedInParameterClass()
    {
        $notification = Horde_Notification_Instance::newInstance('test');
        $this->assertType(
            'Horde_Notification_Listener_Audio',
            $notification->attach(
                'MyAudio', array(), 'Horde_Notification_Listener_Audio'
            )
        );
    }

    public function testMethodAttachHasPostconditionThatTheListenerGotInitializedWithTheProvidedParmeters()
    {
        $notification = Horde_Notification_Instance::newInstance('test');
        $listener = $notification->attach('dummy', array('test'));
        $this->assertEquals(array('test'), $listener->params);
    }

    public function testMethodAttachHasPostconditionThatTheListenerStackGotInitializedAsArray()
    {
        $notification = Horde_Notification_Instance::newInstance('test');
        $notification->attach('audio');
        $this->assertEquals(array(), $_SESSION['test']['audio']);
    }

    public function testMethodAttachThrowsExceptionIfTheListenerTypeIsUnkown()
    {
        $notification = Horde_Notification_Instance::newInstance('test');
        try {
            $notification->attach('MyAudio');
            $this->fail('No exception!');
        } catch (Horde_Exception $e) {
            $this->assertEquals(
                'Notification listener Horde_Notification_Listener_Myaudio not found.',
                $e->getMessage()
            );
        }
    }

    public function testMethodReplaceHasResultNotificationlistener()
    {
        $notification = Horde_Notification_Instance::newInstance('test');
        $notification->attach(
            'test', array(), 'Horde_Notification_Listener_Audio'
        );
        $this->assertType(
            'Horde_Notification_Listener_Dummy',
            $notification->replace(
                'test', array(), 'Horde_Notification_Listener_Dummy'
            )
        );
    }

    public function testMethodDetachHasPostconditionThatTheListenerStackGotUnset()
    {
        $notification = Horde_Notification_Instance::newInstance('test');
        $notification->attach('audio');
        $notification->detach('audio');
        $this->assertFalse(isset($_SESSION['test']['audio']));
    }

    public function testMethodDetachThrowsExceptionIfTheListenerIsUnset()
    {
        $notification = Horde_Notification_Instance::newInstance('test');
        try {
            $notification->detach('MyAudio');
            $this->fail('No exception!');
        } catch (Horde_Exception $e) {
            $this->assertEquals(
                'Notification listener myaudio not found.',
                $e->getMessage()
            );
        }
    }

    public function testMethodPushHasPostconditionThatTheEventGotSavedInAllAttachedListenerStacksHandlingTheEvent()
    {
        $notification = Horde_Notification_Instance::newInstance('test');
        $event = new Horde_Notification_Event('test');
        $flags= array();
        $notification->attach('audio');
        $notification->push('test', 'audio');
        $result = array_shift($_SESSION['test']['audio']);
        $this->assertEquals('Horde_Notification_Event', $result['class']);
        $this->assertEquals(serialize($event), $result['event']);
        $this->assertEquals(serialize($flags), $result['flags']);
        $this->assertEquals('audio', $result['type']);
    }

    public function testMethodPushHasPostconditionThatAnExceptionGetsMarkedAsTypeErrorIfTheTypeWasUnset()
    {
        $notification = Horde_Notification_Instance::newInstance('test');
        $notification->attach('dummy');
        $notification->push(new Exception('test'));
        $result = array_shift($_SESSION['test']['dummy']);
        $this->assertEquals('horde.error', $result['type']);
    }

    public function testMethodPushHasPostconditionThatEventsWithoutTypeGetMarkedAsTypeMessage()
    {
        $notification = Horde_Notification_Instance::newInstance('test');
        $notification->attach('dummy');
        $notification->push('test');
        $result = array_shift($_SESSION['test']['dummy']);
        $this->assertEquals('horde.message', $result['type']);
    }

    public function testMethodNotifyHasPostconditionThatAllListenersWereNotified()
    {
        $notification = Horde_Notification_Instance::newInstance('test');
        $event = new Horde_Notification_Event('test');
        $dummy = $notification->attach('dummy');
        $flags= array();
        $notification->push('test');
        $notification->notify();
        $result = array_shift($dummy->notifications);
        $this->assertEquals('Horde_Notification_Event', $result['class']);
        $this->assertEquals(serialize($event), $result['event']);
        $this->assertEquals(serialize($flags), $result['flags']);
        $this->assertEquals('horde.message', $result['type']);
    }

    public function testMethodNotifyHasPostconditionThatTheSpecifiedListenersWereNotified()
    {
        $notification = Horde_Notification_Instance::newInstance('test');
        $event = new Horde_Notification_Event('test');
        $dummy = $notification->attach('dummy');
        $flags= array();
        $notification->push('test');
        $notification->notify(array('listeners' => 'dummy'));
        $result = array_shift($dummy->notifications);
        $this->assertEquals(serialize($event), $result['event']);
    }

    public function testMethodCountHasResultTheTotalNumberOfEventsInTheStack()
    {
        $notification = Horde_Notification_Instance::newInstance('test');
        $notification->attach('audio');
        $notification->attach('dummy');
        $notification->push('test', 'audio');
        $this->assertEquals(2, $notification->count());
    }

    public function testMethodCountHasResultTheEventNumberForASpecificListenerIfTheListenerHasBeenSpecified()
    {
        $notification = Horde_Notification_Instance::newInstance('test');
        $notification->attach('audio');
        $notification->attach('dummy');
        $notification->push('test', 'audio');
        $this->assertEquals(1, $notification->count('audio'));
    }

}

class Horde_Notification_Instance extends Horde_Notification
{
    static public function newInstance($stack)
    {
        $instance = new Horde_Notification($stack);
        return $instance;
    }
}

class Horde_Notification_Listener_Dummy extends Horde_Notification_Listener
{
    public $params;

    public $notifications;

    public function __construct($params)
    {
        $this->params = $params;
        $this->_name = 'dummy';
        $this->_handles = array(
            'audio' => '',
            'horde.error' => '',
            'horde.message' => '',
        );
    }

    public function notify(&$messageStacks, $options = array())
    {
        $this->notifications = $messageStacks;
    }

    public function getMessage($message, $options = array())
    {
    }
}