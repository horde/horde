<?php
/**
 * Horde_Crypt_Pgp tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2015-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Crypt
 * @subpackage UnitTests
 */
abstract class Horde_Crypt_Pgp_TestBase
extends Horde_Test_Case
{
    private $_language;
    private $_pgp;

    /* Returns the list of backends to test. */
    abstract protected function _setUp();

    protected function setUp()
    {
        $backends = $this->_setUp();

        @date_default_timezone_set('GMT');
        $this->_language = getenv('LANGUAGE');

        $this->_pgp = Horde_Crypt::factory('Pgp', array(
            'backends' => $backends
        ));
    }

    protected function tearDown()
    {
        putenv('LANGUAGE=' . $this->_language);
    }

    public function testBug6601()
    {
        $data = $this->_getFixture('bug_6601.asc');

        putenv('LANGUAGE=C');
        $this->assertEquals(
'Name:             Richard Selsky
Key Type:         Public Key
Key Creation:     04/11/08
Expiration Date:  04/11/13
Key Length:       1024 Bytes
Comment:          [None]
E-Mail:           rselsky@bu.edu
Hash-Algorithm:   pgp-sha1
Key ID:           0xF3C01D42
Key Fingerprint:  5912D91D4C79C6701FFF148604A67B37F3C01D42

',
            $this->_pgp->pgpPrettyKey($data)
        );
    }

    // decrypt() message
    public function testPgpDecrypt()
    {
        // Encrypted data is in ISO-8859-1 format
        $crypt = $this->_getFixture('pgp_encrypted.txt');

        $decrypt = $this->_pgp->decrypt($crypt, array(
            'passphrase' => 'Secret',
            'privkey' => $this->_getPrivateKey(),
            'pubkey' => $this->_getPublicKey(),
            'type' => 'message'
        ));

        $this->assertEquals(
'0123456789012345678901234567890123456789
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog. The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
0123456789012345678901234567890123456789
!"$§%&()=?^´°`+#-.,*\'_:;<>|~\{[]}

',
            Horde_String::convertCharset($decrypt->message, 'ISO-8859-1', 'UTF-8')
        );
    }

    public function testPgpDecryptSymmetric()
    {
        // Encrypted data is in ISO-8859-1 format
        $crypt = $this->_getFixture('pgp_encrypted_symmetric.txt');

        $decrypt = $this->_pgp->decrypt($crypt, array(
            'passphrase' => 'Secret',
            'type' => 'message'
        ));

        $this->assertEquals(
'0123456789012345678901234567890123456789
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog. The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
0123456789012345678901234567890123456789
!"$§%&()=?^´°`+#-.,*\'_:;<>|~\{[]}
',
            Horde_String::convertCharset($decrypt->message, 'ISO-8859-1', 'UTF-8')
        );
    }

    public function testPgpEncrypt()
    {
        $clear = $this->_getFixture('clear.txt');

        $out = $this->_pgp->encrypt($clear, array(
            'recips' => array('me@example.com' => $this->_getPublicKey()),
            'type' => 'message'
        ));

        $this->assertStringMatchesFormat(
'-----BEGIN PGP MESSAGE-----
Version: GnuPG %s

%s
%s
%s
%s
%s
%s
%s
%s
%s
%s
=%s
-----END PGP MESSAGE-----',
            $out
        );
    }

    public function testPgpEncryptSymmetric()
    {
        $clear = $this->_getFixture('clear.txt');

        $out = $this->_pgp->encrypt($clear, array(
            'passphrase' => 'Secret',
            'symmetric' => true,
            'type' => 'message'
        ));

        $this->assertStringMatchesFormat(
'-----BEGIN PGP MESSAGE-----
Version: GnuPG %s

%s
%s
%s
%s
=%s
-----END PGP MESSAGE-----',
            $out
        );
    }

    public function testPgpEncryptedSymmetrically()
    {
        $this->assertFalse(
            $this->_pgp->encryptedSymmetrically(
                $this->_getFixture('pgp_encrypted.txt')
            )
        );
        $this->assertTrue(
            $this->_pgp->encryptedSymmetrically(
                $this->_getFixture('pgp_encrypted_symmetric.txt')
            )
        );
    }

    public function testGetSignersKeyID()
    {
        $this->assertEquals(
            'BADEABD7',
            $this->_pgp->getSignersKeyID($this->_getFixture('pgp_signed.txt'))
        );
    }

    public function testPgpPacketInformation()
    {
        $out = $this->_pgp->pgpPacketInformation($this->_getPublicKey());

        $this->assertArrayHasKey(
            'public_key',
            $out
        );
        $this->assertArrayNotHasKey(
            'secret_key',
            $out
        );
        $this->assertEquals(
            '1155291888',
            $out['public_key']['created']
        );
        $this->assertEquals(
            2,
            count($out['signature'])
        );
        $this->assertEquals(
            '7CA74426BADEABD7',
            $out['keyid']
        );

        $out = $this->_pgp->pgpPacketInformation($this->_getPrivateKey());

        $this->assertArrayHasKey(
            'secret_key',
            $out
        );
        $this->assertArrayNotHasKey(
            'public_key',
            $out
        );
        $this->assertEquals(
            '1155291888',
            $out['secret_key']['created']
        );
        $this->assertEquals(
            2,
            count($out['signature'])
        );
        $this->assertEquals(
            '7CA74426BADEABD7',
            $out['keyid']
        );
    }

    public function testPgpPacketSignature()
    {
        $out = $this->_pgp->pgpPacketSignature(
            $this->_getPublicKey(),
            'me@example.com'
        );

        // Some older gpg versions, verified on 1.4.11, do not report the
        // keyid in the secret|public key packet.
        if (empty($out['keyid'])) {
            $this->markTestSkipped('keyid not provided by backend.');
        }
        $this->assertEquals(
            '7CA74426BADEABD7',
            $out['keyid']
        );

        $out = $this->_pgp->pgpPacketSignature(
            $this->_getPrivateKey(),
            'me@example.com'
        );

        $this->assertEquals(
            '7CA74426BADEABD7',
            $out['keyid']
        );

        $out = $this->_pgp->pgpPacketSignature(
            $this->_getPrivateKey(),
            'foo@example.com'
        );

        $this->assertArrayNotHasKey(
            'keyid',
            $out
        );
    }

    public function testPgpPacketSignatureByUidIndex()
    {
        $out = $this->_pgp->pgpPacketSignatureByUidIndex(
            $this->_getPublicKey(),
            'id1'
        );

        if (empty($out['keyid'])) {
            $this->markTestSkipped('keyid not provided by backend.');
        }

        $this->assertEquals(
            '7CA74426BADEABD7',
            $out['keyid']
        );

        $out = $this->_pgp->pgpPacketSignatureByUidIndex(
            $this->_getPrivateKey(),
            'id1'
        );

        $this->assertEquals(
            '7CA74426BADEABD7',
            $out['keyid']
        );

        $out = $this->_pgp->pgpPacketSignatureByUidIndex(
            $this->_getPrivateKey(),
            'id2'
        );

        $this->assertArrayNotHasKey(
            'keyid',
            $out
        );
    }

    public function testPgpPrettyKey()
    {
        putenv('LANGUAGE=C');

        $this->assertEquals(
'Name:             My Name
Key Type:         Public Key
Key Creation:     08/11/06
Expiration Date:  [Never]
Key Length:       1024 Bytes
Comment:          My Comment
E-Mail:           me@example.com
Hash-Algorithm:   pgp-sha1
Key ID:           0xBADEABD7
Key Fingerprint:  966F4BA9569DE6F65E8253977CA74426BADEABD7

',
            $this->_pgp->pgpPrettyKey($this->_getPublicKey())
        );

        $this->assertEquals(
'Name:             My Name
Key Type:         Private Key
Key Creation:     08/11/06
Expiration Date:  [Never]
Key Length:       1024 Bytes
Comment:          My Comment
E-Mail:           me@example.com
Hash-Algorithm:   pgp-sha1
Key ID:           0xBADEABD7
Key Fingerprint:  966F4BA9569DE6F65E8253977CA74426BADEABD7

',
            $this->_pgp->pgpPrettyKey($this->_getPrivateKey())
        );
    }

    /**
     * @dataProvider pgpGetFingerprintsFromKeyProvider
     */
    public function testPgpGetFingerprintsFromKey($expected, $key)
    {
        $this->assertEquals(
            $expected,
            $this->_pgp->getFingerprintsFromKey($key)
        );
    }

    public function pgpGetFingerprintsFromKeyProvider()
    {
        return array(
            array(
                array(
                    '0xBADEABD7' => '966F4BA9569DE6F65E8253977CA74426BADEABD7'
                ),
                $this->_getPublicKey()
            ),
            array(
                array(
                    '0xBADEABD7' => '966F4BA9569DE6F65E8253977CA74426BADEABD7'
                ),
                $this->_getPrivateKey()
            )
        );
    }

    public function pgpPublicKeyMIMEPart()
    {
        $mime_part = $this->_pgp->publicKeyMIMEPart($this->_getPublicKey());

        $this->assertEquals(
            'application/pgp-keys',
            $mime_part->getType()
        );

        $this->assertEquals(
'-----BEGIN PGP PUBLIC KEY BLOCK-----
Version: GnuPG %s

mQGiBETcWvARBADNitbvsWy5/hhV+WcU2ttmtXkAj2DqJVgJdGS2RH8msO0roG5j
CQK/e0iMJki5lfdgxvxxWgStYMnfF5ftgWA7JV+BZUzJt12Lobm0zdENv2TqL2vc
xlPTmEGsvfPDTbY+Gr3jvuODboXat7bUn2E723WXPdh2A7KNNnLou7JF2wCgiKs/
RqNKM/Zm01PxLbQ+rs9ghd0D/jLUfJeYWySoDsvfO8e4UyDxDVTBLkkdw3XzLx1F
4SS/Cc2Z9yJuXiepzSH/G/vhdN5ROv12kJwA4FbwsFv5C1uCQleWiPngFixca9Nw
lAd2X2Cp0/4D2XRq1M9dEbcYdrgAuyzt2ZToj3aFaYNGwjfHoLqSngOu6/d3KD1d
i0b2A/9wnXo41kPwS73gU1Un2KKMkKqnczCQHdpopO6NjKaLhNcouRauLrgbrS5y
A1CW+nxjkKVvWrP/VFBmapUpjE1C51J9P0/ub8tRr7H0xHdTQyufv01lmfkjUpVF
n3GVf95l4seBFzD7r580aTx+dJztoHEGWrsWZTNJwo6IIlFOIbQlTXkgTmFtZSAo
TXkgQ29tbWVudCkgPG1lQGV4YW1wbGUuY29tPohgBBMRAgAgBQJE3FrwAhsjBgsJ
CAcDAgQVAggDBBYCAwECHgECF4AACgkQfKdEJrreq9fivACeLBcWErSQU4ZGQsje
dhwfdst9cLQAnRETpwmqt/XvcGFVsOE28MUrUzOGuQENBETcWvAQBADNgIJ4nDlf
gBOI/iqyNy08C9+MjxrqMylrj4TPn3rRk8wySr2myX+j9CML5EHOtsxANYeI9u7h
OF11n5Z8fDht/WGJrNR7EtRhNN6mkKsPaEuO3fhz6EgwJYReUVzDJbvnV2fRCvQo
EGaSntZGQaQzIzIL+/gMEFpEVRK1P2I3VwADBQP+K2Rmmkm3DonXFlUUDWWdhEF4
b7fy5/IPj41PSSOdo0IP4dprFoe15Vs9gWOYvzcnjy+BbOwhVwsjE3F36hf04od3
uTSM0dLS/xmpSvgbBh181T5c3W5aKT/daVhyxXJ4csxE+JCVKbaBubne0DPEuyZj
rYlL5Lm0z3VhNCcR0LyISQQYEQIACQUCRNxa8AIbDAAKCRB8p0Qmut6r16Y3AJ9h
umO5uT5yDcir3zwqUAxzBAkE4ACcCtGfb6usaTKnNXo+ZuLoHiOwIE4=
=GCjU
-----END PGP PUBLIC KEY BLOCK-----',
            $mime_part->getContents()
        );
    }

    public function testPgpSign()
    {
        $clear = $this->_getFixture('clear.txt');

        $out = $this->_pgp->encrypt($clear, array(
            'passphrase' => 'Secret',
            'privkey' => $this->_getPrivateKey(),
            'pubkey' => $this->_getPublicKey(),
            'type' => 'signature'
        ));

        $this->assertStringMatchesFormat(
'-----BEGIN PGP SIGNATURE-----
Version: GnuPG %s

%s
%s
=%s
-----END PGP SIGNATURE-----',
            $out
        );

        $out = $this->_pgp->encrypt($clear, array(
            'passphrase' => 'Secret',
            'privkey' => $this->_getPrivateKey(),
            'pubkey' => $this->_getPublicKey(),
            'sigtype' => 'cleartext',
            'type' => 'signature'
        ));

        $this->assertStringMatchesFormat(
'-----BEGIN PGP SIGNED MESSAGE-----
Hash: SHA1

0123456789012345678901234567890123456789
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog. The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
The quick brown fox jumps over the lazy dog.
0123456789012345678901234567890123456789
!"$§%&()=?^´°`+#-.,*\'_:;<>|~\{[]}
-----BEGIN PGP SIGNATURE-----
Version: GnuPG %s

%s
%s
=%s
-----END PGP SIGNATURE-----',
            Horde_String::convertCharset($out, 'ISO-8859-1', 'UTF-8')
        );
    }

    public function testDecryptSignature()
    {
        date_default_timezone_set('GMT');

        $out = $this->_pgp->decrypt(
            $this->_getFixture('clear.txt'),
            array(
                'pubkey' => $this->_getPublicKey(),
                'signature' => $this->_getFixture('pgp_signature.txt'),
                'type' => 'detached-signature'
            )
        );

        $this->assertNotEmpty($out->result);

        $out = $this->_pgp->decrypt(
            $this->_getFixture('pgp_signed.txt'),
            array(
                'pubkey' => $this->_getPublicKey(),
                'type' => 'signature'
            )
        );

        $this->assertNotEmpty($out->result);

        $out = $this->_pgp->decrypt(
            $this->_getFixture('pgp_signed2.txt'),
            array(
                'pubkey' => $this->_getPublicKey(),
                'type' => 'signature'
            )
        );

        $this->assertNotEmpty($out->result);
    }

    public function testVerifyPassphrase()
    {
        $this->assertTrue(
            $this->_pgp->verifyPassphrase(
                $this->_getPublicKey(),
                $this->_getPrivateKey(),
                'Secret'
            )
        );

        $this->assertFalse(
            $this->_pgp->verifyPassphrase(
                $this->_getPublicKey(),
                $this->_getPrivateKey(),
                'Wrong'
            )
        );
    }

    public function testGetPublicKeyFromPrivateKey()
    {
        $this->assertNotNull(
            $this->_pgp->getPublicKeyFromPrivateKey($this->_getPrivateKey())
        );
    }

    /* Helper methods. */

    protected function _getFixture($file)
    {
        return file_get_contents(dirname(__DIR__) . '/fixtures/' . $file);
    }

    protected function _getPrivateKey()
    {
        return $this->_getFixture('pgp_private.asc');
    }

    protected function _getPublicKey()
    {
        return $this->_getFixture('pgp_public.asc');
    }

}
