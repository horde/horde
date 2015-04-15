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
 * @package   Pgp
 */

/**
 * A framework to interact with the OpenPGP standard (RFC 4880).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */
class Horde_Pgp
{
    /**
     * List of initialized backends.
     *
     * @var array
     */
    protected $_backends = array();

    /**
     * Configuration parameters.
     *
     * @var array
     */
    protected $_params;

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     *   - backends: (array) The explicit list of backend drivers
     *               (Horde_Pgp_Backend objects) to use.
     */
    public function __construct(array $params = array())
    {
        $this->_params = $params;
    }

    /**
     * Generates a private key.
     *
     * @param string $name        Full name.
     * @param string $email       E-mail.
     * @param array $opts         Additional options:
     *   - comment: (string) Comment.
     *   - expire: (integer) Expiration date (UNIX timestamp).
     *   - keylength: (integer) Key length.
     *   - passphrase: (string) Passphrase.
     *
     * @return Horde_Pgp_Key_Private  The generated private key.
     * @throws Horde_Pgp_Exception
     */
    public function generateKey($name, $email, array $opts = array())
    {
        $this->_initDrivers();

        $opts = array_merge(array(
            'comment' => '',
            'expire' => null,
            'keylength' => 2048,
            'passphrase' => null
        ), $opts, array(
            'email' => $email,
            'name' => $name
        ));

        return $this->_runInBackend(
            'generateKey',
            array($opts),
            Horde_Pgp_Translation::t("PGP key not generated successfully.")
        );
    }

    /**
     * Encrypts text using PGP public keys.
     *
     * @param string $text  The text to be encrypted.
     * @param mixed $keys   The list of public keys to encrypt.
     *
     * @return Horde_Pgp_Element_Message  The encrypted data.
     * @throws Horde_Pgp_Exception
     */
    public function encrypt($text, $keys)
    {
        return $this->_runInBackend(
            'encrypt',
            array(
                $text,
                array_map(
                    array('Horde_Pgp_Element_PublicKey', 'create'),
                    is_array($keys) ? $keys : array($keys)
                )
            ),
            Horde_Pgp_Translation::t("Could not PGP encrypt data.")
        );
    }

    /**
     * Encrypts text using a PGP symmetric passphrase.
     *
     * @param string $text        The text to be encrypted.
     * @param string $passphrase  The symmetric passphrase.
     *
     * @return Horde_Pgp_Element_Message  The encrypted data.
     * @throws Horde_Pgp_Exception
     */
    public function encryptSymmetric($text, $passphrase)
    {
        return $this->_runInBackend(
            'encryptSymmetric',
            array($text, $passphrase),
            Horde_Pgp_Translation::t("Could not PGP encrypt data.")
        );
    }

    /**
     * Sign data using a PGP private key.
     *
     * @param string $text  The text to be signed.
     * @param mixed $key    The private key to use for signing (must be
     *                      decrypted).
     *
     * @return Horde_Pgp_Element_Message  The signed data.
     * @throws Horde_Pgp_Exception
     */
    public function sign($text, $key)
    {
        return $this->_runInBackend(
            'sign',
            array($text, $this->_getPrivateKey($key), 'message'),
            Horde_Pgp_Translation::t("Could not PGP sign data.")
        );
    }

    /**
     * Sign data using a PGP private key, creating cleartext output.
     *
     * @param string $text  The text to be signed.
     * @param mixed $key    The private key to use for signing (must be
     *                      decrypted).
     *
     * @return Horde_Pgp_Element_SignedMessage  The signed data.
     * @throws Horde_Pgp_Exception
     */
    public function signCleartext($text, $key)
    {
        return $this->_runInBackend(
            'sign',
            array($text, $this->_getPrivateKey($key), 'clear'),
            Horde_Pgp_Translation::t("Could not PGP sign data.")
        );
    }

