<?php
/**
 * Horde_Crypt_Smime:: provides a framework for Horde applications to
 * interact with the OpenSSL library and implement S/MIME.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Crypt
 */
class Horde_Crypt_Smime extends Horde_Crypt
{
    /**
     * Object Identifers to name array.
     *
     * @var array
     */
    protected $_oids = array(
        '2.5.4.3' => 'CommonName',
        '2.5.4.4' => 'Surname',
        '2.5.4.6' => 'Country',
        '2.5.4.7' => 'Location',
        '2.5.4.8' => 'StateOrProvince',
        '2.5.4.9' => 'StreetAddress',
        '2.5.4.10' => 'Organisation',
        '2.5.4.11' => 'OrganisationalUnit',
        '2.5.4.12' => 'Title',
        '2.5.4.20' => 'TelephoneNumber',
        '2.5.4.42' => 'GivenName',

        '2.5.29.14' => 'id-ce-subjectKeyIdentifier',

        '2.5.29.14' => 'id-ce-subjectKeyIdentifier',
        '2.5.29.15' => 'id-ce-keyUsage',
        '2.5.29.17' => 'id-ce-subjectAltName',
        '2.5.29.19' => 'id-ce-basicConstraints',
        '2.5.29.31' => 'id-ce-CRLDistributionPoints',
        '2.5.29.32' => 'id-ce-certificatePolicies',
        '2.5.29.35' => 'id-ce-authorityKeyIdentifier',
        '2.5.29.37' => 'id-ce-extKeyUsage',

        '1.2.840.113549.1.9.1' => 'Email',
        '1.2.840.113549.1.1.1' => 'RSAEncryption',
        '1.2.840.113549.1.1.2' => 'md2WithRSAEncryption',
        '1.2.840.113549.1.1.4' => 'md5withRSAEncryption',
        '1.2.840.113549.1.1.5' => 'SHA-1WithRSAEncryption',
        '1.2.840.10040.4.3' => 'id-dsa-with-sha-1',

        '1.3.6.1.5.5.7.3.2' => 'id_kp_clientAuth',

        '2.16.840.1.113730.1.1' => 'netscape-cert-type',
        '2.16.840.1.113730.1.2' => 'netscape-base-url',
        '2.16.840.1.113730.1.3' => 'netscape-revocation-url',
        '2.16.840.1.113730.1.4' => 'netscape-ca-revocation-url',
        '2.16.840.1.113730.1.7' => 'netscape-cert-renewal-url',
        '2.16.840.1.113730.1.8' => 'netscape-ca-policy-url',
        '2.16.840.1.113730.1.12' => 'netscape-ssl-server-name',
        '2.16.840.1.113730.1.13' => 'netscape-comment',
    );

    /**
     * Verify a passphrase for a given private key.
     *
     * @param string $private_key  The user's private key.
     * @param string $passphrase   The user's passphrase.
     *
     * @return boolean  Returns true on valid passphrase, false on invalid
     *                  passphrase.
     */
    public function verifyPassphrase($private_key, $passphrase)
    {
        $res = is_null($passphrase)
            ? openssl_pkey_get_private($private_key)
            : openssl_pkey_get_private($private_key, $passphrase);

        return is_resource($res);
    }

    /**
     * Encrypt text using S/MIME.
     *
     * @param string $text   The text to be encrypted.
     * @param array $params  The parameters needed for encryption.
     *                       See the individual _encrypt*() functions for
     *                       the parameter requirements.
     *
     * @return string  The encrypted message.
     * @throws Horde_Crypt_Exception
     */
    public function encrypt($text, $params = array())
    {
        /* Check for availability of OpenSSL PHP extension. */
        $this->checkForOpenSSL();

        if (isset($params['type'])) {
            if ($params['type'] === 'message') {
                return $this->_encryptMessage($text, $params);
            } elseif ($params['type'] === 'signature') {
                return $this->_encryptSignature($text, $params);
            }
        }
    }

    /**
     * Decrypt text via S/MIME.
     *
     * @param string $text   The text to be smime decrypted.
     * @param array $params  The parameters needed for decryption.
     *                       See the individual _decrypt*() functions for
     *                       the parameter requirements.
     *
     * @return string  The decrypted message.
     * @throws Horde_Crypt_Exception
     */
    public function decrypt($text, $params = array())
    {
        /* Check for availability of OpenSSL PHP extension. */
        $this->checkForOpenSSL();

        if (isset($params['type'])) {
            if ($params['type'] === 'message') {
                return $this->_decryptMessage($text, $params);
            } elseif (($params['type'] === 'signature') ||
                      ($params['type'] === 'detached-signature')) {
                return $this->_decryptSignature($text, $params);
            }
        }
    }

    /**
     * Verify a signature using via S/MIME.
     *
     * @param string $text  The multipart/signed data to be verified.
     * @param mixed $certs  Either a single or array of root certificates.
     *
     * @return stdClass  Object with the following elements:
     *                   'result' -> Returns true on success.
     *                   'cert' -> The certificate of the signer stored
     *                             in the message (in PEM format).
     *                   'email' -> The email of the signing person.
     * @throws Horde_Crypt_Exception
     */
    public function verify($text, $certs)
    {
        /* Check for availability of OpenSSL PHP extension. */
        $openssl = $this->checkForOpenSSL();

        /* Create temp files for input/output. */
        $input = $this->_createTempFile('horde-smime');
        $output = $this->_createTempFile('horde-smime');

        /* Write text to file */
        file_put_contents($input, $text);
        unset($text);

        $root_certs = array();
        if (!is_array($certs)) {
            $certs = array($certs);
        }
        foreach ($certs as $file) {
            if (file_exists($file)) {
                $root_certs[] = $file;
            }
        }

        $ob = new stdClass;

        if (!empty($root_certs)) {
            $result = openssl_pkcs7_verify($input, 0, $output, $root_certs);
            /* Message verified */
            if ($result === true) {
                $ob->result = true;
                $ob->cert = file_get_contents($output);
                $ob->email = $this->getEmailFromKey($ob->cert);
                return $ob;
            }
        }

        /* Try again without verfying the signer's cert */
        $result = openssl_pkcs7_verify($input, PKCS7_NOVERIFY, $output);

        if ($result === true) {
            throw new Horde_Crypt_Exception($this->_dict->t("Message Verified Successfully but the signer's certificate could not be verified."));
        } elseif ($result == -1) {
            throw new Horde_Crypt_Exception($this->_dict->t("Verification failed - an unknown error has occurred."));
        } else {
            throw new Horde_Crypt_Exception($this->_dict->t("Verification failed - this message may have been tampered with."));
        }

        $ob->cert = file_get_contents($output);
        $ob->email = $this->getEmailFromKey($ob->cert);

        return $ob;
    }

