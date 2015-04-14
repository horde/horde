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
 * Horde_Pgp backend tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2015 Horde LLC
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
     * @dataProvider encryptProvider
     */
    public function testEncrypt($key)
    {
        $result = $this->_pgp->encrypt(
            $this->_getFixture('clear.txt'),
            $key
        );

        $this->assertInstanceOf('Horde_Pgp_Element_Message', $result);
    }

    public function encryptProvider()
    {
        return array(
            // TODO: Requires Elgamal encryption
            // array($this->_getFixture('pgp_public.asc')),
            array($this->_getFixture('pgp_public_rsa.txt'))
        );
    }

    /**
     * @depends testDecryptSymmetric
     */
    public function testEncryptSymmetric()
    {
        $result = $this->_pgp->encryptSymmetric(
            $this->_getFixture('clear.txt'),
            'Secret'
        );

        $this->assertInstanceOf('Horde_Pgp_Element_Message', $result);
    }

    /**
     * @dataProvider signProvider
     */
    public function testSign($text, $key, $pass)
    {
        $result = $this->_pgp->sign(
            $text,
            Horde_Pgp_Element_PrivateKey::create($key)
                ->getUnencryptedKey($pass)
        );

        $this->assertInstanceOf('Horde_Pgp_Element_Message', $result);
    }

    /**
     * @dataProvider signProvider
     */
    public function testSignCleartext($text, $key, $pass)
    {
        $result = $this->_pgp->signCleartext(
            $text,
            Horde_Pgp_Element_PrivateKey::create($key)
                ->getUnencryptedKey($pass)
        );

        $this->assertInstanceOf('Horde_Pgp_Element_SignedMessage', $result);
    }

    /**
     * @dataProvider signProvider
     */
    public function testSignDetached($text, $key, $pass)
    {
        $result = $this->_pgp->signDetached(
            $text,
            Horde_Pgp_Element_PrivateKey::create($key)
                ->getUnencryptedKey($pass)
        );

        $this->assertInstanceOf('Horde_Pgp_Element_Signature', $result);
    }

    public function signProvider()
    {
        return array(
            array(
                $this->_getFixture('clear.txt'),
                $this->_getFixture('pgp_private.asc'),
                'Secret'
            ),
            array(
                $this->_getFixture('clear.txt'),
                $this->_getFixture('pgp_private_rsa.txt'),
                'Secret'
            )
        );
    }

    /**
     * @dataProvider decryptProvider
     */
    public function testDecrypt($encrypted, $key, $pass, $expected)
    {
        $result = $this->_pgp->decrypt(
            $encrypted,
            Horde_Pgp_Element_PrivateKey::create($key)
                ->getUnencryptedKey($pass)
        );

        $this->assertInstanceOf('Horde_Pgp_Element_Message', $result);
    }

    public function decryptProvider()
    {
        return array(
            /* TODO: Requires Elgamal encryption.
            array(
                $this->_getFixture('pgp_encrypted.txt'),
                $this->_getFixture('pgp_private.asc'),
                'Secret',
                $this->_getFixture('clear.txt')
            ),
             */
            array(
                $this->_getFixture('pgp_encrypted_rsa.txt'),
                $this->_getFixture('pgp_private_rsa.txt'),
                'Secret',
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
            'OpenPGP_LiteralDataPacket',
            $out[0][0]
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

    /* Helper methods. */

    protected function _getFixture($file)
    {
        return file_get_contents(dirname(__DIR__) . '/fixtures/' . $file);
    }

}