    /**
     * Sign data using a PGP private key, returning a detached signature.
     *
     * @param string $text  The text to be signed.
     * @param mixed $key    The private key to use for signing (must be
     *                      decrypted).
     *
     * @return Horde_Pgp_Element_Signature  The detached signature.
     * @throws Horde_Pgp_Exception
     */
    public function signDetached($text, $key)
    {
        return $this->_runInBackend(
            'sign',
            array($text, $this->_getPrivateKey($key), 'detach'),
            Horde_Pgp_Translation::t("Could not PGP sign data.")
        );
    }

    /**
     * Decrypts text using a PGP private key.
     *
     * @param mixed $text  The text to be decrypted.
     * @param mixed $key   The private key to use for decryption (must be
     *                     decrypted).
     *
     * @return Horde_Pgp_Element_Message $msg  The decrypted message.
     * @throws Horde_Pgp_Exception
     */
    public function decrypt($text, $key)
    {
        return $this->_runInBackend(
            'decrypt',
            array(
                Horde_Pgp_Element_Message::create($text),
                $this->_getPrivateKey($key)
            ),
            Horde_Pgp_Translation::t("Could not decrypt PGP data.")
        );
    }

    /**
     * Decrypts text using a PGP symmetric passphrase.
     *
     * @param mixed $text         The text to be decrypted.
     * @param string $passphrase  The symmetric passphrase used to encrypt
     *                            the data.
     *
     * @return array $data  Array of decrypted data. Outer array indicates a
     *                      message block. Each entry is an array with two
     *                      array elements: a list of data packets contained
     *                      in that message block and a list of signature data
     *                      associated with that block.
     * @throws Horde_Pgp_Exception
     */
    public function decryptSymmetric($text, $passphrase)
    {
        return $this->_runInBackend(
            'decryptSymmetric',
            array(
                Horde_Pgp_Element_Message::create($text),
                $passphrase
            ),
            Horde_Pgp_Translation::t("Could not decrypt PGP data.")
        );
    }

    /**
     * Verifies text using a PGP public key.
     *
     * @param mixed $text  The text to be verified
     * @param mixed $key   The public key used for signing.
     *
     * @return TODO
     * @throws Horde_Pgp_Exception
     */
    public function verify($text, $key)
    {
        return $this->verifyDetached($text, null, $key);
    }

    /**
     * Verifies text using a PGP public key and a detached signature.
     *
     * @param mixed $text  The text to be verified
     * @param mixed $sig   The detached signature.
     * @param mixed $key   The public key used for signing.
     *
     * @return TODO
     * @throws Horde_Pgp_Exception
     */
    public function verifyDetached($text, $sig, $key)
    {
        if (is_null($sig)) {
            if ($text instanceof Horde_Pgp_Element) {
                $data = $text;
            } else {
                $armor = new Horde_Pgp_Armor($text);
                foreach ($armor as $val) {
                    if (($val instanceof Horde_Pgp_Element_Message) ||
                        ($val instanceof Horde_Pgp_Element_SignedMessage)) {
                        $data = $val;
                        break;
                    }
                }
            }
        } else {
            $sig = Horde_Pgp_Element_Signature::create($sig);
            $data = new Horde_Pgp_Element_SignedMessage(
                new OpenPGP_Message(array(
                    new OpenPGP_LiteralDataPacket(
                        $text,
                        array(
                            'format' => ($sig->message[0]->signature_type === 0x00) ? 'b' : 't'
                        )
                    ),
                    $sig->message[0]
                ))
            );
        }

        return $this->_runInBackend(
            'verify',
            array($data, Horde_Pgp_Element_PublicKey::create($key)),
            Horde_Pgp_Translation::t("Could not verify PGP data.")
        );
    }

