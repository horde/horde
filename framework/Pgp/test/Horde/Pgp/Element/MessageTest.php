<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2015 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pgp
 * @subpackage UnitTests
 */

/**
 * Tests for the PGP message object element.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2015 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pgp
 * @subpackage UnitTests
 */
class Horde_Pgp_Element_MessageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider isEncryptedSymmetricallyProvider
     */
    public function testIsEncryptedSymmetrically($expected, $data)
    {
        $msg = Horde_Pgp_Element_Message::create($data);

        if ($expected) {
            $this->assertTrue($msg->isEncryptedSymmetrically());
        } else {
            $this->assertFalse($msg->isEncryptedSymmetrically());
        }
    }

    public function isEncryptedSymmetricallyProvider()
    {
        return array(
            array(
                false,
                file_get_contents(__DIR__ . '/../fixtures/pgp_encrypted.txt')
            ),
            array(
                true,
                file_get_contents(__DIR__ . '/../fixtures/pgp_encrypted_symmetric.txt')
            )
        );
    }

}
