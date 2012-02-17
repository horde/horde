<?php
/**
 * Tests for the Horde_Mime_Address class.
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
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Mime
 * @subpackage UnitTests
 */
class Horde_Mime_AddressTest extends PHPUnit_Framework_TestCase
{
    public function testWriteAddress()
    {
        $host = 'example.com';
        $mailbox = 'foo';
        $personal = 'Example';

        $this->assertEquals(
            'Example <foo@example.com>',
            Horde_Mime_Address::writeAddress($mailbox, $host, $personal)
        );
    }

    public function testTrimAddress()
    {
        $this->assertEquals(
            'foo@example.com',
            Horde_Mime_Address::trimAddress('<foo@example.com>')
        );
        $this->assertEquals(
            'Foo <foo@example.com>',
            Horde_Mime_Address::trimAddress('Foo <foo@example.com>')
        );
    }

    public function testAddrObject2String()
    {
        $ob = Horde_Mime_Address::parseAddressList('<foo@example.com>');
        $this->assertEquals(
            'foo@example.com',
            Horde_Mime_Address::addrObject2String(reset($ob))
        );

        $ob = Horde_Mime_Address::parseAddressList('Foo  <foo@example.com> ');
        $this->assertEquals(
            'Foo <foo@example.com>',
            Horde_Mime_Address::addrObject2String(reset($ob))
        );
    }

    public function testBareAddress()
    {
        $this->assertEquals(
            'foo@example.com',
            Horde_Mime_Address::bareAddress('<foo@example.com>')
        );
        $this->assertEquals(
            'foo@example.com',
            Horde_Mime_Address::bareAddress('Foo  <foo@example.com> ')
        );
    }

    public function testBug6896()
    {
        // Bug #6896: explode() parsing broken
        $str = 'addr1@example.com, addr2@example.com';

        $this->assertEquals(
            array(
                'addr1@example.com',
                'addr2@example.com'
            ),
            Horde_Mime_Address::explode($str)
        );
    }

    public function testAddrArray2String()
    {
        $str = 'Foo Bar <foobar@example.com>, "bad_email@example.com, Baz" <baz@example.com>, Qux <qux@example.com>';
        $ob = Horde_Mime_Address::parseAddressList($str);
        $this->assertEquals(
            $str,
            Horde_Mime_Address::addrArray2String($ob)
        );
    }

}