    /**
     * Signs a MIME part using PGP.
     *
     * @param Horde_Mime_Part $part  The object to sign.
     * @param array $params          The parameters required for signing.
     *                               ({@see _encryptSignature()}).
     *
     * @return mixed  A Horde_Mime_Part object that is signed according to RFC
     *                3156.
     * @throws Horde_Pgp_Exception
     */
    public function signMimePart(
        Horde_Mime_Part $part, array $params = array()
    )
    {
        /* RFC 3156 Requirements for a PGP signed message:
         * + Content-Type params 'micalg' & 'protocol' are REQUIRED.
         * + The digitally signed message MUST be constrained to 7 bits.
         * + The MIME headers MUST be a part of the signed data.
         * + Ensure there are no trailing spaces in encoded data by forcing
         *   text to be Q-P encoded (see, e.g., RFC 3676 [4.6]). */

        /* Ensure that all text parts are Q-P encoded. */
        foreach ($part as $val) {
            if ($val->getPrimaryType() === 'text') {
                $part->setTransferEncoding('quoted-printable', array(
                    'send' => true                                                              ));
            }
        }

        /* Get the signature. */
        $msg_sign = $this->signDetached($part->toString(array(
            'canonical' => true,
            'headers' => true
        )), array_merge($params, array(
            'sigtype' => 'detach',
            'type' => 'signature'
        )));

        /* Add the PGP signature. */
        $pgp_sign = new Horde_Mime_Part();
        $pgp_sign->setType('application/pgp-signature');
        $pgp_sign->setHeaderCharset('UTF-8');
        $pgp_sign->setDisposition('inline');
        $pgp_sign->setDescription(
            Horde_Pgp_Translation::t("PGP Digital Signature")
        );
        $pgp_sign->setContents($msg_sign, array('encoding' => '7bit'));

        /* Setup the multipart MIME Part. */
        $part = new Horde_Mime_Part();
        $part->setType('multipart/signed');
        $part->setContents(
            "This message is in MIME format and has been PGP signed.\n"
        );
        $part->addPart($part);
        $part->addPart($pgp_sign);
        $part->setContentTypeParameter(
            'protocol',
            'application/pgp-signature'
        );

        // TODO: Get algorithm from $msg_sign
        //$part->setContentTypeParameter('micalg', $sig_info['micalg']);

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
     * @throws Horde_Pgp_Exception
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
            Horde_Pgp_Translation::t("PGP Encrypted Data")
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
     * @throws Horde_Pgp_Exception
     */
    public function signAndEncryptMIMEPart($mime_part, $sign_params = array(),
                                           $encrypt_params = array())
    {
        /* RFC 3156 requires that the entire signed message be encrypted.  We
         * need to explicitly call using Horde_Pgp_Pgp:: because we don't
         * know whether a subclass has extended these methods. */
        $part = $this->signMIMEPart($mime_part, $sign_params);
        $part = $this->encryptMIMEPart($part, $encrypt_params);
        $part->setContents(
            "This message is in MIME format and has been PGP signed and encrypted.\n"
        );

        $part->setCharset($this->_params['email_charset']);
        $part->setDescription(
            Horde_String::convertCharset(
                Horde_Pgp_Translation::t("PGP Signed/Encrypted Data"),
                'UTF-8',
                $this->_params['email_charset']
            )
        );

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
                if (Horde_Pgp_Backend_Openpgp::supported()) {
                    $this->_backends[] = new Horde_Pgp_Backend_Openpgp();
                }
            }
        }
    }

    /**
     * TODO
     */
    protected function _runInBackend($cmd, $args, $error)
    {
        $this->_initDrivers();

        foreach ($this->_backends as $val) {
            try {
                return call_user_func_array(array($val, $cmd), $args);
            } catch (Exception $e) {}
        }

        throw new Horde_Pgp_Exception($error);
    }

    /**
     * TODO
     */
    protected function _getPrivateKey($key)
    {
        $key = Horde_Pgp_Element_PrivateKey::create($key);
        if ($key->encrypted) {
            throw new InvalidArgumentException(
                'Private key must be decrypted.'
            );
        }
        return $key;
    }

}
