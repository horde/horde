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
 * Abstract backend class to implement PGP functionality.
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
 * @package   Pgp
 */
class Horde_Pgp_Backend
{
    /**
     * Is this driver supported?
     *
     * @return boolean  True if supported.
     */
    static public function supported()
    {
        return true;
    }

    /**
     * Generates a private key.
     *
     * @param array $opts  Configuration:
     *   - comment: (string) The comment to use.
     *   - email: (string) The email to use.
     *   - expire: (integer) Expiration date (UNIX timestamp).
     *   - hash: (string) Hash function (DEFAULT: SHA256).
     *   - keylength: (integer) The keylength to use.
     *   - name: (string) The name to use.
     *   - passphrase: (string) The passphrase to use.
     *
     * @return Horde_Pgp_Key_Private  The generated private key.
     */
    public function generateKey($opts)
    {
        throw new BadMethodCallException();
    }

    /**
     * Encrypts text using PGP public keys.
     *
     * @param string $text  The text to be PGP encrypted.
     * @param array $keys   The list of public keys to encrypt
     *                      (Horde_Pgp_Element_PublicKey objects).
     * @param array $opts   Additional options:
     *   - cipher: (integer) Symmetric cipher algorithm.
     *   - compress: (boolean) Compression algorithm.
     *
     * @return Horde_Pgp_Element_Message  The encrypted message.
     */
    public function encrypt($text, $keys, $opts)
    {
        throw new BadMethodCallException();
    }

    /**
     * Encrypts text using a PGP symmetric passphrase.
     *
     * @param string $text        The text to be PGP encrypted.
     * @param array $passphrase   The symmetric passphrase(s).
     * @param array $opts         Additional options:
     *   - cipher: (integer) Symmetric cipher algorithm.
     *   - compress: (boolean) Compression algorithm.
     *
     * @return Horde_Pgp_Element_Message  The encrypted message.
     */
    public function encryptSymmetric($text, $passphrase, $opts)
    {
        throw new BadMethodCallException();
    }

    /**
     * Sign a message using a PGP private key.
     *
     * @param string $text                       The text to be PGP signed.
     * @param Horde_Pgp_Element_PrivateKey $key  The private key to use for
     *                                           signing.
     * @param integer $mode                      The signing mode. Either
     *                                           'clear', 'detach', or
     *                                           'message'.
     * @param array $opts                        Additional options:
     *   - compress: (boolean) Compression algorithm.
     *   - sign_hash: (string) The hash method to use.
     *
     * @return mixed  The signed message.
     */
    public function sign($text, $key, $mode, $opts = array())
    {
        throw new BadMethodCallException();
    }

    /**
     * Decrypts text using a PGP private key.
     *
     * @param Horde_Pgp_Element_Message $msg     The message to be decrypted.
     * @param Horde_Pgp_Element_PrivateKey $key  The private key to use for
     *                                           decryption.
     *
     * @return Horde_Pgp_Element_Message  The decrypted message.
     */
    public function decrypt($msg, $key)
    {
        throw new BadMethodCallException();
    }

    /**
     * Decrypts text using a PGP symmetric passphrase.
     *
     * @param Horde_Pgp_Element_Message $msg  The message to be decrypted.
     * @param string $passphrase              The symmetric passphrase used to
     *                                        encrypt the data.
     *
     * @return Horde_Pgp_Element_Message  The decrypted message.
     */
    public function decryptSymmetric($msg, $passphrase)
    {
        throw new BadMethodCallException();
    }

    /**
     * Verifies data using a PGP public key.
     *
     * @param Horde_Pgp_Element $msg            The text to be verified.
     * @param Horde_Pgp_Element_PublicKey $key  Public key used for signing.
     *
     * @return array  {@see Horde_Pgp#verify()}.
     */
    public function verify($msg, $key)
    {
        throw new BadMethodCallException();
    }

}
