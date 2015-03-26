<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Crypt
 */

/**
 * A framework for Horde applications to interact with the GNU Privacy Guard
 * program ("GnuPG").  GnuPG implements the OpenPGP standard (RFC 4880).
 *
 * GnuPG Website: ({@link http://www.gnupg.org/})
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Crypt
 */
class Horde_Crypt_Pgp extends Horde_Crypt
{
    /**
     * List of initialized backends.
     *
     * @var array
     */
    protected $_backends = array();

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     *   - backends: (array) The explicit list of backend drivers
     *               (Horde_Crypt_Pgp_Backend objects) to use.
     *   - program: (string) The path to the GnuPG binary.
     *   - temp: (string) Location of temporary directory.
     */
    public function __construct($params = array())
    {
        parent::__construct($params);
    }

    /**
     * Generates a personal Public/Private keypair combination.
     *
     * @param string $realname     The name to use for the key.
     * @param string $email        The email to use for the key.
     * @param string $passphrase   The passphrase to use for the key.
     * @param string $comment      The comment to use for the key.
     * @param integer $keylength   The keylength to use for the key.
     * @param integer $expire      The expiration date (UNIX timestamp). No
     *                             expiration if empty.
     * @param string $key_type     Key type (@since 2.2.0).
     * @param string $subkey_type  Subkey type (@since 2.2.0).
     *
     * @return array  An array consisting of the following keys/values:
     *   - private: (string) Private key.
     *   - public: (string) Public key.
     *
     * @throws Horde_Crypt_Exception
     */
    public function generateKey($realname, $email, $passphrase, $comment = '',
                                $keylength = 1024, $expire = null,
                                $key_type = 'RSA', $subkey_type = 'RSA')
    {
        $this->_initDrivers();

        foreach ($this->_backends as $val) {
            try {
                $ret = $val->generateKey(array(
                    'comment' => $comment,
                    'email' => $email,
                    'expire' => $expire,
                    'keylength' => $keylength,
                    'key_type' => $key_type,
                    'name' => $realname,
                    'passphrase' => $passphrase,
                    'subkey_type' => $subkey_type
                ));

                if ($ret !== false) {
                    return $ret;
                }
            } catch (Exception $e) {}
        }

        throw new Horde_Crypt_Exception(
            Horde_Crypt_Translation::t("Public/Private keypair not generated successfully.")
        );
    }

    /**
     * Returns information on a PGP data block.
     *
     * @param string $pgpdata  The PGP data block.
     *
     * @return array  An array with information on the PGP data block. If an
     *                element is not present in the data block, it will
     *                likewise not be set in the array.
     * <pre>
     * Array Format:
     * -------------
     * [public_key]/[secret_key] => Array
     *   (
     *     [created] => Key creation - UNIX timestamp
     *     [expires] => Key expiration - UNIX timestamp (0 = never expires)
     *     [size]    => Size of the key in bits
     *   )
     *
     * [keyid] => Key ID of the PGP data (if available)
     *            16-bit hex value
     *
     * [signature] => Array (
     *     [id{n}/'_SIGNATURE'] => Array (
     *         [name]        => Full Name
     *         [comment]     => Comment
     *         [email]       => E-mail Address
     *         [keyid]       => 16-bit hex value
     *         [created]     => Signature creation - UNIX timestamp
     *         [expires]     => Signature expiration - UNIX timestamp
     *         [micalg]      => The hash used to create the signature
     *         [sig_{hex}]   => Array [details of a sig verifying the ID] (
     *             [created]     => Signature creation - UNIX timestamp
     *             [expires]     => Signature expiration - UNIX timestamp
     *             [keyid]       => 16-bit hex value
     *             [micalg]      => The hash used to create the signature
     *         )
     *     )
     * )
     * </pre>
     *
     * Each user ID will be stored in the array 'signature' and have data
     * associated with it, including an array for information on each
     * signature that has signed that UID. Signatures not associated with a
     * UID (e.g. revocation signatures and sub keys) will be stored under the
     * special keyword '_SIGNATURE'.
     */
    public function pgpPacketInformation($pgpdata)
    {
        $this->_initDrivers();

        foreach ($this->_backends as $val) {
            try {
                return $val->packetInfo($pgpdata);
            } catch (Exception $e) {}
        }

        return array();
    }

