<?php
/**
 * Test the basic event class.
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
 * Test the basic event class.
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
class Horde_Notification_Class_Notification_EventTest extends PHPUnit_Framework_TestCase
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