    /**
     * Extract the contents from signed S/MIME data.
     *
     * @param string $data     The signed S/MIME data.
     * @param string $sslpath  The path to the OpenSSL binary.
     *
     * @return string  The contents embedded in the signed data.
     * @throws Horde_Crypt_Exception
     */
    public function extractSignedContents($data, $sslpath)
    {
        /* Check for availability of OpenSSL PHP extension. */
        $this->checkForOpenSSL();

        /* Create temp files for input/output. */
        $input = $this->_createTempFile('horde-smime');
        $output = $this->_createTempFile('horde-smime');

        /* Write text to file. */
        file_put_contents($input, $data);
        unset($data);

        exec($sslpath . ' smime -verify -noverify -nochain -in ' . $input . ' -out ' . $output);

        $ret = file_get_contents($output);
        if ($ret) {
            return $ret;
        }

        throw new Horde_Crypt_Exception($this->_dict->t("OpenSSL error: Could not extract data from signed S/MIME part."));
    }

    /**
     * Sign a MIME part using S/MIME.
     *
     * @param Horde_Mime_Part $mime_part  The object to sign.
     * @param array $params               The parameters required for signing.
     *
     * @return mixed  A Horde_Mime_Part object that is signed.
     * @throws Horde_Crypt_Exception
     */
    public function signMIMEPart($mime_part, $params)
    {
        /* Sign the part as a message */
        $message = $this->encrypt($mime_part->toString(array('headers' => true, 'canonical' => true)), $params);

        /* Break the result into its components */
        $mime_message = Horde_Mime_Part::parseMessage($message, array('forcemime' => true));

        $smime_sign = $mime_message->getPart('2');
        $smime_sign->setDescription($this->_dict->t("S/MIME Cryptographic Signature"));
        $smime_sign->setTransferEncoding('base64', array('send' => true));

        $smime_part = new Horde_Mime_Part();
        $smime_part->setType('multipart/signed');
        $smime_part->setContents("This is a cryptographically signed message in MIME format.\n");
        $smime_part->setContentTypeParameter('protocol', 'application/pkcs7-signature');
        $smime_part->setContentTypeParameter('micalg', 'sha1');
        $smime_part->addPart($mime_part);
        $smime_part->addPart($smime_sign);

        return $smime_part;
    }

    /**
     * Encrypt a MIME part using S/MIME.
     *
     * @param Horde_Mime_Part $mime_part  The object to encrypt.
     * @param array $params               The parameters required for
     *                                    encryption.
     *
     * @return mixed  A Horde_Mime_Part object that is encrypted.
     * @throws Horde_Crypt_Exception
     */
    public function encryptMIMEPart($mime_part, $params = array())
    {
        /* Sign the part as a message */
        $message = $this->encrypt($mime_part->toString(array('headers' => true, 'canonical' => true)), $params);

        $msg = new Horde_Mime_Part();
        $msg->setCharset($this->_params['email_charset']);
        $msg->setDescription(Horde_String::convertCharset($this->_dict->t("S/MIME Encrypted Message"), 'UTF-8', $this->_params['email_charset']));
        $msg->setDisposition('inline');
        $msg->setType('application/pkcs7-mime');
        $msg->setContentTypeParameter('smime-type', 'enveloped-data');
        $msg->setContents(substr($message, strpos($message, "\n\n") + 2));

        return $msg;
    }

    /**
     * Encrypt a message in S/MIME format using a public key.
     *
     * @param string $text   The text to be encrypted.
     * @param array $params  The parameters needed for encryption.
     * <pre>
     * Parameters:
     * ===========
     * 'type'   => 'message' (REQUIRED)
     * 'pubkey' => public key (REQUIRED)
     * </pre>
     *
     * @return string  The encrypted message.
     * @throws Horde_Crypt_Exception
     */
    protected function _encryptMessage($text, $params)
    {
        /* Check for required parameters. */
        if (!isset($params['pubkey'])) {
            throw new Horde_Crypt_Exception($this->_dict->t("A public S/MIME key is required to encrypt a message."));
        }

        /* Create temp files for input/output. */
        $input = $this->_createTempFile('horde-smime');
        $output = $this->_createTempFile('horde-smime');

        /* Store message in file. */
        file_put_contents($input, $text);
        unset($text);

        /* Encrypt the document. */
        if (openssl_pkcs7_encrypt($input, $output, $params['pubkey'], array())) {
            $result = file_get_contents($output);
            if (!empty($result)) {
                return $this->_fixContentType($result, 'encrypt');
            }
        }

        throw new Horde_Crypt_Exception($this->_dict->t("Could not S/MIME encrypt message."));
    }

