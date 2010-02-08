<?php
/**
 * Test the basic notification handler class.
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
 * Test the basic notification handler class.
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

class Horde_Notification_Class_Notification_Handler_BaseTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->storage = new Horde_Notification_Storage_Session('test');
        $this->handler = new Horde_Notification_Handler_Base($this->storage);
    }

    public function testMethodAttachHasResultNotificationlistener()
    {
        $this->assertType(
            'Horde_Notification_Listener_Audio',
            $this->handler->attach('audio')
        );
    }

    public function testMethodAttachHasResultNotificationlistenerTheSameListenerAsBeforeIfThisListenerHasAlreadyBeenAttached()
    {
        $listener = $this->handler->attach('audio');
        $this->assertSame($listener, $this->handler->attach('audio'));
    }

    public function testMethodAttachHasResultNotificationlistenerClassAsSpecifiedInParameterClass()
    {
        $this->assertType(
            'Horde_Notification_Listener_Audio',
            $this->handler->attach(
                'MyAudio', array(), 'Horde_Notification_Listener_Audio'
            )
        );
    }

    public function testMethodAttachHasPostconditionThatTheListenerGotInitializedWithTheProvidedParmeters()
    {
        $listener = $this->handler->attach('dummy', array('test'));
        $this->assertEquals(array('test'), $listener->params);
    }

    public function testMethodAttachHasPostconditionThatTheListenerStackGotInitializedAsArray()
    {
        $this->handler->attach('audio');
        $this->assertEquals(array(), $_SESSION['test']['audio']);
    }

    public function testMethodAttachThrowsExceptionIfTheListenerTypeIsUnkown()
    {
        try {
            $this->handler->attach('MyAudio');
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
        $this->handler->attach(
            'test', array(), 'Horde_Notification_Listener_Audio'
        );
        $this->assertType(
            'Horde_Notification_Listener_Dummy',
            $this->handler->replace(
                'test', array(), 'Horde_Notification_Listener_Dummy'
            )
        );
    }

    public function testMethodDetachHasPostconditionThatTheListenerStackGotUnset()
    {
        $this->handler->attach('audio');
        $this->handler->detach('audio');
        $this->assertFalse(isset($_SESSION['test']['audio']));
    }

    public function testMethodDetachThrowsExceptionIfTheListenerIsUnset()
    {
        try {
            $this->handler->detach('MyAudio');
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
        $event = new Horde_Notification_Event('test');
        $flags= array();
        $this->handler->attach('audio');
        $this->handler->push('test', 'audio');
        $result = array_shift($_SESSION['test']['audio']);
        $this->assertEquals('Horde_Notification_Event', $result['class']);
        $this->assertEquals(serialize($event), $result['event']);
        $this->assertEquals(serialize($flags), $result['flags']);
        $this->assertEquals('audio', $result['type']);
    }

    public function testMethodPushHasPostconditionThatAnExceptionGetsMarkedAsTypeErrorIfTheTypeWasUnset()
    {
        $this->handler->attach('dummy');
        $this->handler->push(new Exception('test'));
        $result = array_shift($_SESSION['test']['dummy']);
        $this->assertEquals('horde.error', $result['type']);
    }

    public function testMethodPushHasPostconditionThatEventsWithoutTypeGetMarkedAsTypeMessage()
    {
        $this->handler->attach('dummy');
        $this->handler->push('test');
        $result = array_shift($_SESSION['test']['dummy']);
        $this->assertEquals('horde.message', $result['type']);
    }

    public function testMethodNotifyHasPostconditionThatAllListenersWereNotified()
    {
        $event = new Horde_Notification_Event('test');
        $dummy = $this->handler->attach('dummy');
        $flags= array();
        $this->handler->push('test');
        $this->handler->notify();
        $result = array_shift($dummy->notifications);
        $this->assertEquals('Horde_Notification_Event', $result['class']);
        $this->assertEquals(serialize($event), $result['event']);
        $this->assertEquals(serialize($flags), $result['flags']);
        $this->assertEquals('horde.message', $result['type']);
    }

    public function testMethodNotifyHasPostconditionThatTheSpecifiedListenersWereNotified()
    {
        $event = new Horde_Notification_Event('test');
        $dummy = $this->handler->attach('dummy');
        $flags= array();
        $this->handler->push('test');
        $this->handler->notify(array('listeners' => 'dummy'));
        $result = array_shift($dummy->notifications);
        $this->assertEquals(serialize($event), $result['event']);
    }

    public function testMethodCountHasResultTheTotalNumberOfEventsInTheStack()
    {
        $this->handler->attach('audio');
        $this->handler->attach('dummy');
        $this->handler->push('test', 'audio');
        $this->assertEquals(2, $this->handler->count());
    }

    public function testMethodCountHasResultTheEventNumberForASpecificListenerIfTheListenerHasBeenSpecified()
    {
        $this->handler->attach('audio');
        $this->handler->attach('dummy');
        $this->handler->push('test', 'audio');
        $this->assertEquals(1, $this->handler->count('audio'));
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
