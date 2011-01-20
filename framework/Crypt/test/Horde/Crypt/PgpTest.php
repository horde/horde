<?php
/**
 * Horde_Crypt_Pgp tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package    Crypt
 * @subpackage UnitTests
 */

class Horde_Crypt_PgpTest extends PHPUnit_Framework_TestCase
{
    protected $_pgp;
    protected $_privkey;
    protected $_pubkey;

    protected function setUp()
    {
        if (!is_executable('/usr/bin/gpg')) {
            $this->markTestSkipped('GPG binary not found at /usr/bin/gpg.');
        }

        $this->_pgp = Horde_Crypt::factory('Pgp', array(
            'program' => '/usr/bin/gpg',
            'temp' => Horde_Util::getTempDir()
        ));

        $this->_privkey = file_get_contents(dirname(__FILE__) . '/fixtures/pgp_private.asc');
        $this->_pubkey = file_get_contents(dirname(__FILE__) . '/fixtures/pgp_public.asc');
    }

    public function testBug6601()
    {
        @date_default_timezone_set('GMT');

        $data = file_get_contents(dirname(__FILE__) . '/fixtures/bug_6601.asc');

        $this->assertEquals(
'Name:             Richard Selsky
Key Type:         Public Key
Key Creation:     04/11/08
Expiration Date:  [Never]
Key Length:       1024 Bytes
Comment:          [None]
E-Mail:           rselsky@bu.edu
Hash-Algorithm:   pgp-sha1
Key ID:           0xF3C01D42
Key Fingerprint:  5912 D91D 4C79 C670 1FFF  1486 04A6 7B37 F3C0 1D42

',
            $this->_pgp->pgpPrettyKey($data)
        );
    }