    /**
     * Sign a message in S/MIME format using a private key.
     *
     * @param string $text   The text to be signed.
     * @param array $params  The parameters needed for signing.
     * <pre>
     * Parameters:
     * ===========
     * 'certs'       =>  Additional signing certs (Optional)
     * 'passphrase'  =>  Passphrase for key (REQUIRED)
     * 'privkey'     =>  Private key (REQUIRED)
     * 'pubkey'      =>  Public key (REQUIRED)
     * 'sigtype'     =>  Determine the signature type to use. (Optional)
     *                   'cleartext'  --  Make a clear text signature
     *                   'detach'     --  Make a detached signature (DEFAULT)
     * 'type'        =>  'signature' (REQUIRED)
     * </pre>
     *
     * @return string  The signed message.
     * @throws Horde_Crypt_Exception
     */
    protected function _encryptSignature($text, $params)
    {
        /* Check for required parameters. */
        if (!isset($params['pubkey']) ||
            !isset($params['privkey']) ||
            !array_key_exists('passphrase', $params)) {
            throw new Horde_Crypt_Exception($this->_dict->t("A public S/MIME key, private S/MIME key, and passphrase are required to sign a message."));
        }

        /* Create temp files for input/output/certificates. */
        $input = $this->_createTempFile('horde-smime');
        $output = $this->_createTempFile('horde-smime');
        $certs = $this->_createTempFile('horde-smime');

        /* Store message in temporary file. */
        file_put_contents($input, $text);
        unset($text);

        /* Store additional certs in temporary file. */
        if (!empty($params['certs'])) {
            file_put_contents($certs, $params['certs']);
        }

        /* Determine the signature type to use. */
        $flags = (isset($params['sigtype']) && ($params['sigtype'] == 'cleartext'))
            ? PKCS7_TEXT
            : PKCS7_DETACHED;

        $privkey = (is_null($params['passphrase'])) ? $params['privkey'] : array($params['privkey'], $params['passphrase']);

        if (empty($params['certs'])) {
            $res = openssl_pkcs7_sign($input, $output, $params['pubkey'], $privkey, array(), $flags);
        } else {
            $res = openssl_pkcs7_sign($input, $output, $params['pubkey'], $privkey, array(), $flags, $certs);
        }

        if (!$res) {
            throw new Horde_Crypt_Exception($this->_dict->t("Could not S/MIME sign message."));
        }

        $data = file_get_contents($output);
        return $this->_fixContentType($data, 'signature');
    }

    /**
     * Decrypt an S/MIME encrypted message using a private/public keypair
     * and a passhprase.
     *
     * @param string $text   The text to be decrypted.
     * @param array $params  The parameters needed for decryption.
     * <pre>
     * Parameters:
     * ===========
     * 'type'        =>  'message' (REQUIRED)
     * 'pubkey'      =>  public key. (REQUIRED)
     * 'privkey'     =>  private key. (REQUIRED)
     * 'passphrase'  =>  Passphrase for Key. (REQUIRED)
     * </pre>
     *
     * @return string  The decrypted message.
     * @throws Horde_Crypt_Exception
     */
    protected function _decryptMessage($text, $params)
    {
        /* Check for required parameters. */
        if (!isset($params['pubkey']) ||
            !isset($params['privkey']) ||
            !array_key_exists('passphrase', $params)) {
            throw new Horde_Crypt_Exception($this->_dict->t("A public S/MIME key, private S/MIME key, and passphrase are required to decrypt a message."));
        }

        /* Create temp files for input/output. */
        $input = $this->_createTempFile('horde-smime');
        $output = $this->_createTempFile('horde-smime');

        /* Store message in file. */
        file_put_contents($input, $text);
        unset($text);

        $privkey = is_null($params['passphrase'])
            ? $params['privkey']
            : array($params['privkey'], $params['passphrase']);
        if (openssl_pkcs7_decrypt($input, $output, $params['pubkey'], $privkey)) {
            return file_get_contents($output);
        }

        throw new Horde_Crypt_Exception($this->_dict->t("Could not decrypt S/MIME data."));
    }

    /**
     * Sign and Encrypt a MIME part using S/MIME.
     *
     * @param Horde_Mime_Part $mime_part   The object to sign and encrypt.
     * @param array $sign_params           The parameters required for
     *                                     signing. @see _encryptSignature().
     * @param array $encrypt_params        The parameters required for
     *                                     encryption.
     *                                     @see _encryptMessage().
     *
     * @return mixed  A Horde_Mime_Part object that is signed and encrypted.
     * @throws Horde_Crypt_Exception
     */
    public function signAndEncryptMIMEPart($mime_part, $sign_params = array(),
                                           $encrypt_params = array())
    {
        $part = $this->signMIMEPart($mime_part, $sign_params);
        return $this->encryptMIMEPart($part, $encrypt_params);
    }

