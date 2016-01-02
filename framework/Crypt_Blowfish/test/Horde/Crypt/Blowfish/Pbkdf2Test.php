<?php
/**
 * Copyright 2015-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2015-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Crypt_Blowfish
 * @subpackage UnitTests
 */

/**
 * Tests for PBKDF2.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2015-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Crypt_Blowfish
 * @subpackage UnitTests
 */
class Horde_Crypt_Blowfish_Pbkdf2Test extends Horde_Test_Case
{
    /**
     * Test vectors.
     *
     * @dataProvider vectorsProvider
     */
    public function testVectors($expected, $algo, $pass, $salt, $iter, $klen)
    {
        $pbkdf2 = new Horde_Crypt_Blowfish_Pbkdf2($pass, $klen, array(
            'algo' => $algo,
            'i_count' => $iter,
            'salt' => $salt
        ));

        $this->assertEquals(
            $expected,
            bin2hex($pbkdf2)
        );

        $this->assertEquals(
            $algo,
            $pbkdf2->hashAlgo
        );

        $this->assertEquals(
            $iter,
            $pbkdf2->iterations
        );

        $this->assertEquals(
            $salt,
            $pbkdf2->salt
        );
    }

    public function vectorsProvider()
    {
        return array(
            /* Begin: RFC 6070 Vectors */
            array(
                // Expected
                '0c60c80f961f0e71f3a9b524af6012062fe037a6',
                // Hash
                'SHA1',
                // Password
                'password',
                // Salt
                'salt',
                // Iterations
                1,
                // Key length
                20
            ),
            array(
                'ea6c014dc72d6f8ccd1ed92ace1d41f0d8de8957',
                'SHA1',
                'password',
                'salt',
                2,
                20
            ),
            array(
                '4b007901b765489abead49d926f721d065a429c1',
                'SHA1',
                'password',
                'salt',
                4096,
                20
            ),
            /* Disable - 16 million iterations takes about 30 seconds on
             * my dev machine so don't want to cause that kind of CPU load
             * when doing automated testing.
            array(
                'eefe3d61cd4da4e4e9945b3d6ba2158c2634e984',
                'SHA1',
                'password',
                'salt',
                16777216,
                20
            ),
            */
            array(
                '3d2eec4fe41c849b80c8d83662c0e44a8b291a964cf2f07038',
                'SHA1',
                'passwordPASSWORDpassword',
                'saltSALTsaltSALTsaltSALTsaltSALTsalt',
                4096,
                25
            ),
            array(
                '56fa6aa75548099dcc37d7f03425e0c3',
                'SHA1',
                "pass\0word",
                "sa\0lt",
                4096,
                16
            ),
            /* End: RFC 6070 Vectors */
            array(
                '3144c39857011a14e27d2b83e6c814f8f3dab70208aa1b4ffab1b7978599ffc3',
                'SHA256',
                'Password Password',
                '123SaLt456',
                16384,
                32
            ),
            array(
                '0a866b26a368f733d2bd95dc0acea6b544c38ba31bc357f527cf85e9f6a937bb',
                'SHA512',
                'Password Password',
                '123SaLt456',
                16384,
                32
            ),
        );
    }

    public function testAutoSaltGeneration()
    {
        $pbkdf2 = new Horde_Crypt_Blowfish_Pbkdf2('password', 20);

        $this->assertEquals(
            20,
            strlen($pbkdf2->salt)
        );
    }

}
