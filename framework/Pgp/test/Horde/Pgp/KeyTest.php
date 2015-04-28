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
 * Tests for PGP key handling.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2015 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pgp
 * @subpackage UnitTests
 */
class Horde_Pgp_KeyTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getCreationPropertyProvider
     */
    public function testGetCreationProperty($expected, $key)
    {
        $this->assertEquals(
            $expected,
            $key->creation
        );
    }

    public function getCreationPropertyProvider()
    {
        return array(
            array(
                1155291888,
                $this->_getKey('pgp_public.asc', 'public')
            ),
            array(
                1155291888,
                $this->_getKey('pgp_private.asc', 'private')
            ),
            array(
                1428808030,
                $this->_getKey('pgp_public_rsa.txt', 'public')
            ),
            array(
                1428808030,
                $this->_getKey('pgp_private_rsa.txt', 'private')
            )
        );
    }

    /**
     * @dataProvider getIdPropertyProvider
     */
    public function testGetIdProperty($expected, $key)
    {
        $this->assertEquals(
            $expected,
            $key->id
        );
    }

    public function getIdPropertyProvider()
    {
        return array(
            array(
                'BADEABD7',
                $this->_getKey('pgp_public.asc', 'public')
            ),
            array(
                'BADEABD7',
                $this->_getKey('pgp_private.asc', 'private')
            ),
            array(
                'F78F30D6',
                $this->_getKey('pgp_public_rsa.txt', 'public')
            ),
            array(
                'F78F30D6',
                $this->_getKey('pgp_private_rsa.txt', 'private')
            )
        );
    }

    /**
     * @dataProvider getFingerprintPropertyProvider
     */
    public function testGetFingerprintProperty($expected, $key)
    {
        $this->assertEquals(
            $expected,
            $key->fingerprint
        );
    }

    public function getFingerprintPropertyProvider()
    {
        return array(
            array(
                '966F4BA9569DE6F65E8253977CA74426BADEABD7',
                $this->_getKey('pgp_public.asc', 'public')
            ),
            array(
                '966F4BA9569DE6F65E8253977CA74426BADEABD7',
                $this->_getKey('pgp_private.asc', 'private')
            ),
            array(
                'C2F1B25DED428057096161322CA37A36F78F30D6',
                $this->_getKey('pgp_public_rsa.txt', 'public')
            ),
            array(
                'C2F1B25DED428057096161322CA37A36F78F30D6',
                $this->_getKey('pgp_private_rsa.txt', 'private')
            )
        );
    }

    /**
     * @dataProvider getUserIdsProvider
     */
    public function testGetUserIdsProperty($expected, $key)
    {
        $key_ob = $key->getUserIds();
        reset($key_ob);

        foreach ($expected as $val) {
            $curr = current($key_ob);

            foreach ($val as $key2 => $val2) {
                $this->assertEquals(
                    $val2,
                    $curr->$key2
                );
            }

            next($key_ob);
        }
    }

    public function getUserIdsProvider()
    {
        return array(
            array(
                array(
                    array(
                        'comment' => 'My Comment',
                        'created' => new DateTime('@1155291888'),
                        'email' => 'My Name <me@example.com>'
                    )
                ),
                $this->_getKey('pgp_public.asc', 'public')
            ),
            array(
                array(
                    array(
                        'comment' => 'My Comment',
                        'created' => new DateTime('@1155291888'),
                        'email' => 'My Name <me@example.com>'
                    )
                ),
                $this->_getKey('pgp_private.asc', 'private')
            ),
            array(
                array(
                    array(
                        'comment' => 'RSA',
                        'created' => new DateTime('@1428808030'),
                        'email' => 'Test User <test@example.com>'
                    )
                ),
                $this->_getKey('pgp_public_rsa.txt', 'public')
            ),
            array(
                array(
                    array(
                        'comment' => 'RSA',
                        'created' => new DateTime('@1428808030'),
                        'email' => 'Test User <test@example.com>'
                    )
                ),
                $this->_getKey('pgp_private_rsa.txt', 'private')
            )
        );
    }

    /**
     * @dataProvider getFingerprintsProvider
     */
    public function testGetFingerprints($expected, $key)
    {
        $this->assertEquals(
            $expected,
            $key->getFingerprints()
        );
    }

    public function getFingerprintsProvider()
    {
        return array(
            array(
                array(
                    /* Key */
                    'BADEABD7' => '966F4BA9569DE6F65E8253977CA74426BADEABD7',
                    /* Subkey */
                    '9EF074A9' => 'F4248B3AC97C1F749555929C24ED29779EF074A9'
                ),
                $this->_getKey('pgp_public.asc', 'public')
            ),
            array(
                array(
                    /* Key */
                    'BADEABD7' => '966F4BA9569DE6F65E8253977CA74426BADEABD7',
                    /* Subkey */
                    '9EF074A9' => 'F4248B3AC97C1F749555929C24ED29779EF074A9'
                ),
                $this->_getKey('pgp_private.asc', 'private')
            ),
            array(
                array(
                    /* Key */
                    'F78F30D6' => 'C2F1B25DED428057096161322CA37A36F78F30D6',
                    /* Subkey */
                    '5302C294' => '063A32E02D9B279D93E82068E03B24D55302C294'
                ),
                $this->_getKey('pgp_public_rsa.txt', 'public')
            ),
            array(
                array(
                    /* Key */
                    'F78F30D6' => 'C2F1B25DED428057096161322CA37A36F78F30D6',
                    /* Subkey */
                    '5302C294' => '063A32E02D9B279D93E82068E03B24D55302C294'
                ),
                $this->_getKey('pgp_private_rsa.txt', 'private')
            )
        );
    }

    /**
     * @dataProvider containsEmailProvider
     */
    public function testContainsEmailProvider($email, $key, $expected)
    {
        if ($expected) {
            $this->assertTrue($key->containsEmail($email));
        } else {
            $this->assertFalse($key->containsEmail($email));
        }
    }

    public function containsEmailProvider()
    {
        return array(
            array(
                'me@example.com',
                $this->_getKey('pgp_public.asc', 'public'),
                true
            ),
            array(
                'foo@example.com',
                $this->_getKey('pgp_public.asc', 'public'),
                false
            ),
            array(
                'me@example.com',
                $this->_getKey('pgp_private.asc', 'private'),
                true
            ),
            array(
                'foo@example.com',
                $this->_getKey('pgp_private.asc', 'private'),
                false
            ),
            array(
                'test@example.com',
                $this->_getKey('pgp_public_rsa.txt', 'public'),
                true
            ),
            array(
                'foo@example.com',
                $this->_getKey('pgp_public_rsa.txt', 'public'),
                false
            ),
            array(
                'test@example.com',
                $this->_getKey('pgp_private_rsa.txt', 'private'),
                true
            ),
            array(
                'foo@example.com',
                $this->_getKey('pgp_private_rsa.txt', 'private'),
                false
            )
        );
    }

    public function testCreateMimePart()
    {
        $key_ob = $this->_getKey('pgp_public_rsa.txt', 'public');
        $part = $key_ob->createMimePart();

        $this->assertInstanceOf(
            'Horde_Mime_Part',
            $part
        );

        $this->assertEquals(
            'application/pgp-keys',
            $part->getType()
        );

        $this->assertNotEmpty($part->getContents());
    }

    /**
     * @dataProvider getEncryptPacketsProvider
     */
    public function testGetEncryptPackets($key, $expected)
    {
        $list = $key->getEncryptPackets();

        $this->assertEquals(
            count($expected),
            count($list)
        );

        for ($i = 0; $i < count($expected); ++$i) {
            $this->assertEquals(
                $expected[$i],
                $list[$i]->created
            );
        }
    }

    public function getEncryptPacketsProvider()
    {
        return array(
            array(
                $this->_getKey('pgp_public.asc', 'public'),
                array(
                    new DateTime('@1155291888')
                )
            ),
            array(
                $this->_getKey('pgp_private.asc', 'private'),
                array(
                    new DateTime('@1155291888')
                )
            ),
            array(
                $this->_getKey('pgp_public_rsa.txt', 'public'),
                array(
                    new DateTime('@1428808030')
                )
            ),
            array(
                $this->_getKey('pgp_private_rsa.txt', 'private'),
                array(
                    new DateTime('@1428808030')
                )
            ),
            array(
                $this->_getKey('pgp_public_revoked.txt', 'public'),
                array()
            ),
            array(
                $this->_getKey('pgp_public_revokedsub.txt', 'public'),
                array()
            )
        );
    }

    /**
     * @dataProvider unencryptKeyProvider
     */
    public function testUnecryptKey($key, $passphrase, $expected)
    {
        $this->assertTrue($key->encrypted);

        try {
            $unencrypted = $key->getUnencryptedKey($passphrase);
            if (!$expected) {
                $this->fail('Expected exception');
            }

            $this->assertInstanceOf(
                'Horde_Pgp_Element_PrivateKey',
                $unencrypted
            );

            $this->assertFalse($unencrypted->encrypted);
        } catch (Horde_Pgp_Exception $e) {
            if ($expected) {
                $this->fail('Did not expect exception');
            }
            return;
        }
    }

    public function unencryptKeyProvider()
    {
        return array(
            array(
                $this->_getKey('pgp_private.asc', 'private'),
                'Invalid Passphrase',
                false
            ),
            array(
                $this->_getKey('pgp_private.asc', 'private'),
                'Secret',
                true
            ),
            array(
                $this->_getKey('pgp_private_rsa.txt', 'private'),
                'Invalid Passphrase',
                false
            ),
            array(
                $this->_getKey('pgp_private_rsa.txt', 'private'),
                'Secret',
                true
            )
        );
    }

    protected function _getKey($key, $type)
    {
        $class = ($type === 'public')
            ? 'Horde_Pgp_Element_PublicKey'
            : 'Horde_Pgp_Element_PrivateKey';
        return $class::create(
            file_get_contents(__DIR__ . '/fixtures/' . $key)
        );
    }

}
