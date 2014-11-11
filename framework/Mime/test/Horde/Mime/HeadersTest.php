<?php
/**
 * Tests for the Horde_Mime_Headers class.
 *
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */

/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */
class Horde_Mime_HeadersTest extends PHPUnit_Framework_TestCase
{
    public function testSerialize()
    {
        $hdrs = new Horde_Mime_Headers();
        $hdrs->addHeader('Subject', 'My Subject');
        $hdrs->addHeader('To', 'recipient@example.com');
        $hdrs->addHeader('Cc', 'null@example.com');
        $hdrs->addHeader('Bcc', 'invisible@example.com');
        $hdrs->addHeader('From', 'sender@example.com');

        $stored = serialize($hdrs);
        $hdrs2 = unserialize($stored);

        $this->assertEquals(
            'null@example.com',
            $hdrs2->getValue('cc')
        );
    }

    public function testHeaderDecode()
    {
        $hdrs = new Horde_Mime_Headers();
        $hdrs->addHeader(
            'Test',
            '=?iso-8859-15?b?VmVyc2nzbg==?=',
            array(
                'decode' => true
            )
        );

        $this->assertEquals(
            'Versión',
            $hdrs->getValue('Test')
        );
    }

    public function testHeaderAutoDetectCharset()
    {
        $hdrs = Horde_Mime_Headers::parseHeaders(
            // This string is in Windows-1252
            'Test: ' . base64_decode('UnVubmVyc5IgQWxlcnQh=')
        );

        $this->assertEquals(
            'Runners’ Alert!',
            $hdrs->getValue('Test')
        );
    }

    public function testHeaderCharsetConversion()
    {
        $hdrs = new Horde_Mime_Headers();
        $hdrs->addHeader('To', 'Empfänger <recipient@example.com>');

        $hdr_array = $hdrs->toArray(array(
            'charset' => 'iso-8859-1'
        ));

        $this->assertEquals(
            '=?iso-8859-1?b?RW1wZuRuZ2Vy?= <recipient@example.com>',
            $hdr_array['To']
        );
    }

    public function testMultipleContentType()
    {
        $hdrs = Horde_Mime_Headers::parseHeaders(
            "Content-Type: multipart/mixed\n" .
            "Content-Type: multipart/mixed\n"
        );

        $this->assertInternalType(
            'string',
            $hdrs->getValue('content-type', Horde_Mime_Headers::VALUE_BASE)
        );
    }

    public function testMultivalueHeaders()
    {
        $hdrs = Horde_Mime_Headers::parseHeaders(
"To: recipient1@example.com, recipient2@example.com"
        );
        $this->assertEquals(
            'recipient1@example.com, recipient2@example.com',
            $hdrs->getValue('to')
        );

        $hdrs = Horde_Mime_Headers::parseHeaders(
"To: recipient1@example.com
To: recipient2@example.com"
        );
        $this->assertEquals(
            'recipient1@example.com, recipient2@example.com',
            $hdrs->getValue('to')
        );
    }

    public function testAddHeaderWithGroup()
    {
        $email = 'Test: foo@example.com, bar@example.com;';

        $rfc822 = new Horde_Mail_Rfc822();
        $ob = $rfc822->parseAddressList($email);

        $hdrs = new Horde_Mime_Headers();
        $hdrs->addHeader('To', $ob);

        $this->assertEquals(
            $email,
            $hdrs->getValue('to')
        );
    }

    public function testUnencodedMimeHeader()
    {
        // The header is base64 encoded to preserve charset data.
        $hdr = 'RnJvbTogwqkgVklBR1JBIMKuIE9mZmljaWFsIFNpdGUgPGZvb0BleGFtcGxlLmNvbT4=';
        $hdrs = Horde_Mime_Headers::parseHeaders(base64_decode($hdr));
        $this->assertEquals(
            '© VIAGRA ® Official Site <foo@example.com>',
            $hdrs->getValue('from')
        );
    }

    public function testParseContentDispositionHeaderWithUtf8Data()
    {
        $msg = file_get_contents(__DIR__ . '/fixtures/sample_msg_eai.txt');
        $hdrs = Horde_Mime_Headers::parseHeaders($msg);

        $cd_params = $hdrs->getValue(
            'content-disposition',
            $hdrs::VALUE_PARAMS
        );

        $this->assertEquals(
            'blåbærsyltetøy',
            $cd_params['filename']
        );
    }

