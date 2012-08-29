<?php
/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Mail
 * @subpackage UnitTests
 */

class Horde_Mail_AddressTest extends PHPUnit_Framework_TestCase
{
    public function testDomainMatch()
    {
        $address = new Horde_Mail_Rfc822_Address('Test <test@example.com>');

        $this->assertTrue($address->matchDomain('example.com'));
        $this->assertFalse($address->matchDomain('foo.example.com'));

        $address2 = new Horde_Mail_Rfc822_Address('Test <test@foo.example.com>');
        $this->assertTrue($address2->matchDomain('example.com'));
        $this->assertTrue($address2->matchDomain('foo.example.com'));
    }

}
