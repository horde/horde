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
     *
     * @return Horde_Pgp_Element_Message  The encrypted message.
     */
    public function encrypt($text, $keys)
    {
        throw new BadMethodCallException();
    }

    /**
     * Encrypts text using a PGP symmetric passphrase.
     *
     * @param string $text        The text to be PGP encrypted.
     * @param string $passphrase  The symmetric passphrase.
     *
     * @return Horde_Pgp_Element_Message  The encrypted message.
     */
    public function encryptSymmetric($text, $passphrase)
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
     *
     * @return mixed  The signed message.
     */
    public function sign($text, $key, $mode)
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
     * @return string $text  The decrypted text.
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
     * @return array $data  {@see Horde_Pgp::decryptSymmetric()}
     */
    public function decryptSymmetric($msg, $passphrase)
    {
        throw new BadMethodCallException();
    }

    /**
     * TODO
     */
    public function verify($text, $key, $sig)
    {
        throw new BadMethodCallException();
    }

}
