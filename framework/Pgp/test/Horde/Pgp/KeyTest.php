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
            $this->_getKey($key)->creation
        );
    }

    public function getCreationPropertyProvider()
    {
        return array(
            array(
                1155291888,
                'public'
            ),
            array(
                1155291888,
                'private'
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
            $this->_getKey($key)->id
        );
    }

    public function getIdPropertyProvider()
    {
        return array(
            array(
                'BADEABD7',
                'public'
            ),
            array(
                'BADEABD7',
                'private'
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
            $this->_getKey($key)->fingerprint
        );
    }

    public function getFingerprintPropertyProvider()
    {
        return array(
            array(
                '966F4BA9569DE6F65E8253977CA74426BADEABD7',
                'public'
            ),
            array(
                '966F4BA9569DE6F65E8253977CA74426BADEABD7',
                'private'
            )
        );
    }

    /**
     * @dataProvider getUserIdsProvider
     */
    public function testGetUserIdsProperty($expected, $key)
    {
        $key_ob = $this->_getKey($key)->getUserIds();
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
                        'email' => 'My Name <me@example.com>'
                    )
                ),
                'public'
            ),
            array(
                array(
                    array(
                        'comment' => 'My Comment',
                        'email' => 'My Name <me@example.com>'
                    )
                ),
                'private'
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
            $this->_getKey($key)->getFingerprints()
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
                'public'
            ),
            array(
                array(
                    /* Key */
                    'BADEABD7' => '966F4BA9569DE6F65E8253977CA74426BADEABD7',
                    /* Subkey */
                    '9EF074A9' => 'F4248B3AC97C1F749555929C24ED29779EF074A9'
                ),
                'private'
            )
        );
    }

    /**
     * @dataProvider containsEmailProvider
     */
    public function testContainsEmailProvider($email, $key, $expected)
    {
        $key_ob = $this->_getKey($key);

        if ($expected) {
            $this->assertTrue($key_ob->containsEmail($email));
        } else {
            $this->assertFalse($key_ob->containsEmail($email));
        }
    }

    public function containsEmailProvider()
    {
        return array(
            array(
                'me@example.com',
                'public',
                true
            ),
            array(
                'foo@example.com',
                'public',
                false
            ),
            array(
                'me@example.com',
                'private',
                true
            ),
            array(
                'foo@example.com',
                'private',
                false
            )
        );
    }

    public function testCreateMimePart()
    {
        $key_ob = $this->_getKey('public');
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
     * @dataProvider unencryptKeyProvider
     */
    public function testUnecryptKey($passphrase, $expected)
    {
        $key = $this->_getKey('private');

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

            $this->assertFalse($key->encrypted);
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
                'Invalid Passphrase',
                false
            ),
            array(
                'Secret',
                true
            )
        );
    }

    protected function _getKey($key)
    {
        $class = ($key === 'public')
            ? 'Horde_Pgp_Element_PublicKey'
            : 'Horde_Pgp_Element_PrivateKey';
        return $class::create(
            file_get_contents(__DIR__ . '/fixtures/pgp_' . $key . '.asc')
        );
    }

}
