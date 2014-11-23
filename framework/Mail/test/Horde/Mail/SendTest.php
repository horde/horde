<?php
/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Mail
 * @subpackage UnitTests
 */

class Horde_Mail_SendTest extends PHPUnit_Framework_TestCase
{
    /* Test case for mixed EOLs. */
    public function testMixedEOLs()
    {
        $ob = new Horde_Mail_Transport_Mock();
        $ob->sep = "\n";

        $recipients = 'Test <test@example.com>';
        $body = "Foo\r\nBar\nBaz\rTest";
        $headers = array(
            'To' => '<test2@example.com>',
            'From' => '<foo@example.com>',
            'Subject' => 'Test',
            'X-Test' => 'Line 1\r\n\tLine 2\n\tLine 3\r\tLine 4',
            'X-Truncated-Header' => $body
        );

        $ob->send($recipients, $headers, $body);

        if (preg_match("/(?<=\r)\n/", $ob->sentMessages[0]['header_text'])) {
            $this->fail("Unexpected EOL in headers.");
        }

        if (preg_match("/(?<=\r)\n/", $ob->sentMessages[0]['body'])) {
            $this->fail("Unexpected EOL in body.");
        }

        $ob->sep = "\r\n";
        $ob->send($recipients, $headers, $body);

        if (preg_match("/(?<!\r)\n/", $ob->sentMessages[1]['header_text'])) {
            $this->fail("Unexpected EOL in headers.");
        }

        if (preg_match("/(?<!\r)\n/", $ob->sentMessages[1]['body'])) {
            $this->fail("Unexpected EOL in body.");
        }
    }

    public function testBug12116()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('Intl module is not available.');
        }
        $addr = new Horde_Mail_Rfc822_Address();
        $addr->personal = 'Aäb';
        $addr->mailbox = 'test';
        $addr->host = 'üexample.com';

        $ob = new Horde_Mail_Transport_Mock();
        $ob->send(
            array($addr),
            array(
                'Return-Path' => $addr
            ),
            'Foo'
        );

        $this->assertEquals(
            array('test@xn--example-m2a.com'),
            $ob->sentMessages[0]['recipients']
        );

        $this->assertEquals(
            'test@xn--example-m2a.com',
            $ob->sentMessages[0]['from']
        );
    }

    public function testMissingFrom()
    {
        $ob = new Horde_Mail_Transport_Mock();

        try {
            $ob->send(array('foo@example.com'), array(), 'Foo');
            $this->fail('Expected Horde_Mail_Exception.');
        } catch (Horde_Mail_Exception $e) {
        }
    }

}
