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
        $rsa = new Crypt_RSA();
        $k = $rsa->createKey($opts['keylength']);
        $rsa->loadKey($k['privatekey']);

        $nkey = new OpenPGP_SecretKeyPacket(array(
            'n' => $rsa->modulus->toBytes(),
            'e' => $rsa->publicExponent->toBytes(),
            'd' => $rsa->exponent->toBytes(),
            'p' => $rsa->primes[1]->toBytes(),
            'q' => $rsa->primes[2]->toBytes(),
            'u' => $rsa->coefficients[2]->toBytes()
        ));

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

        $wkey = new OpenPGP_Crypt_RSA($nkey);
        $m = $wkey->sign_key_userid(array($nkey, $uid));

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
                    $m = $wkey->sign_key_userid($m);
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
            $cipher->setIV($iv);

            $secret = '';
            foreach ($nkey::$secret_key_fields[$nkey->algorithm] as $f) {
                $f = $nkey->key[$f];
                $secret .= pack('n', OpenPGP::bitlength($f)) . $f;
            }
            $secret .= hash('sha1', $secret, true);

            $nkey->encrypted_data = $iv . $cipher->encrypt($secret);
            $nkey->s2k = $s2k;
            $nkey->s2k_useage = 254;
            $nkey->symmetric_algorithm = 7;
        }

        return Horde_Pgp_Element_PrivateKey::createFromData($m);
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

        return Horde_Pgp_Element_Message::createFromData($encrypted);
    }

    /**
     */
    public function decryptSymmetric($msg, $passphrase)
    {
        try {
            $decrypted = OpenPGP_Crypt_Symmetric::decryptSymmetric(
                $passphrase,
                $msg->getMessageOb()
            );
        } catch (Exception $e) {
            throw new BadMethodCallException();
        }

        return $decrypted->signatures();
    }

}