    /**
     * Returns human readable information on a PGP key.
     *
     * @param string $pgpdata  The PGP data block.
     *
     * @return string  Tabular information on the PGP key.
     * @throws Horde_Crypt_Exception
     */
    public function pgpPrettyKey($pgpdata)
    {
        $msg = '';
        $fingerprints = $this->getFingerprintsFromKey($pgpdata);
        $info = $this->pgpPacketInformation($pgpdata);

        if (empty($info['signature'])) {
            return $msg;
        }

        /* Making the property names the same width for all localizations .*/
        $leftrow = array(
            Horde_Crypt_Translation::t("Name"),
            Horde_Crypt_Translation::t("Key Type"),
            Horde_Crypt_Translation::t("Key Creation"),
            Horde_Crypt_Translation::t("Expiration Date"),
            Horde_Crypt_Translation::t("Key Length"),
            Horde_Crypt_Translation::t("Comment"),
            Horde_Crypt_Translation::t("E-Mail"),
            Horde_Crypt_Translation::t("Hash-Algorithm"),
            Horde_Crypt_Translation::t("Key ID"),
            Horde_Crypt_Translation::t("Key Fingerprint")
        );

        array_walk(
            $leftrow,
            function (&$s, $k, $m) {
                $s .= ':' . str_repeat(' ', $m - Horde_String::length($s));
            },
            max(array_map('strlen', $leftrow)) + 2
        );

        foreach ($info['signature'] as $uid_idx => $val) {
            if ($uid_idx == '_SIGNATURE') {
                continue;
            }

            $key = $this->pgpPacketSignatureByUidIndex($pgpdata, $uid_idx);

            $keyid = empty($key['keyid'])
                ? null
                : $this->getKeyIDString($key['keyid']);
            $fingerprint = isset($fingerprints[$keyid])
                ? $fingerprints[$keyid]
                : null;
            $sig_key = 'sig_' . $key['keyid'];

            $msg .= $leftrow[0] . (isset($key['name']) ? stripcslashes($key['name']) : '') . "\n"
                . $leftrow[1] . (($key['key_type'] == 'public_key') ? Horde_Crypt_Translation::t("Public Key") : Horde_Crypt_Translation::t("Private Key")) . "\n"
                . $leftrow[2] . strftime("%D", $val[$sig_key]['created']) . "\n"
                . $leftrow[3] . (empty($val[$sig_key]['expires']) ? '[' . Horde_Crypt_Translation::t("Never") . ']' : strftime("%D", $val[$sig_key]['expires'])) . "\n"
                . $leftrow[4] . $key['key_size'] . " Bytes\n"
                . $leftrow[5] . (empty($key['comment']) ? '[' . Horde_Crypt_Translation::t("None") . ']' : $key['comment']) . "\n"
                . $leftrow[6] . (empty($key['email']) ? '[' . Horde_Crypt_Translation::t("None") . ']' : $key['email']) . "\n"
                . $leftrow[7] . (empty($key['micalg']) ? '[' . Horde_Crypt_Translation::t("Unknown") . ']' : $key['micalg']) . "\n"
                . $leftrow[8] . (empty($keyid) ? '[' . Horde_Crypt_Translation::t("Unknown") . ']' : $keyid) . "\n"
                . $leftrow[9] . (empty($fingerprint) ? '[' . Horde_Crypt_Translation::t("Unknown") . ']' : $fingerprint) . "\n\n";
        }

        return $msg;
    }

    /**
     * TODO
     *
     * @since 2.4.0
     */
    public function getKeyIDString($keyid)
    {
        /* Get the 8 character key ID string. */
        if (strpos($keyid, '0x') === 0) {
            $keyid = substr($keyid, 2);
        }
        if (strlen($keyid) > 8) {
            $keyid = substr($keyid, -8);
        }
        return '0x' . $keyid;
    }

