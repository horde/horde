<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Alarm
 * @subpackage UnitTests
 */

class Horde_Alarm_HandlerTest extends PHPUnit_Framework_TestCase
{
    protected static $alarm;
    protected static $storage;
    protected static $mail;

    public function setUp()
    {
        if (!class_exists('Horde_Notification')) {
            $this->markTestSkipped('Horde_Notification not installed');
            return;
        }
        if (!class_exists('Horde_Mail')) {
            $this->markTestSkipped('Horde_Mail not installed');
            return;
        }

        self::$alarm = new Horde_Alarm_Object();
        $now = time();
        $hash = array('id' => 'personalalarm',
                      'user' => 'john',
                      'start' => new Horde_Date($now),
                      'end' => new Horde_Date($now + 3600),
                      'methods' => array(),
                      'params' => array(),
                      'title' => 'This is a personal alarm.',
                      'text' => 'Action is required.');
        self::$alarm->set($hash);

        self::$storage = new Horde_Notification_Storage_Object();
        $notification = new Horde_Alarm_HandlerTest_NotificationFactory(self::$storage);
        $handler = new Horde_Alarm_Handler_Notify(array('notification' => $notification));
        self::$alarm->addHandler('notify', $handler);

        $handler = new Horde_Alarm_Handler_Desktop(array('js_notify' => array($this, 'desktopCallback'), 'icon' => 'test.png'));
        self::$alarm->addHandler('desktop', $handler);

        self::$mail = new Horde_Mail_Transport_Mock();
        $factory = new Horde_Alarm_HandlerTest_IdentityFactory();
        $handler = new Horde_Alarm_Handler_Mail(array('mail' => self::$mail, 'identity' => $factory, 'charset' => 'us-ascii'));
        self::$alarm->addHandler('mail', $handler);
    }

    public function testNotify()
    {
        $alarm = self::$alarm->get('personalalarm', 'john');
        $alarm['methods'] = array('notify');
        self::$alarm->set($alarm);
        self::$alarm->notify('john', false);

        $this->assertEquals(1, count(self::$storage->notifications['_unattached']));
        $this->assertEquals('This is a personal alarm.', self::$storage->notifications['_unattached'][0]->message);
        $this->assertEquals('horde.alarm', self::$storage->notifications['_unattached'][0]->type);
    }

    public function testMail()
    {
        $header =
'Subject: This is a personal alarm.
To: john@example.com
From: john@example.com
Auto-Submitted: auto-generated
X-Horde-Alarm: This is a personal alarm.
Message-ID: <%d.Horde.%s@%s>
User-Agent: Horde Application Framework 4
Date: %s, %d %s %s %d:%d:%d %s%d
Content-Type: text/plain; charset=UTF-8; format=flowed; DelSp=Yes
MIME-Version: 1.0';
        $body = "Action is required.\r\n";

        $alarm = self::$alarm->get('personalalarm', 'john');
        $alarm['methods'] = array('mail');
        self::$alarm->set($alarm);
        self::$alarm->notify('john', false);
        $last_sent = end(self::$mail->sentMessages);

        $this->assertStringMatchesFormat(
            $header,
            trim(str_replace("\r\n", "\n", $last_sent['header_text']))
        );
        $this->assertEquals($body, $last_sent['body']);

        self::$mail->sentMessages = array();
        self::$alarm->notify('john', false);
        $this->assertEquals(self::$mail->sentMessages, array());

        /* Test re-sending mails after changing the alarm. */
        self::$alarm->set(self::$alarm->get('personalalarm', 'john'));
        self::$alarm->notify('john', false);

        $last_sent = end(self::$mail->sentMessages);
        $this->assertStringMatchesFormat(
            $header,
            trim(str_replace("\r\n", "\n", $last_sent['header_text']))
        );
        $this->assertEquals($body, $last_sent['body']);
    }

    public function testDesktop()
    {
        $alarm = self::$alarm->get('personalalarm', 'john');
        $alarm['methods'] = array('desktop');
        self::$alarm->set($alarm);
        self::$alarm->notify('john', false);
    }

    public function desktopCallback($js)
    {
        $this->assertEquals(
            "if(window.webkitNotifications&&!window.webkitNotifications.checkPermission())(function(){var notify=window.webkitNotifications.createNotification('test.png','This is a personal alarm.','Action is required.');notify.show();(function(){notify.cancel()}).delay(5)})()",
            $js);
    }
}

class Horde_Alarm_HandlerTest_IdentityFactory
{
    public function create()
    {
        return new Horde_Alarm_HandlerTest_Identity();
    }
}

class Horde_Alarm_HandlerTest_Identity
{
    public function getDefaultFromAddress()
    {
        return 'john@example.com';
    }
}

class Horde_Alarm_HandlerTest_NotificationFactory
{
    private $storage;

    public function __construct($storage)
    {
        $this->storage = $storage;
    }

    public function create()
    {
        return new Horde_Notification_Handler($this->storage);
    }
}
