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
 * Tests for PGP armor parsing.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2015 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pgp
 * @subpackage UnitTests
 */
class Horde_Pgp_ParseTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider parsePgpDataProvider
     */
    public function testParsePgpData($fixture, $expected, $headers)
    {
        $data = file_get_contents(__DIR__ . '/fixtures/' . $fixture);

        $stream = new Horde_Stream_Temp();
        $stream->add($data, true);

        $obs = array(
            new Horde_Pgp_Armor($data),
            new Horde_Pgp_Armor($stream)
        );

        foreach ($obs as $ob) {
            $this->assertEquals(
                count($expected),
                count($ob)
            );

            $i = 0;
            foreach ($ob as $val) {
                $this->assertEquals(
                    $expected[$i++],
                    get_class($val)
                );

                $this->assertEquals(
                    $headers,
                    $val->headers
                );
            }
        }
    }

    public function parsePgpDataProvider()
    {
        return array(
            array(
                'clear.txt',
                array(),
                array()
            ),
            array(
                'pgp_encrypted_symmetric.txt',
                array('Horde_Pgp_Element_Message'),
                array('Version' => 'GnuPG v1.4.5 (GNU/Linux)')
            ),
            array(
                'pgp_encrypted.txt',
                array('Horde_Pgp_Element_Message'),
                array('Version' => 'GnuPG v1.4.5 (GNU/Linux)')
            ),
            array(
                'pgp_private.asc',
                array('Horde_Pgp_Element_PrivateKey'),
                array('Version' => 'GnuPG v1.4.5 (GNU/Linux)')
            ),
            array(
                'pgp_public.asc',
                array('Horde_Pgp_Element_PublicKey'),
                array('Version' => 'GnuPG v1.4.5 (GNU/Linux)')
            ),
            array(
                'pgp_signature.txt',
                array('Horde_Pgp_Element_Signature'),
                array('Version' => 'GnuPG v1.4.5 (GNU/Linux)')
            ),
            array(
                'pgp_signed.txt',
                array('Horde_Pgp_Element_SignedMessage'),
                array('Hash' => 'SHA1')
            )
        );
    }

}
