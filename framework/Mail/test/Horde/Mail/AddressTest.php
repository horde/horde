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
    public function testBaseDomain()
    {
        $address = new Horde_Mail_Rfc822_Address('Test <test@example.com>');
        $this->assertEquals(
            'example.com',
            $address->base_domain
        );

        $address2 = new Horde_Mail_Rfc822_Address('Test <test@foo.bar.example.com>');
        $this->assertEquals(
            'example.com',
            $address2->base_domain
        );

        $address3 = new Horde_Mail_Rfc822_Address('Test <test@example.co.uk>');
        $this->assertEquals(
            'example.co.uk',
            $address3->base_domain
        );

        $address4 = new Horde_Mail_Rfc822_Address('Test <test@foo.bar.example.co.uk>');
        $this->assertEquals(
            'example.co.uk',
            $address4->base_domain
        );
    }

}
