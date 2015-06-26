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
     *   - hash: (string) Hash function.
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
            'hash' => 'SHA256',
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
     * @param array $opts   Additional options:
     *   - cipher: (string) Default symmetric cipher algorithm to use. One of:
     *             3DES, CAST5, AES128, AES192, AES256, Twofish.
     *   - compress: (string) Default compression to use. One of:
     *               NONE, ZIP, ZLIB
     *
     * @return Horde_Pgp_Element_Message  The encrypted data.
     * @throws Horde_Pgp_Exception
     */
    public function encrypt($text, $keys, array $opts = array())
    {
        return $this->_runInBackend(
            'encrypt',
            array(
                $text,
                array_map(
                    array('Horde_Pgp_Element_PublicKey', 'create'),
                    is_array($keys) ? $keys : array($keys)
                ),
                array_merge(
                    $opts,
                    $this->_getCompression($opts, 'ZIP'),
                    $this->_getCipher($opts, 'AES128')
                )
            ),
            Horde_Pgp_Translation::t("Could not PGP encrypt data.")
        );
    }

    /**
     * Encrypts text using a PGP symmetric passphrase.
     *
     * @param string $text        The text to be encrypted.
     * @param mixed $passphrase   The symmetric passphrase(s).
     * @param array $opts         Additional options:
     *   - cipher: (string) Default symmetric cipher algorithm to use. One of:
     *             3DES, CAST5, AES128, AES192, AES256, Twofish.
     *   - compress: (string) Default compression to use. One of:
     *               NONE, ZIP, ZLIB
     *
     * @return Horde_Pgp_Element_Message  The encrypted data.
     * @throws Horde_Pgp_Exception
     */
    public function encryptSymmetric($text, $passphrase, array $opts = array())
    {
        /* For maximum interoperability, use 3DES to encode symmetric data
         * since, without public key information. we don't know what the
         * foreign recipient will support; 3DES is the only MUST implement
         * symmetric algorithm in RFC 4880. */
        return $this->_runInBackend(
            'encryptSymmetric',
            array(
                $text,
                is_array($passphrase) ? $passphrase : array($passphrase),
                array_merge(
                    $opts,
                    $this->_getCompression($opts, 'ZIP'),
                    $this->_getCipher($opts, '3DES')
                )
            ),
            Horde_Pgp_Translation::t("Could not PGP encrypt data.")
        );
    }

    /**
     * Sign data using a PGP private key.
     *
     * Returns message object that contains both the signed data and the
     * signature packet.
     *
     * @param string $text  The text to be signed.
     * @param mixed $key    The private key to use for signing (must be
     *                      decrypted).
     * @param array $opts   Additional options:
     *   - compress: (string) Default compression to use. One of:
     *               NONE, ZIP, ZLIB
     *   - sign_hash: (string) The hash method to use.
     *
     * @return Horde_Pgp_Element_Message  The signed data.
     * @throws Horde_Pgp_Exception
     */
    public function sign($text, $key, array $opts = array())
    {
        return $this->_runInBackend(
            'sign',
            array(
                $text,
                $this->_getPrivateKey($key),
                'message',
                array_merge(
                    array('sign_hash' => null),
                    $opts,
                    $this->_getCompression($opts, 'ZIP')
                )
            ),
            Horde_Pgp_Translation::t("Could not PGP sign data.")
        );
    }

    /**
     * Sign data using a PGP private key, creating cleartext output.
     *
     * @param string $text  The text to be signed.
     * @param mixed $key    The private key to use for signing (must be
     *                      decrypted).
     * @param array $opts   Additional options:
     *   - sign_hash: (string) The hash method to use.
     *
     * @return Horde_Pgp_Element_SignedMessage  The signed data.
     * @throws Horde_Pgp_Exception
     */
    public function signCleartext($text, $key, array $opts = array())
    {
        return $this->_runInBackend(
            'sign',
            array(
                $text,
                $this->_getPrivateKey($key),
                'clear',
                array_merge(array(
                    'sign_hash' => null
                ), $opts)
            ),
            Horde_Pgp_Translation::t("Could not PGP sign data.")
        );
    }

    /**
     * Sign data using a PGP private key, returning a detached signature.
     *
     * @param string $text  The text to be signed.
     * @param mixed $key    The private key to use for signing (must be
     *                      decrypted).
     * @param array $opts   Additional options:
     *   - sign_hash: (string) The hash method to use.
     *
     * @return Horde_Pgp_Element_Signature  The detached signature.
     * @throws Horde_Pgp_Exception
     */
    public function signDetached($text, $key, array $opts = array())
    {
        return $this->_runInBackend(
            'sign',
            array(
                $text,
                $this->_getPrivateKey($key),
                'detach',
                array_merge(array(
                    'sign_hash' => null
                ), $opts)
            ),
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
     * @return array  List of verified packets. Each sub array contains two
     *                values: the list of packets verfied by signing and
     *                the list of signature packets used to verify.
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
     * @return  {@see detach()}
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

    /**
     * TODO
     */
    protected function _getCipher($opts, $default)
    {
        /* RFC 4880 [9.2] */
        return $this->_getOption($opts, $default, 'cipher', array(
            '3DES' => 2,
            'CAST5' => 3,
            'AES128' => 7,
            'AES192' => 8,
            'AES256' => 9,
            'Twofish' => 10
        ));
    }

    /**
     * TODO
     */
    protected function _getCompression($opts, $default)
    {
        /* RFC 4880 [9.3] */
        return $this->_getOption($opts, $default, 'compress', array(
            'NONE' => 0,
            'ZIP' => 1,
            'ZLIB' => 2
        ));
    }

    /**
     * TODO
     */
    protected function _getOption($opts, $default, $name, $map)
    {
        $val = isset($opts[$name])
            ? $opts[$name]
            : $default;

        if (is_string($val)) {
            $val = $map[$val];
        }

        return array($name => $val);
    }


}
