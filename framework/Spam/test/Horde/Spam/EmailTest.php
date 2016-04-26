<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl LGPL
 * @category   Horde
 * @package    Spam
 * @subpackage UnitTests
 */
class Horde_Spam_EmailTest extends Horde_Spam_TestBase
{
    public function setUp()
    {
        parent::setUp();
        list($this->spam_header, $this->spam_body) = explode("\n\n", $this->spam, 2);
        list($this->ham_header, $this->ham_body) = explode("\n\n", $this->ham, 2);
        $this->ham_header = str_replace(
            'Return-Path: <tbtf-approval@world.std.com>',
            'Return-Path: <report@example.com>',
            $this->ham_header
        );
    }

    public function testReportSpamRedirect()
    {
        $this->_testReportSpamSuccess($this->_getHordeSpam(true, 'redirect'));
        $this->_testReportSpamRedirect();
    }

    public function testReportSpamRedirectAsStream()
    {
        $this->_testReportSpamSuccess($this->_getHordeSpam(true, 'redirect'), true);
        $this->_testReportSpamRedirect();
    }

    protected function _testReportSpamRedirect()
    {
        $this->assertEquals(
            $this->spam_header,
            $this->transport->sentMessages[0]['header_text']
        );
        $this->assertEquals(
            $this->spam_body,
            $this->transport->sentMessages[0]['body']
        );
        $this->assertEquals(
            'report@example.com',
            $this->transport->sentMessages[0]['from']
        );
        $this->assertEquals(
            array('spam@example.com'),
            $this->transport->sentMessages[0]['recipients']
        );
    }

    public function testReportSpamDigest()
    {
        $this->_testReportSpamSuccess($this->_getHordeSpam(true, 'digest'));
        $this->_testReportSpamDigest();
    }

    public function testReportSpamDigestAsStream()
    {
        $this->_testReportSpamSuccess($this->_getHordeSpam(true, 'digest'), true);
        $this->_testReportSpamDigest();
    }

    protected function _testReportSpamDigest()
    {
        $this->assertStringStartsWith(
            'multipart/digest; boundary=',
            $this->transport->sentMessages[0]['headers']['Content-Type']
        );
        $this->assertEquals(
            'spam report from john',
            $this->transport->sentMessages[0]['headers']['Subject']
        );
        $this->assertEquals(
            'report@example.com',
            $this->transport->sentMessages[0]['headers']['From']
        );
        $this->assertEquals(
            'spam@example.com',
            $this->transport->sentMessages[0]['headers']['To']
        );
        $this->assertEquals(
            'report@example.com',
            $this->transport->sentMessages[0]['from']
        );
        $this->assertEquals(
            array('spam@example.com'),
            $this->transport->sentMessages[0]['recipients']
        );
        $this->assertEquals(
            1,
            preg_match(
                '/boundary="([^"]*)"/',
                $this->transport->sentMessages[0]['headers']['Content-Type'],
                $match
            )
        );
        $this->assertStringStartsWith(
            "This message is in MIME format.

--$match[1]

",
            $this->transport->sentMessages[0]['body']
        );
        $this->assertStringEndsWith(
            "
--$match[1]--
",
            $this->transport->sentMessages[0]['body']
        );
    }

    public function testReportHamRedirect()
    {
        $this->_testReportHamSuccess($this->_getHordeSpam(false, 'redirect'));
        $this->assertEquals(
            $this->ham_header,
            $this->transport->sentMessages[0]['header_text']
        );
        $this->assertEquals(
            $this->ham_body,
            $this->transport->sentMessages[0]['body']
        );
        $this->assertEquals(
            'report@example.com',
            $this->transport->sentMessages[0]['from']
        );
        $this->assertEquals(
            array('ham@example.com'),
            $this->transport->sentMessages[0]['recipients']
        );
    }

    public function testReportHamDigest()
    {
        $this->_testReportHamSuccess($this->_getHordeSpam(false, 'digest'));
        $this->assertStringStartsWith(
            'multipart/digest; boundary=',
            $this->transport->sentMessages[0]['headers']['Content-Type']
        );
        $this->assertEquals(
            'innocent report from john',
            $this->transport->sentMessages[0]['headers']['Subject']
        );
        $this->assertEquals(
            'report@example.com',
            $this->transport->sentMessages[0]['headers']['From']
        );
        $this->assertEquals(
            'ham@example.com',
            $this->transport->sentMessages[0]['headers']['To']
        );
        $this->assertEquals(
            'report@example.com',
            $this->transport->sentMessages[0]['from']
        );
        $this->assertEquals(
            array('ham@example.com'),
            $this->transport->sentMessages[0]['recipients']
        );
        $this->assertEquals(
            1,
            preg_match(
                '/boundary="([^"]*)"/',
                $this->transport->sentMessages[0]['headers']['Content-Type'],
                $match
            )
        );
        $this->assertStringStartsWith(
            "This message is in MIME format.

--$match[1]

",
            $this->transport->sentMessages[0]['body']
        );
        $this->assertStringEndsWith(
            "
--$match[1]--
",
            $this->transport->sentMessages[0]['body']
        );
    }

    protected function _getHordeSpam($spam, $format)
    {
        $this->transport = new Horde_Mail_Transport_Mock();
        $horde_spam = new Horde_Spam_Email(
            $this->transport,
            ($spam ? 'spam' : 'ham') . '@example.com',
            'report@example.com',
            'john',
            $format
        );
        $horde_spam->setLogger(
            new Horde_Log_Logger(new Horde_Log_Handler_Cli())
        );
        return $horde_spam;
    }
}
