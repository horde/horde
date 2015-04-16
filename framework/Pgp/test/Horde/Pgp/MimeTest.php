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
 * Tests for PGP MIME part encryption/signing.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2015 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pgp
 * @subpackage UnitTests
 */
class Horde_Pgp_MimeTest extends PHPUnit_Framework_TestCase
{
    private $key;
    private $part;
    private $pgp_mime;

    protected function setUp()
    {
        $this->part = new Horde_Mime_Part();
        $this->part->setType('text/plain');
        $this->part->setContents('Foo Bar.');

        $this->key = Horde_Pgp_Element_PrivateKey::create(
            file_get_contents(__DIR__ . '/fixtures/pgp_private_rsa.txt')
        )->getUnencryptedKey('Secret');

        $this->pgp_mime = new Horde_Pgp_Mime();
    }

    public function testSignPart()
    {
        $signed = $this->pgp_mime->signPart($this->part, $this->key);

        $this->assertEquals(
            'multipart/signed',
            $signed->getType()
        );

        $this->assertEquals(
            'application/pgp-signature',
            $signed->getContentTypeParameter('protocol')
        );

        $this->assertEquals(
            'pgp-sha256',
            $signed->getContentTypeParameter('micalg')
        );

        $this->assertEquals(
            2,
            count($signed->getParts())
        );

        $signed->buildMimeIds();
        $detach_sig = $signed['2'];

        $this->assertEquals(
            'application/pgp-signature',
            $detach_sig->getType()
        );

        $this->assertStringStartsWith(
            '-----BEGIN PGP SIGNATURE-----',
            $detach_sig->getContents()
        );

        $result = $this->pgp_mime->verifyDetached(
            $signed['1']->toString(array(
                'canonical' => true,
                'headers' => true
            )),
            $detach_sig->getContents(),
            $this->key->getPublicKey()
        );

        $this->assertTrue(isset($result[0][1][0]));
        $this->assertInstanceOf(
            'OpenPGP_SignaturePacket',
            $result[0][1][0]
        );
    }

    public function testEncryptPart()
    {
        $encrypted = $this->pgp_mime->encryptPart(
            $this->part,
            $this->key->getPublicKey()
        );

        $this->_testEncryptPart($encrypted);
    }

    protected function _testEncryptPart($encrypted)
    {
        $this->assertEquals(
            'multipart/encrypted',
            $encrypted->getType()
        );

        $this->assertEquals(
            'application/pgp-encrypted',
            $encrypted->getContentTypeParameter('protocol')
        );

        $this->assertEquals(
            2,
            count($encrypted->getParts())
        );

        $encrypted->buildMimeIds();
        $version = $encrypted['1'];
        $data = $encrypted['2'];

        $this->assertEquals(
            'application/pgp-encrypted',
            $version->getType()
        );

        $this->assertEquals(
            'Version: 1',
            rtrim($version->getContents())
        );

        $this->assertEquals(
            'application/octet-stream',
            $data->getType()
        );

        $this->assertStringStartsWith(
            '-----BEGIN PGP MESSAGE-----',
            $data->getContents()
        );

        $result = $this->pgp_mime->decrypt(
            $data->toString(array(
                'canonical' => true,
                'headers' => true
            )),
            $this->key
        );

        $this->assertInstanceOf(
            'Horde_Pgp_Element_Message',
            $result
        );
    }

    public function testSignAndEncryptPart()
    {
        $result = $this->pgp_mime->signAndEncryptPart(
            $this->part,
            $this->key,
            $this->key->getPublicKey()
        );

        $this->_testEncryptPart($result);
    }

}
