<?php
/**
 * Test the status event class.
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
 * Test the status event class.
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
class Horde_Notification_Class_Notification_Event_StatusTest extends PHPUnit_Framework_TestCase
{
    public function testMethodTostringHasResultTheTextOfTheEvent()
    {
        if (!class_exists('Horde_Nls')) {
            $this->markTestSkipped('The Horde_Nls class is not available!');
        }
        $GLOBALS['registry']->setCharset('ISO-8859-1');
        $event = new Horde_Notification_Event_Status('<b>test</b>');
        $this->assertEquals('&lt;b&gt;test&lt;/b&gt;', (string) $event);
    }

    public function testMethodTostringHasUnescapedResultIfContentRawFlagIsSet()
    {
        $event = new Horde_Notification_Event_Status('<b>test</b>', null, array('content.raw'));
        $this->assertEquals('<b>test</b>', (string) $event);
    }

}
