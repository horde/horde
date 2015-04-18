<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */

/**
 * PGP backend that uses the openpgp-php library.
 *
 * NOTE: This class is NOT intended to be accessed outside of this package.
 * There is NO guarantees that the API of this class will not change across
 * versions.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      https://github.com/singpolyma/openpgp-php/
 * @package   Pgp
 */
class Horde_Pgp_Backend_Openpgp
extends Horde_Pgp_Backend
{
    /**
     * Autoload necessary libraries.
     */
    static public function autoload()
    {
        /* Ensure the openpgp-php libraries are autoloaded. */
        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require_once __DIR__ . '/vendor/autoload.php';
        } else {
            require_once __DIR__ . '/../../../../bundle/vendor/autoload.php';
        }
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        self::autoload();
    }

    /**
     */
    public function generateKey($opts)
    {
        $skey = $this->_generateSecretKeyPacket(
            $opts['keylength'],
            'OpenPGP_SecretKeyPacket'
        );

        $id = new Horde_Mail_Rfc822_Address($opts['email']);
        if (strlen($opts['comment'])) {
            $id->comment[] = $opts['comment'];
        }
        if (strlen($opts['name'])) {
            $id->personal = $opts['name'];
        }

        /* This is the private key we are creating. */
        $key = new OpenPGP_Message(array(
            $skey,
            new OpenPGP_UserIDPacket(
                $id->writeAddress(array('comment' => true))
            )
        ));

        $rsa = OpenPGP_Crypt_RSA::convert_private_key($skey);
        $rsa->setHash(Horde_String::lower($opts['hash']));
        $rsa_sign_func = array(
            'RSA' => array(
                $opts['hash'] => function($data) use($rsa) {
                    return array($rsa->sign($data));
                }
            )
        );

        /* Create signature packet. */
        $sig = new OpenPGP_SignaturePacket($key, 'RSA', $opts['hash']);
        /* "Generic certification of a User ID and Public-Key packet." */
        $sig->signature_type = 0x10;

        /* Add subpacket information. */
        $sig->hashed_subpackets[] = new OpenPGP_SignaturePacket_KeyFlagsPacket(
            array(0x03)
        );

        $sig->hashed_subpackets[] = new OpenPGP_SignaturePacket_PreferredSymmetricAlgorithmsPacket(
            // AES-256, AES-192, AES-128, 3DES
            array(0x09, 0x08, 0x07, 0x02)
        );

        $sig->hashed_subpackets[] = new OpenPGP_SignaturePacket_PreferredHashAlgorithmsPacket(
            // SHA256, SHA384, SHA512, SHA224, SHA-1
            array(0x08, 0x09, 0x0a, 0x0b, 0x02)
        );

        $sig->hashed_subpackets[] = new OpenPGP_SignaturePacket_PreferredCompressionAlgorithmsPacket(
            // ZLIB, ZIP
            array(0x02, 0x01)
        );

        $ks_prefs = new OpenPGP_SignaturePacket_KeyServerPreferencesPacket();
        $ks_prefs->no_modify = true;
        $sig->hashed_subpackets[] = $ks_prefs;

        $sig->hashed_subpackets[] = new OpenPGP_SignaturePacket_FeaturesPacket(
            // 1 = Supports modification detection (packets 18 and 19)
            array(0x01)
        );
        if (isset($opts['expire'])) {
            $sig->hashed_subpackets[] = new OpenPGP_SignaturePacket_KeyExpirationTimePacket(
                $opts['expire'] - time()
            );
        }

        $sig->unhashed_subpackets[] = new OpenPGP_SignaturePacket_IssuerPacket(
            substr($skey->fingerprint, -16)
        );

        $key[] = $sig;

        /* Create self-signature. */
        $sig->sign_data($rsa_sign_func);

        /* OpenPGP currently (as of April 2015) encrypts passphrases w/
         * AES-128 & SHA-1, so use this strategy. */
        if (strlen($opts['passphrase'])) {
            $cipher = new Crypt_AES(CRYPT_AES_MODE_CFB);
            $cipher->setKeyLength(128);

            $s2k = new OpenPGP_S2K(crypt_random_string(8), 2);
            $cipher->setKey($s2k->make_key($opts['passphrase'], 16));

            $iv = crypt_random_string(16);

            $this->_encryptPrivateKey($skey, $cipher, $s2k, $iv);
        }

        /* Encryption subkey. See RFC 4880 [5.5.1.2] (by convention, top-level
         * key is used for signing and subkeys are used for encryption) */
        $ekey = $this->_generateSecretKeyPacket(
            $opts['keylength'],
            'OpenPGP_SecretSubkeyPacket'
        );

        /* Computing signature: RFC 4880 [5.2.4] */
        $sig = new OpenPGP_SignaturePacket(
            implode('', $skey->fingerprint_material()) .
            implode('', $ekey->fingerprint_material()),
            'RSA',
            $opts['hash']
        );

        /* This is a "Subkey Binding Signature". */
        $sig->signature_type = 0x18;
        $sig->hashed_subpackets[] = new OpenPGP_SignaturePacket_KeyFlagsPacket(
            array(0x0C)
        );
        $sig->unhashed_subpackets[] = new OpenPGP_SignaturePacket_IssuerPacket(
            substr($skey->fingerprint, -16)
        );
        $sig->sign_data($rsa_sign_func);
        if (strlen($opts['passphrase'])) {
            $this->_encryptPrivateKey($ekey, $cipher, $s2k, $iv);
        }

        $key[] = $ekey;
        $key[] = $sig;

        return new Horde_Pgp_Element_PrivateKey($key);
    }

    /**
     * Generate a RSA secret key (sub)packet.
     *
     * @param integer $keylength   RSA keylength.
     * @param string $packet_type  Secret key packet to create.
     *
     * @return OpenPGP_SecretKeyPacket  Secret key packet object.
     */
    protected function _generateSecretKeyPacket($keylength, $packet_type)
    {
        $rsa = new Crypt_RSA();
        $k = $rsa->createKey($keylength);
        $rsa->loadKey($k['privatekey']);

        return new $packet_type(array(
            'n' => $rsa->modulus->toBytes(),
            'e' => $rsa->publicExponent->toBytes(),
            'd' => $rsa->exponent->toBytes(),
            'p' => $rsa->primes[1]->toBytes(),
            'q' => $rsa->primes[2]->toBytes(),
            'u' => $rsa->coefficients[2]->toBytes()
        ));
    }

    /**
     * Encrypt a secret key packet.
     *
     * @param OpenPGP_SecretKeyPacket $p  Secret key packet.
     * @param Crypt_RSA $cipher           RSA cipher object.
     * @param OpenPGP_S2K $s2k            OpenPGP String-to-key object.
     * @param string $iv                  Initial vector.
     */
    protected function _encryptPrivateKey($p, $cipher, $s2k, $iv)
    {
        $cipher->setIV($iv);

        $secret = '';
        foreach ($p::$secret_key_fields[$p->algorithm] as $f) {
            $f = $p->key[$f];
            $secret .= pack('n', OpenPGP::bitlength($f)) . $f;
        }
        $secret .= hash('sha1', $secret, true);

        $p->encrypted_data = $iv . $cipher->encrypt($secret);
        $p->s2k = $s2k;
        $p->s2k_useage = 254;
        $p->symmetric_algorithm = 7;
    }

    /**
     */
    public function encrypt($text, $keys, $opts)
    {
        $p = array();

        foreach ($keys as $val) {
            /* Search for the key flag indicating that a key may be used to
             * encrypt communications (RFC 4880 [5.2.3.21]). In the absence
             * of finding this flag, use the first subkey and, in the absence
             * of that, use the main key. */
            $current = null;
            $sub = false;

            foreach ($val->getKeyList() as $val2) {
                foreach (array('hashed', 'unhashed') as $k) {
                    foreach ($val2->signature->{$k . '_subpackets'} as $val3) {
                        if ($val3 instanceof OpenPGP_SignaturePacket_KeyFlagsPacket) {
                            foreach ($val3->flags as $val4) {
                                if ($val4 & 0x04) {
                                    $current = $val2->key;
                                    break 4;
                                }
                            }
                        }
                    }
                }

                if (is_null($current)) {
                    $current = $val2->key;
                } elseif (!$sub &&
                          ($val2->key instanceof OpenPGP_PublicSubkeyPacket) ||
                          ($val2->key instanceof OpenPGP_SecretSubkeyPacket)) {
                    $current = $val2->key;
                    $sub = true;
                }
            }

            $p[] = $current;

            /* Use 3DES with ElGamal; 3DES is a MUST implement, so assume that
             * someone requiring ElGamal encryption will more likely have
             * support for 3DES than AES. */
            if ($current->algorithm === 16) {
                $opts['cipher'] = 2;
            }
        }

        return $this->_encrypt($p, $text, $opts);
    }

    /**
     */
    public function encryptSymmetric($text, $passphrase, $opts)
    {
        return $this->_encrypt($passphrase, $text, $opts);
    }

    /**
     * Encrypt data.
     *
     * @param mixed $key         The list of public keys used to encrypt or a
     *                           list of passphrases.
     * @param mixed $data        The data to be PGP encrypted.
     * @param array $opts  Additional options:
     *   - cipher: (integer) Cipher algorithm.
     *   - compress: (integer) Compression algorithm.
     *
     * @param Horde_Pgp_Element_Message  Encrypted message.
     */
    protected function _encrypt($key, $data, $opts)
    {
        $msg = $this->_compressMessageOb(
            $this->_getMessageOb($data),
            $opts['compress']
        );

        /* Following code adapted from OpenPGP_Crypt_Symmetric::encrypt(). */

        list($cipher, $key_bytes, $block_bytes) =
            OpenPGP_Crypt_Symmetric::getCipher($opts['cipher']);
        $prefix = crypt_random_string($block_bytes);
        $prefix .= substr($prefix, -2);

        $to_encrypt = $prefix . $msg->to_bytes();

        $mdc = new OpenPGP_ModificationDetectionCodePacket(
            hash('sha1', $to_encrypt . "\xD3\x14", true)
        );

        /* This is the symmetric encryption session key. */
        $ckey = crypt_random_string($key_bytes);
        $cipher->setKey($ckey);

        /* This is the symmetrically encrypted version of plaintext. */
        $encrypted = array(
            new OpenPGP_IntegrityProtectedDataPacket(
                $cipher->encrypt($to_encrypt . $mdc->to_bytes())
            )
        );

        /* Now we need to encrypt the symmetric session key into the various
         * session key encrypted entities. */
        foreach ($key as $k) {
            /* Symmetric encryption. */
            if (is_string($k)) {
                $s2k = new OpenPGP_S2K(crypt_random_string(8, 2)); // SHA-1
                $cipher->setKey($s2k->make_key($k, $key_bytes));

                $encrypted[] = new OpenPGP_SymmetricSessionKeyPacket(
                    $s2k,
                    $cipher->encrypt(chr($opts['cipher']) . $ckey),
                    $opts['cipher']
                );
                continue;
            }

            /* Public key encryption. */
            switch ($k->algorithm) {
            case 1:
            case 2:
            case 3:
                $rsa = new OpenPGP_Crypt_RSA($k);
                $pk = $rsa->public_key();
                $pk->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
                break;

            case 16:
                $pk = new Horde_Pgp_Crypt_Elgamal($k);
                break;
            }

            $pk_encrypt = $pk->encrypt(
                chr($opts['cipher']) .
                $ckey .
                pack('n', OpenPGP_Crypt_Symmetric::checksum($ckey))
            );

            $esk = array();
            foreach ((is_array($pk_encrypt) ? $pk_encrypt : array($pk_encrypt)) as $val) {
                $esk[] = pack('n', OpenPGP::bitlength($val)) . $val;
            }

            $encrypted[] = new OpenPGP_AsymmetricSessionKeyPacket(
                $k->algorithm,
                $k->fingerprint(),
                implode('', $esk)
            );
        }

        return new Horde_Pgp_Element_Message(
            new OpenPGP_Message(array_reverse($encrypted))
        );
    }

    /**
     * Create a OpenPGP message object, inserting text data into a literal
     * data packet.
     *
     * @param mixed $data  Data.
     *
     * @return OpenPGP_Message  Message object.
     */
    protected function _getMessageOb($data)
    {
        if (!($data instanceof OpenPGP_Message)) {
            if (!($data instanceof OpenPGP_Packet)) {
                $data = new OpenPGP_LiteralDataPacket(
                    $data,
                    array('format' => 'u')
                );
            }
            $data = new OpenPGP_Message(array($data));
        }

        return $data;
    }

    /**
     * Compress PGP data, if compression is available.
     *
     * @param OpenPGP_Message $msg  PGP message.
     * @param integer $algo         Compression algorithm.
     *
     * @return OpenPGP_Message  (Possibly compressed) message.
     */
    protected function _compressMessageOb($msg, $algo)
    {
        if ($algo && Horde_Util::extensionExists('zlib')) {
            $zip = new OpenPGP_CompressedDataPacket($msg);
            $zip->algorithm = $algo;
            $msg = new OpenPGP_Message(array($zip));
        }

        return $msg;
    }

    /**
     */
    public function sign($text, $key, $mode, $opts = array())
    {
        $rsa = new OpenPGP_Crypt_RSA($key->message);
        $pkey = $rsa->key();

        $text = $this->_getMessageOb($text)->packets[0];

        switch ($pkey->algorithm) {
        case 1:
        case 2:
        case 3:
            // RSA
            $hash = $opts['sign_hash'] ?: 'SHA256';
            $result = $rsa->sign($text, $hash);
            break;

        case 17:
            // DSA; use SHA1 by default, since that is what DSA/DSS was
            // designed for.
            $hash = $opts['sign_hash'] ?: 'SHA1';
            $sig = new OpenPGP_SignaturePacket($text, 'DSA', $hash);
            $sig->hashed_subpackets[] = new OpenPGP_SignaturePacket_IssuerPacket(
                substr($pkey->fingerprint, -16)
            );

            $dsa = new Horde_Pgp_Crypt_DSA($pkey);

            $sig->sign_data(array(
                'DSA' => array(
                    $hash => function ($data) use ($dsa, $hash) {
                        return $dsa->sign($data, $hash);
                    }
                )
            ));

            $result = new OpenPGP_Message(array($sig, $text));
            break;
        }

        switch ($mode) {
        case 'clear':
            $sm = new Horde_Pgp_Element_SignedMessage(
                new OpenPGP_Message(array($result[1], $result[0]))
            );
            $sm->headers['Hash'] = $hash;
            return $sm;

        case 'detach':
            foreach ($result as $val) {
                if ($val instanceof OpenPGP_SignaturePacket) {
                    return new Horde_Pgp_Element_Signature(
                        new OpenPGP_Message(array($val))
                    );
                }
            }
            break;

        case 'message':
            return new Horde_Pgp_Element_Message(
                $this->_compressMessageOb($result, $opts['compress'])
            );
        }
    }

    /**
     */
    public function decrypt($msg, $key)
    {
        $decryptor = new OpenPGP_Crypt_RSA($key->message);
        $elgamal = null;

        foreach ($msg->message as $val) {
            if ($val instanceof OpenPGP_AsymmetricSessionKeyPacket) {
                $pkey = $decryptor->key($val->keyid);
                if (!($pkey instanceof OpenPGP_PublicKeyPacket)) {
                    continue;
                }

                switch ($pkey->algorithm) {
                case 1:
                case 2:
                    return new Horde_Pgp_Element_Message(
                        $decryptor->decrypt($msg->message)
                    );

                case 16:
                    $elgamal = new Horde_Pgp_Crypt_Elgamal($pkey);

                    /* Put encrypted data into a packet object to take
                     * advantage of built-in MPI read methods. */
                    $edata = new OpenPGP_Packet();
                    $edata->input = $val->encrypted_data;
                    $sk_data = $elgamal->decrypt(
                        $edata->read_mpi() . $edata->read_mpi()
                    );

                    $sk = substr($sk_data, 1, strlen($sk_data) - 3);
                    /* Last 2 bytes are checksum */
                    $chk = unpack('n', substr($sk_data, -2));
                    $chk = reset($chk);

                    $sk_chk = 0;
                    for ($i = 0, $j = strlen($sk); $i < $j; ++$i) {
                        $sk_chk = ($sk_chk + ord($sk[$i])) % 65536;
                    }

                    if ($sk_chk != $chk) {
                        throw new RuntimeException();
                    }

                    return new Horde_Pgp_Element_Message(
                        OpenPGP_Crypt_Symmetric::decryptPacket(
                            OpenPGP_Crypt_Symmetric::getEncryptedData(
                                $msg->message
                            ),
                            /* Symmetric algorithm identifer */
                            ord($sk_data[0]),
                            /* Session secret key */
                            $sk
                        )
                    );
                }
            }
        }

        throw new RuntimeException();
    }

    /**
     */
    public function decryptSymmetric($msg, $passphrase)
    {
        $decrypted = OpenPGP_Crypt_Symmetric::decryptSymmetric(
            $passphrase,
            $msg->message
        );

        if (!is_null($decrypted)) {
            /* It is possible data could be decrypted to junk PGP data
             * ($decrypted != NULL). Search for valid packets if $decrypted is
             * returned. */
            foreach ($decrypted as $val) {
                switch (get_class($val)) {
                case 'OpenPGP_Packet':
                case 'OpenPGP_ExperimentalPacket':
                    /* Assume that these packets are not valid. */
                    break;

                default:
                    return new Horde_Pgp_Element_Message($decrypted);
                }
            }
        }

        throw new RuntimeException();
    }

    /**
     */
    public function verify($msg, $key)
    {
        $verify = new OpenPGP_Crypt_RSA($key->message);
        $pkey = $verify->key();

        switch ($pkey->algorithm) {
        case 1:
        case 2:
        case 3:
            // RSA
            return $verify->verify($msg->message);

        case 17:
            // DSA
            $dsa = new Horde_Pgp_Crypt_DSA($pkey);
            $verifier = function ($m, $s) use ($dsa) {
                return $dsa->verify(
                    $m,
                    Horde_String::lower($s->hash_algorithm_name()),
                    new Math_BigInteger($s->data[0], 256),
                    new Math_BigInteger($s->data[1], 256)
                );
            };

            return $msg->message->verified_signatures(array(
                'DSA' => array(
                    'MD5'    => $verifier,
                    'SHA1'   => $verifier,
                    'SHA224' => $verifier,
                    'SHA256' => $verifier,
                    'SHA384' => $verifier,
                    'SHA512' => $verifier
                )
            ));
        }

        throw new RuntimeException();
    }

}
