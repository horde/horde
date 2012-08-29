<?php
/**
 * Tests for the Horde_Mime class.
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
class Horde_Mime_MimeTest extends PHPUnit_Framework_TestCase
{
    public function testUudecode()
    {
        $data = Horde_Mime::uudecode(file_get_contents(__DIR__ . '/fixtures/uudecode.txt'));

        $this->assertEquals(
            2,
            count($data)
        );

        $this->assertArrayHasKey('data', $data[0]);
        $this->assertEquals(
            'Test string',
            $data[0]['data']
        );
        $this->assertArrayHasKey('name', $data[0]);
        $this->assertEquals(
            'test.txt',
            $data[0]['name']
        );
        $this->assertArrayHasKey('perm', $data[0]);
        $this->assertEquals(
            '644',
            $data[0]['perm']
        );

        $this->assertArrayHasKey('data', $data[1]);
        $this->assertEquals(
            '2nd string',
            $data[1]['data']
        );
        $this->assertArrayHasKey('name', $data[1]);
        $this->assertEquals(
            'test2.txt',
            $data[1]['name']
        );
        $this->assertArrayHasKey('perm', $data[1]);
        $this->assertEquals(
            '755',
            $data[1]['perm']
        );
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

}
