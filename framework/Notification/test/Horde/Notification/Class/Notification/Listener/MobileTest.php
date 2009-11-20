<?php
/**
 * Test the mobile listener class.
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
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the mobile listener class.
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
class Horde_Notification_Class_Notification_Listener_MobileTest extends PHPUnit_Extensions_OutputTestCase
{
    public function setUp()
    {
        if (!class_exists('Horde_Perms')) {
            $this->markTestSkipped('The Horde_Perms package is not installed!');
        }

        if (!class_exists('Horde_Mobile')) {
            $this->markTestSkipped('The Horde_Mobile package is not installed!');
        }

        /**
         * The listener pulls the registry from global scope to get the image
         * directory.
         */
        $GLOBALS['registry'] = $this->getMock(
            'Horde_Registry', array(), array(), '', false, false
        );

        $this->mobile = $this->getMock(
            'Horde_Mobile', array(), array(), '', false, false
        );
    }

    public function testMethodHandleHasResultBooleanTrueForHordeMessages()
    {
        $listener = new Horde_Notification_Listener_Mobile();
        $this->assertTrue($listener->handles('horde.message'));
    }

    public function testMethodGetnameHasResultStringStatus()
    {
        $listener = new Horde_Notification_Listener_Mobile();
        $this->assertEquals('status', $listener->getName());
    }

    public function testMethodNotifyHasNoOutputIfTheMessageStackIsEmpty()
    {
        $listener = new Horde_Notification_Listener_Mobile();
        $messages = array();
        $listener->setMobileObject($this->mobile);
        $listener->notify($messages);
    }

    public function testMethodNotifyHasSameOutputAsTheStatusListenerIfNoMobileObjectWasSet()
    {
        $this->markTestIncomplete('This is untestable without mocking half of the Horde framework.');
        $listener = new Horde_Notification_Listener_Mobile();
        $event = new Horde_Notification_Event('test');
        $messages = array(
            array(
                'class' => 'Horde_Notification_Event',
                'event' => serialize($event),
                'type'  => 'horde.message'
            )
        );
        $this->expectOutputString(
            '<ul class="notices"><li>test</li></ul>'
        );
        $listener->notify($messages);
    }

    public function testMethodNotifyHasPostconditionThatTheMobileObjectReceivedTheNotifications()
    {
        $element = $this->getMock('Horde_Mobile_element');
        $this->mobile->expects($this->exactly(2))
            ->method('add')
            ->with(
                $this->logicalOr(
                    $this->logicalAnd(
                        $this->isInstanceOf('Horde_Mobile_Text'),
                        $this->attributeEqualTo('_text', 'MSG: test')
                    ),
                    $this->logicalAnd(
                        $this->isInstanceOf('Horde_Mobile_Text'),
                        $this->attributeEqualTo('_text', "\n")
                    )
                )
            )
            ->will($this->returnValue($element));
        $listener = new Horde_Notification_Listener_Mobile();
        $listener->setMobileObject($this->mobile);
        $event = new Horde_Notification_Event('test');
        $messages = array(
            array(
                'class' => 'Horde_Notification_Event',
                'event' => serialize($event),
                'type'  => 'horde.message',
            )
        );
        $listener->notify($messages);
    }

    public function testMethodGetmessageHasSameOutputAsTheStatusListenerIfNoMobileObjectWasSet()
    {
        $listener = new Horde_Notification_Listener_Mobile();
        $event = new Horde_Notification_Event('test');
        $flags = array('content.raw' => true);
        $message = array(
            'class' => 'Horde_Notification_Event',
            'event' => serialize($event),
            'type'  => 'horde.message',
            'flags' => serialize($flags)
        );
        $listener->getMessage($message, array('data' => true));
    }

}