    /**
     * Convert a PEM format certificate to readable HTML version
     *
     * @param string $cert   PEM format certificate
     *
     * @return string  HTML detailing the certificate.
     */
    public function certToHTML($cert)
    {
        /* Common Fields */
        $fieldnames = array(
            'Email' => $this->_dict->t("Email Address"),
            'CommonName' => $this->_dict->t("Common Name"),
            'Organisation' => $this->_dict->t("Organisation"),
            'OrganisationalUnit' => $this->_dict->t("Organisational Unit"),
            'Country' => $this->_dict->t("Country"),
            'StateOrProvince' => $this->_dict->t("State or Province"),
            'Location' => $this->_dict->t("Location"),
            'StreetAddress' => $this->_dict->t("Street Address"),
            'TelephoneNumber' => $this->_dict->t("Telephone Number"),
            'Surname' => $this->_dict->t("Surname"),
            'GivenName' => $this->_dict->t("Given Name")
        );

        /* Netscape Extensions */
        $fieldnames += array(
            'netscape-cert-type' => $this->_dict->t("Netscape certificate type"),
            'netscape-base-url' => $this->_dict->t("Netscape Base URL"),
            'netscape-revocation-url' => $this->_dict->t("Netscape Revocation URL"),
            'netscape-ca-revocation-url' => $this->_dict->t("Netscape CA Revocation URL"),
            'netscape-cert-renewal-url' => $this->_dict->t("Netscape Renewal URL"),
            'netscape-ca-policy-url' => $this->_dict->t("Netscape CA policy URL"),
            'netscape-ssl-server-name' => $this->_dict->t("Netscape SSL server name"),
            'netscape-comment' => $this->_dict->t("Netscape certificate comment")
        );

        /* X590v3 Extensions */
        $fieldnames += array(
            'id-ce-extKeyUsage' => $this->_dict->t("X509v3 Extended Key Usage"),
            'id-ce-basicConstraints' => $this->_dict->t("X509v3 Basic Constraints"),
            'id-ce-subjectAltName' => $this->_dict->t("X509v3 Subject Alternative Name"),
            'id-ce-subjectKeyIdentifier' => $this->_dict->t("X509v3 Subject Key Identifier"),
            'id-ce-certificatePolicies' => $this->_dict->t("Certificate Policies"),
            'id-ce-CRLDistributionPoints' => $this->_dict->t("CRL Distribution Points"),
            'id-ce-keyUsage' => $this->_dict->t("Key Usage")
        );

        $cert_details = $this->parseCert($cert);
        if (!is_array($cert_details)) {
            return '<pre class="fixed">' . $this->_dict->t("Unable to extract certificate details") . '</pre>';
        }
        $certificate = $cert_details['certificate'];

        $text = '<pre class="fixed">';

        /* Subject (a/k/a Certificate Owner) */
        if (isset($certificate['subject'])) {
            $text .= "<strong>" . $this->_dict->t("Certificate Owner") . ":</strong>\n";

            foreach ($certificate['subject'] as $key => $value) {
                if (isset($fieldnames[$key])) {
                    $text .= sprintf("&nbsp;&nbsp;%s: %s\n", $fieldnames[$key], $value);
                } else {
                    $text .= sprintf("&nbsp;&nbsp;*%s: %s\n", $key, $value);
                }
            }
            $text .= "\n";
        }

        /* Issuer */
        if (isset($certificate['issuer'])) {
            $text .= "<strong>" . $this->_dict->t("Issuer") . ":</strong>\n";

            foreach ($certificate['issuer'] as $key => $value) {
                if (isset($fieldnames[$key])) {
                    $text .= sprintf("&nbsp;&nbsp;%s: %s\n", $fieldnames[$key], $value);
                } else {
                    $text .= sprintf("&nbsp;&nbsp;*%s: %s\n", $key, $value);
                }
            }
            $text .= "\n";
        }

        /* Dates  */
        $text .= "<strong>" . $this->_dict->t("Validity") . ":</strong>\n";
        $text .= sprintf("&nbsp;&nbsp;%s: %s\n", $this->_dict->t("Not Before"), strftime("%x %X", $certificate['validity']['notbefore']));
        $text .= sprintf("&nbsp;&nbsp;%s: %s\n", $this->_dict->t("Not After"), strftime("%x %X", $certificate['validity']['notafter']));
        $text .= "\n";

        /* Certificate Owner - Public Key Info */
        $text .= "<strong>" . $this->_dict->t("Public Key Info") . ":</strong>\n";
        $text .= sprintf("&nbsp;&nbsp;%s: %s\n", $this->_dict->t("Public Key Algorithm"), $certificate['subjectPublicKeyInfo']['algorithm']);
        if ($certificate['subjectPublicKeyInfo']['algorithm'] == 'rsaEncryption') {
            if (Horde_Util::extensionExists('bcmath')) {
                $modulus = $certificate['subjectPublicKeyInfo']['subjectPublicKey']['modulus'];
                $modulus_hex = '';
                while ($modulus != '0') {
                    $modulus_hex = dechex(bcmod($modulus, '16')) . $modulus_hex;
                    $modulus = bcdiv($modulus, '16', 0);
                }

                if ((strlen($modulus_hex) > 64) &&
                    (strlen($modulus_hex) < 128)) {
                    str_pad($modulus_hex, 128, '0', STR_PAD_RIGHT);
                } elseif ((strlen($modulus_hex) > 128) &&
                          (strlen($modulus_hex) < 256)) {
                    str_pad($modulus_hex, 256, '0', STR_PAD_RIGHT);
                }

                $text .= "&nbsp;&nbsp;" . sprintf($this->_dict->t("RSA Public Key (%d bit)"), strlen($modulus_hex) * 4) . ":\n";

                $modulus_str = '';

                for ($i = 0, $m_len = strlen($modulus_hex); $i < $m_len; $i += 2) {
                    if (($i % 32) == 0) {
                        $modulus_str .= "\n&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                    }
                    $modulus_str .= substr($modulus_hex, $i, 2) . ':';
                }

                $text .= sprintf("&nbsp;&nbsp;&nbsp;&nbsp;%s: %s\n", $this->_dict->t("Modulus"), $modulus_str);
            }

            $text .= sprintf("&nbsp;&nbsp;&nbsp;&nbsp;%s: %s\n", $this->_dict->t("Exponent"), $certificate['subjectPublicKeyInfo']['subjectPublicKey']['publicExponent']);
        }
        $text .= "\n";

        /* X509v3 extensions */
        if (isset($certificate['extensions'])) {
            $text .= "<strong>" . $this->_dict->t("X509v3 extensions") . ":</strong>\n";

            foreach ($certificate['extensions'] as $key => $value) {
                if (is_array($value)) {
                    $value = $this->_dict->t("Unsupported Extension");
                }
                if (isset($fieldnames[$key])) {
                    $text .= sprintf("&nbsp;&nbsp;%s:\n&nbsp;&nbsp;&nbsp;&nbsp;%s\n", $fieldnames[$key], wordwrap($value, 40, "\n&nbsp;&nbsp;&nbsp;&nbsp;"));
                } else {
                    $text .= sprintf("&nbsp;&nbsp;%s:\n&nbsp;&nbsp;&nbsp;&nbsp;%s\n", $key, wordwrap($value, 60, "\n&nbsp;&nbsp;&nbsp;&nbsp;"));
                }
            }

            $text .= "\n";
        }

        /* Certificate Details */
        $text .= "<strong>" . $this->_dict->t("Certificate Details") . ":</strong>\n";
        $text .= sprintf("&nbsp;&nbsp;%s: %d\n", $this->_dict->t("Version"), $certificate['version']);
        $text .= sprintf("&nbsp;&nbsp;%s: %d\n", $this->_dict->t("Serial Number"), $certificate['serialNumber']);

        foreach ($cert_details['fingerprints'] as $hash => $fingerprint) {
            $label = sprintf($this->_dict->t("%s Fingerprint"), Horde_String::upper($hash));
            $fingerprint_str = '';
            for ($i = 0, $f_len = strlen($fingerprint); $i < $f_len; $i += 2) {
                $fingerprint_str .= substr($fingerprint, $i, 2) . ':';
            }
            $text .= sprintf("&nbsp;&nbsp;%s:\n&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;%s\n", $label, $fingerprint_str);
        }
        $text .= sprintf("&nbsp;&nbsp;%s: %s\n", $this->_dict->t("Signature Algorithm"), $cert_details['signatureAlgorithm']);
        $text .= sprintf("&nbsp;&nbsp;%s:", $this->_dict->t("Signature"));

        $sig_str = '';
        for ($i = 0, $s_len = strlen($cert_details['signature']); $i < $s_len; ++$i) {
            if (($i % 16) == 0) {
                $sig_str .= "\n&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
            }
            $sig_str .= sprintf("%02x:", ord($cert_details['signature'][$i]));
        }

        return $text . $sig_str . "\n</pre>";
    }

    /**
     * Extract the contents of a PEM format certificate to an array.
     *
     * @param string $cert  PEM format certificate
     *
     * @return mixed  Array containing all extractable information about
     *                the certificate. Returns false on error.
     */
    public function parseCert($cert)
    {
        $cert_split = preg_split('/(-----((BEGIN)|(END)) CERTIFICATE-----)/', $cert);
        $raw_cert = base64_decode(isset($cert_split[1]) ? $cert_split[1] : $cert);

        $cert_data = $this->_parseASN($raw_cert);
        if (!is_array($cert_data) ||
            ($cert_data[0] == 'UNKNOWN') ||
            ($cert_data[1][0] == 'UNKNOWN') ||
            /* Bug #8751: Check for required number of fields. The ASN
             * parsing code doesn't seem to be able to handle v1 data - it
             * combines the version and serial number fields.
             * openssl_x509_parse() works, but doesn't have a stable API.
             * Since v1 is such an old standard anyway, best just to abort
             * here. */
            !isset($cert_data[1][0][1][6])) {
            return false;
        }

        $cert_details = array(
            'fingerprints' => array(
                'md5' => hash('md5', $raw_cert),
                'sha1' => hash('sha1', $raw_cert)
            ),
            'certificate' => array(
                'extensions' => array(),
                'version' => $cert_data[1][0][1][0][1] + 1,
                'serialNumber' => $cert_data[1][0][1][1][1],
                'signature' => $cert_data[1][0][1][2][1][0][1],
                'issuer' => $cert_data[1][0][1][3][1],
                'validity' => $cert_data[1][0][1][4][1],
                'subject' => @$cert_data[1][0][1][5][1],
                'subjectPublicKeyInfo' => $cert_data[1][0][1][6][1]
            ),
            'signatureAlgorithm' => $cert_data[1][1][1][0][1],
            'signature' => $cert_data[1][2][1]
        );

        // issuer
        $issuer = array();
        foreach ($cert_details['certificate']['issuer'] as $value) {
            $issuer[$value[1][1][0][1]] = $value[1][1][1][1];
        }
        $cert_details['certificate']['issuer'] = $issuer;

        // subject
        $subject = array();
        foreach ($cert_details['certificate']['subject'] as $value) {
            $subject[$value[1][1][0][1]] = $value[1][1][1][1];
        }
        $cert_details['certificate']['subject'] = $subject;

        // validity
        $vals = $cert_details['certificate']['validity'];
        $cert_details['certificate']['validity'] = array();
        $cert_details['certificate']['validity']['notbefore'] = $vals[0][1];
        $cert_details['certificate']['validity']['notafter'] = $vals[1][1];
        foreach ($cert_details['certificate']['validity'] as $key => $val) {
            $year = substr($val, 0, 2);
            $month = substr($val, 2, 2);
            $day = substr($val, 4, 2);
            $hour = substr($val, 6, 2);
            $minute = substr($val, 8, 2);
            if (($val[11] == '-') || ($val[9] == '+')) {
                // handle time zone offset here
                $seconds = 0;
            } elseif (Horde_String::upper($val[11]) == 'Z') {
                $seconds = 0;
            } else {
                $seconds = substr($val, 10, 2);
                if (($val[11] == '-') || ($val[9] == '+')) {
                    // handle time zone offset here
                }
            }
            $cert_details['certificate']['validity'][$key] = mktime ($hour, $minute, $seconds, $month, $day, $year);
        }

        // Split the Public Key into components.
        $subjectPublicKeyInfo = array();
        $subjectPublicKeyInfo['algorithm'] = $cert_details['certificate']['subjectPublicKeyInfo'][0][1][0][1];
        if ($subjectPublicKeyInfo['algorithm'] == 'rsaEncryption') {
            $subjectPublicKey = $this->_parseASN($cert_details['certificate']['subjectPublicKeyInfo'][1][1]);
            $subjectPublicKeyInfo['subjectPublicKey']['modulus'] = $subjectPublicKey[1][0][1];
            $subjectPublicKeyInfo['subjectPublicKey']['publicExponent'] = $subjectPublicKey[1][1][1];
        }
        $cert_details['certificate']['subjectPublicKeyInfo'] = $subjectPublicKeyInfo;

        if (isset($cert_data[1][0][1][7]) &&
            is_array($cert_data[1][0][1][7][1])) {
            foreach ($cert_data[1][0][1][7][1] as $ext) {
                $oid = $ext[1][0][1];
                $cert_details['certificate']['extensions'][$oid] = $ext[1][1];
            }
        }

        $i = 9;

        while (isset($cert_data[1][0][1][$i]) &&
               is_array($cert_data[1][0][1][$i][1])) {
            $oid = $cert_data[1][0][1][$i][1][0][1];
            $cert_details['certificate']['extensions'][$oid] = $cert_data[1][0][1][$i][1][1];
            ++$i;
        }

        foreach ($cert_details['certificate']['extensions'] as $oid => $val) {
            switch ($oid) {
            case 'netscape-base-url':
            case 'netscape-revocation-url':
            case 'netscape-ca-revocation-url':
            case 'netscape-cert-renewal-url':
            case 'netscape-ca-policy-url':
            case 'netscape-ssl-server-name':
            case 'netscape-comment':
                $val = $this->_parseASN($val[1]);
                $cert_details['certificate']['extensions'][$oid] = $val[1];
                break;

            case 'id-ce-subjectAltName':
                $val = $this->_parseASN($val[1]);
                $cert_details['certificate']['extensions'][$oid] = '';
                foreach ($val[1] as $name) {
                    if (!empty($cert_details['certificate']['extensions'][$oid])) {
                        $cert_details['certificate']['extensions'][$oid] .= ', ';
                    }
                    $cert_details['certificate']['extensions'][$oid] .= $name[1];
                }
                break;

            case 'netscape-cert-type':
                $val = $this->_parseASN($val[1]);
                $val = ord($val[1]);
                $newVal = '';

                if ($val & 0x80) {
                    $newVal .= empty($newVal) ? 'SSL client' : ', SSL client';
                }
                if ($val & 0x40) {
                    $newVal .= empty($newVal) ? 'SSL server' : ', SSL server';
                }
                if ($val & 0x20) {
                    $newVal .= empty($newVal) ? 'S/MIME' : ', S/MIME';
                }
                if ($val & 0x10) {
                    $newVal .= empty($newVal) ? 'Object Signing' : ', Object Signing';
                }
                if ($val & 0x04) {
                    $newVal .= empty($newVal) ? 'SSL CA' : ', SSL CA';
                }
                if ($val & 0x02) {
                    $newVal .= empty($newVal) ? 'S/MIME CA' : ', S/MIME CA';
                }
                if ($val & 0x01) {
                    $newVal .= empty($newVal) ? 'Object Signing CA' : ', Object Signing CA';
                }

                $cert_details['certificate']['extensions'][$oid] = $newVal;
                break;

            case 'id-ce-extKeyUsage':
                $val = $this->_parseASN($val[1]);
                $val = $val[1];

                $newVal = '';
                if ($val[0][1] != 'sequence') {
                    $val = array($val);
                } else {
                    $val = $val[1][1];
                }
                foreach ($val as $usage) {
                    if ($usage[1] == 'id_kp_clientAuth') {
                        $newVal .= empty($newVal) ? 'TLS Web Client Authentication' : ', TLS Web Client Authentication';
                    } else {
                        $newVal .= empty($newVal) ? $usage[1] : ', ' . $usage[1];
                    }
                }
                $cert_details['certificate']['extensions'][$oid] = $newVal;
                break;

            case 'id-ce-subjectKeyIdentifier':
                $val = $this->_parseASN($val[1]);
                $val = $val[1];

                $newVal = '';

                for ($i = 0, $v_len = strlen($val); $i < $v_len; ++$i) {
                    $newVal .= sprintf("%02x:", ord($val[$i]));
                }
                $cert_details['certificate']['extensions'][$oid] = $newVal;
                break;

            case 'id-ce-authorityKeyIdentifier':
                $val = $this->_parseASN($val[1]);
                if ($val[0] == 'string') {
                    $val = $val[1];

                    $newVal = '';
                    for ($i = 0, $v_len = strlen($val); $i < $v_len; ++$i) {
                        $newVal .= sprintf("%02x:", ord($val[$i]));
                    }
                    $cert_details['certificate']['extensions'][$oid] = $newVal;
                } else {
                    $cert_details['certificate']['extensions'][$oid] = $this->_dict->t("Unsupported Extension");
                }
                break;

            case 'id-ce-basicConstraints':
            case 'default':
                $cert_details['certificate']['extensions'][$oid] = $this->_dict->t("Unsupported Extension");
                break;
            }
        }

        return $cert_details;
    }

    /**
     * Attempt to parse ASN.1 formated data.
     *
     * @param string $data  ASN.1 formated data
     *
     * @return array  Array contained the extracted values.
     */
    protected function _parseASN($data)
    {
        $result = array();

        while (strlen($data) > 1) {
            $class = ord($data[0]);
            switch ($class) {
            case 0x30:
                // Sequence
                $len = ord($data[1]);
                $bytes = 0;
                if ($len & 0x80) {
                    $bytes = $len & 0x0f;
                    $len = 0;
                    for ($i = 0; $i < $bytes; $i++) {
                        $len = ($len << 8) | ord($data[$i + 2]);
                    }
                }
                $sequence_data = substr($data, 2 + $bytes, $len);
                $data = substr($data, 2 + $bytes + $len);

                $values = $this->_parseASN($sequence_data);
                if (!is_array($values) || is_string($values[0])) {
                    $values = array($values);
                }
                $sequence_values = array();
                $i = 0;
                foreach ($values as $val) {
                    if ($val[0] == 'extension') {
                        $sequence_values['extensions'][] = $val;
                    } else {
                        $sequence_values[$i++] = $val;
                    }
                }
                $result[] = array('sequence', $sequence_values);
                break;

            case 0x31:
                // Set of
                $len = ord($data[1]);
                $bytes = 0;
                if ($len & 0x80) {
                    $bytes = $len & 0x0f;
                    $len = 0;
                    for ($i = 0; $i < $bytes; $i++) {
                        $len = ($len << 8) | ord($data[$i + 2]);
                    }
                }
                $sequence_data = substr($data, 2 + $bytes, $len);
                $data = substr($data, 2 + $bytes + $len);
                $result[] = array('set', $this->_parseASN($sequence_data));
                break;

            case 0x01:
                // Boolean type
                $boolean_value = (ord($data[2]) == 0xff);
                $data = substr($data, 3);
                $result[] = array('boolean', $boolean_value);
                break;

            case 0x02:
                // Integer type
                $len = ord($data[1]);
                $bytes = 0;
                if ($len & 0x80) {
                    $bytes = $len & 0x0f;
                    $len = 0;
                    for ($i = 0; $i < $bytes; $i++) {
                        $len = ($len << 8) | ord($data[$i + 2]);
                    }
                }

                $integer_data = substr($data, 2 + $bytes, $len);
                $data = substr($data, 2 + $bytes + $len);

                $value = 0;
                if ($len <= 4) {
                    /* Method works fine for small integers */
                    for ($i = 0, $i_len = strlen($integer_data); $i < $i_len; ++$i) {
                        $value = ($value << 8) | ord($integer_data[$i]);
                    }
                } else {
                    /* Method works for arbitrary length integers */
                    if (Horde_Util::extensionExists('bcmath')) {
                        for ($i = 0, $i_len = strlen($integer_data); $i < $i_len; ++$i) {
                            $value = bcadd(bcmul($value, 256), ord($integer_data[$i]));
                        }
                    } else {
                        $value = -1;
                    }
                }
                $result[] = array('integer(' . $len . ')', $value);
                break;

            case 0x03:
                // Bitstring type
                $len = ord($data[1]);
                $bytes = 0;
                if ($len & 0x80) {
                    $bytes = $len & 0x0f;
                    $len = 0;
                    for ($i = 0; $i < $bytes; $i++) {
                        $len = ($len << 8) | ord($data[$i + 2]);
                    }
                }
                $bitstring_data = substr($data, 3 + $bytes, $len);
                $data = substr($data, 2 + $bytes + $len);
                $result[] = array('bit string', $bitstring_data);
                break;

            case 0x04:
                // Octetstring type
                $len = ord($data[1]);
                $bytes = 0;
                if ($len & 0x80) {
                    $bytes = $len & 0x0f;
                    $len = 0;
                    for ($i = 0; $i < $bytes; $i++) {
                        $len = ($len << 8) | ord($data[$i + 2]);
                    }
                }
                $octectstring_data = substr($data, 2 + $bytes, $len);
                $data = substr($data, 2 + $bytes + $len);
                $result[] = array('octet string', $octectstring_data);
                break;

            case 0x05:
                // Null type
                $data = substr($data, 2);
                $result[] = array('null', null);
                break;

            case 0x06:
                // Object identifier type
                $len = ord($data[1]);
                $bytes = 0;
                if ($len & 0x80) {
                    $bytes = $len & 0x0f;
                    $len = 0;
                    for ($i = 0; $i < $bytes; $i++) {
                        $len = ($len << 8) | ord($data[$i + 2]);
                    }
                }
                $oid_data = substr($data, 2 + $bytes, $len);
                $data = substr($data, 2 + $bytes + $len);

                // Unpack the OID
                $plain  = floor(ord($oid_data[0]) / 40);
                $plain .= '.' . ord($oid_data[0]) % 40;

                $value = 0;
                $i = 1;
                $o_len = strlen($oid_data);

                while ($i < $o_len) {
                    $value = $value << 7;
                    $value = $value | (ord($oid_data[$i]) & 0x7f);

                    if (!(ord($oid_data[$i]) & 0x80)) {
                        $plain .= '.' . $value;
                        $value = 0;
                    }
                    $i++;
                }

                if (isset($this->_oids[$plain])) {
                    $result[] = array('oid', $this->_oids[$plain]);
                } else {
                    $result[] = array('oid', $plain);
                }

                break;

            case 0x12:
            case 0x13:
            case 0x14:
            case 0x15:
            case 0x16:
            case 0x81:
            case 0x80:
                // Character string type
                $len = ord($data[1]);
                $bytes = 0;
                if ($len & 0x80) {
                    $bytes = $len & 0x0f;
                    $len = 0;
                    for ($i = 0; $i < $bytes; $i++) {
                        $len = ($len << 8) | ord($data[$i + 2]);
                    }
                }
                $string_data = substr($data, 2 + $bytes, $len);
                $data = substr($data, 2 + $bytes + $len);
                $result[] = array('string', $string_data);
                break;

            case 0x17:
                // Time types
                $len = ord($data[1]);
                $bytes = 0;
                if ($len & 0x80) {
                    $bytes = $len & 0x0f;
                    $len = 0;
                    for ($i = 0; $i < $bytes; $i++) {
                        $len = ($len << 8) | ord($data[$i + 2]);
                    }
                }
                $time_data = substr($data, 2 + $bytes, $len);
                $data = substr($data, 2 + $bytes + $len);
                $result[] = array('utctime', $time_data);
                break;

            case 0x82:
                // X509v3 extensions?
                $len = ord($data[1]);
                $bytes = 0;
                if ($len & 0x80) {
                    $bytes = $len & 0x0f;
                    $len = 0;
                    for ($i = 0; $i < $bytes; $i++) {
                        $len = ($len << 8) | ord($data[$i + 2]);
                    }
                }
                $sequence_data = substr($data, 2 + $bytes, $len);
                $data = substr($data, 2 + $bytes + $len);
                $result[] = array('extension', 'X509v3 extensions');
                $result[] = $this->_parseASN($sequence_data);
                break;

            case 0xa0:
            case 0xa3:
                // Extensions
                $extension_data = substr($data, 0, 2);
                $data = substr($data, 2);
                $result[] = array('extension', dechex($extension_data));
                break;

            case 0xe6:
                $extension_data = substr($data, 0, 1);
                $data = substr($data, 1);
                $result[] = array('extension', dechex($extension_data));
                break;

            case 0xa1:
                $extension_data = substr($data, 0, 1);
                $data = substr($data, 6);
                $result[] = array('extension', dechex($extension_data));
                break;

            default:
                // Unknown
                $result[] = array('UNKNOWN', dechex($data));
                $data = '';
                break;
            }
        }

        return (count($result) > 1) ? $result : array_pop($result);
    }

    /**
     * Decrypt an S/MIME signed message using a public key.
     *
     * @param string $text   The text to be verified.
     * @param array $params  The parameters needed for verification.
     *
     * @return string  The verification message.
     * @throws Horde_Crypt_Exception
     */
    protected function _decryptSignature($text, $params)
    {
        throw new Horde_Crypt_Exception('_decryptSignature() ' . $this->_dict->t("not yet implemented"));
    }

    /**
     * Check for the presence of the OpenSSL extension to PHP.
     *
     * @throws Horde_Crypt_Exception
     */
    public function checkForOpenSSL()
    {
        if (!Horde_Util::extensionExists('openssl')) {
            throw new Horde_Crypt_Exception($this->_dict->t("The openssl module is required for the Horde_Crypt_Smime:: class."));
        }
    }

    /**
     * Extract the email address from a public key.
     *
     * @param string $key  The public key.
     *
     * @return mixed  Returns the first email address found, or null if
     *                there are none.
     */
    public function getEmailFromKey($key)
    {
        $key_info = openssl_x509_parse($key);
        if (!is_array($key_info)) {
            return null;
        }

        if (isset($key_info['subject'])) {
            if (isset($key_info['subject']['Email'])) {
                return $key_info['subject']['Email'];
            } elseif (isset($key_info['subject']['emailAddress'])) {
                return $key_info['subject']['emailAddress'];
            }
        }

        // Check subjectAltName per http://www.ietf.org/rfc/rfc3850.txt
        if (isset($key_info['extensions']['subjectAltName'])) {
            $names = preg_split('/\s*,\s*/', $key_info['extensions']['subjectAltName'], -1, PREG_SPLIT_NO_EMPTY);
            foreach ($names as $name) {
                if (strpos($name, ':') === false) {
                    continue;
                }
                list($kind, $value) = explode(':', $name, 2);
                if (Horde_String::lower($kind) == 'email') {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Convert a PKCS 12 encrypted certificate package into a private key,
     * public key, and any additional keys.
     *
     * @param string $text   The PKCS 12 data.
     * @param array $params  The parameters needed for parsing.
     * <pre>
     * Parameters:
     * ===========
     * 'sslpath' => The path to the OpenSSL binary. (REQUIRED)
     * 'password' => The password to use to decrypt the data. (Optional)
     * 'newpassword' => The password to use to encrypt the private key.
     *                  (Optional)
     * </pre>
     *
     * @return stdClass  An object.
     *                   'private' -  The private key in PEM format.
     *                   'public'  -  The public key in PEM format.
     *                   'certs'   -  An array of additional certs.
     * @throws Horde_Crypt_Exception
     */
    public function parsePKCS12Data($pkcs12, $params)
    {
        /* Check for availability of OpenSSL PHP extension. */
        $this->checkForOpenSSL();

        if (!isset($params['sslpath'])) {
            throw new Horde_Crypt_Exception($this->_dict->t("No path to the OpenSSL binary provided. The OpenSSL binary is necessary to work with PKCS 12 data."));
        }
        $sslpath = escapeshellcmd($params['sslpath']);

        /* Create temp files for input/output. */
        $input = $this->_createTempFile('horde-smime');
        $output = $this->_createTempFile('horde-smime');

        $ob = new stdClass;

        /* Write text to file */
        file_put_contents($input, $pkcs12);
        unset($pkcs12);

        /* Extract the private key from the file first. */
        $cmdline = $sslpath . ' pkcs12 -in ' . $input . ' -out ' . $output . ' -nocerts';
        if (isset($params['password'])) {
            $cmdline .= ' -passin stdin';
            if (!empty($params['newpassword'])) {
                $cmdline .= ' -passout stdin';
            } else {
                $cmdline .= ' -nodes';
            }
        } else {
            $cmdline .= ' -nodes';
        }

        if ($fd = popen($cmdline, 'w')) {
            fwrite($fd, $params['password'] . "\n");
            if (!empty($params['newpassword'])) {
                fwrite($fd, $params['newpassword'] . "\n");
            }
            pclose($fd);
        } else {
            throw new Horde_Crypt_Exception($this->_dict->t("Error while talking to smime binary."));
        }

        $ob->private = trim(file_get_contents($output));
        if (empty($ob->private)) {
            throw new Horde_Crypt_Exception($this->_dict->t("Password incorrect"));
        }

        /* Extract the client public key next. */
        $cmdline = $sslpath . ' pkcs12 -in ' . $input . ' -out ' . $output . ' -nokeys -clcerts';
        if (isset($params['password'])) {
            $cmdline .= ' -passin stdin';
        }

        if ($fd = popen($cmdline, 'w')) {
            fwrite($fd, $params['password'] . "\n");
            pclose($fd);
        } else {
            throw new Horde_Crypt_Exception($this->_dict->t("Error while talking to smime binary."));
        }

        $ob->public = trim(file_get_contents($output));

        /* Extract the CA public key next. */
        $cmdline = $sslpath . ' pkcs12 -in ' . $input . ' -out ' . $output . ' -nokeys -cacerts';
        if (isset($params['password'])) {
            $cmdline .= ' -passin stdin';
        }

        if ($fd = popen($cmdline, 'w')) {
            fwrite($fd, $params['password'] . "\n");
            pclose($fd);
        } else {
            throw new Horde_Crypt_Exception($this->_dict->t("Error while talking to smime binary."));
        }

        $ob->certs = trim(file_get_contents($output));

        return $ob;
    }

    /**
     * The Content-Type parameters PHP's openssl_pkcs7_* functions return are
     * deprecated.  Fix these headers to the correct ones (see RFC 2311).
     *
     * @param string $text  The PKCS7 data.
     * @param string $type  Is this 'message' or 'signature' data?
     *
     * @return string  The PKCS7 data with the correct Content-Type parameter.
     */
    protected function _fixContentType($text, $type)
    {
        if ($type == 'message') {
            $from = 'application/x-pkcs7-mime';
            $to = 'application/pkcs7-mime';
        } else {
            $from = 'application/x-pkcs7-signature';
            $to = 'application/pkcs7-signature';
        }
        return str_replace('Content-Type: ' . $from, 'Content-Type: ' . $to, $text);
    }

}
