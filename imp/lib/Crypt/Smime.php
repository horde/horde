<?php
/**
 * The IMP_Crypt_Smime:: class contains all functions related to handling
 * S/MIME messages within IMP.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Crypt_Smime extends Horde_Crypt_Smime
{
    /* Name of the S/MIME public key field in addressbook. */
    const PUBKEY_FIELD = 'smimePublicKey';

    /**
     * Add the personal public key to the prefs.
     *
     * @param mixed $key  The public key to add (either string or array).
     */
    public function addPersonalPublicKey($key)
    {
        $GLOBALS['prefs']->setValue('smime_public_key', (is_array($key)) ? implode('', $key) : $key);
    }

    /**
     * Add the personal private key to the prefs.
     *
     * @param mixed $key  The private key to add (either string or array).
     */
    public function addPersonalPrivateKey($key)
    {
        $GLOBALS['prefs']->setValue('smime_private_key', (is_array($key)) ? implode('', $key) : $key);
    }

    /**
     * Add the list of additional certs to the prefs.
     *
     * @param mixed $key  The private key to add (either string or array).
     */
    public function addAdditionalCert($key)
    {
        $GLOBALS['prefs']->setValue('smime_additional_cert', (is_array($key)) ? implode('', $key) : $key);
    }

    /**
     * Get the personal public key from the prefs.
     *
     * @return string  The personal S/MIME public key.
     */
    public function getPersonalPublicKey()
    {
        return $GLOBALS['prefs']->getValue('smime_public_key');
    }

    /**
     * Get the personal private key from the prefs.
     *
     * @return string  The personal S/MIME private key.
     */
    public function getPersonalPrivateKey()
    {
        return $GLOBALS['prefs']->getValue('smime_private_key');
    }

    /**
     * Get any additional certificates from the prefs.
     *
     * @return string  Additional signing certs for inclusion.
     */
    public function getAdditionalCert()
    {
        return $GLOBALS['prefs']->getValue('smime_additional_cert');
    }

    /**
     * Deletes the specified personal keys from the prefs.
     */
    public function deletePersonalKeys()
    {
        $GLOBALS['prefs']->setValue('smime_public_key', '');
        $GLOBALS['prefs']->setValue('smime_private_key', '');
        $GLOBALS['prefs']->setValue('smime_additional_cert', '');
        $this->unsetPassphrase();
    }

    /**
     * Add a public key to an address book.
     *
     * @param string $cert  A public certificate to add.
     *
     * @throws Horde_Crypt_Exception
     */
    public function addPublicKey($cert)
    {
        /* Make sure the certificate is valid. */
        $key_info = openssl_x509_parse($cert);
        if (!is_array($key_info) || !isset($key_info['subject'])) {
            throw new Horde_Crypt_Exception(_("Not a valid public key."));
        }

        /* Add key to the user's address book. */
        $email = $this->getEmailFromKey($cert);
        if (is_null($email)) {
            throw new Horde_Crypt_Exception(_("No email information located in the public key."));
        }

        /* Get the name corresponding to this key. */
        if (isset($key_info['subject']['CN'])) {
            $name = $key_info['subject']['CN'];
        } elseif (isset($key_info['subject']['OU'])) {
            $name = $key_info['subject']['OU'];
        } else {
            throw new Horde_Crypt_Exception(_("Not a valid public key."));
        }

        $GLOBALS['registry']->call('contacts/addField', array($email, $name, self::PUBKEY_FIELD, $cert, $GLOBALS['prefs']->getValue('add_source')));
    }

    /**
     * Returns the params needed to encrypt a message being sent to the
     * specified email address.
     *
     * @param string $address  The e-mail address of the recipient.
     *
     * @return array  The list of parameters needed by encrypt().
     * @throws Horde_Crypt_Exception
     */
    protected function _encryptParameters($address)
    {
        /* We can only encrypt if we are sending to a single person. */
        $addrOb = Horde_Mime_Address::bareAddress($address, $GLOBALS['session']->get('imp', 'maildomain'), true);
        $key_addr = array_pop($addrOb);

        $public_key = $this->getPublicKey($key_addr);

        return array(
            'pubkey' => $public_key,
            'type' => 'message'
        );
    }

    /**
     * Retrieves a public key by e-mail.
     * The key will be retrieved from a user's address book(s).
     *
     * @param string $address  The e-mail address to search for.
     *
     * @return string  The S/MIME public key requested.
     * @throws Horde_Exception
     */
    public function getPublicKey($address)
    {
        try {
            $key = Horde::callHook('smime_key', array($address), 'imp');
            if ($key) {
                return $key;
            }
        } catch (Horde_Exception_HookNotSet $e) {}

        $params = IMP::getAddressbookSearchParams();

        try {
            $key = $GLOBALS['registry']->call('contacts/getField', array($address, self::PUBKEY_FIELD, $params['sources'], false, true));
        } catch (Horde_Exception $e) {
            /* See if the address points to the user's public key. */
            $identity = $GLOBALS['injector']->getInstance('IMP_Identity');
            $personal_pubkey = $this->getPersonalPublicKey();
            if (!empty($personal_pubkey) && $identity->hasAddress($address)) {
                return $personal_pubkey;
            }

            throw $e;
        }

        /* If more than one public key is returned, just return the first in
         * the array. There is no way of knowing which is the "preferred" key,
         * if the keys are different. */
        return is_array($key) ? reset($key) : $key;
    }

    /**
     * Retrieves all public keys from a user's address book(s).
     *
     * @return array  All S/MIME public keys available.
     * @throws Horde_Crypt_Exception
     */
    public function listPublicKeys()
    {
        $params = IMP::getAddressbookSearchParams();
        if (empty($params['sources'])) {
            return array();
        }
        return $GLOBALS['registry']->call('contacts/getAllAttributeValues', array(self::PUBKEY_FIELD, $params['sources']));
    }

    /**
     * Deletes a public key from a user's address book(s) by e-mail.
     *
     * @param string $email  The e-mail address to delete.
     *
     * @throws Horde_Crypt_Exception
     */
    public function deletePublicKey($email)
    {
        $params = IMP::getAddressbookSearchParams();
        $GLOBALS['registry']->call('contacts/deleteField', array($email, self::PUBKEY_FIELD, $params['sources']));
    }

    /**
     * Returns the parameters needed for signing a message.
     *
     * @return array  The list of parameters needed by encrypt().
     */
    protected function _signParameters()
    {
        return array(
            'type' => 'signature',
            'pubkey' => $this->getPersonalPublicKey(),
            'privkey' => $this->getPersonalPrivateKey(),
            'passphrase' => $this->getPassphrase(),
            'sigtype' => 'detach',
            'certs' => $this->getAdditionalCert()
        );
    }

    /**
     * Verifies a signed message with a given public key.
     *
     * @param string $text  The text to verify.
     *
     * @return stdClass  See Horde_Crypt_Smime::verify().
     * @throws Horde_Crypt_Exception
     */
    public function verifySignature($text)
    {
        return $this->verify($text, empty($GLOBALS['conf']['openssl']['cafile']) ? array() : $GLOBALS['conf']['openssl']['cafile']);
    }


    /**
     * Decrypt a message with user's public/private keypair.
     *
     * @param string $text  The text to decrypt.
     *
     * @return string  See Horde_Crypt_Smime::decrypt().
     * @throws Horde_Crypt_Exception
     */
    public function decryptMessage($text)
    {
        return $this->decrypt($text, array('type' => 'message', 'pubkey' => $this->getPersonalPublicKey(), 'privkey' => $this->getPersonalPrivateKey(), 'passphrase' => $this->getPassphrase()));
    }

    /**
     * Gets the user's passphrase from the session cache.
     *
     * @return mixed  The passphrase, if set.  Returns false if the passphrase
     *                has not been loaded yet.  Returns null if no passphrase
     *                is needed.
     */
    public function getPassphrase()
    {
        global $session;

        $private_key = $GLOBALS['prefs']->getValue('smime_private_key');
        if (empty($private_key)) {
            return false;
        }

        if ($session->exists('imp', 'smime_passphrase')) {
            $secret = $GLOBALS['injector']->getInstance('Horde_Secret');
            return $secret->read($secret->getKey('imp'), $session->get('imp', 'smime_passphrase'));
        } elseif (!$session->exists('imp', 'smime_null_passphrase')) {
            $session->set(
                'imp',
                'smime_null_passphrase',
                $this->verifyPassphrase($private_key, null)
                    ? null
                    : false
            );
        }

        return $session->get('imp', 'smime_null_passphrase');
    }

    /**
     * Store's the user's passphrase in the session cache.
     *
     * @param string $passphrase  The user's passphrase.
     *
     * @return boolean  Returns true if correct passphrase, false if incorrect.
     */
    public function storePassphrase($passphrase)
    {
        if ($this->verifyPassphrase($this->getPersonalPrivateKey(), $passphrase) === false) {
            return false;
        }

        $secret = $GLOBALS['injector']->getInstance('Horde_Secret');
        $GLOBALS['session']->set('imp', 'smime_passphrase', $secret->write($secret->getKey('imp'), $passphrase));

        return true;
    }

    /**
     * Clear the passphrase from the session cache.
     */
    public function unsetPassphrase()
    {
        global $session;

        $session->remove('imp', 'smime_null_passphrase');
        $session->remove('imp', 'smime_passphrase');
    }

    /**
     * Generates the javascript code for saving public keys.
     *
     * @param string $mailbox  The mailbox of the message.
     * @param integer $uid     The UID of the message.
     * @param string $id       The MIME ID of the message.
     *
     * @return string  The URL for saving public keys.
     */
    public function savePublicKeyURL($mailbox, $uid, $id)
    {
        $params = array(
            'actionID' => 'save_attachment_public_key',
            'mailbox' => $mailbox,
            'uid' => $uid,
            'mime_id' => $id
        );
        return Horde::popupJs(Horde::url('smime.php'), array('params' => $params, 'height' => 200, 'width' => 450));
    }

    /**
     * Encrypt a MIME_Part using S/MIME using IMP defaults.
     *
     * @param MIME_Part $mime_part  The MIME_Part object to encrypt.
     * @param mixed $to_address     The e-mail address of the key to use for
     *                              encryption.
     *
     * @return MIME_Part  See Horde_Crypt_Smime::encryptMIMEPart().
     * @throws Horde_Crypt_Exception
     */
    public function IMPencryptMIMEPart($mime_part, $to_address)
    {
        return $this->encryptMIMEPart($mime_part, $this->_encryptParameters($to_address));
    }

    /**
     * Sign a MIME_Part using S/MIME using IMP defaults.
     *
     * @param MIME_Part $mime_part  The MIME_Part object to sign.
     *
     * @return MIME_Part  See Horde_Crypt_Smime::signMIMEPart().
     * @throws Horde_Crypt_Exception
     */
    public function IMPsignMIMEPart($mime_part)
    {
        return $this->signMIMEPart($mime_part, $this->_signParameters());
    }

    /**
     * Sign and encrypt a MIME_Part using S/MIME using IMP defaults.
     *
     * @param MIME_Part $mime_part  The MIME_Part object to sign and encrypt.
     * @param string $to_address    The e-mail address of the key to use for
     *                              encryption.
     *
     * @return MIME_Part  See Horde_Crypt_Smime::signAndencryptMIMEPart().
     * @throws Horde_Crypt_Exception
     */
    public function IMPsignAndEncryptMIMEPart($mime_part, $to_address)
    {
        return $this->signAndEncryptMIMEPart($mime_part, $this->_signParameters(), $this->_encryptParameters($to_address));
    }

    /**
     * Store the public/private/additional certificates in the preferences
     * from a given PKCS 12 file.
     *
     * @param string $pkcs12    The PKCS 12 data.
     * @param string $password  The password of the PKCS 12 file.
     * @param string $pkpass    The password to use to encrypt the private key.
     *
     * @throws Horde_Crypt_Exception
     */
    public function addFromPKCS12($pkcs12, $password, $pkpass = null)
    {
        $sslpath = empty($GLOBALS['conf']['openssl']['path'])
            ? null
            : $GLOBALS['conf']['openssl']['path'];

        $params = array('sslpath' => $sslpath, 'password' => $password);
        if (!empty($pkpass)) {
            $params['newpassword'] = $pkpass;
        }

        $result = $this->parsePKCS12Data($pkcs12, $params);
        $this->addPersonalPrivateKey($result->private);
        $this->addPersonalPublicKey($result->public);
        $this->addAdditionalCert($result->certs);
    }

    /**
     * Extract the contents from signed S/MIME data.
     *
     * @param string $data  The signed S/MIME data.
     *
     * @return string  The contents embedded in the signed data.
     * @throws Horde_Crypt_Exception
     */
    public function extractSignedContents($data)
    {
        $sslpath = empty($GLOBALS['conf']['openssl']['path'])
            ? null
            : $GLOBALS['conf']['openssl']['path'];

        return parent::extractSignedContents($data, $sslpath);
    }

    /* UI related functions. */

    /**
     * Print certificate information.
     *
     * @param string $cert  The S/MIME certificate.
     */
    public function printCertInfo($key = '')
    {
        $cert_info = $this->certToHTML($key);

        if (empty($cert_info)) {
            $this->textWindowOutput('S/MIME Key Information', _("Invalid key"));
        } else {
            $this->textWindowOutput('S/MIME Key Information', $cert_info, true);
        }
    }

    /**
     * Output text in a window.
     *
     * @param string $name  The window name.
     * @param string $msg   The text contents.
     * @param string $html  $msg is HTML format?
     */
    public function textWindowOutput($name, $msg, $html = false)
    {
        $GLOBALS['browser']->downloadHeaders($name, $html ? 'text/html' : 'text/plain; charset=' . 'UTF-8', true, strlen($msg));
        echo $msg;
    }

    /**
     * Generate import key dialog.
     *
     * @param string $target  Action ID for the UI screen.
     * @param string $reload  The reload cache value.
     */
    public function importKeyDialog($target, $reload)
    {
        $title = _("Import S/MIME Key");
        require IMP_TEMPLATES . '/common-header.inc';
        IMP::status();

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);
        $t->set('selfurl', Horde::url('smime.php'));
        $t->set('broken_mp_form', $GLOBALS['browser']->hasQuirk('broken_multipart_form'));
        $t->set('reload', htmlspecialchars($reload));
        $t->set('target', $target);
        $t->set('forminput', Horde_Util::formInput());
        $t->set('import_public_key', $target == 'process_import_public_key');
        $t->set('import_personal_certs', $target == 'process_import_personal_certs');
        echo $t->fetch(IMP_TEMPLATES . '/smime/import_key.html');
    }

    /**
     * Attempt to import a key from form/uploaded data.
     *
     * @param string $key  Key string.
     *
     * @return string  The key contents.
     * @throws Horde_Browser_Exception
     */
    public function getImportKey($key)
    {
        if (!empty($key)) {
            return $key;
        }

        $GLOBALS['browser']->wasFileUploaded('upload_key', _("key"));
        return file_get_contents($_FILES['upload_key']['tmp_name']);
    }

    /**
     * Reload the window.
     *
     * @param string $reload  The reload cache value.
     */
    public function reloadWindow($reload)
    {
        global $session;

        $href = $session[$reload];
        unset($session[$reload]);
        echo Horde::wrapInlineScript(array(
            'opener.focus();',
            'opener.location.href="' . $href . '";',
            'window.close();'
        ));
    }

}
