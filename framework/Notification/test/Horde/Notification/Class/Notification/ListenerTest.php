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

    public function testMethodHandleHasEventClassName()
    {
        $listener = new Horde_Notification_Listener_Mock();
        $this->assertEquals('Horde_Notification_Event', $listener->handles('mock'));
    }

    public function testMethodHandleHasEventClassNameIfItMatchesAsteriskExpression()
    {
        $listener = new Horde_Notification_Listener_Mock();
        $listener->addType('t*', 'Test_Event');
        $this->assertEquals('Test_Event', $listener->handles('test'));
    }

    public function testMethodGetnameHasResultStringTheNameOfTheListener()
    {
        $listener = new Horde_Notification_Listener_Mock();
        $this->assertEquals('mock', $listener->getName());
    }

}

class Horde_Notification_Listener_Mock extends Horde_Notification_Listener
{
    protected $_handles = array('mock' => 'Horde_Notification_Event');
    protected $_name = 'mock';

    public function notify($events, $options = array())
    {
    }
}
