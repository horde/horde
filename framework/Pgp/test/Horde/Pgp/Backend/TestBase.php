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
 * @package    Pgp
 * @subpackage UnitTests
 */

/**
 * Horde_Pgp backend tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2015-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pgp
 * @subpackage UnitTests
 */
abstract class Horde_Pgp_Backend_TestBase
extends Horde_Test_Case
{
    private $_pgp;

    /* Returns the list of backends to test. */
    abstract protected function _setUp();

    protected function setUp()
    {
        $this->_pgp = new Horde_Pgp(array(
            'backends' => $this->_setUp()
        ));
    }

    /**
     * @dataProvider generateKeyProvider
     */
    public function testGenerateKey($passphrase)
    {
        $key = $this->_pgp->generateKey(
            'Foo',
            'foo@example.com',
            array(
                'comment' => 'Sample Comment',
                'expire' => time() + 60,
                'keylength' => 512,
                'passphrase' => $passphrase
            )
        );

        $this->assertInstanceOf(
            'Horde_Pgp_Element_PrivateKey',
            $key
        );

        $this->assertTrue($key->containsEmail('foo@example.com'));

        if (is_null($passphrase)) {
            $this->assertFalse($key->encrypted);
        } else {
            $this->assertTrue($key->encrypted);
            $this->assertInstanceof(
                'Horde_Pgp_Element_PrivateKey',
                $key->getUnencryptedKey($passphrase)
            );
        }
    }

    public function generateKeyProvider()
    {
        return array(
            array(null),
            array('Secret')
        );
    }

    /**
     * @dataProvider decryptProvider
     */
    public function testDecrypt($encrypted, $privkey, $expected)
    {
        $result = $this->_pgp->decrypt($encrypted, $privkey);

        $this->assertInstanceOf('Horde_Pgp_Element_Message', $result);

        $out = $result->message->signatures();

        $this->assertEquals(
            $expected,
            $out[0][0]->data
        );
    }

    public function decryptProvider()
    {
        return array(
            array(
                $this->_getFixture('pgp_encrypted.txt'),
                $this->_getPrivateKey('pgp_private.asc', 'Secret'),
                /* This was encrypted with an extra EOL.*/
                $this->_getFixture('clear.txt') . "\n"
            ),
            array(
                $this->_getFixture('pgp_encrypted_rsa.txt'),
                $this->_getPrivateKey('pgp_private_rsa.txt', 'Secret'),
                $this->_getFixture('clear.txt')
            )
        );
    }

    public function testDecryptSymmetric()
    {
        $encrypted = $this->_getFixture('pgp_encrypted_symmetric.txt');

        /* Invalid passphrase. */
        try {
            $this->_pgp->decryptSymmetric(
                $encrypted,
                'Incorrect Secret'
            );

            $this->fail('Expecting Exception');
        } catch (Exception $e) {}

        /* Valid passphrase. */
        $result = $this->_pgp->decryptSymmetric(
            $encrypted,
            'Secret'
        );

        $this->assertInstanceOf(
            'Horde_Pgp_Element_Message',
            $result
        );

        $sigs = $result->message->signatures();

        $this->assertEquals(
            1,
            count($sigs)
        );

        $text = $sigs[0][0];

        $this->assertEquals(
            $this->_getFixture('clear.txt'),
            $text->data
        );
        $this->assertEquals(
            'clear.txt',
            $text->filename
        );
        $this->assertEquals(
            1155564523,
            $text->timestamp
        );
        $this->assertEquals(
            657,
            $text->size
        );
    }

    /**
     * @dataProvider verifyProvider
     */
    public function testVerify($data, $key)
    {
        $out = $this->_pgp->verify($data, $key);

        $this->assertEquals(
            1,
            count($out)
        );

        $this->assertInstanceOf(
            'OpenPGP_SignaturePacket',
            $out[0][1][0]
        );
    }

    public function verifyProvider()
    {
        return array(
            // DSA signature
            array(
                $this->_getFixture('pgp_signed.txt'),
                $this->_getFixture('pgp_public.asc')
            ),
            // RSA signature
            array(
                $this->_getFixture('pgp_signed2.txt'),
                $this->_getFixture('pgp_public_rsa.txt')
            )
        );
    }

    /**
     * @dataProvider verifyDetachedProvider
     */
    public function testVerifyDetached($data, $sig, $key)
    {
        $out = $this->_pgp->verifyDetached($data, $sig, $key);

        $this->assertEquals(
            1,
            count($out)
        );

        $this->assertInstanceOf(
            'OpenPGP_LiteralDataPacket',
            $out[0][0]
        );

        $this->assertInstanceOf(
            'OpenPGP_SignaturePacket',
            $out[0][1][0]
        );
    }

    public function verifyDetachedProvider()
    {
        return array(
            // DSA signature
            array(
                $this->_getFixture('clear.txt'),
                $this->_getFixture('pgp_signature.txt'),
                $this->_getFixture('pgp_public.asc')
            ),
            // RSA signature
            array(
                $this->_getFixture('clear.txt'),
                $this->_getFixture('pgp_signature2.txt'),
                $this->_getFixture('pgp_public_rsa.txt')
            )
        );
    }

    /**
     * @dataProvider encryptProvider
     * @depends testDecrypt
     */
    public function testEncrypt($pubkey, $privkeys)
    {
        $cleartext = $this->_getFixture('clear.txt');

        $result = $this->_pgp->encrypt($cleartext, $pubkey);

        $this->assertInstanceOf(
            'Horde_Pgp_Element_Message',
            $result
        );

        $this->assertEquals(
            1 + count($privkeys),
            count($result->message->packets)
        );

        foreach ($privkeys as $val) {
            $this->testDecrypt(strval($result), $val, $cleartext);
        }
    }

    public function encryptProvider()
    {
        return array(
            array(
                $this->_getFixture('pgp_public.asc'),
                array(
                    $this->_getPrivateKey('pgp_private.asc', 'Secret')
                ),
            ),
            array(
                $this->_getFixture('pgp_public_rsa.txt'),
                array(
                    $this->_getPrivateKey('pgp_private_rsa.txt', 'Secret')
                )
            ),
            array(
                array(
                    $this->_getFixture('pgp_public.asc'),
                    $this->_getFixture('pgp_public_rsa.txt')
                ),
                array(
                    $this->_getPrivateKey('pgp_private.asc', 'Secret'),
                    $this->_getPrivateKey('pgp_private_rsa.txt', 'Secret')
                )
            )
        );
    }

    /**
     * @dataProvider encryptSymmetricProvider
     * @depends testDecryptSymmetric
     */
    public function testEncryptSymmetric($compress, $cipher, $data, $pass)
    {
        $result = $this->_pgp->encryptSymmetric(
            $data,
            $pass,
            array(
                'cipher' => $cipher,
                'compress' => $compress
            )
        );

        $this->assertInstanceOf(
            'Horde_Pgp_Element_Message',
            $result
        );

        $this->assertEquals(
            1 + count($pass),
            count($result->message->packets)
        );

        foreach ($pass as $val) {
            $result2 = $this->_pgp->decryptSymmetric(
                $result,
                $val
            );
            $result2_sigs = $result2->message->signatures();

            $this->assertEquals(
                $data,
                $result2_sigs[0][0]->data
            );
        }
    }

    public function encryptSymmetricProvider()
    {
        $ciphers = array(
            '3DES', 'CAST5', 'AES128', 'AES192', 'AES256', 'Twofish'
        );
        $compress = array(
            'NONE', 'ZIP', 'ZLIB'
        );
        $fixtures = array(
            array(
                $this->_getFixture('clear.txt'),
                array(
                    'Secret'
                )
            ),
            array(
                $this->_getFixture('clear.txt'),
                array(
                    'Secret',
                    'Second Secret'
                )
            )
        );

        $data = array();
        foreach ($compress as $c1) {
            foreach ($ciphers as $c2) {
                foreach ($fixtures as $f) {
                    $data[] = array($c1, $c2, $f[0], $f[1]);
                }
            }
        }

        return $data;
    }

    /**
     * @dataProvider signProvider
     * @depends testVerify
     */
    public function testSign($text, $privkey)
    {
        $compress = array(
            'NONE', 'ZIP', 'ZLIB'
        );

        foreach ($compress as $c) {
            $result = $this->_pgp->sign(
                $text,
                $privkey,
                array(
                    'compress' => $c
                )
            );

            $this->assertInstanceOf(
                'Horde_Pgp_Element_Message',
                $result
            );

            $this->assertInstanceOf(
                ($c === 'NONE')
                    ? 'OpenPGP_SignaturePacket'
                    : 'OpenPGP_CompressedDataPacket',
                $result->message[0]
            );

            $this->testVerify(strval($result), $privkey->getPublicKey());
        }
    }

    /**
     * @dataProvider signProvider
     * @depends testVerify
     */
    public function testSignCleartext($text, $privkey)
    {
        $result = $this->_pgp->signCleartext($text, $privkey);

        $this->assertInstanceOf(
            'Horde_Pgp_Element_SignedMessage',
            $result
        );

        $this->testVerify(strval($result), $privkey->getPublicKey());
    }

    /**
     * @dataProvider signProvider
     * @depends testVerifyDetached
     */
    public function testSignDetached($text, $privkey)
    {
        $result = $this->_pgp->signDetached($text, $privkey);

        $this->assertInstanceOf(
            'Horde_Pgp_Element_Signature',
            $result
        );

        $this->testVerifyDetached(
            $text,
            strval($result),
            $privkey->getPublicKey()
        );
    }

    public function signProvider()
    {
        return array(
            array(
                $this->_getFixture('clear.txt'),
                $this->_getPrivateKey('pgp_private.asc', 'Secret')
            ),
            array(
                $this->_getFixture('clear.txt'),
                $this->_getPrivateKey('pgp_private_rsa.txt', 'Secret')
            )
        );
    }

    /* Helper methods. */

    protected function _getFixture($file)
    {
        return file_get_contents(dirname(__DIR__) . '/fixtures/' . $file);
    }

    protected function _getPrivateKey($file, $pass = null)
    {
        $pkey = Horde_Pgp_Element_PrivateKey::create(
            $this->_getFixture($file)
        );

        return is_null($pass)
            ? $pkey
            : $pkey->getUnencryptedKey($pass);
    }

}