    // decrypt() message
    public function testPgpDecrypt()
    {
        // Encrypted data is in ISO-8859-1 format
        $crypt = file_get_contents(dirname(__FILE__) . '/fixtures/pgp_encrypted.txt');

        $decrypt = $this->_pgp->decrypt($crypt, array(
            'passphrase' => 'Secret',
            'privkey' => $this->_privkey,
            'pubkey' => $this->_pubkey,
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
        $crypt = file_get_contents(dirname(__FILE__) . '/fixtures/pgp_encrypted_symmetric.txt');

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
        $clear = file_get_contents(dirname(__FILE__) . '/fixtures/clear.txt');

        $out = $this->_pgp->encrypt($clear, array(
            'recips' => array('me@example.com' => $this->_pubkey),
            'type' => 'message'
        ));

        $this->assertStringMatchesFormat(
'-----BEGIN PGP MESSAGE-----
Version: GnuPG v%d.%d.%d (%s)

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
        $clear = file_get_contents(dirname(__FILE__) . '/fixtures/clear.txt');

        $out = $this->_pgp->encrypt($clear, array(
            'passphrase' => 'Secret',
            'symmetric' => true,
            'type' => 'message'
        ));

        $this->assertStringMatchesFormat(
'-----BEGIN PGP MESSAGE-----
Version: GnuPG v%d.%d.%d (%s)

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
            $this->_pgp->encryptedSymmetrically(file_get_contents(dirname(__FILE__) . '/fixtures/pgp_encrypted.txt'))
        );
        $this->assertTrue(
            $this->_pgp->encryptedSymmetrically(file_get_contents(dirname(__FILE__) . '/fixtures/pgp_encrypted_symmetric.txt'))
        );
    }

    public function testGenerateRevocation()
    {
        $this->assertStringMatchesFormat(
'-----BEGIN PGP PUBLIC KEY BLOCK-----
Version: GnuPG v%d.%d.%d (%s)
Comment: A revocation certificate should follow

%s
%s
=%s
-----END PGP PUBLIC KEY BLOCK-----',
            $this->_pgp->generateRevocation(
                $this->_privkey,
                'me@example.com',
                'Secret'
            )
        );
    }

    public function testGetSignersKeyID()
    {
        $this->assertEquals(
            'BADEABD7',
            $this->_pgp->getSignersKeyID(file_get_contents(dirname(__FILE__) . '/fixtures/pgp_signed.txt'))
        );
    }

    public function testParsePGPData()
    {
        $out = $this->_pgp->parsePGPData(file_get_contents(dirname(__FILE__) . '/fixtures/pgp_signed.txt'));

        $this->assertEquals(
            2,
            count($out)
        );

        $this->assertEquals(
            Horde_Crypt_Pgp::ARMOR_SIGNED_MESSAGE,
            $out[0]['type']
        );
        $this->assertEquals(
            17,
            count($out[0]['data'])
        );

        $this->assertEquals(
            Horde_Crypt_Pgp::ARMOR_SIGNATURE,
            $out[1]['type']
        );
        $this->assertEquals(
            7,
            count($out[1]['data'])
        );
    }


    public function testPgpPacketInformation()
    {
        $out = $this->_pgp->pgpPacketInformation($this->_pubkey);

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

        $out = $this->_pgp->pgpPacketInformation($this->_privkey);

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
        $out = $this->_pgp->pgpPacketSignature($this->_pubkey, 'me@example.com');

        $this->assertEquals(
            '7CA74426BADEABD7',
            $out['keyid']
        );

        $out = $this->_pgp->pgpPacketSignature($this->_privkey, 'me@example.com');

        $this->assertEquals(
            '7CA74426BADEABD7',
            $out['keyid']
        );

        $out = $this->_pgp->pgpPacketSignature($this->_privkey, 'foo@example.com');

        $this->assertArrayNotHasKey(
            'keyid',
            $out
        );
    }

    public function testPgpPacketSignatureByUidIndex()
    {
        $out = $this->_pgp->pgpPacketSignatureByUidIndex($this->_pubkey, 'id1');

        $this->assertEquals(
            '7CA74426BADEABD7',
            $out['keyid']
        );

        $out = $this->_pgp->pgpPacketSignatureByUidIndex($this->_privkey, 'id1');

        $this->assertEquals(
            '7CA74426BADEABD7',
            $out['keyid']
        );

        $out = $this->_pgp->pgpPacketSignatureByUidIndex($this->_privkey, 'id2');

        $this->assertArrayNotHasKey(
            'keyid',
            $out
        );
    }

    public function testPgpPrettyKey()
    {
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
Key Fingerprint:  966F 4BA9 569D E6F6 5E82  5397 7CA7 4426 BADE ABD7

',
            $this->_pgp->pgpPrettyKey($this->_pubkey)
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
Key Fingerprint:  966F 4BA9 569D E6F6 5E82  5397 7CA7 4426 BADE ABD7

',
            $this->_pgp->pgpPrettyKey($this->_privkey)
        );
    }

    public function pgpPublicKeyMIMEPart()
    {
        $mime_part = $this->_pgp->publicKeyMIMEPart($this->_pubkey);

        $this->assertEquals(
            'application/pgp-keys',
            $mime_part->getType()
        );

        $this->assertEquals(
'-----BEGIN PGP PUBLIC KEY BLOCK-----
Version: GnuPG v%d.%d.%d (GNU/Linux)

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
        $clear = file_get_contents(dirname(__FILE__) . '/fixtures/clear.txt');

        $out = $this->_pgp->encrypt($clear, array(
            'passphrase' => 'Secret',
            'privkey' => $this->_privkey,
            'pubkey' => $this->_pubkey,
            'type' => 'signature'
        ));

        $this->assertStringMatchesFormat(
'-----BEGIN PGP SIGNATURE-----
Version: GnuPG v%d.%d.%d (%s)

%s
%s
=%s
-----END PGP SIGNATURE-----',
            $out
        );

        $out = $this->_pgp->encrypt($clear, array(
            'passphrase' => 'Secret',
            'privkey' => $this->_privkey,
            'pubkey' => $this->_pubkey,
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
Version: GnuPG v%d.%d.%d (%s)

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
            file_get_contents(dirname(__FILE__) . '/fixtures/clear.txt'),
            array(
                'pubkey' => $this->_pubkey,
                'signature' => file_get_contents(dirname(__FILE__) . '/fixtures/pgp_signature.txt'),
                'type' => 'detached-signature'
            )
        );

        $this->assertTrue($out->result);

        $out = $this->_pgp->decrypt(
            file_get_contents(dirname(__FILE__) . '/fixtures/pgp_signed.txt'),
            array(
                'pubkey' => $this->_pubkey,
                'type' => 'signature'
            )
        );

        $this->assertTrue($out->result);

        $out = $this->_pgp->decrypt(
            file_get_contents(dirname(__FILE__) . '/fixtures/pgp_signed2.txt'),
            array(
                'pubkey' => $this->_pubkey,
                'type' => 'signature'
            )
        );

        $this->assertTrue($out->result);
    }

    public function testVerifyPassphrase()
    {
        $this->assertTrue(
            $this->_pgp->verifyPassphrase($this->_pubkey, $this->_privkey, 'Secret')
        );

        $this->assertFalse(
            $this->_pgp->verifyPassphrase($this->_pubkey, $this->_privkey, 'Wrong')
        );
    }

    public function testKeyserverRetrieve()
    {
        $this->_pgp->getPublicKeyserver('4DE5B969');
    }

    public function testKeyserverRetrieveByEmail()
    {
        $this->assertEquals(
            '4DE5B969',
            $this->_pgp->getKeyID('jan@horde.org')
        );
    }

}
