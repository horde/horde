<?php
/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Mail
 * @subpackage UnitTests
 */

class Horde_Mail_MatchTest extends PHPUnit_Framework_TestCase
{
    public function testMatch()
    {
        $address = new Horde_Mail_Rfc822_Address('Test <test@example.com>');

        $this->assertTrue($address->match('Foo <test@example.com>'));
        $this->assertTrue($address->match('Foo <test@EXAMPLE.COM>'));
        $this->assertFalse($address->match('Foo <Test@example.com>'));
    }

    public function testInsensitiveMatch()
    {
        $address = new Horde_Mail_Rfc822_Address('Test <test@example.com>');

        $this->assertTrue($address->matchInsensitive('Foo <test@example.com>'));
        $this->assertTrue($address->matchInsensitive('Foo <test@EXAMPLE.COM>'));
        $this->assertTrue($address->matchInsensitive('Foo <Test@example.com>'));
    }

}
