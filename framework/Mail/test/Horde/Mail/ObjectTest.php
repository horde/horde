<?php
/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Mail
 * @subpackage UnitTests
 */

class Horde_Mail_ObjectTest extends PHPUnit_Framework_TestCase
{
    public function testWriteAddress()
    {
        $address = 'Test <test@example.com>';

        $parser = new Horde_Mail_Rfc822();
        $result = $parser->parseAddressList($address);

        $this->assertEquals(
            $address,
            $result[0]->writeAddress()
        );
    }

    public function testEncoding()
    {
        $address = 'Fooã <test@example.com>';

        $parser = new Horde_Mail_Rfc822();
        $result = $parser->parseAddressList($address);

        $this->assertEquals(
            $address,
            $result[0]->writeAddress()
        );

        $this->assertEquals(
            '=?utf-8?b?Rm9vw6M=?= <test@example.com>',
            $result[0]->writeAddress(array('encode' => true))
        );

        $this->assertEquals(
            '=?iso-8859-1?b?Rm9v4w==?= <test@example.com>',
            $result[0]->writeAddress(array('encode' => 'iso-8859-1'))
        );

        $email = 'ß <test@example.com>';
        $result = $parser->parseAddressList($email);

        $this->assertEquals(
            '=?utf-8?b?w58=?= <test@example.com>',
            $result[0]->writeAddress(array('encode' => true))
        );

        $email2 = 'ß X <test@example.com>';
        $result = $parser->parseAddressList($email2);

        $this->assertEquals(
            '=?utf-8?b?w58=?= X <test@example.com>',
            $result[0]->writeAddress(array('encode' => true))
        );

        $email3 = '"ß X" <test@example.com>';
        $result = $parser->parseAddressList($email3);

        $this->assertEquals(
            '=?utf-8?b?w58=?= X <test@example.com>',
            $result[0]->writeAddress(array('encode' => true))
        );
    }

    public function testAddressConstructor()
    {
        $address = 'Test <test@example.com>';

        $addr_ob = new Horde_Mail_Rfc822_Address($address);

        $this->assertEquals(
            'Test',
            $addr_ob->personal
        );

        $this->assertEquals(
            'test',
            $addr_ob->mailbox
        );

        $this->assertEquals(
            'example.com',
            $addr_ob->host
        );
    }

    public function testEncodedAddressWithIDNHost()
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('Intl module is not available.');
        }

        $ob = new Horde_Mail_Rfc822_Address();
        $ob->personal = 'Aäb';
        $ob->mailbox = 'test';
        $ob->host = 'üexample.com';

        $this->assertEquals(
            '=?utf-8?b?QcOkYg==?= <test@xn--example-m2a.com>',
            $ob->encoded
        );
    }

    public function testDecodedAddressWithIDNHost()
    {
        $ob = new Horde_Mail_Rfc822_Address();
        $ob->personal = '=?utf-8?b?QcOkYg==?=';
        $ob->mailbox = 'test';
        $ob->host = 'xn--example-m2a.com';

        $this->assertEquals(
            'Aäb <test@üexample.com>',
            strval($ob)
        );
    }

    public function testBug4834()
    {
        // Bug #4834: Wrong encoding of email lists with groups.
        $addr = '"John Doe" <john@example.com>, Group: peter@example.com, jane@example.com;';

        $parser = new Horde_Mail_Rfc822();
        $result = $parser->parseAddressList($addr);

        $this->assertEquals(
            'John Doe <john@example.com>, Group: peter@example.com, jane@example.com;',
            strval($result)
        );
    }

    public function testValid()
    {
        $ob = new Horde_Mail_Rfc822_Address();

        $this->assertFalse($ob->valid);

        $ob->mailbox = 'test';

        $this->assertTrue($ob->valid);
    }

}
