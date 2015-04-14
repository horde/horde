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
        $id = new Horde_Mail_Rfc822_Address($opts['email']);
        if (strlen($opts['comment'])) {
            $id->comment[] = $opts['comment'];
        }
        if (strlen($opts['name'])) {
            $id->personal = $opts['name'];
        }

        $uid = new OpenPGP_UserIDPacket(
            $id->writeAddress(array('comment' => true))
        );

        /* Signing key */
        $skey = $this->_generateSecretKeyPacket(
            $opts['keylength'],
            'OpenPGP_SecretKeyPacket'
        );

        $skey_rsa = new OpenPGP_Crypt_RSA($skey);
        $m = $skey_rsa->sign_key_userid(array($skey, $uid));

        if (isset($opts['expire'])) {
            foreach ($m as $k => $v) {
                if ($v instanceof OpenPGP_SignaturePacket) {
                    /* Need to recalculate hash. No way of adding this packet
                     * to be factored into the sign_key_userid() call
                     * above. */
                    unset($m[$k]);
                    $sig = new OpenPGP_SignaturePacket($m, 'RSA', 'SHA256');
                    $sig->signature_type = $v->signature_type;
                    $sig->hashed_subpackets = $v->hashed_subpackets;
                    $sig->hashed_subpackets[] = new OpenPGP_SignaturePacket_KeyExpirationTimePacket($opts['expire'] - time());
                    $m[$k] = $sig;
                    $m = $skey_rsa->sign_key_userid($m);
                    break;
                }
            }
        }

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
            'SHA256'
        );

        /* This is a "Subkey Binding Signature". */
        $sig->signature_type = 0x18;
        $sig->hashed_subpackets[] = new OpenPGP_SignaturePacket_KeyFlagsPacket(
            array(0x0C)
        );
        $sig->hashed_subpackets[] = new OpenPGP_SignaturePacket_IssuerPacket(
            substr($skey_rsa->key()->fingerprint, -16)
        );

        $priv_key = $skey_rsa->private_key();
        $priv_key->setHash('sha256');
        $sig->sign_data(array(
            'RSA' => array(
                'SHA256' => function($data) use($priv_key) {
                    return array($priv_key->sign($data));
                }
            )
        ));

        if (strlen($opts['passphrase'])) {
            $this->_encryptPrivateKey($ekey, $cipher, $s2k, $iv);
        }

        $m[] = $ekey;
        $m[] = $sig;

        return new Horde_Pgp_Element_PrivateKey($m);
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
    public function encrypt($text, $keys)
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
        }

        /* TODO: Support ElGamal encryption */
        $encrypted = OpenPGP_Crypt_Symmetric::encrypt(
            $p,
            new OpenPGP_Message(array(
                new OpenPGP_LiteralDataPacket($text, array('format' => 'u'))
            ))
        );

        return new Horde_Pgp_Element_Message($encrypted);
    }

    /**
     */
    public function encryptSymmetric($text, $passphrase)
    {
        $encrypted = OpenPGP_Crypt_Symmetric::encrypt(
            $passphrase,
            new OpenPGP_Message(array(
                new OpenPGP_LiteralDataPacket($text, array('format' => 'u'))
            ))
        );

        return new Horde_Pgp_Element_Message($encrypted);
    }

    /**
     */
    public function sign($text, $key, $mode)
    {
        $rsa = new OpenPGP_Crypt_RSA($key->message);
        $pkey = $rsa->key();

        $text = new OpenPGP_LiteralDataPacket($text, array('format' => 'u'));

        switch ($pkey->algorithm) {
        case 1:
        case 2:
        case 3:
            // RSA
            $result = $rsa->sign($text, 'SHA256');
            break;

        case 17:
            // DSA
            $sig = new OpenPGP_SignaturePacket($text, 'DSA', 'SHA256');
            $sig->hashed_subpackets[] = new OpenPGP_SignaturePacket_IssuerPacket(
                substr($pkey->fingerprint, -16)
            );

            $sig->sign_data(array(
                'DSA' => array(
                    'SHA256' => function ($data) use ($pkey) {
                        $dsa = new Horde_Pgp_Crypt_DSA();
                        return $dsa->sign(
                            $data,
                            'SHA256',
                            new Math_BigInteger($pkey->key['p'], 256),
                            new Math_BigInteger($pkey->key['q'], 256),
                            new Math_BigInteger($pkey->key['g'], 256),
                            new Math_BigInteger($pkey->key['x'], 256)
                        );
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
            $sm->headers['Hash'] = 'SHA256';
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
            return new Horde_Pgp_Element_Message($result);
        }
    }

    /**
     */
    public function decrypt($msg, $key)
    {
        /* TODO: Support ElGamal decryption */
        $decryptor = new OpenPGP_Crypt_RSA($key->message);
        return new Horde_Pgp_Element_Message(
            $decryptor->decrypt($msg->message)
        );
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
            $p = new Math_BigInteger($pkey->key['p'], 256);
            $q = new Math_BigInteger($pkey->key['q'], 256);
            $g = new Math_BigInteger($pkey->key['g'], 256);
            $y = new Math_BigInteger($pkey->key['y'], 256);

            $verifier = function ($m, $s) use ($p, $q, $g, $y) {
                $dsa = new Horde_Pgp_Crypt_DSA();
                return $dsa->verify(
                    $m,
                    strtolower($s->hash_algorithm_name()),
                    new Math_BigInteger($s->data[0], 256),
                    new Math_BigInteger($s->data[1], 256),
                    $p,
                    $q,
                    $g,
                    $y
                );
            };

            $a = $msg->message->verified_signatures(array(
                'DSA' => array(
                    'MD5'    => $verifier,
                    'SHA1'   => $verifier,
                    'SHA224' => $verifier,
                    'SHA256' => $verifier,
                    'SHA384' => $verifier,
                    'SHA512' => $verifier
                )
            ));
            return $a;

        default:
            // Unknown signing
            throw new RuntimeException();
        }
    }

}
