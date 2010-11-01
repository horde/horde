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
require_once dirname(__FILE__) . '/../../Autoload.php';

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

class Horde_Notification_Class_Notification_HandlerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->storage = new Horde_Notification_Storage_Session('test');
        $this->handler = new Horde_Notification_Handler($this->storage);
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

    public function testMethodAttachThrowsExceptionIfTheListenerTypeIsUnknown()
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
                'Notification listener MyAudio not found.',
                $e->getMessage()
            );
        }
    }

    public function testMethodClearHasPostconditionThatTheStorageOfTheSpecifiedListenerWasCleared()
    {
        $storage = $this->getMock('Horde_Notification_Storage_Interface');
        $storage->expects($this->once())
            ->method('clear')
            ->with('dummy');
        $handler = new Horde_Notification_Handler($storage);
        $handler->attach('dummy');
        $handler->clear('dummy');
    }

    public function testMethodClearHasPostconditionThatAllUnattachedEventsHaveBeenClearedFromStorageIfNoListenerWasSpecified()
    {
        $storage = $this->getMock('Horde_Notification_Storage_Interface');
        $storage->expects($this->once())
            ->method('clear')
            ->with('_unattached');
        $handler = new Horde_Notification_Handler($storage);
        $handler->clear();
    }

    public function testMethodGetHasResultNullIfTheSpecifiedListenerIsNotAttached()
    {
        $this->assertNull($this->handler->get('not attached'));
    }

    public function testMethodAddtypeHasPostconditionThatTheSpecifiedListenerHandlesTheGivenMessageType()
    {
        $this->handler->attach('dummy');
        $this->handler->addType('dummy', 'newtype', 'NewType');
        $this->assertEquals('NewType', $this->handler->getListener('dummy')->handles('newtype'));
    }

    public function testMethodAdddecoratorHasPostconditionThatTheGivenDecoratorWasAddedToTheHandlerAndReceivesPushCalls()
    {
        $decorator = $this->getMock('Horde_Notification_Handler_Decorator_Base');
        $decorator->expects($this->once())
            ->method('push')
            ->with($this->isInstanceOf('Horde_Notification_Event'));
        $event = new Horde_Notification_Event('test');
        $this->handler->attach('audio');
        $this->handler->addDecorator($decorator);
        $this->handler->push($event, 'audio');
    }

    public function testMethodAdddecoratorHasPostconditionThatTheGivenDecoratorWasAddedToTheHandlerAndReceivesNotifyCalls()
    {
        $decorator = $this->getMock('Horde_Notification_Handler_Decorator_Base');
        $decorator->expects($this->once())
            ->method('notify');
        $this->handler->attach('audio');
        $this->handler->addDecorator($decorator);
        $this->handler->notify();
    }

    public function testMethodPushHasPostconditionThatTheEventGotSavedInAllAttachedListenerStacksHandlingTheEvent()
    {
        $event = new Horde_Notification_Event('test');
        $this->handler->attach('audio');
        $this->handler->push('test', 'audio', array(), array('immediate' => true));
        $result = array_shift($_SESSION['test']['audio']);
        $this->assertNotNull($result);
        $this->assertType('Horde_Notification_Event', $result);
        $this->assertEquals(array(), $result->flags);
        $this->assertEquals('audio', $result->type);
    }

    public function testMethodPushHasPostconditionThatAnExceptionGetsMarkedAsTypeStatusIfTheTypeWasUnset()
    {
        $this->handler->attach('dummy');
        $this->handler->push(new Exception('test'), null, array(), array('immediate' => true));
        $result = array_shift($_SESSION['test']['dummy']);
        $this->assertNotNull($result);
        $this->assertType('Horde_Notification_Event', $result);
        $this->assertEquals(array(), $result->flags);
        $this->assertEquals('status', $result->type);
    }

    public function testMethodPushHasPostconditionThatEventsWithoutTypeGetMarkedAsTypeStatus()
    {
        $this->handler->attach('dummy');
        $this->handler->push('test', null, array(), array('immediate' => true));
        $result = array_shift($_SESSION['test']['dummy']);
        $this->assertNotNull($result);
        $this->assertType('Horde_Notification_Event', $result);
        $this->assertEquals(array(), $result->flags);
        $this->assertEquals('status', $result->type);
    }

    public function testMethodNotifyHasPostconditionThatAllListenersWereNotified()
    {
        $dummy = $this->handler->attach('dummy');
        $this->handler->push('test', 'dummy');
        $this->handler->notify();
        $result = array_shift($dummy->events);
        $this->assertNotNull($result);
        $this->assertType('Horde_Notification_Event', $result);
        $this->assertEquals(array(), $result->flags);
        $this->assertEquals('dummy', $result->type);
    }

    public function testMethodNotifyHasPostconditionThatTheSpecifiedListenersWereNotified()
    {
        $dummy = $this->handler->attach('dummy');
        $this->handler->push('test', 'dummy');
        $this->handler->notify(array('listeners' => 'dummy'));
        $result = array_shift($dummy->events);
        $this->assertNotNull($result);
        $this->assertType('Horde_Notification_Event', $result);
        $this->assertEquals(array(), $result->flags);
        $this->assertEquals('dummy', $result->type);
    }

    public function testMethodCountHasResultTheTotalNumberOfEventsInTheStack()
    {
        $this->handler->attach('audio');
        $this->handler->attach('dummy');
        $this->handler->push('test', 'audio');
        $this->handler->push('test', 'dummy');
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
    public $events;
    public $params;

    public function __construct($params)
    {
        $this->params = $params;
        $this->_name = 'dummy';
        $this->_handles = array(
            'dummy' => 'Horde_Notification_Event',
            'status' => 'Horde_Notification_Event'
        );
    }

    public function notify($events, $options = array())
    {
        $this->events = $events;
    }

}
