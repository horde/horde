<?php
/**
 * Test the basic event class.
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
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the basic event class.
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
class Horde_Notification_Class_Notification_EventTest extends Horde_Test_Case
{
    public function testMethodConstructHasPostconditionThatTheGivenMessageWasSavedIfItWasNotNull()
    {
        $event = new Horde_Notification_Event('test');
        $this->assertEquals('test', $event->message);
    }

    public function testMethodGetmessageHasResultStringTheStoredMessage()
    {
        $event = new Horde_Notification_Event('');
        $event->message = 'test';
        $this->assertEquals('test', $event->message);
    }

    public function testMethodGetmessageHasResultStringEmptyIfNoMessageWasStored()
    {
        $event = new Horde_Notification_Event('');
        $this->assertEquals('', $event->message);
    }
}
