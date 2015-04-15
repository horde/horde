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
    public function testSignMimePart()
    {
        $part = new Horde_Mime_Part();
        $part->setType('text/plain');
        $part->setContents('Foo Bar.');

        $key = Horde_Pgp_Element_PrivateKey::create(
            file_get_contents(__DIR__ . '/fixtures/pgp_private_rsa.txt')
        )->getUnencryptedKey('Secret');

        $pgp_mime = new Horde_Pgp_Mime();
        $signed = $pgp_mime->signPart($part, $key);

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

        $result = $pgp_mime->verifyDetached(
            $signed['1']->toString(array(
                'canonical' => true,
                'headers' => true
            )),
            $detach_sig->getContents(),
            $key->getPublicKey()
        );

        $this->assertTrue(isset($result[0][1][0]));
        $this->assertInstanceOf(
            'OpenPGP_SignaturePacket',
            $result[0][1][0]
        );
    }

}
