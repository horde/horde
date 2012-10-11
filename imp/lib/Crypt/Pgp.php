<?php
/**
 * The IMP_Crypt_Pgp:: class contains all functions related to handling
 * PGP messages within IMP.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Crypt_Pgp extends Horde_Crypt_Pgp
{
    /* Name of PGP public key field in addressbook. */
    const PUBKEY_FIELD = 'pgpPublicKey';

    /* Encryption type constants. */
    const ENCRYPT = 'pgp_encrypt';
    const SIGN = 'pgp_sign';
    const SIGNENC = 'pgp_signenc';
    const SYM_ENCRYPT = 'pgp_sym_enc';
    const SYM_SIGNENC = 'pgp_syn_sign';

    /**
     * Return the list of available encryption options for composing.
     *
     * @return array  Keys are encryption type constants, values are gettext
     *                strings describing the encryption type.
     */
    public function encryptList()
    {
        $ret = array(
            self::ENCRYPT => _("PGP Encrypt Message")
        );

        if ($this->getPersonalPrivateKey()) {
            $ret += array(
                self::SIGN => _("PGP Sign Message"),
                self::SIGNENC => _("PGP Sign/Encrypt Message")
            );
        }

        return $ret + array(
            self::SYM_ENCRYPT => _("PGP Encrypt Message with passphrase"),
            self::SYM_SIGNENC => _("PGP Sign/Encrypt Message with passphrase")
        );
    }

    /**
     * Generate the personal Public/Private keypair and store in prefs.
     *
     * @param string $realname    See Horde_Crypt_Pgp::.
     * @param string $email       See Horde_Crypt_Pgp::.
     * @param string $passphrase  See Horde_Crypt_Pgp::.
     * @param string $comment     See Horde_Crypt_Pgp::.
     * @param string $keylength   See Horde_Crypt_Pgp::.
     * @param integer $expire     See Horde_Crypt_Pgp::.
     *
     * @throws Horde_Crypt_Exception
     */
    public function generatePersonalKeys($name, $email, $passphrase,
                                         $comment = '', $keylength = 1024,
                                         $expire = null)
    {
        $keys = $this->generateKey($name, $email, $passphrase, $comment, $keylength, $expire);

        /* Store the keys in the user's preferences. */
        $this->addPersonalPublicKey($keys['public']);
        $this->addPersonalPrivateKey($keys['private']);
    }

    /**
     * Add the personal public key to the prefs.
     *
     * @param mixed $public_key  The public key to add (either string or
     *                           array).
     */
    public function addPersonalPublicKey($public_key)
    {
        $GLOBALS['prefs']->setValue('pgp_public_key', trim(is_array($public_key) ? implode('', $public_key) : $public_key));
    }

    /**
     * Add the personal private key to the prefs.
     *
     * @param mixed $private_key  The private key to add (either string or
     *                            array).
     */
    public function addPersonalPrivateKey($private_key)
    {
        $GLOBALS['prefs']->setValue('pgp_private_key', trim(is_array($private_key) ? implode('', $private_key) : $private_key));
    }

    /**
     * Get the personal public key from the prefs.
     *
     * @return string  The personal PGP public key.
     */
    public function getPersonalPublicKey()
    {
        return $GLOBALS['prefs']->getValue('pgp_public_key');
    }

    /**
     * Get the personal private key from the prefs.
     *
     * @return string  The personal PGP private key.
     */
    public function getPersonalPrivateKey()
    {
        return $GLOBALS['prefs']->getValue('pgp_private_key');
    }

    /**
     * Deletes the specified personal keys from the prefs.
     */
    public function deletePersonalKeys()
    {
        $GLOBALS['prefs']->setValue('pgp_public_key', '');
        $GLOBALS['prefs']->setValue('pgp_private_key', '');

        $this->unsetPassphrase('personal');
    }

    /**
     * Add a public key to an address book.
     *
     * @param string $public_key  An PGP public key.
     *
     * @return array  See Horde_Crypt_Pgp::pgpPacketInformation()
     * @throws Horde_Crypt_Exception
     * @throws Horde_Exception
     */
    public function addPublicKey($public_key)
    {
        /* Make sure the key is valid. */
        $key_info = $this->pgpPacketInformation($public_key);
        if (!isset($key_info['signature'])) {
            throw new Horde_Crypt_Exception(_("Not a valid public key."));
        }

        /* Remove the '_SIGNATURE' entry. */
        unset($key_info['signature']['_SIGNATURE']);

        /* Store all signatures that appear in the key. */
        foreach ($key_info['signature'] as $id => $sig) {
            /* Check to make sure the key does not already exist in ANY
             * address book and remove the id from the key_info for a correct
             * output. */
            try {
                $result = $this->getPublicKey($sig['email'], array('nocache' => true, 'noserver' => true));
                if (!empty($result)) {
                    unset($key_info['signature'][$id]);
                    continue;
                }
            } catch (Horde_Crypt_Exception $e) {}

            /* Add key to the user's address book. */
            $GLOBALS['registry']->call('contacts/addField', array($sig['email'], $sig['name'], self::PUBKEY_FIELD, $public_key, $GLOBALS['prefs']->getValue('add_source')));
        }

        return $key_info;
    }

    /**
     * Retrieves a public key by e-mail.
     *
     * First, the key will be attempted to be retrieved from a user's address
     * book(s).
     * Second, if unsuccessful, the key is attempted to be retrieved via a
     * public PGP keyserver.
     *
     * @param string $address  The e-mail address to search by.
     * @param array $options   Additional options:
     *   - keyid: (string) The key ID of the user's key.
     *            DEFAULT: key ID not used
     *   - nocache: (boolean) Don't retrieve from cache?
     *              DEFAULT: false
     *   - noserver: (boolean) Whether to check the public key servers for the
     *               key.
     *               DEFAULT: false
     *
     * @return string  The PGP public key requested.
     * @throws Horde_Crypt_Exception
     */
    public function getPublicKey($address, $options = array())
    {
        $keyid = empty($options['keyid'])
            ? ''
            : $options['keyid'];

        /* If there is a cache driver configured, try to get the public key
         * from the cache. */
        if (empty($options['nocache']) && ($cache = $GLOBALS['injector']->getInstance('Horde_Cache'))) {
            $result = $cache->get("PGPpublicKey_" . $address . $keyid, 3600);
            if ($result) {
                Horde::log('PGPpublicKey: ' . serialize($result), 'DEBUG');
                return $result;
            }
        }

        try {
            $key = Horde::callHook('pgp_key', array($address, $keyid), 'imp');
            if ($key) {
                return $key;
            }
        } catch (Horde_Exception_HookNotSet $e) {}

        /* Try retrieving by e-mail only first. */
        $params = $GLOBALS['injector']->getInstance('IMP_Ui_Contacts')->getAddressbookSearchParams();
        $result = null;
        try {
            $result = $GLOBALS['registry']->call('contacts/getField', array($address, self::PUBKEY_FIELD, $params['sources'], true, true));
        } catch (Horde_Exception $e) {}

        if (is_null($result)) {
            /* TODO: Retrieve by ID. */

            /* See if the address points to the user's public key. */
            $identity = $GLOBALS['injector']->getInstance('IMP_Identity');
            $personal_pubkey = $this->getPersonalPublicKey();
            if (!empty($personal_pubkey) && $identity->hasAddress($address)) {
                $result = $personal_pubkey;
            } elseif (empty($options['noserver'])) {
                try {
                    $result = $this->getFromPublicKeyserver($keyid, $address);

                    /* If there is a cache driver configured and a cache
                     * object exists, store the retrieved public key in the
                     * cache. */
                    if (is_object($cache)) {
                        $cache->set("PGPpublicKey_" . $address . $keyid, $result, 3600);
                    }
                } catch (Horde_Crypt_Exception $e) {
                    /* Return now, if no public key found at all. */
                    Horde::log('PGPpublicKey: ' . $e->getMessage(), 'DEBUG');
                    throw new Horde_Crypt_Exception(sprintf(_("Could not retrieve public key for %s."), $address));
                }
            } else {
                $result = '';
            }
        }

        /* If more than one public key is returned, just return the first in
         * the array. There is no way of knowing which is the "preferred" key,
         * if the keys are different. */
        if (is_array($result)) {
            reset($result);
        }

        return $result;
    }

    /**
     * Retrieves all public keys from a user's address book(s).
     *
     * @return array  All PGP public keys available.
     * @throws Horde_Crypt_Exception
     */
    public function listPublicKeys()
    {
        $params = $GLOBALS['injector']->getInstance('IMP_Ui_Contacts')->getAddressbookSearchParams();

        return empty($params['sources'])
            ? array()
            : $GLOBALS['registry']->call('contacts/getAllAttributeValues', array(self::PUBKEY_FIELD, $params['sources']));
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
        $params = $GLOBALS['injector']->getInstance('IMP_Ui_Contacts')->getAddressbookSearchParams();
        return $GLOBALS['registry']->call('contacts/deleteField', array($email, self::PUBKEY_FIELD, $params['sources']));
    }

    /**
     * Get a public key via a public PGP keyserver.
     *
     * @param string $keyid    The key ID of the requested key.
     * @param string $address  The email address of the requested key.
     *
     * @return string  See Horde_Crypt_Pgp::getPublicKeyserver()
     * @throws Horde_Crypt_Exception
     */
    public function getFromPublicKeyserver($keyid, $address = null)
    {
        return $this->_keyserverConnect($keyid, 'get', $address);
    }

    /**
     * Send a public key to a public PGP keyserver.
     *
     * @param string $pubkey  The PGP public key.
     *
     * @return string  See Horde_Crypt_Pgp::putPublicKeyserver()
     * @throws Horde_Crypt_Exception
     */
    public function sendToPublicKeyserver($pubkey)
    {
        return $this->_keyserverConnect($pubkey, 'put');
    }

    /**
     * Connect to the keyservers
     *
     * @param string $data        The data to send to the keyserver.
     * @param string $method      The method to use - either 'get' or 'put'.
     * @param string $additional  Any additional data.
     *
     * @return string  See Horde_Crypt_Pgp::getPublicKeyserver() -or-
     *                     Horde_Crypt_Pgp::putPublicKeyserver().
     * @throws Horde_Crypt_Exception
     */
    protected function _keyserverConnect($data, $method, $additional = null)
    {
        global $conf;

        if (empty($conf['gnupg']['keyserver'])) {
            throw new Horde_Crypt_Exception(_("Public PGP keyserver support has been disabled."));
        }

        $timeout = empty($conf['gnupg']['timeout'])
            ? Horde_Crypt_Pgp::KEYSERVER_TIMEOUT
            : $conf['gnupg']['timeout'];

        if ($method == 'put') {
            return $this->putPublicKeyserver($data, $conf['gnupg']['keyserver'][0], $timeout);
        }

        foreach ($conf['gnupg']['keyserver'] as $server) {
            try {
                return $this->getPublicKeyserver($data, $server, $timeout, $additional);
            } catch (Horde_Crypt_Exception $e) {}
        }
        throw new Horde_Crypt_Exception(_("Could not connect to public PGP keyserver"));
    }

    /**
     * Verifies a signed message with a given public key.
     *
     * @param string $text       The text to verify.
     * @param string $address    E-mail address of public key.
     * @param string $signature  A PGP signature block.
     * @param string $charset    Charset to use.
     *
     * @return stdClass  See Horde_Crypt_Pgp::decrypt().
     * @throws Horde_Crypt_Exception
     */
    public function verifySignature($text, $address, $signature = '',
                                    $charset = null)
    {
        if (!empty($signature)) {
            $packet_info = $this->pgpPacketInformation($signature);
            if (isset($packet_info['keyid'])) {
                $keyid = $packet_info['keyid'];
            }
        }

        if (!isset($keyid)) {
            $keyid = $this->getSignersKeyID($text);
        }

        /* Get key ID of key. */
        $public_key = $this->getPublicKey($address, array('keyid' => $keyid));

        if (empty($signature)) {
            $options = array('type' => 'signature');
        } else {
            $options = array('type' => 'detached-signature', 'signature' => $signature);
        }
        $options['pubkey'] = $public_key;

        if (!empty($charset)) {
            $options['charset'] = $charset;
        }

        return $this->decrypt($text, $options);
    }

    /**
     * Decrypt a message with user's public/private keypair or a passphrase.
     *
     * @param string $text         The text to decrypt.
     * @param string $type         Either 'literal', 'personal', or
     *                             'symmetric'.
     * @param boolean $passphrase  If $type is 'personal' or 'symmetrical',
     *                             the passphrase to use.
     *
     * @return stdClass  See Horde_Crypt_Pgp::decrypt().
     * @throws Horde_Crypt_Exception
     */
    public function decryptMessage($text, $type, $passphrase = null)
    {
        switch ($type) {
        case 'literal':
            return $this->decrypt($text, array('type' => 'message', 'no_passphrase' => true));
            break;

        case 'symmetric':
            return $this->decrypt($text, array('type' => 'message', 'passphrase' => $passphrase));
            break;

        case 'personal':
            return $this->decrypt($text, array('type' => 'message', 'pubkey' => $this->getPersonalPublicKey(), 'privkey' => $this->getPersonalPrivateKey(), 'passphrase' => $passphrase));
        }
    }

    /**
     * Gets a passphrase from the session cache.
     *
     * @param integer $type  The type of passphrase. Either 'personal' or
     *                       'symmetric'.
     * @param string $id     If $type is 'symmetric', the ID of the stored
     *                       passphrase.
     *
     * @return mixed  The passphrase, if set, or null.
     */
    public function getPassphrase($type, $id = null)
    {
        if ($type == 'personal') {
            $id = 'personal';
        }

        if (!($cache = $GLOBALS['session']->get('imp', 'pgp')) ||
            !isset($cache[$type][$id])) {
            return null;
        }

        $secret = $GLOBALS['injector']->getInstance('Horde_Secret');
        return $secret->read($secret->getKey(), $cache[$type][$id]);
    }

    /**
     * Store's the user's passphrase in the session cache.
     *
     * @param integer $type       The type of passphrase. Either 'personal' or
     *                            'symmetric'.
     * @param string $passphrase  The user's passphrase.
     * @param string $id          If $type is 'symmetric', the ID of the
     *                            stored passphrase.
     *
     * @return boolean  Returns true if correct passphrase, false if incorrect.
     */
    public function storePassphrase($type, $passphrase, $id = null)
    {
        if ($type == 'personal') {
            if ($this->verifyPassphrase($this->getPersonalPublicKey(), $this->getPersonalPrivateKey(), $passphrase) === false) {
                return false;
            }
            $id = 'personal';
        }

        $secret = $GLOBALS['injector']->getInstance('Horde_Secret');

        $cache = $GLOBALS['session']->get('imp', 'pgp', Horde_Session::TYPE_ARRAY);
        $cache[$type][$id] = $secret->write($secret->getKey(), $passphrase);
        $GLOBALS['session']->set('imp', 'pgp', $cache);

        return true;
    }

    /**
     * Clear the passphrase from the session cache.
     *
     * @param integer $type       The type of passphrase. Either 'personal' or
     *                            'symmetric'.
     * @param string $id          If $type is 'symmetric', the ID of the
     *                            stored passphrase. Else, all passphrases
     *                            are deleted.
     */
    public function unsetPassphrase($type, $id = null)
    {
        if ($cache = $GLOBALS['session']->get('imp', 'pgp')) {
            if (($type == 'symmetric') && !is_null($id)) {
                unset($cache['symmetric'][$id]);
            } else {
                unset($cache[$type]);
            }
            $GLOBALS['session']->set('imp', 'pgp', $cache);
        }
    }

    /**
     * Generates a cache ID for symmetric message data.
     *
     * @param string $mailbox  The mailbox of the message.
     * @param integer $uid     The UID of the message.
     * @param string $id       The MIME ID of the message.
     *
     * @return string  A unique symmetric cache ID.
     */
    public function getSymmetricId($mailbox, $uid, $id)
    {
        return implode('|', array($mailbox, $uid, $id));
    }

    /**
     * Provide the list of parameters needed for signing a message.
     *
     * @return array  The list of parameters needed by encrypt().
     */
    protected function _signParameters()
    {
        return array(
            'pubkey' => $this->getPersonalPublicKey(),
            'privkey' => $this->getPersonalPrivateKey(),
            'passphrase' => $this->getPassphrase('personal')
        );
    }

    /**
     * Provide the list of parameters needed for encrypting a message.
     *
     * @param Horde_Mail_Rfc822_List $addresses  The e-mail address of the
     *                                           keys to use for encryption.
     * @param string $symmetric                  If true, the symmetric
     *                                           password to use for
     *                                           encrypting. If null, uses the
     *                                           personal key.
     *
     * @return array  The list of parameters needed by encrypt().
     * @throws Horde_Crypt_Exception
     */
    protected function _encryptParameters(Horde_Mail_Rfc822_List $addresses,
                                          $symmetric)
    {
        if (!is_null($symmetric)) {
            return array(
                'symmetric' => true,
                'passphrase' => $symmetric
            );
        }

        $addr_list = array();

        foreach ($addresses as $val) {
            /* Get the public key for the address. */
            $bare_addr = $val->bare_address;
            $addr_list[$bare_addr] = $this->getPublicKey($bare_addr);
        }

        return array('recips' => $addr_list);
    }

    /**
     * Sign a Horde_Mime_Part using PGP using IMP default parameters.
     *
     * @param Horde_Mime_Part $mime_part  The object to sign.
     *
     * @return Horde_Mime_Part  See Horde_Crypt_Pgp::signMIMEPart().
     * @throws Horde_Crypt_Exception
     */
    public function impSignMimePart($mime_part)
    {
        return $this->signMimePart($mime_part, $this->_signParameters());
    }

    /**
     * Encrypt a Horde_Mime_Part using PGP using IMP default parameters.
     *
     * @param Horde_Mime_Part $mime_part         The object to encrypt.
     * @param Horde_Mail_Rfc822_List $addresses  The e-mail address of the
     *                                           keys to use for encryption.
     * @param string $symmetric                  If true, the symmetric
     *                                           password to use for
     *                                           encrypting. If null, uses the
     *                                           personal key.
     *
     * @return Horde_Mime_Part  See Horde_Crypt_Pgp::encryptMimePart().
     * @throws Horde_Crypt_Exception
     */
    public function impEncryptMimePart($mime_part,
                                       Horde_Mail_Rfc822_List $addresses,
                                       $symmetric = null)
    {
        return $this->encryptMimePart($mime_part, $this->_encryptParameters($addresses, $symmetric));
    }

    /**
     * Sign and Encrypt a Horde_Mime_Part using PGP using IMP default
     * parameters.
     *
     * @param Horde_Mime_Part $mime_part         The object to sign and
     *                                           encrypt.
     * @param Horde_Mail_Rfc822_List $addresses  The e-mail address of the
     *                                           keys to use for encryption.
     * @param string $symmetric                  If true, the symmetric
     *                                           password to use for
     *                                           encrypting. If null, uses the
     *                                           personal key.
     *
     * @return Horde_Mime_Part  See Horde_Crypt_Pgp::signAndencryptMimePart().
     * @throws Horde_Crypt_Exception
     */
    public function impSignAndEncryptMimePart($mime_part,
                                              Horde_Mail_Rfc822_List $addresses,
                                              $symmetric = null)
    {
        return $this->signAndEncryptMimePart($mime_part, $this->_signParameters(), $this->_encryptParameters($addresses, $symmetric));
    }

    /**
     * Generate a Horde_Mime_Part object, in accordance with RFC 2015/3156,
     * that contains the user's public key.
     *
     * @return Horde_Mime_Part  See Horde_Crypt_Pgp::publicKeyMimePart().
     */
    public function publicKeyMimePart()
    {
        return parent::publicKeyMimePart($this->getPersonalPublicKey());
    }

    /* UI related functions. */

    /**
     * Print PGP Key information.
     *
     * @param string $key  The PGP key.
     */
    public function printKeyInfo($key = '')
    {
        try {
            $key_info = $this->pgpPrettyKey($key);
        } catch (Horde_Crypt_Exception $e) {
            Horde::log($e, 'INFO');
            $key_info = $e->getMessage();
        }

        $this->textWindowOutput('PGP Key Information', $key_info);
    }

    /**
     * Output text in a window.
     *
     * @param string $name  The window name.
     * @param string $msg   The text contents.
     */
    public function textWindowOutput($name, $msg)
    {
        $GLOBALS['browser']->downloadHeaders($name, 'text/plain; charset=' . 'UTF-8', true, strlen($msg));
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
        global $notification, $page_output, $registry;

        $page_output->topbar = $page_output->sidebar = false;

        $page_output->addInlineScript(array(
            '$$("INPUT.horde-cancel").first().observe("click", function() { window.close(); })'
        ), true);

        IMP::header(_("Import PGP Key"));

        /* Need to use regular status notification - AJAX notifications won't
         * show in popup windows. */
        if ($registry->getView() == Horde_Registry::VIEW_DYNAMIC) {
            $notification->detach('status');
            $notification->attach('status');
        }
        IMP::status();

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/pgp'
        ));
        $view->addHelper('Text');

        $view->forminput = Horde_Util::formInput();
        $view->reload = $reload;
        $view->selfurl = Horde::url('pgp.php');
        $view->target = $target;

        echo $view->render('import_key');

        $page_output->footer();
    }

    /**
     * Reload the window.
     *
     * @param string $reload  The reload cache value.
     */
    public function reloadWindow($reload)
    {
        global $session;

        $href = $session->retrieve($reload);
        $session->purge($reload);

        echo Horde::wrapInlineScript(array(
            'opener.focus();'.
            'opener.location.href="' . $href . '";',
            'window.close();'
        ));
    }

    /**
     * Extracts public/private keys from armor data.
     *
     * @param string $data  Armor text.
     *
     * @return array  Array with these keys:
     *   - public: (array) Array of public keys.
     *   - private: (array) Array of private keys.
     */
    public function getKeys($data)
    {
        $out = array(
            'public' => array(),
            'private' => array()
        );

        foreach ($this->parsePGPData($data) as $val) {
            switch ($val['type']) {
            case Horde_Crypt_Pgp::ARMOR_PUBLIC_KEY:
            case Horde_Crypt_Pgp::ARMOR_PRIVATE_KEY:
                $key = implode("\n", $val['data']);
                if ($key_info = $this->pgpPacketInformation($key)) {
                    if (($val['type'] == Horde_Crypt_Pgp::ARMOR_PUBLIC_KEY) &&
                        !empty($key_info['public_key'])) {
                        $out['public'][] = $key;
                    } elseif (($val['type'] == Horde_Crypt_Pgp::ARMOR_PRIVATE_KEY) &&
                        !empty($key_info['secret_key'])) {
                        $out['private'][] = $key;
                    }
                }
                break;
            }
        }

        return $out;
    }

}
