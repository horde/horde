<?php
/**
 * Test the basic listener class.
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
 * Test the basic listener class.
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
class Horde_Notification_Class_Notification_ListenerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!class_exists('PEAR_Error')) {
            $this->markTestSkipped('The PEAR_Error class is not available!');
        }
    }

    public function testMethodHandleHasResultBooleanFalse()
    {
        $listener = new Horde_Notification_Listener_Mock();
        $this->assertFalse($listener->handles('test'));
    }

    public function testMethodGetnameHasResultStringTheNameOfTheListener()
    {
        $listener = new Horde_Notification_Listener_Mock();
        $this->assertEquals('mock', $listener->getName());
    }

    public function testMethodGeteventHasResultNotificationeventTheUnserializedMessageEvent()
    {
        $listener = new Horde_Notification_Listener_Mock();
        $event = new Horde_Notification_Event('test');
        $message = array(
            'class' => 'Horde_Notification_Event',
            'event' => serialize($event)
        );
        $this->assertType(
            'Horde_Notification_Event',
            $listener->getEvent($message)
        );
    }

    public function testMethodGeteventHasResultNotificationeventTheUnserializedMessageEventIfTheClassInformationInTheMessageIsInvalid()
    {
        $listener = new Horde_Notification_Listener_Mock();
        $event = new Horde_Notification_Event('test');
        $message = array(
            'class' => 'Does_Not_Exist',
            'event' => serialize($event)
        );
        $this->assertType(
            'Horde_Notification_Event',
            $listener->getEvent($message)
        );
    }

    public function testMethodGeteventHasResultNotificationeventTheUnserializedMessageIfTheUnserializedObjectHasAnAttributeMessage()
    {
        $listener = new Horde_Notification_Listener_Mock();
        $event = new stdClass;
        $event->_message = 'test';
        $message = array(
            'class' => '',
            'event' => serialize($event)
        );
        $this->assertType(
            'Horde_Notification_Event',
            $listener->getEvent($message)
        );
    }

    public function testMethodGeteventHasResultPearerrorIfTheMessageCouldNotBeUnserialized()
    {
        $this->markTestIncomplete('Fails because of strict standards (PEAR::raiseError()).');
        $listener = new Horde_Notification_Listener_Mock();
        $message = array(
            'class' => '',
            'event' => 'unserializable'
        );
        $this->assertType(
            'PEAR_Error',
            $listener->getEvent($message)
        );
    }

    public function testMethodGeteventHasResultPearerrorIfTheMessageContainedAPearerror()
    {
        $listener = new Horde_Notification_Listener_Mock();
        $event = new PEAR_Error();
        $message = array(
            'class' => '',
            'event' => serialize($event)
        );
        $this->assertType(
            'PEAR_Error',
            $listener->getEvent($message)
        );
    }

    public function testMethodGeteventHasResultPearerrorWithHiddenAttributeMessageIfTheMessageContainedAPearerrorWithUserInfo()
    {
        $listener = new Horde_Notification_Listener_Mock();
        $event = new PEAR_Error('message', null, null, null, 'test');
        $message = array(
            'class' => '',
            'event' => serialize($event)
        );
        $result = $listener->getEvent($message);
        $this->assertEquals('message : test', $result->_message);
    }

    public function testMethodGeteventHasResultPearerrorWithHiddenAttributeMessageComposedOfArrayElementsIfTheMessageContainedAPearerrorWithAnArrayOfUserInfo()
    {
        $listener = new Horde_Notification_Listener_Mock();
        $user_info = array('1', '2');
        $event = new PEAR_Error('message', null, null, null, $user_info);
        $message = array(
            'class' => '',
            'event' => serialize($event)
        );
        $result = $listener->getEvent($message);
        $this->assertEquals('message : 1, 2', $result->_message);
    }

    public function testMethodGeteventHasResultPearerrorWithHiddenAttributeMessageComposedOfArrayElementsIfTheMessageContainedAPearerrorWithAnArrayOfUserInfoErrors()
    {
        $listener = new Horde_Notification_Listener_Mock();
        $user_info = array(new PEAR_Error('a'), new PEAR_Error('b'));
        $event = new PEAR_Error('message', null, null, null, $user_info);
        $message = array(
            'class' => '',
            'event' => serialize($event)
        );
        $result = $listener->getEvent($message);
        $this->assertEquals('message : a, b', $result->_message);
    }

    public function testMethodGeteventHasResultPearerrorWithHiddenAttributeMessageComposedOfArrayElementsIfTheMessageContainedAPearerrorWithAnArrayOfUserInfoObjectThatImplementGetmessageButNotTostring()
    {
        $listener = new Horde_Notification_Listener_Mock();
        $user_info = array(new Message('a'), new Message('b'));
        $event = new PEAR_Error('message', null, null, null, $user_info);
        $message = array(
            'class' => '',
            'event' => serialize($event)
        );
        $result = $listener->getEvent($message);
        $this->assertEquals('message : a, b', $result->_message);
    }

    public function testMethodGetflagsHasResultArrayEmptyIfTheGivenMessageHasNoFlags()
    {
        $listener = new Horde_Notification_Listener_Mock();
        $message = array();
        $this->assertEquals(array(), $listener->getFlags($message));
    }

    public function testMethodGetflagsHasResultArrayEmptyIfTheFlagsCouldNotBeUnserialized()
    {
        $listener = new Horde_Notification_Listener_Mock();
        $message = array('flags' => 'unserializable');
        $this->assertEquals(array(), $listener->getFlags($message));
    }

    public function testMethodGetflagsHasResultArrayMessageFlags()
    {
        $listener = new Horde_Notification_Listener_Mock();
        $message = array('flags' => serialize(array('a' => 'a')));
        $this->assertEquals(array('a' => 'a'), $listener->getFlags($message));
    }
}

class Horde_Notification_Listener_Mock extends Horde_Notification_Listener
{
    protected $_name = 'mock';

    public function notify(&$messageStacks, $options = array())
    {
    }

    public function getMessage($message, $options = array())
    {
    }
}

class Message
{
    private $_message;

    public function __construct($message)
    {
        $this->_message = $message;
    }

    public function getMessage()
    {
        return $this->_message;
    }
}
