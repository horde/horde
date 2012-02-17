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
        $result = $parser->parseAddressList($address, array(
            'validate' => false
        ));

        $this->assertEquals(
            $address,
            $result[0]->writeAddress()
        );

        $this->assertEquals(
            '=?utf-8?b?Rm9vw6M=?= <test@example.com>',
            $result[0]->writeAddress(array('encode' => true))
        );
    }

}
