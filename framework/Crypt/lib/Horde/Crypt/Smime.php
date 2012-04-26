<?php
/**
 * Library to interact with the OpenSSL library and implement S/MIME.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Crypt
 */
class Horde_Crypt_Smime extends Horde_Crypt
{
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
     * <pre>
     * cert - (string) The certificate of the signer stored in the message (in
     *        PEM format).
     * email - (string) The email of the signing person.
     * msg - (string) Status string.
     * verify - (boolean) True if certificate was verified.
     * </pre>
     * @throws Horde_Crypt_Exception
     */
    public function verify($text, $certs)
    {
        /* Check for availability of OpenSSL PHP extension. */
        $this->checkForOpenSSL();

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

        if (!empty($root_certs) &&
            (openssl_pkcs7_verify($input, 0, $output, $root_certs) === true)) {
            /* Message verified */
            $ob->msg = Horde_Crypt_Translation::t("Message verified successfully.");
            $ob->verify = true;
        } else {
            /* Try again without verfying the signer's cert */
            $result = openssl_pkcs7_verify($input, PKCS7_NOVERIFY, $output);

            if ($result === -1) {
                throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Verification failed - an unknown error has occurred."));
            } elseif ($result === false) {
                throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Verification failed - this message may have been tampered with."));
            }

            $ob->msg = Horde_Crypt_Translation::t("Message verified successfully but the signer's certificate could not be verified.");
            $ob->verify = false;
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

        throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("OpenSSL error: Could not extract data from signed S/MIME part."));
    }

    /**
     * Sign a MIME part using S/MIME. This produces S/MIME Version 3.2
     * compatible data (see RFC 5751 [3.4]).
     *
     * @param Horde_Mime_Part $mime_part  The object to sign.
     * @param array $params               The parameters required for signing.
     *
     * @return Horde_Mime_Part  A signed MIME part object.
     * @throws Horde_Crypt_Exception
     */
    public function signMIMEPart($mime_part, $params)
    {
        /* Sign the part as a message */
        $message = $this->encrypt($mime_part->toString(array('headers' => true, 'canonical' => true)), $params);

        /* Break the result into its components */
        $mime_message = Horde_Mime_Part::parseMessage($message, array('forcemime' => true));

        $smime_sign = $mime_message->getPart('2');
        $smime_sign->setDescription(Horde_Crypt_Translation::t("S/MIME Cryptographic Signature"));
        $smime_sign->setTransferEncoding('base64', array('send' => true));

        $smime_part = new Horde_Mime_Part();
        $smime_part->setType('multipart/signed');
        $smime_part->setContents("This is a cryptographically signed message in MIME format.\n");
        $smime_part->setContentTypeParameter('protocol', 'application/pkcs7-signature');
        // Per RFC 5751 [3.4.3.2], 'sha1' has been deprecated for 'sha-1'.
        $smime_part->setContentTypeParameter('micalg', 'sha-1');
        $smime_part->addPart($mime_part);
        $smime_part->addPart($smime_sign);

        return $smime_part;
    }