    /**
     * Returns only information on the first ID that matches the email address
     * input.
     *
     * @param string $pgpdata  The PGP data block.
     * @param string $email    An e-mail address.
     *
     * @return array  An array with information on the PGP data block. If an
     *                element is not present in the data block, it will
     *                likewise not be set in the array. Array elements:
     *   - comment: Comment
     *   - created: Signature creation (UNIX timestamp)
     *   - email: E-mail Address
     *   - key_created: Key creation (UNIX timestamp)
     *   - key_expires: Key expiration (UNIX timestamp; 0 = never expires)
     *   - key_size: Size of the key in bits
     *   - key_type: The key type (public_key or secret_key)
     *   - keyid: 16-bit hex value
     *   - micalg: The hash used to create the signature
     *   - name: Full Name
     */
    public function pgpPacketSignature($pgpdata, $email)
    {
        $data = $this->pgpPacketInformation($pgpdata);
        $out = array();

        /* Check that [signature] key exists. */
        if (!isset($data['signature'])) {
            return $out;
        }

        /* Store the signature information now. */
        if (($email == '_SIGNATURE') &&
            isset($data['signature']['_SIGNATURE'])) {
            foreach ($data['signature'][$email] as $key => $value) {
                $out[$key] = $value;
            }
        } else {
            $uid_idx = 1;

            while (isset($data['signature']['id' . $uid_idx])) {
                if ($data['signature']['id' . $uid_idx]['email'] == $email) {
                    foreach ($data['signature']['id' . $uid_idx] as $key => $val) {
                        $out[$key] = $val;
                    }
                    break;
                }
                ++$uid_idx;
            }
        }

        return $this->_pgpPacketSignature($data, $out);
    }

    /**
     * Returns information on a PGP signature embedded in PGP data.  Similar
     * to pgpPacketSignature(), but returns information by unique User ID
     * Index (format id{n} where n is an integer of 1 or greater).
     *
     * @see pgpPacketSignature()
     *
     * @param string $pgpdata  See pgpPacketSignature().
     * @param string $uid_idx  The UID index.
     *
     * @return array  See pgpPacketSignature().
     */
    public function pgpPacketSignatureByUidIndex($pgpdata, $uid_idx)
    {
        $data = $this->pgpPacketInformation($pgpdata);

        return isset($data['signature'][$uid_idx])
            ? $this->_pgpPacketSignature($data, $data['signature'][$uid_idx])
            : array();
    }

    /**
     * Adds some data to the pgpPacketSignature*() function array.
     *
     * @see pgpPacketSignature().
     *
     * @param array $data  See pgpPacketSignature().
     * @param array $out   The return array.
     *
     * @return array  The return array.
     */
    protected function _pgpPacketSignature($data, $out)
    {
        /* If empty, return now. */
        if (empty($out)) {
            return $out;
        }

        $key_type = null;

        /* Store any public/private key information. */
        if (isset($data['public_key'])) {
            $key_type = 'public_key';
        } elseif (isset($data['secret_key'])) {
            $key_type = 'secret_key';
        }

        if ($key_type) {
            $out['key_type'] = $key_type;
            if (isset($data[$key_type]['created'])) {
                $out['key_created'] = $data[$key_type]['created'];
            }
            if (isset($data[$key_type]['expires'])) {
                $out['key_expires'] = $data[$key_type]['expires'];
            }
            if (isset($data[$key_type]['size'])) {
                $out['key_size'] = $data[$key_type]['size'];
            }
        }

        return $out;
    }

    /**
     * Returns the key ID of the key used to sign a block of PGP data.
     *
     * @param string $text  The PGP signed text block.
     *
     * @return string  The key ID of the key used to sign $text, or null if
     *                 not found.
     */
    public function getSignersKeyID($text)
    {
        $this->_initDrivers();

        foreach ($this->_backends as $val) {
            try {
                return $val->getSignersKeyId($text);
            } catch (Exception $e) {}
        }

        return null;
    }

