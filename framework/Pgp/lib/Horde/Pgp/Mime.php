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
 * Extend the PGP object to produce MIME PGP data (RFC 3156).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */
class Horde_Pgp_Mime
extends Horde_Pgp
{
    /**
     * Signs a MIME part using PGP.
     *
     * @param Horde_Mime_Part $part  The object to sign.
     * @param mixed $key             The private key to use for signing (must
     *                               be decrypted).
     * @param array $opts            Additional options:
     *   - nocompress: (boolean) If true, don't compress signed data.
     *
     * @return Horde_Mime_Part  A signed object.
     * @throws Horde_Pgp_Exception
     */
    public function signPart(
        Horde_Mime_Part $part, $key, array $opts = array()
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
        $detach_sig = $this->signDetached(
            $part->toString(array(
                'canonical' => true,
                'headers' => true
            )),
            $key,
            $opts
        );

        /* Add the PGP signature. */
        $sign = new Horde_Mime_Part();
        $sign->setType('application/pgp-signature');
        $sign->setHeaderCharset('UTF-8');
        $sign->setDisposition('inline');
        $sign->setDescription(
            Horde_Pgp_Translation::t("PGP Digital Signature")
        );
        $sign->setContents(
            strval($detach_sig),
            array('encoding' => '7bit')
        );

        /* Setup the multipart part. */
        $base = new Horde_Mime_Part();
        $base->setType('multipart/signed');
        $base->setContents(
            "This message is in MIME format and has been PGP signed.\n"
        );
        $base->addPart($part);
        $base->addPart($sign);

        /* Add Content-Type paremeter info. (RFC 3156 [5]) */
        $base->setContentTypeParameter(
            'protocol',
            'application/pgp-signature'
        );

        $p = $detach_sig->getSignaturePacket();
        $base->setContentTypeParameter(
            'micalg',
            'pgp-' . strtolower($p::$hash_algorithms[$p->hash_algorithm])
        );

        return $base;
    }

    /**
     * Encrypts a MIME part using PGP.
     *
     * @param Horde_Mime_Part $part  The object to encrypt.
     * @param mixed $keys            The public key(s) to use for encryption.
     * @param array $opts            Additional options:
     *   - nocompress: (boolean) If true, don't compress encrypted data.
     *
     * @return Horde_Mime_Part  An encrypted object.
     * @throws Horde_Pgp_Exception
     */
    public function encryptPart(
        Horde_Mime_Part $part, $keys, array $opts = array()
    )
    {
        $encrypted = $this->encrypt(
            $part->toString(array(
                'canonical' => true,
                'headers' => true
            )),
            $keys,
            $opts
        );

        $base = $this->_encryptPart($encrypted);
        $base->setHeaderCharset('UTF-8');
        $base->setDescription(
            Horde_Pgp_Translation::t("PGP Encrypted Data")
        );
        $base->setContents(
            "This message is in MIME format and has been PGP encrypted.\n"
        );

        return $base;
    }

    /**
     * Create the base MIME part used for encryption (RFC 3156 [4]).
     *
     * @param Horde_Pgp_Element_Message $encrypted  Encrypted data.
     *
     * @return Horde_Mime_Part  Base encrypted MIME part.
     */
    protected function _encryptPart($encrypted)
    {
        $base = new Horde_Mime_Part();
        $base->setType('multipart/encrypted');
        $base->setHeaderCharset('UTF-8');
        $base->setContentTypeParameter(
            'protocol',
            'application/pgp-encrypted'
        );

        $part1 = new Horde_Mime_Part();
        $part1->setType('application/pgp-encrypted');
        $part1->setCharset(null);
        $part1->setContents("Version: 1\n", array('encoding' => '7bit'));
        $base->addPart($part1);

        $part2 = new Horde_Mime_Part();
        $part2->setType('application/octet-stream');
        $part2->setCharset(null);
        $part2->setContents(strval($encrypted), array('encoding' => '7bit'));
        $part2->setDisposition('inline');
        $base->addPart($part2);

        return $base;
    }

    /**
     * Signs and encrypts a MIME part using PGP.
     *
     * @param Horde_Mime_Part $part  The part to sign and encrypt.
     * @param mixed $privkey         The private key to use for signing (must
     *                               be decrypted).
     * @param mixed $pubkeys         The public keys to use for encryption.
     * @param array $opts            Additional options:
     *   - nocompress: (boolean) If true, don't compress signed/encrypted
     *                 data.
     *
     * @return Horde_Mime_Part  A signed and encrypted part.
     * @throws Horde_Pgp_Exception
     */
    public function signAndEncryptPart(
        Horde_Mime_Part $part, $privkey, $pubkeys, array $opts = array()
    )
    {
        /* We use the combined method of sign & encryption in a single
         * OpenPGP packet (RFC 3156 [6.2]). */
        $signed = $this->sign(
            $part->toString(array(
                'canonical' => true,
                'headers' => true
            )),
            $privkey,
            $opts
        );

        $encrypted = $this->encrypt(
            $signed->message,
            $pubkeys,
            array_merge($opts, array(
                'nocompress' => true
            ))
        );

        $base = $this->_encryptPart($encrypted);
        $base->setHeaderCharset('UTF-8');
        $base->setDescription(
            Horde_Pgp_Translation::t("PGP Signed/Encrypted Data")
        );
        $base->setContents(
            "This message is in MIME format and has been PGP signed and encrypted.\n"
        );

        return $base;
    }

    /**
     * Generate a Horde_Mime_Part object that contains a public key (RFC
     * 3156 [7]).
     *
     * @param mixed $key  The public key.
     *
     * @return Horde_Mime_Part  An object that contains the public key.
     */
    public function publicKeyPart($key)
    {
        $key = Horde_Pgp_Element_PublicKey::create($key);

        $part = new Horde_Mime_Part();
        $part->setType('application/pgp-keys');
        $part->setHeaderCharset('UTF-8');
        $part->setDescription(Horde_Crypt_Translation::t("PGP Public Key"));
        $part->setContents(strval($key), array('encoding' => '7bit'));

        return $part;
    }

}
