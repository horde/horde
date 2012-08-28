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
            $address->base_domain
        );
    }

}