    /**
     * Verify a passphrase for a given public/private keypair.
     *
     * @param string $public_key   The user's PGP public key.
     * @param string $private_key  The user's PGP private key.
     * @param string $passphrase   The user's passphrase.
     *
     * @return boolean  Returns true on valid passphrase, false on invalid
     *                  passphrase.
     * @throws Horde_Crypt_Exception
     */
    public function verifyPassphrase($public_key, $private_key, $passphrase)
    {
        /* Get e-mail address of public key. */
        $info = $this->pgpPacketInformation($public_key);
        if (!isset($info['signature']['id1']['email'])) {
            throw new Horde_Crypt_Exception(
                Horde_Crypt_Translation::t("Could not determine the recipient's e-mail address.")
            );
        }

        /* Encrypt a test message. */
        try {
            $result = $this->encrypt(
                'Test',
                array(
                    'type' => 'message',
                    'pubkey' => $public_key,
                    'recips' => array(
                        $info['signature']['id1']['email'] => $public_key
                    )
                )
            );
        } catch (Horde_Crypt_Exception $e) {
            return false;
        }

        /* Try to decrypt the message. */
        try {
            $this->decrypt(
                $result,
                array(
                    'type' => 'message',
                    'pubkey' => $public_key,
                    'privkey' => $private_key,
                    'passphrase' => $passphrase
                )
            );
        } catch (Horde_Crypt_Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Sends a PGP public key to a public keyserver.
     *
     * @param string $pubkey  The PGP public key
     * @param string $server  The keyserver to use.
     * @param float $timeout  The keyserver timeout.
     *
     * @throws Horde_Crypt_Exception
     */
    public function putPublicKeyserver($pubkey,
                                       $server = self::KEYSERVER_PUBLIC,
                                       $timeout = self::KEYSERVER_TIMEOUT)
    {
        return $this->_getKeyserverOb($server)->put($pubkey);
    }

    /**
     * Returns the first matching key ID for an email address from a
     * public keyserver.
     *
     * @param string $address  The email address of the PGP key.
     * @param string $server   The keyserver to use.
     * @param float $timeout   The keyserver timeout.
     *
     * @return string  The PGP key ID.
     * @throws Horde_Crypt_Exception
     */
    public function getKeyID($address, $server = self::KEYSERVER_PUBLIC,
                             $timeout = self::KEYSERVER_TIMEOUT)
    {
        return $this->_getKeyserverOb($server)->getKeyId($address);
    }

    /**
     * Get the fingerprints from a key block.
     *
     * @param string $pgpdata  The PGP data block.
     *
     * @return array  The fingerprints in $pgpdata indexed by key id.
     */
    public function getFingerprintsFromKey($pgpdata)
    {
        $this->_initDrivers();

        foreach ($this->_backends as $val) {
            try {
                return $val->getFingerprintsFromKey($pgpdata);
            } catch (Exception $e) {}
        }

        return array();
    }

    /**
     * Generates a public key from a private key.
     *
     * @param string $data  Armor text of private key.
     *
     * @return string  Armor text of public key, or null if it could not be
     *                 generated.
     */
    public function getPublicKeyFromPrivateKey($data)
    {
        $this->_initDrivers();

        foreach ($this->_backends as $val) {
            try {
                return $val->getPublicKeyFromPrivateKey($data);
            } catch (Exception $e) {}
        }

        return null;
    }

    /**
     * Encrypts text using PGP.
     *
     * @param string $text   The text to be PGP encrypted.
     * @param array $params  The parameters needed for encryption.
     *                       See the individual _encrypt*() functions for the
     *                       parameter requirements.
     *
     * @return string  The encrypted message.
     * @throws Horde_Crypt_Exception
     */
    public function encrypt($text, $params = array())
    {
        switch (isset($params['type']) ? $params['type'] : false) {
        case 'message':
            $error = Horde_Crypt_Translation::t(
                "Could not PGP encrypt message."
            );
            $func = 'encryptMessage';
            break;

        case 'signature':
            /* Check for required parameters. */
            if (!isset($params['pubkey']) ||
                !isset($params['privkey']) ||
                !isset($params['passphrase'])) {
                /* This is a programming error, not a user displayable
                 * error. */
                throw new InvalidArgumentException(
                    'A public PGP key, private PGP key, and passphrase are required to sign a message.'
                );
            }

            $error = Horde_Crypt_Translation::t("Could not PGP sign message.");
            $func = 'encryptSignature';
            break;

        default:
            throw new InvalidArgumentException(
                'Incorrect "type" parameter provided.'
            );
        }

        $this->_initDrivers();

        foreach ($this->_backends as $val) {
            try {
                return $val->$func($text, $params);
            } catch (Exception $e) {}
        }

        throw new Horde_Crypt_Exception($error);
    }

    /**
     * Decrypts text using PGP.
     *
     * @param string $text   The text to be PGP decrypted.
     * @param array $params  The parameters needed for decryption.
     *                       See the individual _decrypt*() functions for the
     *                       parameter requirements.
     *
     * @return object  An object with the following properties:
     *   - message: (string) The signature result text.
     *   - result: (boolean) The result of the signature test.
     *
     * @throws Horde_Crypt_Exception
     */
    public function decrypt($text, $params = array())
    {
        switch (isset($params['type']) ? $params['type'] : false) {
        case 'detached-signature':
        case 'signature':
            /* Check for required parameters. */
            if (!isset($params['pubkey'])) {
                throw new InvalidArgumentException(
                    'A public PGP key is required to verify a signed message.'
                );
            }
            if (($params['type'] === 'detached-signature') &&
                !isset($params['signature'])) {
                throw new InvalidArgumentException(
                    'The detached PGP signature block is required to verify the signed message.'
                );
            }

            $func = 'decryptSignature';
            break;

        case 'message':
            /* Check for required parameters. */
            if (!isset($params['passphrase']) &&
                empty($params['no_passphrase'])) {
                throw new InvalidArgumentException(
                    'A passphrase is required to decrypt a message.'
                );
            }

            $func = 'decryptMessage';
            break;

        default:
            throw new InvalidArgumentException(
                'Incorrect "type" parameter provided.'
            );
        }

        $this->_initDrivers();

        foreach ($this->_backends as $val) {
            try {
                return $val->$func($text, $params);
            } catch (Exception $e) {}
        }

        throw new Horde_Crypt_Exception(
            Horde_Crypt_Translation::t("Could not decrypt PGP data.")
        );
    }

    /**
     * Returns whether a text has been encrypted symmetrically.
     *
     * @todo Return null, instead of exception, if tools are not available to
     *       determine whether data was encrypted symmetrically.
     *
     * @param string $text  The PGP encrypted text.
     *
     * @return boolean  True if the text is symmetrically encrypted.
     * @throws Horde_Crypt_Exception
     */
    public function encryptedSymmetrically($text)
    {
        $this->_initDrivers();

        foreach ($this->_backends as $val) {
            try {
                return $val->isEncryptedSymmetrically($text);
            } catch (Exception $e) {}
        }

        throw new Horde_Crypt_Exception(
            Horde_Crypt_Translation::t("Unable to determine if data was encrypted symmetrically.")
        );
    }

    /**
     * Signs a MIME part using PGP.
     *
     * @param Horde_Mime_Part $mime_part  The object to sign.
     * @param array $params               The parameters required for signing.
     *                                    ({@see _encryptSignature()}).
     *
     * @return mixed  A Horde_Mime_Part object that is signed according to RFC
     *                3156.
     * @throws Horde_Crypt_Exception
     */
    public function signMIMEPart($mime_part, $params = array())
    {
        $params = array_merge($params, array(
            'sigtype' => 'detach',
            'type' => 'signature'
        ));

        /* RFC 3156 Requirements for a PGP signed message:
         * + Content-Type params 'micalg' & 'protocol' are REQUIRED.
         * + The digitally signed message MUST be constrained to 7 bits.
         * + The MIME headers MUST be a part of the signed data.
         * + Ensure there are no trailing spaces in encoded data by forcing
         *   text to be Q-P encoded (see, e.g., RFC 3676 [4.6]). */

        /* Ensure that all text parts are Q-P encoded. */
        foreach ($mime_part->contentTypeMap(false) as $key => $val) {
            if (strpos($val, 'text/') === 0) {
                $mime_part[$key]->setTransferEncoding('quoted-printable', array(
                    'send' => true
                ));
            }
        }

        /* Get the signature. */
        $msg_sign = $this->encrypt($mime_part->toString(array(
            'canonical' => true,
            'headers' => true
        )), $params);

        /* Add the PGP signature. */
        $pgp_sign = new Horde_Mime_Part();
        $pgp_sign->setType('application/pgp-signature');
        $pgp_sign->setHeaderCharset('UTF-8');
        $pgp_sign->setDisposition('inline');
        $pgp_sign->setDescription(
            Horde_Crypt_Translation::t("PGP Digital Signature")
        );
        $pgp_sign->setContents($msg_sign, array('encoding' => '7bit'));

        /* Get the algorithim information from the signature. Since we are
         * analyzing a signature packet, we need to use the special keyword
         * '_SIGNATURE' - see Horde_Crypt_Pgp. */
        $sig_info = $this->pgpPacketSignature($msg_sign, '_SIGNATURE');

        /* Setup the multipart MIME Part. */
        $part = new Horde_Mime_Part();
        $part->setType('multipart/signed');
        $part->setContents(
            "This message is in MIME format and has been PGP signed.\n"
        );
        $part->addPart($mime_part);
        $part->addPart($pgp_sign);
        $part->setContentTypeParameter(
            'protocol',
            'application/pgp-signature'
        );
        $part->setContentTypeParameter('micalg', $sig_info['micalg']);

        return $part;
    }

    /**
     * Encrypts a MIME part using PGP.
     *
     * @param Horde_Mime_Part $mime_part  The object to encrypt.
     * @param array $params               The parameters required for
     *                                    encryption
     *                                    ({@see _encryptMessage()}).
     *
     * @return mixed  A Horde_Mime_Part object that is encrypted according to
     *                RFC 3156.
     * @throws Horde_Crypt_Exception
     */
    public function encryptMIMEPart($mime_part, $params = array())
    {
        $params = array_merge($params, array('type' => 'message'));

        $signenc_body = $mime_part->toString(array(
            'canonical' => true,
            'headers' => true
        ));
        $message_encrypt = $this->encrypt($signenc_body, $params);

        /* Set up MIME Structure according to RFC 3156. */
        $part = new Horde_Mime_Part();
        $part->setType('multipart/encrypted');
        $part->setHeaderCharset('UTF-8');
        $part->setContentTypeParameter(
            'protocol',
            'application/pgp-encrypted'
        );
        $part->setDescription(
            Horde_Crypt_Translation::t("PGP Encrypted Data")
        );
        $part->setContents(
            "This message is in MIME format and has been PGP encrypted.\n"
        );

        $part1 = new Horde_Mime_Part();
        $part1->setType('application/pgp-encrypted');
        $part1->setCharset(null);
        $part1->setContents("Version: 1\n", array('encoding' => '7bit'));
        $part->addPart($part1);

        $part2 = new Horde_Mime_Part();
        $part2->setType('application/octet-stream');
        $part2->setCharset(null);
        $part2->setContents($message_encrypt, array('encoding' => '7bit'));
        $part2->setDisposition('inline');
        $part->addPart($part2);

        return $part;
    }

    /**
     * Signs and encrypts a MIME part using PGP.
     *
     * @param Horde_Mime_Part $mime_part   The object to sign and encrypt.
     * @param array $sign_params           The parameters required for
     *                                     signing
     *                                     ({@see _encryptSignature()}).
     * @param array $encrypt_params        The parameters required for
     *                                     encryption
     *                                     ({@see _encryptMessage()}).
     *
     * @return mixed  A Horde_Mime_Part object that is signed and encrypted
     *                according to RFC 3156.
     * @throws Horde_Crypt_Exception
     */
    public function signAndEncryptMIMEPart($mime_part, $sign_params = array(),
                                           $encrypt_params = array())
    {
        /* RFC 3156 requires that the entire signed message be encrypted.  We
         * need to explicitly call using Horde_Crypt_Pgp:: because we don't
         * know whether a subclass has extended these methods. */
        $part = $this->signMIMEPart($mime_part, $sign_params);
        $part = $this->encryptMIMEPart($part, $encrypt_params);
        $part->setContents(
            "This message is in MIME format and has been PGP signed and encrypted.\n"
        );

        $part->setCharset($this->_params['email_charset']);
        $part->setDescription(
            Horde_String::convertCharset(
                Horde_Crypt_Translation::t("PGP Signed/Encrypted Data"),
                'UTF-8',
                $this->_params['email_charset']
            )
        );

        return $part;
    }

    /**
     * Generates a Horde_Mime_Part object, in accordance with RFC 3156, that
     * contains a public key.
     *
     * @param string $key  The public key.
     *
     * @return Horde_Mime_Part  An object that contains the public key.
     */
    public function publicKeyMIMEPart($key)
    {
        $part = new Horde_Mime_Part();
        $part->setType('application/pgp-keys');
        $part->setHeaderCharset('UTF-8');
        $part->setDescription(Horde_Crypt_Translation::t("PGP Public Key"));
        $part->setContents($key, array('encoding' => '7bit'));

        return $part;
    }

    /**
     * Initialize the backend driver list.
     */
    protected function _initDrivers()
    {
        if (empty($this->_backends)) {
            if (isset($this->_params['backends'])) {
                $this->_backends = $this->_params['backends'];
            } else {
                if (Horde_Crypt_Pgp_Backend_Pecl::supported()) {
                    $this->_backends[] = new Horde_Crypt_Pgp_Backend_Pecl();
                }
                if (Horde_Crypt_Pgp_Backend_Binary::supported()) {
                    $this->_backends[] = new Horde_Crypt_Pgp_Backend_Binary(
                        $this->_params['program'],
                        isset($this->_params['temp']) ? $this->_params['temp'] : null
                    );
                }
            }
        }
    }

    /* Deprecated components. */

    /**
     * @deprecated  Use Horde_Crypt_Pgp_Parse instead.
     */
    const ARMOR_MESSAGE = 1;
    const ARMOR_SIGNED_MESSAGE = 2;
    const ARMOR_PUBLIC_KEY = 3;
    const ARMOR_PRIVATE_KEY = 4;
    const ARMOR_SIGNATURE = 5;
    const ARMOR_TEXT = 6;

    /**
     * @deprecated  Use Horde_Crypt_Pgp_Parse instead.
     */
    protected $_armor = array(
        'MESSAGE' => self::ARMOR_MESSAGE,
        'SIGNED MESSAGE' => self::ARMOR_SIGNED_MESSAGE,
        'PUBLIC KEY BLOCK' => self::ARMOR_PUBLIC_KEY,
        'PRIVATE KEY BLOCK' => self::ARMOR_PRIVATE_KEY,
        'SIGNATURE' => self::ARMOR_SIGNATURE
    );

    /**
     * @deprecated  Use Horde_Crypt_Pgp_Keyserver instead.
     */
    const KEYSERVER_PUBLIC = 'pool.sks-keyservers.net';
    const KEYSERVER_REFUSE = 3;
    const KEYSERVER_TIMEOUT = 10;

    /**
     * @deprecated  Use Horde_Crypt_Pgp_Parse instead.
     */
    public function parsePGPData($text)
    {
        $parse = new Horde_Crypt_Pgp_Parse();
        return $parse->parse($text);
    }

    /**
     * @deprecated  Use Horde_Crypt_Pgp_Keyserver instead.
     */
    public function getPublicKeyserver($keyid,
                                       $server = self::KEYSERVER_PUBLIC,
                                       $timeout = self::KEYSERVER_TIMEOUT,
                                       $address = null)
    {
        $keyserver = $this->_getKeyserverOb($server);
        if (empty($keyid) && !empty($address)) {
            $keyid = $keyserver->getKeyID($address);
        }
        return $keyserver->get($keyid);
    }

    /**
     * @deprecated
     */
    public function generateRevocation($key, $email, $passphrase)
    {
        throw new Horde_Crypt_Exception('Not supported');
    }

    /**
     * @deprecated
     * @internal
     */
    protected function _getKeyserverOb($server)
    {
        $params = array(
            'keyserver' => $server,
            'http' => new Horde_Http_Client()
        );

        if (!empty($this->_params['proxy_host'])) {
            $params['http']->{'request.proxyServer'} = $this->_params['proxy_host'];
            if (isset($this->_params['proxy_port'])) {
                $params['http']->{'request.proxyPort'} = $this->_params['proxy_port'];
            }
        }

        return new Horde_Crypt_Pgp_Keyserver($this, $params);
    }

}