    public function testCaseInsensitiveContentParameters()
    {
        $hdr = 'Content-Type: multipart/mixed; BOUNDARY="foo"';
        $hdrs = Horde_Mime_Headers::parseHeaders($hdr);

        $c_params =  $hdrs->getValue(
            'Content-Type',
            $hdrs::VALUE_PARAMS
        );
        $this->assertEquals(
            'foo',
            $c_params['boundary']
        );
    }

    public function testParseEaiAddresses()
    {
        /* Simple message. */
        $msg = file_get_contents(__DIR__ . '/fixtures/sample_msg_eai_2.txt');
        $hdrs = Horde_Mime_Headers::parseHeaders($msg);

        $this->assertEquals(
            'Jøran Øygårdvær <jøran@example.com>',
            $hdrs->getValue('from')
        );

        /* Message with EAI addresses in 2 fields, and another header that
         * contains a string (that looks like an address). */
        $msg = file_get_contents(__DIR__ . '/fixtures/sample_msg_eai_4.txt');
        $hdrs = Horde_Mime_Headers::parseHeaders($msg);

        $this->assertEquals(
            'Jøran Øygårdvær <jøran@example.com>',
            $hdrs->getValue('from')
        );
        $this->assertEquals(
            'Jøran Øygårdvær <jøran@example.com>',
            $hdrs->getValue('cc')
        );
        $this->assertEquals(
            'Jøran Øygårdvær <jøran@example.com>',
            $hdrs->getValue('signed-off-by')
        );
    }

    public function testUndisclosedHeaderParsing()
    {
        $hdrs = new Horde_Mime_Headers();
        $hdrs->addHeader('To', 'undisclosed-recipients');
        $this->assertEquals(
            '',
            $hdrs->getValue('To')
        );

        $hdrs = new Horde_Mime_Headers();
        $hdrs->addHeader('To', 'undisclosed-recipients:');
        $this->assertEquals(
            '',
            $hdrs->getValue('To')
        );

        $hdrs = new Horde_Mime_Headers();
        $hdrs->addHeader('To', 'undisclosed-recipients:;');
        $this->assertEquals(
            '',
            $hdrs->getValue('To')
        );
    }

    public function testMultipleToAddresses()
    {
        $msg = file_get_contents(__DIR__ . '/fixtures/multiple_to.txt');
        $hdrs = Horde_Mime_Headers::parseHeaders($msg);

        $this->assertNotEmpty($hdrs->getValue('To'));
    }

    public function testBug12189()
    {
        $msg = file_get_contents(__DIR__ . '/fixtures/header_trailing_ws.txt');
        $hdrs = Horde_Mime_Headers::parseHeaders($msg);

        $this->assertNotNull($hdrs->getValue('From'));
    }

    public function testParseHeadersGivingStreamResource()
    {
        $fp = fopen(__DIR__ . '/fixtures/multiple_to.txt', 'r');
        $hdrs = Horde_Mime_Headers::parseHeaders($fp);
        fclose($fp);

        $this->assertNotEmpty($hdrs->getValue('To'));
    }

    public function testParseHeadersGivingHordeStreamObject()
    {
        $stream = new Horde_Stream_Existing(array(
            'stream' => fopen(__DIR__ . '/fixtures/multiple_to.txt', 'r')
        ));
        $hdrs = Horde_Mime_Headers::parseHeaders($stream);

        $this->assertNotEmpty($hdrs->getValue('To'));
    }

    public function testParseHeadersBlankSubject()
    {
        $stream = new Horde_Stream_Existing(array(
            'stream' => fopen(__DIR__ . '/fixtures/blank_subject.txt', 'r')
        ));
        $hdrs = Horde_Mime_Headers::parseHeaders($stream);

        $this->assertNotEmpty($hdrs->getValue('To'));
    }

    public function testMultiplePriorityHeaders()
    {
        $hdrs = Horde_Mime_Headers::parseHeaders(
            "Importance: High\n" .
            "Importance: Low\n"
        );

        $this->assertInternalType(
            'string',
            $hdrs->getValue('importance', Horde_Mime_Headers::VALUE_BASE)
        );
        $this->assertEquals(
            'High',
            $hdrs->getValue('importance')
        );

        $hdrs = Horde_Mime_Headers::parseHeaders(
            "X-Priority: 1\n" .
            "X-priority: 5\n"
        );

        $this->assertInternalType(
            'string',
            $hdrs->getValue('x-priority', Horde_Mime_Headers::VALUE_BASE)
        );
        $this->assertEquals(
            '1',
            $hdrs->getValue('x-priority')
        );
    }

}
