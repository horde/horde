<?php
/**
 * Test the javascript listener class.
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
 * Test the javascript listener class.
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
class Horde_Notification_Class_Notification_Listener_JavascriptTest extends PHPUnit_Extensions_OutputTestCase
{
    public function testMethodHandleHasResultBooleanTrueForjavascriptMessages()
    {
        $listener = new Horde_Notification_Listener_Javascript();
        $this->assertTrue($listener->handles('javascript'));
    }

    public function testMethodGetnameHasResultStringJavascript()
    {
        $listener = new Horde_Notification_Listener_Javascript();
        $this->assertEquals('javascript', $listener->getName());
    }

    public function testMethodNotifyHasNoOutputIfTheMessageStackIsEmpty()
    {
        $listener = new Horde_Notification_Listener_Javascript();
        $messages = array();
        $listener->notify($messages);
    }

    public function testMethodNotifyHasOutputEventMessageEmbeddedInScriptElement()
    {
        $listener = new Horde_Notification_Listener_Javascript();
        $event = new Horde_Notification_Event('test');
        $messages = array(
            array(
                'class' => 'Horde_Notification_Event',
                'event' => serialize($event),
                'type'  => 'javascript'
            )
        );
        $this->expectOutputString(
            '<script type="text/javascript">//<![CDATA['
            . "\n" . 'test' . "\n" . '//]]></script>' . "\n"
        );
        $listener->notify($messages);
    }

    public function testMethodNotifyHasOutputEventMessageNotEmbeddedIfEmbeddingIsDeactivated()
    {
        $listener = new Horde_Notification_Listener_Javascript();
        $event = new Horde_Notification_Event('test');
        $messages = array(
            array(
                'class' => 'Horde_Notification_Event',
                'event' => serialize($event),
                'type'  => 'javascript'
            )
        );
        $this->expectOutputString('test' . "\n");
        $listener->notify($messages, array('noscript' => true));
    }

    public function testMethodNotifyHasOutputJavaScriptFileLinkIfTheEventContainedSuchAFileLink()
    {
        $listener = new Horde_Notification_Listener_Javascript();
        $event = new Horde_Notification_Event('test');
        $messages = array(
            array(
                'class' => 'Horde_Notification_Event',
                'event' => serialize($event),
                'type'  => 'javascript-file'
            )
        );
        $this->expectOutputString(
            '<script type="text/javascript">//<![CDATA['
            . "\n" . '//]]></script>' . "\n" .
            '<script type="text/javascript" src="test"></script>' . "\n"
        );
        $listener->notify($messages);
    }
}
