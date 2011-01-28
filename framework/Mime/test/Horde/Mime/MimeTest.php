<?php
/**
 * Tests for the Horde_Mime class.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * @author     Michael Slusarz <slusarz@curecanti.org>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package    Mime
 * @subpackage UnitTests
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * @author     Michael Slusarz <slusarz@curecanti.org>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package    Mime
 * @subpackage UnitTests
 */
class Horde_Mime_MimeTest extends PHPUnit_Framework_TestCase
{
    public function testUudecode()
    {
        $data = Horde_Mime::uudecode(file_get_contents(dirname(__FILE__) . '/fixtures/uudecode.txt'));

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

    public function testBug4834()
    {
        // Bug #4834: Wrong encoding of email lists with groups.
        $addr = '"John Doe" <john@example.com>, Group: peter@example.com, jane@example.com;';
        $expect = 'John Doe <john@example.com>, Group: peter@example.com, jane@example.com;';

        $this->assertEquals(Horde_Mime::encodeAddress($addr, 'us-ascii'), $expect);
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
        $this->assertEquals(Horde_Mime::encodeParam($pname, $str, 'UTF-8'), $expected);

        Horde_Mime::$brokenRFC2231 = false;
        unset($expected['test']);
        $this->assertEquals(Horde_Mime::encodeParam($pname, $str, 'UTF-8'), $expected);
    }

    public function testEncodeAddress()
    {
        $email = 'ß <test@example.com>';

        $this->assertEquals(
            '=?utf-8?b?w58=?= <test@example.com>',
            Horde_Mime::encodeAddress($email, 'UTF-8')
        );

        $this->assertEquals(
            '=?utf-8?b?w58=?= <test@example.com>',
            Horde_Mime::encodeAddress($email, 'UTF-8', 'example.com')
        );

        $email2 = 'ß X <test@example.com>';

        $this->assertEquals(
            '=?utf-8?b?w58=?= X <test@example.com>',
            Horde_Mime::encodeAddress($email2, 'UTF-8', 'example.com')
        );

        $email3 = '"ß X" <test@example.com>';

        $this->assertEquals(
            '=?utf-8?b?w58=?= X <test@example.com>',
            Horde_Mime::encodeAddress($email3, 'UTF-8', 'example.com')
        );
    }

    public function testDecode()
    {
        $this->assertEquals(
            ' François Xavier. XXXXXX  <foo@example.com>',
            Horde_Mime::decode('=?utf-8?Q?_Fran=C3=A7ois_Xavier=2E_XXXXXX_?= <foo@example.com>', 'UTF-8')
        );
    }

}
