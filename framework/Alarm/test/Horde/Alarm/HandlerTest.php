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

    public function setUp()
    {
        self::$alarm = Horde_Alarm::factory('Object');
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
    }

    public function testNotify()
    {
        if (!class_exists('Horde_Notification')) {
            $this->markTestSkipped('Horde_Notification not installed');
            return;
        }
        $alarm = self::$alarm->get('personalalarm', 'john');
        $alarm['methods'] = array('notify');
        self::$alarm->set($alarm);
        $storage = new Horde_Notification_Storage_Object();
        $handler = new Horde_Alarm_Handler_Notify(array('notification' => new Horde_Notification_Handler($storage)));
        self::$alarm->addHandler('notify', $handler);
        self::$alarm->notify('john', false);

        $this->assertEquals(1, count($storage->notifications['_unattached']));
        $this->assertEquals('This is a personal alarm.', $storage->notifications['_unattached'][0]->message);
        $this->assertEquals('horde.alarm', $storage->notifications['_unattached'][0]->type);
    }

    public function testMail()
    {
        if (!class_exists('Mail')) {
            $this->markTestSkipped('Mail not installed');
            return;
        }
        $alarm = self::$alarm->get('personalalarm', 'john');
        $alarm['methods'] = array('mail');
        self::$alarm->set($alarm);
        $mail = new Horde_Alarm_HandlerTest_Mail();
        $factory = new Horde_Alarm_HandlerTest_IdentityFactory();
        $handler = new Horde_Alarm_Handler_Mail(array('mail' => $mail, 'identity' => $factory, 'charset' => 'us-ascii'));
        self::$alarm->addHandler('mail', $handler);
        self::$alarm->notify('john', false);
        $regexp = <<<EOR
Subject: This is a personal alarm\.
To: john@example\.com
From: john@example\.com
Auto-Submitted: auto-generated
X-Horde-Alarm: This is a personal alarm\.
Message-ID: <\d{14}\.Horde\.\w+@\w+>
User-Agent: Horde Application Framework 4
Date: \w{3}, \d\d \w{3} \d{4} \d\d:\d\d:\d\d [+-]\d{4}
Content-Type: text\/plain; charset=us-ascii; format=flowed; DelSp=Yes
MIME-Version: 1\.0

Action is required\.

EOR;

        $this->assertRegExp('/' . trim(str_replace("\r\n", "\n", $regexp)) . '/', trim(str_replace("\r\n", "\n", $mail->sentOutput)));
        $mail->sentOutput = null;
        self::$alarm->notify('john', false);
        $this->assertNull($mail->sentOutput);
    }
}

class Horde_Alarm_HandlerTest_IdentityFactory
{
    public function getIdentity()
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

class Horde_Alarm_HandlerTest_Mail extends Mail
{
    public $sentOutput;

    public function send($recipients, $headers, $body)
    {
        list(, $textHeaders) = Mail::prepareHeaders($headers);
        $this->sentOutput = $textHeaders . "\n\n" . $body;
    }
}
