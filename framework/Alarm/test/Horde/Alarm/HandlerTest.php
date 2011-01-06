<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Horde_Alarm
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
        $notification = new Horde_Notification_Handler(self::$storage);
        $handler = new Horde_Alarm_Handler_Notify(array('notification' => $notification));
        self::$alarm->addHandler('notify', $handler);

        $handler = new Horde_Alarm_Handler_Desktop(array('notification' => $notification, 'icon' => 'test.png'));
        self::$alarm->addHandler('desktop', $handler);

        self::$mail = Horde_Mail::factory('Mock');
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
        $body = "Action is required.\n";

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
