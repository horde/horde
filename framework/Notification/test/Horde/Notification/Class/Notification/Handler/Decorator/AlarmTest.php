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
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
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
        $this->markTestIncomplete('Currently broken');
        if (!class_exists('Horde_Alarm')) {
            $this->markTestSkipped('The Horde_Alarm package is not installed!');
        }

        $this->alarm = $this->getMockForAbstractClass('Horde_Alarm');
        $this->alarm_handler = new Horde_Notification_Handler_Decorator_Alarm(
            $this->alarm, null
        );
    }

    public function testMethodNotifyHasPostconditionThatTheAlarmSystemGotNotifiedIfTheStatusListenerShouldBeNotified()
    {
        $this->alarm->expects($this->once())
            ->method('notify')
            ->with('');
        $this->alarm_handler->notify(array('listeners' => array('status')));
    }

}
