<?php
/**
 * Test the alarm notification handler class.
 *
 * @category Horde
 * @package  Notification
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Notification
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../../Autoload.php';

/**
 * Test the alarm notification handler class.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Notification
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Notification
 */

class Horde_Notification_Class_Notification_Handler_Decorator_AlarmTest
extends Horde_Test_Case
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
