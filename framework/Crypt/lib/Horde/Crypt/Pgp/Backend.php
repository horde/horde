<?php
/**
 * Copyright 2015-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Crypt
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
 * @copyright 2015-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Crypt
 * @internal
 */
class Horde_Crypt_Pgp_Backend
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
     * Generates a personal public/private keypair combination.
     *
     * @param array $opts  Configuration:
     *   - comment: (string) The comment to use.
     *   - email: (string) The email to use.
     *   - expire: (integer) The expiration date (UNIX timestamp). No
     *             expiration if empty.
     *   - keylength: (integer) The keylength to use.
     *   - key_type: (string) Key type.
     *   - name: (string) The name to use.
     *   - passphrase: (string) The passphrase to use.
     *   - subkey_type: (string) Subkey type.
     *
     * @return mixed  False on error; an array on success consisting of the
     *                following keys/values:
     *   - private: (string) Private key.
     *   - public: (string) Public key.
     */
    public function generateKey($opts)
    {
        throw new BadMethodCallException();
    }

    /**
     * Returns information on a PGP data block.
     *
     * @param string $pgpdata  The PGP data block.
     *
     * @return array  An array with information on the PGP data block.
     *                {@see Horde_Crypt_Pgp::pgpPacketInformation()}
     */
    public function packetInfo($pgpdata)
    {
        throw new BadMethodCallException();
    }

    /**
     * Returns all information on a PGP data block.
     *
     * @since Horde_Crypt 2.7.0
     *
     * @param string $pgpdata  The PGP data block.
     *
     * @return array  An array with information on the PGP data block.
     *                {@see Horde_Crypt_Pgp::pgpPacketInformationMultiple()}
     */
    public function packetInfoMultiple($pgpdata)
    {
        throw new BadMethodCallException();
    }

    /**
     * Returns the key ID of the key used to sign a block of PGP data.
     *
     * @param string $text  The PGP signed text block.
     *
     * @return mixed  The key ID of the key used to sign $text.
     */
    public function getSignersKeyId($text)
    {
        throw new BadMethodCallException();
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
        throw new BadMethodCallException();
    }

    /**
     * Returns whether a text has been encrypted symmetrically.
     *
     * @param string $text  The PGP encrypted text.
     *
     * @return boolean  True if the text is symmetrically encrypted.
     */
    public function isEncryptedSymmetrically($text)
    {
        throw new BadMethodCallException();
    }

    /**
     * Encrypts a message in PGP format using a public key.
     *
     * @param string $text   The text to be encrypted.
     * @param array $params  The parameters needed for encryption.
     *   - passphrase: The passphrase for the symmetric encryption (REQUIRED
     *                 if 'symmetric' is true)
     *   - recips: An array with the e-mail address of the recipient as the
     *             key and that person's public key as the value.
     *             (REQUIRED if 'symmetric' is false)
     *   - symmetric: Whether to use symmetric instead of asymmetric
     *                encryption (defaults to false).
     *   - type: [REQUIRED] 'message'
     *
     * @return string  The encrypted message.
     */
    public function encryptMessage($text, $params)
    {
        throw new BadMethodCallException();
    }

    /**
     * Signs a message in PGP format using a private key.
     *
     * @param string $text   The text to be signed.
     * @param array $params  The parameters needed for signing.
     *   - passphrase: [REQUIRED] Passphrase for PGP Key.
     *   - privkey: [REQUIRED] PGP private key.
     *   - pubkey: [REQUIRED] PGP public key.
     *   - sigtype: Determine the signature type to use.
     *              - 'cleartext': Make a clear text signature
     *              - 'detach': Make a detached signature (DEFAULT)
     *   - type: [REQUIRED] 'signature'
     *
     * @return string  The signed message.
     */
    public function encryptSignature($text, $params)
    {
        throw new BadMethodCallException();
    }

    /**
     * Decrypts an PGP encrypted message using a private/public keypair and a
     * passhprase.
     *
     * @param string $text   The text to be decrypted.
     * @param array $params  The parameters needed for decryption.
     *   - no_passphrase: Passphrase is not required.
     *   - passphrase: Passphrase for PGP Key. (REQUIRED, see no_passphrase)
     *   - privkey: PGP private key. (REQUIRED for asymmetric encryption)
     *   - pubkey: PGP public key. (REQUIRED for asymmetric encryption)
     *   - type: [REQUIRED] 'message'
     *
     * @return object  An object with the following properties:
     *   - message: (string) The signature result text.
     *   - result: (boolean) The result of the signature test.
     */
    public function decryptMessage($text, $params)
    {
        throw new BadMethodCallException();
    }

    /**
     * Decrypts an PGP signed message using a public key.
     *
     * @param string $text   The text to be verified.
     * @param array $params  The parameters needed for verification.
     *   - charset: Charset of the message body.
     *   - pubkey: [REQUIRED] PGP public key.
     *   - signature: PGP signature block. (REQUIRED for detached signature)
     *   - type: [REQUIRED] 'signature' or 'detached-signature'
     *
     * @return object  An object with the following properties:
     *   - message: (string) The signature result text.
     *   - result: (boolean) The result of the signature test.
     */
    public function decryptSignature($text, $params)
    {
        throw new BadMethodCallException();
    }

    /**
     * Generates a public key from a private key.
     *
     * @param string $data  Armor text of private key.
     *
     * @return string  Armor text of public key.
     */
    public function getPublicKeyFromPrivateKey($data)
    {
        throw new BadMethodCallException();
    }

}