    /**
     * Encrypt a MIME part using S/MIME. This produces S/MIME Version 3.2
     * compatible data (see RFC 5751 [3.3]).
     *
     * @param Horde_Mime_Part $mime_part  The object to encrypt.
     * @param array $params               The parameters required for
     *                                    encryption.
     *
     * @return Horde_Mime_Part  An encrypted MIME part object.
     * @throws Horde_Crypt_Exception
     */
    public function encryptMIMEPart($mime_part, $params = array())
    {
        /* Sign the part as a message */
        $message = $this->encrypt($mime_part->toString(array('headers' => true, 'canonical' => true)), $params);

        $msg = new Horde_Mime_Part();
        $msg->setCharset($this->_params['email_charset']);
        $msg->setHeaderCharset('UTF-8');
        $msg->setDescription(Horde_Crypt_Translation::t("S/MIME Encrypted Message"));
        $msg->setDisposition('inline');
        $msg->setType('application/pkcs7-mime');
        $msg->setContentTypeParameter('smime-type', 'enveloped-data');
        $msg->setContents(substr($message, strpos($message, "\n\n") + 2), array('encoding' => 'base64'));

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
            throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("A public S/MIME key is required to encrypt a message."));
        }

        /* Create temp files for input/output. */
        $input = $this->_createTempFile('horde-smime');
        $output = $this->_createTempFile('horde-smime');

        /* Store message in file. */
        file_put_contents($input, $text);
        unset($text);

        /* Encrypt the document. */
        $ciphers = array(
            OPENSSL_CIPHER_3DES,
            OPENSSL_CIPHER_DES,
            OPENSSL_CIPHER_RC2_128,
            OPENSSL_CIPHER_RC2_64,
            OPENSSL_CIPHER_RC2_40
        );

        foreach ($ciphers as $val) {
            if (openssl_pkcs7_encrypt($input, $output, $params['pubkey'], array(), 0, $val)) {
                $result = file_get_contents($output);
                if (!empty($result)) {
                    return $this->_fixContentType($result, 'encrypt');
                }
            }
        }

        throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Could not S/MIME encrypt message."));
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
            throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("A public S/MIME key, private S/MIME key, and passphrase are required to sign a message."));
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
            throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Could not S/MIME sign message."));
        }

        /* Output from openssl_pkcs7_sign may contain both \n and \r\n EOLs.
         * Canonicalize to \r\n. */
        $fp = fopen($output, 'r');
        stream_filter_register('horde_eol', 'Horde_Stream_Filter_Eol');
        stream_filter_append($fp, 'horde_eol');
        $data = stream_get_contents($fp);
        fclose($fp);

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
            throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("A public S/MIME key, private S/MIME key, and passphrase are required to decrypt a message."));
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

        throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Could not decrypt S/MIME data."));
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
     * Convert a PEM format certificate to readable HTML version.
     *
     * @param string $cert   PEM format certificate.
     *
     * @return string  HTML detailing the certificate.
     */
    public function certToHTML($cert)
    {
        $fieldnames = array(
            /* Common Fields */
            'description' => Horde_Crypt_Translation::t("Description"),
            'emailAddress' => Horde_Crypt_Translation::t("Email Address"),
            'commonName' => Horde_Crypt_Translation::t("Common Name"),
            'organizationName' => Horde_Crypt_Translation::t("Organisation"),
            'organizationalUnitName' => Horde_Crypt_Translation::t("Organisational Unit"),
            'countryName' => Horde_Crypt_Translation::t("Country"),
            'stateOrProvinceName' => Horde_Crypt_Translation::t("State or Province"),
            'localityName' => Horde_Crypt_Translation::t("Location"),
            'streetAddress' => Horde_Crypt_Translation::t("Street Address"),
            'telephoneNumber' => Horde_Crypt_Translation::t("Telephone Number"),
            'surname' => Horde_Crypt_Translation::t("Surname"),
            'givenName' => Horde_Crypt_Translation::t("Given Name"),

            /* X590v3 Extensions */
            'exendedtKeyUsage' => Horde_Crypt_Translation::t("X509v3 Extended Key Usage"),
            'basicConstraints' => Horde_Crypt_Translation::t("X509v3 Basic Constraints"),
            'subjectAltName' => Horde_Crypt_Translation::t("X509v3 Subject Alternative Name"),
            'subjectKeyIdentifier' => Horde_Crypt_Translation::t("X509v3 Subject Key Identifier"),
            'certificatePolicies' => Horde_Crypt_Translation::t("Certificate Policies"),
            'crlDistributionPoints' => Horde_Crypt_Translation::t("CRL Distribution Points"),
            'keyUsage' => Horde_Crypt_Translation::t("Key Usage")
        );

        $details = $this->parseCert($cert);

        $text = '<pre class="fixed">';

        /* Subject (a/k/a Certificate Owner) */
        $text .= "<strong>" . Horde_Crypt_Translation::t("Certificate Owner") . ":</strong>\n";

        foreach ($details['subject'] as $key => $value) {
            $value = $this->_implodeValues($value);
            $text .= isset($fieldnames[$key])
                ? sprintf("&nbsp;&nbsp;%s: %s\n", $fieldnames[$key], $value)
                : sprintf("&nbsp;&nbsp;*%s: %s\n", $key, $value);
        }
        $text .= "\n";

        /* Issuer */
        $text .= "<strong>" . Horde_Crypt_Translation::t("Issuer") . ":</strong>\n";

        foreach ($details['issuer'] as $key => $value) {
            $value = $this->_implodeValues($value);
            $text .= isset($fieldnames[$key])
                ? sprintf("&nbsp;&nbsp;%s: %s\n", $fieldnames[$key], $value)
                : sprintf("&nbsp;&nbsp;*%s: %s\n", $key, $value);
        }
        $text .= "\n";

        /* Dates  */
        $text .= "<strong>" . Horde_Crypt_Translation::t("Validity") . ":</strong>\n" .
            sprintf("&nbsp;&nbsp;%s: %s\n", Horde_Crypt_Translation::t("Not Before"), strftime("%x %X", $details['validity']['notbefore']->getTimestamp())) .
            sprintf("&nbsp;&nbsp;%s: %s\n", Horde_Crypt_Translation::t("Not After"), strftime("%x %X", $details['validity']['notafter']->getTimestamp())) .
            "\n";

        /* X509v3 extensions */
        if (!empty($details['extensions'])) {
            $text .= "<strong>" . Horde_Crypt_Translation::t("X509v3 extensions") . ":</strong>\n";

            foreach ($details['extensions'] as $key => $value) {
                $value = $this->_implodeValues($value, 6);
                $text .= isset($fieldnames[$key])
                    ? sprintf("&nbsp;&nbsp;%s:\n&nbsp;&nbsp;&nbsp;&nbsp;%s\n", $fieldnames[$key], trim($value))
                    : sprintf("&nbsp;&nbsp;*%s:\n&nbsp;&nbsp;&nbsp;&nbsp;%s\n", $key, trim($value));
            }

            $text .= "\n";
        }

        /* Certificate Details */
        $text .= "<strong>" . Horde_Crypt_Translation::t("Certificate Details") . ":</strong>\n" .
            sprintf("&nbsp;&nbsp;%s: %d\n", Horde_Crypt_Translation::t("Version"), $details['version']) .
            sprintf("&nbsp;&nbsp;%s: %d\n", Horde_Crypt_Translation::t("Serial Number"), $details['serialNumber']);

        return $text . "\n</pre>";
    }

    /**
     * Formats a multi-value cert field.
     *
     * @param array|string $value  A cert field value.
     * @param integer $indent      The indention level.
     *
     * @return string  The formatted cert field value(s).
     */
    protected function _implodeValues($value, $indent = 4)
    {
        if (is_array($value)) {
            $value = "\n" . str_repeat('&nbsp;', $indent)
                . implode("\n" . str_repeat('&nbsp;', $indent), $value);
        }
        return $value;
    }

    /**
     * Extract the contents of a PEM format certificate to an array.
     *
     * @param string $cert  PEM format certificate.
     *
     * @return array  All extractable information about the certificate.
     */
    public function parseCert($cert)
    {
        $data = openssl_x509_parse($cert, false);

        $details = array(
            'extensions' => $data['extensions'],
            'issuer' => $data['issuer'],
            'serialNumber' => $data['serialNumber'],
            'subject' => $data['subject'],
            'validity' => array(
                'notafter' => new DateTime('@' . $data['validTo_time_t']),
                'notbefore' => new DateTime('@' . $data['validFrom_time_t'])
            ),
            'version' => $data['version']
        );

        // Add additional fields for BC purposes.
        $details['certificate'] = $details;

        $bc_changes = array(
            'emailAddress' => 'Email',
            'commonName' => 'CommonName',
            'organizationName' => 'Organisation',
            'organizationalUnitName' => 'OrganisationalUnit',
            'countryName' => 'Country',
            'stateOrProvinceName' => 'StateOrProvince',
            'localityName' => 'Location',
            'streetAddress' => 'StreetAddress',
            'telephoneNumber' => 'TelephoneNumber',
            'surname' => 'Surname',
            'givenName' => 'GivenName'
        );
        foreach (array('issuer', 'subject') as $val) {
            foreach (array_keys($details[$val]) as $key) {
                if (isset($bc_changes[$key])) {
                    $details['certificate'][$val][$bc_changes[$key]] = $details[$val][$key];
                    unset($details['certificate'][$val][$key]);
                }
            }
        }

        return $details;
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
        throw new Horde_Crypt_Exception('_decryptSignature() ' . Horde_Crypt_Translation::t("not yet implemented"));
    }

    /**
     * Check for the presence of the OpenSSL extension to PHP.
     *
     * @throws Horde_Crypt_Exception
     */
    public function checkForOpenSSL()
    {
        if (!Horde_Util::extensionExists('openssl')) {
            throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("The openssl module is required for the Horde_Crypt_Smime:: class."));
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
            throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("No path to the OpenSSL binary provided. The OpenSSL binary is necessary to work with PKCS 12 data."));
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
            throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Error while talking to smime binary."));
        }

        $ob->private = trim(file_get_contents($output));
        if (empty($ob->private)) {
            throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Password incorrect"));
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
            throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Error while talking to smime binary."));
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
            throw new Horde_Crypt_Exception(Horde_Crypt_Translation::t("Error while talking to smime binary."));
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
