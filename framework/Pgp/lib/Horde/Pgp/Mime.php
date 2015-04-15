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

}
