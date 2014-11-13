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
    /**
     * @dataProvider serializeProvider
     */
    public function testSerialize($header, $value)
    {
        $hdrs = new Horde_Mime_Headers();
        $hdrs->addHeader($header, $value);

        $hdrs2 = unserialize(serialize($hdrs));

        /* @deprecated */
        $this->assertEquals(
            $value,
            $hdrs2->getValue($header)
        );

        $this->assertequals(
            $value,
            strval($hdrs2[$header])
        );
    }

    public function serializeProvider()
    {
        return array(
            array(
                'Subject', 'My Subject'
            ),
            array(
                'To', 'recipient@example.com'
            ),
            array(
                'Cc', 'null@example.com'
            ),
            array(
                'Bcc', 'invisible@example.com'
            ),
            array(
                'From', 'sender@example.com'
            )
        );
    }

    /**
     * @dataProvider normalHeaderDecodeProvider
     */
    public function testNormalHeaderDecode($header, $value, $decoded)
    {
        $hdrs = new Horde_Mime_Headers();
        $hdrs->addHeader($header, $value);

        /* @deprecated */
        $this->assertEquals(
            $decoded,
            $hdrs->getValue($header)
        );

        $this->assertEquals(
            $decoded,
            $hdrs[$header]->value_single
        );
    }

    public function normalHeaderDecodeProvider()
    {
        return array(
            array(
                'Test',
                '=?iso-8859-15?b?VmVyc2nzbg==?=',
                'Versión'
            )
        );
    }

    /**
     * @dataProvider contentParamHeaderDecodeProvider
     */
    public function testContentParamHeaderDecode(
        $header, $value, $decode_value, $decode_params
    )
    {
        $hdrs = new Horde_Mime_Headers();
        $hdrs->addHeader($header, $value);

        $this->assertEquals(
            $decode_value,
            $hdrs[$header]->value
        );

        $this->assertEquals(
            $decode_params,
            $hdrs[$header]->params
        );
    }

    public function contentParamHeaderDecodeProvider()
    {
        return array(
            array(
                'Content-Type',
                'text/plain; name="=?iso-8859-15?b?VmVyc2nzbg==?="',
                'text/plain',
                array(
                    'name' => 'Versión'
                )
            )
        );
    }

    /**
     * @dataProvider headerAutoDetectCharsetProvider
     */
    public function testHeaderAutoDetectCharset($header, $value, $decoded)
    {
        $hdrs = Horde_Mime_Headers::parseHeaders($value);

        /* @deprecated */
        $this->assertEquals(
            $decoded,
            $hdrs->getValue($header)
        );

        $this->assertEquals(
            $decoded,
            $hdrs[$header]->value_single
        );
    }

    public function headerAutoDetectCharsetProvider()
    {
        return array(
            array(
                'Test',
                // This string is in Windows-1252
                'Test: ' . base64_decode('UnVubmVyc5IgQWxlcnQh='),
                'Runners’ Alert!'
            )
        );
    }

    /**
     * @dataProvider headerCharsetConversionProvider
     */
    public function testHeaderCharsetConversion(
        $header, $values, $charset, $encoded
    )
    {
        $hdrs = new Horde_Mime_Headers();
        foreach ($values as $val) {
            $hdrs->addHeader($header, $val);
        }

        $hdr_encode = $hdrs[$header]->sendEncode(array(
            'charset' => $charset
        ));

        $this->assertEquals(
            $encoded,
            $hdr_encode
        );

        $hdr_array = $hdrs->toArray(array(
            'charset' => $charset
        ));

        $this->assertEquals(
            (count($encoded) > 1) ? $encoded : reset($encoded),
            $hdr_array[$header]
        );
    }

    public function headerCharsetConversionProvider()
    {
        return array(
            /* Single address header */
            array(
                'To',
                array(
                    'Empfänger <recipient@example.com>'
                ),
                'iso-8859-1',
                array(
                    '=?iso-8859-1?b?RW1wZuRuZ2Vy?= <recipient@example.com>'
                )
            ),
            /* Multiple address header */
            array(
                'Resent-To',
                array(
                    'Empfänger <recipient@example.com>',
                    'Foo <foo@example.com>'
                ),
                'iso-8859-1',
                array(
                    '=?iso-8859-1?b?RW1wZuRuZ2Vy?= <recipient@example.com>',
                    'Foo <foo@example.com>'
                )
            )
        );
    }

    public function testMultipleContentType()
    {
        $hdrs = Horde_Mime_Headers::parseHeaders(
            "Content-Type: multipart/mixed\n" .
            "Content-Type: multipart/mixed\n"
        );

        /* @deprecated */
        $this->assertInternalType(
            'string',
            $hdrs->getValue('content-type', Horde_Mime_Headers::VALUE_BASE)
        );

        $this->assertInternalType(
            'string',
            $hdrs['content-type']->value
        );
    }

    /**
     * @dataProvider multivalueHeadersProvider
     */
    public function testMultivalueHeaders($header, $in, $expected)
    {
        $hdrs = Horde_Mime_Headers::parseHeaders($in);

        /* @deprecated */
        $this->assertEquals(
            $expected,
            $hdrs->getValue($header)
        );

        $this->assertEquals(
            $expected,
            $hdrs['to']->value
        );
    }

    public function multivalueHeadersProvider()
    {
        $expected = 'recipient1@example.com, recipient2@example.com';

        return array(
            array(
                'To',
                'To: recipient1@example.com, recipient2@example.com',
                $expected
            ),
            array(
                'To',
                "To: recipient1@example.com\nTo: recipient2@example.com",
                $expected
            )
        );
    }

    /**
     * @dataProvider addHeaderWithGroupProvider
     */
    public function testAddHeaderWithGroup($header, $email)
    {
        $rfc822 = new Horde_Mail_Rfc822();
        $ob = $rfc822->parseAddressList($email);

        $hdrs = new Horde_Mime_Headers();
        $hdrs->addHeader($header, $ob);

        /* @deprecated */
        $this->assertEquals(
            $email,
            $hdrs->getValue($header)
        );

        $this->assertEquals(
            $email,
            $hdrs[$header]->value
        );
    }

    public function addHeaderWithGroupProvider()
    {
        return array(
            array(
                'To',
                'Test: foo@example.com, bar@example.com;'
            )
        );
    }

    /**
     * @dataProvider uuencodeMimeHeaderProvider
     */
    public function testUnencodedMimeHeader($header, $in, $decoded)
    {
        $hdrs = Horde_Mime_Headers::parseHeaders($in);

        /* @deprecated */
        $this->assertEquals(
            $decoded,
            $hdrs->getValue($header)
        );

        $this->assertEquals(
            $decoded,
            $hdrs[$header]->value
        );
    }

    public function uuencodeMimeHeaderProvider()
    {
        return array(
            array(
                'From',
                // The header is base64 encoded to preserve charset data.
                base64_decode('RnJvbTogwqkgVklBR1JBIMKuIE9mZmljaWFsIFNpdGUgPGZvb0BleGFtcGxlLmNvbT4='),
                '© VIAGRA ® Official Site <foo@example.com>'
            )
        );
    }

    /**
     * @dataProvider parseContentDispositionHeaderWithUtf8DataProvider
     */
    public function testParseContentDispositionHeaderWithUtf8Data(
        $header, $parameter, $msg, $value
    )
    {
        $hdrs = Horde_Mime_Headers::parseHeaders($msg);

        /* @deprecated */
        $cd_params = $hdrs->getValue(
            $header,
            $hdrs::VALUE_PARAMS
        );
        $this->assertEquals(
            $value,
            $cd_params[$parameter]
        );

        $this->assertEquals(
            $value,
            $hdrs[$header]->params[$parameter]
        );
    }

    public function parseContentDispositionHeaderWithUtf8DataProvider()
    {
        return array(
            array(
                'content-disposition',
                'filename',
                file_get_contents(__DIR__ . '/fixtures/sample_msg_eai.txt'),
                'blåbærsyltetøy'
            )
        );
    }

    public function testCaseInsensitiveContentParameters()
    {
        $hdr = 'Content-Type: multipart/mixed; BOUNDARY="foo"';
        $hdrs = Horde_Mime_Headers::parseHeaders($hdr);

        /* @deprecated */
        $c_params =  $hdrs->getValue(
            'Content-Type',
            $hdrs::VALUE_PARAMS
        );
        $this->assertEquals(
            'foo',
            $c_params['boundary']
        );

        $this->assertEquals(
            'foo',
            $hdrs['content-type']['boundary']
        );
    }

    public function testParseEaiAddresses()
    {
        /* Simple message. */
        $msg = file_get_contents(__DIR__ . '/fixtures/sample_msg_eai_2.txt');
        $hdrs = Horde_Mime_Headers::parseHeaders($msg);

        /* @deprecated */
        $this->assertEquals(
            'Jøran Øygårdvær <jøran@example.com>',
            $hdrs->getValue('from')
        );

        $this->assertEquals(
            'Jøran Øygårdvær <jøran@example.com>',
            $hdrs['from']->value
        );

        /* Message with EAI addresses in 2 fields, and another header that
         * contains a string (that looks like an address). */
        $msg = file_get_contents(__DIR__ . '/fixtures/sample_msg_eai_4.txt');
        $hdrs = Horde_Mime_Headers::parseHeaders($msg);

        /* @deprecated */
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

        $this->assertEquals(
            'Jøran Øygårdvær <jøran@example.com>',
            $hdrs['from']->value
        );
        $this->assertEquals(
            'Jøran Øygårdvær <jøran@example.com>',
            $hdrs['cc']->value
        );
        $this->assertEquals(
            'Jøran Øygårdvær <jøran@example.com>',
            $hdrs['signed-off-by']->value_single
        );
    }

    /**
     * @dataProvider undisclosedHeaderParsingProvider
     */
    public function testUndisclosedHeaderParsing($header, $value)
    {
        $hdrs = new Horde_Mime_Headers();
        $hdrs->addHeader($header, $value);

        /* @deprecated */
        $this->assertEquals(
            '',
            $hdrs->getValue($header)
        );

        $this->assertEquals(
            '',
            $hdrs[$header]->value
        );
    }

    public function undisclosedHeaderParsingProvider()
    {
        return array(
            array('To', 'undisclosed-recipients'),
            array('To', 'undisclosed-recipients:'),
            array('To', 'undisclosed-recipients:;')
        );
    }

    public function testMultipleToAddresses()
    {
        $msg = file_get_contents(__DIR__ . '/fixtures/multiple_to.txt');
        $hdrs = Horde_Mime_Headers::parseHeaders($msg);

        /* @deprecated */
        $this->assertNotEmpty($hdrs->getValue('To'));

        $this->assertNotEmpty($hdrs['To']->value);
    }

    public function testBug12189()
    {
        $msg = file_get_contents(__DIR__ . '/fixtures/header_trailing_ws.txt');
        $hdrs = Horde_Mime_Headers::parseHeaders($msg);

        /* @deprecated */
        $this->assertNotNull($hdrs->getValue('From'));

        $this->assertNotNull($hdrs['From']->value);
    }

    public function testParseHeadersGivingStreamResource()
    {
        $fp = fopen(__DIR__ . '/fixtures/multiple_to.txt', 'r');
        $hdrs = Horde_Mime_Headers::parseHeaders($fp);
        fclose($fp);

        /* @deprecated */
        $this->assertNotEmpty($hdrs->getValue('To'));

        $this->assertNotEmpty($hdrs['To']->value);
    }

    public function testParseHeadersGivingHordeStreamObject()
    {
        $stream = new Horde_Stream_Existing(array(
            'stream' => fopen(__DIR__ . '/fixtures/multiple_to.txt', 'r')
        ));
        $hdrs = Horde_Mime_Headers::parseHeaders($stream);

        /* @deprecated */
        $this->assertNotEmpty($hdrs->getValue('To'));

        $this->assertNotEmpty($hdrs['To']);
    }

    public function testParseHeadersBlankSubject()
    {
        $stream = new Horde_Stream_Existing(array(
            'stream' => fopen(__DIR__ . '/fixtures/blank_subject.txt', 'r')
        ));
        $hdrs = Horde_Mime_Headers::parseHeaders($stream);

        /* @deprecated */
        $this->assertNotEmpty($hdrs->getValue('To'));

        $this->assertNotEmpty($hdrs['To']);
    }

    /**
     * @dataProvider multiplePriorityHeadersProvider
     */
    public function testMultiplePriorityHeaders($header, $data, $value)
    {
        $hdrs = Horde_Mime_Headers::parseHeaders($data);

        /* @deprecated */
        $this->assertInternalType(
            'string',
            $hdrs->getValue($header, Horde_Mime_Headers::VALUE_BASE)
        );
        $this->assertEquals(
            $value,
            $hdrs->getValue($header)
        );

        $this->assertInternalType(
            'string',
            $hdrs[$header]->value_single
        );
        $this->assertEquals(
            $value,
            $hdrs[$header]->value_single
        );

    }

    public function multiplePriorityHeadersProvider()
    {
        return array(
            array(
                'Importance',
                "Importance: High\nImportance: Low\n",
                'High'
            ),
            array(
                'X-priority',
                "X-Priority: 1\nX-priority: 5\n",
                '1'
            )
        );
    }

}
