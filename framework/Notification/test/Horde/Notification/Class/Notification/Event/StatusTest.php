<?php
/**
 * Test the status event class.
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
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test the status event class.
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
class Horde_Notification_Class_Notification_Event_StatusTest extends Horde_Test_Case
{
    public function testMethodTostringHasResultTheTextOfTheEvent()
    {
        $event = new Horde_Notification_Event_Status('<b>test</b>');
        $event->charset = 'ISO-8859-1';
        $this->assertEquals('&lt;b&gt;test&lt;/b&gt;', (string) $event);
    }

    public function testMethodTostringHasUnescapedResultIfContentRawFlagIsSet()
    {
        $event = new Horde_Notification_Event_Status('<b>test</b>', null, array('content.raw'));
        $this->assertEquals('<b>test</b>', (string) $event);
    }

}
