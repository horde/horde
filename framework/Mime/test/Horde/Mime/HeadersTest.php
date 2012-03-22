<?php
/**
 * Tests for the Horde_Mime_Headers class.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Autoload.php';

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

}
