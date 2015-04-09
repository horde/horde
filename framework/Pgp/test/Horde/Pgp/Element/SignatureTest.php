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
 * Tests for the PGP signature element object.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2015 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pgp
 * @subpackage UnitTests
 */
class Horde_Pgp_Element_SignatureTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getSignersKeyIdProvider
     */
    public function testGetSignersKeyId($expected, $data)
    {
        $sig = Horde_Pgp_Element_Signature::findSignature($data);

        $this->assertEquals(
            $expected,
            $sig->getSignersKeyId()
        );
    }

    public function getSignersKeyIdProvider()
    {
        return array(
            array(
                'BADEABD7',
                file_get_contents(__DIR__ . '/../fixtures/pgp_signed.txt')
            )
        );
    }

}
