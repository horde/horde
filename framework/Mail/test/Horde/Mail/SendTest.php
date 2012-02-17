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
            'To: <test2@example.com>',
            'Subject: Test',
            'X-Test: Line 1\r\n\tLine 2\n\tLine 3\r\tLine 4',
            'X-Truncated-Header: ' . $body
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

}
