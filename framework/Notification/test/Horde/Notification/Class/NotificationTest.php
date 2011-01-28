<?php
/**
 * Test the notification class.
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
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the notification class.
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

class Horde_Notification_Class_NotificationTest extends PHPUnit_Framework_TestCase
{
    public function testMethodSingletonReturnsAlwaysTheSameInstanceForTheSameStackName()
    {
        $notification1 = Horde_Notification::singleton('test');
        $notification2 = Horde_Notification::singleton('test');
        $this->assertSame($notification1, $notification2);
    }

    public function testMethodConstructHasPostconditionThatTheSessionStackGotInitializedAsArray()
    {
        $notification = Horde_Notification_Instance::newInstance('test');
        $this->assertEquals(array(), $_SESSION['test']);
    }
}

class Horde_Notification_Instance extends Horde_Notification
{
    static public function newInstance($stack)
    {
        $storage = new Horde_Notification_Storage_Session($stack);
        return new Horde_Notification_Handler($storage);
    }
}
