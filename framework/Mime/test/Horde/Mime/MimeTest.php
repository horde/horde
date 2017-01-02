<?php
/**
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @copyright  2010-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */

/**
 * Tests for the Horde_Mime class.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2010-2016 Horde LLC
 * @internal
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */
class Horde_Mime_MimeTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider is8bitProvider
     */
    public function testIs8bit($data, $expected)
    {
        $this->assertEquals(
            $expected,
            Horde_Mime::is8bit($data)
        );
    }

    public function is8bitProvider()
    {
        return array(
            array('A', false),
            array('a', false),
            array('1', false),
            array('!', false),
            array("\0", false),
            array("\10", false),
            array("\127", false),
            array("\x80", true),
            array('ä', true),
            array('A©B', true),
            array(' ® ', true),
            // This string is in Windows-1252
            array(base64_decode('UnVubmVyc5IgQWxlcnQh='), true)
        );
    }

    /**
     * @dataProvider decodeProvider
     */
    public function testDecode($data, $expected)
    {
        $this->assertEquals(
            $expected,
            Horde_Mime::decode($data)
        );
    }

    public function decodeProvider()
    {
        return array(
            array(
                '=?utf-8?Q?_Fran=C3=A7ois_Xavier=2E_XXXXXX_?= <foo@example.com>',
                ' François Xavier. XXXXXX  <foo@example.com>'
            ),

            /* Adapted from Dovecot's
             * src/lib-mail/test-message-header-decode.c. */
            array(
                " \t=?utf-8?q?=c3=a4?=  =?utf-8?q?=c3=a4?=  b  \t\r\n ",
                " \tää  b  \t\r\n "
            ),
            array(
                "a =?utf-8?q?=c3=a4?= b",
                "a ä b"
            ),
            array(
                "a =?utf-8?q?=c3=a4?=\t\t\r\n =?utf-8?q?=c3=a4?= b",
                "a ää b"
            ),
            array(
                "a =?utf-8?q?=c3=a4?=  x  =?utf-8?q?=c3=a4?= b",
                "a ä  x  ä b"
            ),
            array(
                "a =?utf-8?b?w6TDpCDDpA==?= b",
                "a ää ä b"
            ), array(
                "=?utf-8?b?w6Qgw6Q=?=",
                "ä ä"
            ),

            /* Not MIME encoded. */
            array(
                '=? required=?',
                '=? required=?'
            )
        );
    }

    /**
     * @dataProvider encodeProvider
     */
    public function testEncode($data, $charset, $expected)
    {
        $this->assertEquals(
            $expected,
            Horde_Mime::encode($data, $charset)
        );
    }

    public function encodeProvider()
    {
        return array(
            /* Adapted from Dovecot's
             * src/lib-mail/test-message-header-encode.c. */
            array(
                'a b',
                'utf-8',
                'a b'
            ),
            array(
                'a bcäde f',
                'utf-8',
                'a =?utf-8?b?YmPDpGRl?= f'
            ),
            array(
                'a ää ä b',
                'utf-8',
                'a =?utf-8?b?w6TDpCDDpA==?= b'
            ),
            array(
                'ä a ä',
                'utf-8',
                '=?utf-8?b?w6Q=?= a =?utf-8?b?w6Q=?='
            ),
            array(
                'ää a ä',
                'utf-8',
                // Dovecot: '=?utf-8?b?w6TDpCBhIMOk?='
                '=?utf-8?b?w6TDpA==?= a =?utf-8?b?w6Q=?='
            ),
            array(
                '=',
                'utf-8',
                '='
            ),
            array(
                '?',
                'utf-8',
                '?'
            ),
            array(
                'a=?',
                'utf-8',
                'a=?'
            ),
            array(
                '=?',
                'utf-8',
                // Dovecot: '=?utf-8?q?=3D=3F?='
                '=?utf-8?b?PT8=?='
            ),
            array(
                '=?x',
                'utf-8',
                // Dovecot: '=?utf-8?q?=3D=3Fx?='
                '=?utf-8?b?PT94?='
            ),
            array(
                "a\n=?",
                'utf-8',
                // Dovecot: "a\n\t=?utf-8?q?=3D=3F?="
                "a\n=?utf-8?b?PT8=?="
            ),
            array(
                "a\t=?",
                'utf-8',
                // Dovecot: "a\t=?utf-8?q?=3D=3F?="
                "a\t=?utf-8?b?PT8=?="
            ),
            array(
                "a =?",
                'utf-8',
                // Dovecot: "a =?utf-8?q?=3D=3F?="
                'a =?utf-8?b?PT8=?='
            ),
            array(
                "foo\001bar",
                'utf-8',
                // Dovecot: "=?utf-8?q?foo=01bar?="
                '=?utf-8?b?Zm9vAWJhcg==?='
            ),
            array(
                "\x01\x02\x03\x04\x05\x06\x07\x08",
                'utf-8',
                "=?utf-8?b?AQIDBAUGBwg=?="
            ),

            /* Null character in encode output. */
            array(
                "\x00",
                'UTF-16LE',
                '=?utf-16le?b?AAA=?='
            )
        );
    }

}
