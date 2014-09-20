<?php
/**
 * Tests for the Horde_Mime class.
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
class Horde_Mime_MimeTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Horde_Mime::$brokenRFC2231 = false;
    }

    public function testRfc2231()
    {
        // Horde_Mime RFC 2231 & workaround for broken MUA's
        $pname = 'test';
        $str = str_repeat('a', 100) . '.txt';
        $expected = array(
            'test' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.txt',
            'test*0' =>'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            'test*1' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.txt'
        );

        Horde_Mime::$brokenRFC2231 = true;
        $this->assertEquals(Horde_Mime::encodeParam($pname, $str), $expected);

        Horde_Mime::$brokenRFC2231 = false;
        unset($expected['test']);
        $this->assertEquals(Horde_Mime::encodeParam($pname, $str), $expected);
    }

    public function testDecode()
    {
        $this->assertEquals(
            ' François Xavier. XXXXXX  <foo@example.com>',
            Horde_Mime::decode('=?utf-8?Q?_Fran=C3=A7ois_Xavier=2E_XXXXXX_?= <foo@example.com>')
        );

        /* Not MIME encoded. */
        $this->assertEquals(
            '=? required=?',
            Horde_Mime::decode('=? required=?')
        );
    }

    public function testIsChild()
    {
        $this->assertTrue(Horde_Mime::isChild('1', '1.0'));
        $this->assertTrue(Horde_Mime::isChild('1', '1.1'));
        $this->assertTrue(Horde_Mime::isChild('1', '1.1.0'));
        $this->assertFalse(Horde_Mime::isChild('1', '1'));
        $this->assertFalse(Horde_Mime::isChild('1', '2.1'));
        $this->assertFalse(Horde_Mime::isChild('1', '10.0'));
    }

    public function testEncodeParamQuotesQuote()
    {
        $this->assertEquals(
            array('foo' => "\"\x01\""),
            Horde_Mime::encodeParam('foo', "\x01")
        );
    }

    public function testBug12127()
    {
        Horde_Mime::$brokenRFC2231 = true;

        $this->assertEquals(
            array(
                'foo' => 'test'
            ),
            Horde_Mime::encodeParam('foo', 'test', array(
                'charset' => 'UTF-16LE'
            ))
        );

        $this->assertEquals(
            array(
                'foo*' => "utf-16le''%01%01",
                'foo' => '"=?utf-16le?b?AQE=?="'
            ),
            Horde_Mime::encodeParam('foo', 'ā', array(
                'charset' => 'UTF-16LE'
            ))
        );
    }

    public function testNullCharacterInEncodeOutput()
    {
        $this->assertEquals(
            '=?utf-16le?b?AAA=?=',
            Horde_Mime::encode("\x00", 'UTF-16LE')
        );
    }

}
