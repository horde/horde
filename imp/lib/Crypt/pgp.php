<?php
/**
 * The IMP_PGP:: class contains all functions related to handling
 * PGP messages within IMP.
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Horde_Crypt_pgp extends Horde_Crypt_pgp
{
    /* Name of PGP public key field in addressbook. */
    const PUBKEY_FIELD = 'pgpPublicKey';

    /**
     * Constructor
     */
    function __construct()
    {
        parent::__construct(array(
            'program' => $GLOBALS['conf']['utils']['gnupg'],
            'temp' => Horde::getTempDir()
        ));
    }

    /**
     * Generate the personal Public/Private keypair and store in prefs.
     *
     * @param string $realname    See Horde_Crypt_pgp::
     * @param string $email       See Horde_Crypt_pgp::
     * @param string $passphrase  See Horde_Crypt_pgp::
     * @param string $comment     See Horde_Crypt_pgp::
     * @param string $keylength   See Horde_Crypt_pgp::
     *
     * @return PEAR_Error  Returns PEAR_Error object on error.
     */
    public function generatePersonalKeys($name, $email, $passphrase,
                                         $comment = '', $keylength = 1024)
    {
        $keys = $this->generateKey($name, $email, $passphrase, $comment, $keylength);
        if (is_a($keys, 'PEAR_Error')) {
            return $keys;
        }

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
        $GLOBALS['prefs']->setValue('pgp_public_key', (is_array($public_key)) ? implode('', $public_key) : $public_key);
    }

    /**
     * Add the personal private key to the prefs.
     *
     * @param mixed $private_key  The private key to add (either string or
     *                            array).
     */
    public function addPersonalPrivateKey($private_key)
    {
        $GLOBALS['prefs']->setValue('pgp_private_key', (is_array($private_key)) ? implode('', $private_key) : $private_key);
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

        $this->unsetPassphrase();
    }

    /**
     * Add a public key to an address book.
     *
     * @param string $public_key  An PGP public key.
     *
     * @return array  See Horde_Crypt_pgp::pgpPacketInformation()
     *                Returns PEAR_Error or error.
     */
    public function addPublicKey($public_key)
    {
        /* Make sure the key is valid. */
        $key_info = $this->pgpPacketInformation($public_key);
        if (!isset($key_info['signature'])) {
            return PEAR::raiseError(_("Not a valid public key."), 'horde.error');
        }

        /* Remove the '_SIGNATURE' entry. */
        unset($key_info['signature']['_SIGNATURE']);

        /* Store all signatures that appear in the key. */
        foreach ($key_info['signature'] as $id => $sig) {
            /* Check to make sure the key does not already exist in ANY
             * address book and remove the id from the key_info for a correct
             * output. */
            $result = $this->getPublicKey($sig['email'], null, false);
            if (!is_a($result, 'PEAR_Error') && !empty($result)) {
                unset($key_info['signature'][$id]);
                continue;
            }

            /* Add key to the user's address book. */
            $result = $GLOBALS['registry']->call('contacts/addField', array($sig['email'], $sig['name'], self::PUBKEY_FIELD, $public_key, $GLOBALS['prefs']->getValue('add_source')));
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
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
     * @param string $address      The e-mail address to search by.
     * @param string $fingerprint  The fingerprint of the user's key.
     * @param boolean $server      Whether to check the publick key servers for
     *                             the key.
     *
     * @return string  The PGP public key requested. Returns PEAR_Error object
     *                 on error.
     */
    public function getPublicKey($address, $fingerprint = null, $server = true)
    {
        $result = false;

        /* If there is a cache driver configured, try to get the public key
         * from the cache. */
        if (!empty($GLOBALS['conf']['cache']['driver'])) {
            $cache = &Horde_Cache::singleton($GLOBALS['conf']['cache']['driver'], Horde::getDriverConfig('cache', $GLOBALS['conf']['cache']['driver']));
            if (is_a($cache, 'PEAR_Error')) {
                Horde::fatal($cache, __FILE__, __LINE__);
            } else {
                $result = $cache->get("PGPpublicKey_" . $address . $fingerprint, 3600);
            }
        }

        if ($result) {
            Horde::logMessage('PGPpublicKey: ' . serialize($result), __FILE__, __LINE__, PEAR_LOG_DEBUG);
            return $result;
        }

        /* Try retrieving by e-mail only first. */
        $params = IMP_Compose::getAddressSearchParams();
        $result = $GLOBALS['registry']->call('contacts/getField', array($address, self::PUBKEY_FIELD, $params['sources'], false, true));

        /* TODO: Retrieve by ID. */

        /* See if the address points to the user's public key. */
        if (is_a($result, 'PEAR_Error')) {
            require_once 'Horde/Identity.php';
            $identity = &Identity::singleton(array('imp', 'imp'));
            $personal_pubkey = $this->getPersonalPublicKey();
            if (!empty($personal_pubkey) && $identity->hasAddress($address)) {
                $result = $personal_pubkey;
            }
        }

        /* Try retrieving via a PGP public keyserver. */
        if ($server && is_a($result, 'PEAR_Error')) {
            $result = $this->getFromPublicKeyserver($fingerprint, $address);
        }

        /* Return now, if no public key found at all. */
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        /* If more than one public key is returned, just return the first in
         * the array. There is no way of knowing which is the "preferred" key,
         * if the keys are different. */
        if (is_array($result)) {
            reset($result);
        }

        /* If there is a cache driver configured and a cache object exists,
         * store the public key in the cache. */
        if (is_object($cache)) {
            $cache->set("PGPpublicKey_" . $address . $fingerprint, $result, 3600);
        }

        return $result;
    }

    /**
     * Retrieves all public keys from a user's address book(s).
     *
     * @return array  All PGP public keys available. Returns PEAR_Error object
     *                on error.
     */
    public function listPublicKeys()
    {
        $params = IMP_Compose::getAddressSearchParams();
        return (empty($params['sources'])) ? array() : $GLOBALS['registry']->call('contacts/getAllAttributeValues', array(self::PUBKEY_FIELD, $params['sources']));
    }

    /**
     * Deletes a public key from a user's address book(s) by e-mail.
     *
     * @param string $email  The e-mail address to delete.
     *
     * @return PEAR_Error  Returns PEAR_Error object on error.
     */
    public function deletePublicKey($email)
    {
        $params = IMP_Compose::getAddressSearchParams();
        return $GLOBALS['registry']->call('contacts/deleteField', array($email, self::PUBKEY_FIELD, $params['sources']));
    }

    /**
     * Get a public key via a public PGP keyserver.
     *
     * @param string $fingerprint  The fingerprint of the requested key.
     * @param string $address      The email address of the requested key.
     *
     * @return string  See Horde_Crypt_pgp::getPublicKeyserver()
     */
    public function getFromPublicKeyserver($fingerprint, $address = null)
    {
        return $this->_keyserverConnect($fingerprint, 'get', $address);
    }

    /**
     * Send a public key to a public PGP keyserver.
     *
     * @param string $pubkey  The PGP public key.
     *
     * @return string  See Horde_Crypt_pgp::putPublicKeyserver()
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
     * @return string  See Horde_Crypt_pgp::getPublicKeyserver()  -or-
     *                     Horde_Crypt_pgp::putPublicKeyserver().
     */
    protected function _keyserverConnect($data, $method, $additional = null)
    {
        global $conf;

        if (!empty($conf['utils']['gnupg_keyserver'])) {
            $timeout = (empty($conf['utils']['gnupg_timeout'])) ? PGP_KEYSERVER_TIMEOUT : $conf['utils']['gnupg_timeout'];
            if ($method == 'get') {
                foreach ($conf['utils']['gnupg_keyserver'] as $server) {
                    $result = $this->getPublicKeyserver($data, $server, $timeout, $additional);
                    if (!is_a($result, 'PEAR_Error')) {
                        return $result;
                    }
                }
                return $result;
            } else {
                return $this->putPublicKeyserver($data, $conf['utils']['gnupg_keyserver'][0], $timeout);
            }
        } else {
            return PEAR::raiseError(_("Public PGP keyserver support has been disabled."), 'horde.warning');
        }
    }

    /**
     * Verifies a signed message with a given public key.
     *
     * @param string $text       The text to verify.
     * @param string $address    E-mail address of public key.
     * @param string $signature  A PGP signature block.
     *
     * @return string  See Horde_Crypt_pgp::decryptSignature()  -or-
     *                     Horde_Crypt_pgp::decryptDetachedSignature().
     */
    public function verifySignature($text, $address, $signature = '')
    {
        $fingerprint = null;

        /* Get fingerprint of key. */
        if (!empty($signature)) {
            $packet_info = $this->pgpPacketInformation($signature);
            if (isset($packet_info['fingerprint'])) {
                $fingerprint = $packet_info['fingerprint'];
            }
        } else {
            $fingerprint = $this->getSignersKeyID($text);
        }

        $public_key = $this->getPublicKey($address, $fingerprint);
        if (is_a($public_key, 'PEAR_Error')) {
            return $public_key;
        }

        if (empty($signature)) {
            $options = array('type' => 'signature');
        } else {
            $options = array('type' => 'detached-signature', 'signature' => $signature);
        }
        $options['pubkey'] = $public_key;

        /* decrypt() returns a PEAR_Error object on error. */
        return $this->decrypt($text, $options);
    }

    /**
     * Decrypt a message with user's public/private keypair or a passphrase.
     *
     * @param string $text             The text to decrypt.
     * @param boolean $symmetric_hint  Whether the text has been encrypted
     *                                 symmetrically. If null, we try to find
     *                                 out.
     * @param boolean $passphrase      Whether a passphrase has to be used.
     *
     * @return string  The decrypted message. Returns PEAR_Error object on
     *                 error.
     */
    public function decryptMessage($text, $symmetric_hint = null,
                                   $passphrase = true)
    {
        if (is_null($symmetric_hint)) {
            $symmetric_hint = $this->encryptedSymmetrically($text);
        }

        /* decrypt() returns a PEAR_Error object on error. */
        if (!$passphrase) {
            return $this->decrypt($text, array('type' => 'message', 'no_passphrase' => true));
        } elseif ($symmetric_hint) {
            return $this->decrypt($text, array('type' => 'message', 'passphrase' => $this->getSymmetricPassphrase()));
        } else {
            return $this->decrypt($text, array('type' => 'message', 'pubkey' => $this->getPersonalPublicKey(), 'privkey' => $this->getPersonalPrivateKey(), 'passphrase' => $this->getPassphrase()));
        }
    }

    /**
     * Gets the user's passphrase from the session cache.
     *
     * @return string  The passphrase, if set.
     */
    public function getPassphrase()
    {
        if (isset($_SESSION['imp']['pgp_passphrase'])) {
            return Secret::read(IMP::getAuthKey(), $_SESSION['imp']['pgp_passphrase']);
        }
    }

    /**
     * Gets the user's passphrase for symmetric encryption from the session
     * cache.
     *
     * @return string  The passphrase, if set.
     */
    public function getSymmetricPassphrase()
    {
        if (isset($_SESSION['imp']['pgp_sym_passphrase'])) {
            return Secret::read(IMP::getAuthKey(), $_SESSION['imp']['pgp_sym_passphrase']);
        }
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
        if ($this->verifyPassphrase($this->getPersonalPublicKey(), $this->getPersonalPrivateKey(), $passphrase) === false) {
            return false;
        }

        $_SESSION['imp']['pgp_passphrase'] = Secret::write(IMP::getAuthKey(), $passphrase);
        return true;
    }

    /**
     * Store's the user's passphrase for symmetric encryption in the session
     * cache.
     *
     * @param string $passphrase  The user's passphrase.
     *
     * @return boolean  True
     */
    public function storeSymmetricPassphrase($passphrase)
    {
        $_SESSION['imp']['pgp_sym_passphrase'] = Secret::write(IMP::getAuthKey(), $passphrase);
        return true;
    }

    /**
     * Clear the passphrase from the session cache.
     */
    public function unsetPassphrase()
    {
        unset($_SESSION['imp']['pgp_passphrase']);
    }

    /**
     * Clear the passphrase for symmetric encryption from the session cache.
     */
    public function unsetSymmetricPassphrase()
    {
        unset($_SESSION['imp']['pgp_sym_passphrase']);
    }

    /**
     * Generates the javascript code for saving public keys.
     *
     * @param Horde_Mime_Part &$mime_part  The part containing the public key.
     * @param string $cache                The part identifier.
     *
     * @return string  The URL for saving public keys.
     */
    public function savePublicKeyURL(&$mime_part, $cache = null)
    {
        if (empty($cache)) {
            require_once 'Horde/SessionObjects.php';
            $cacheSess = &Horde_SessionObjects::singleton();
            $oid = $cacheSess->storeOid($mime_part);
        }

        return $this->getJSOpenWinCode('save_attachment_public_key', false, array('mimecache' => $oid));
    }

    /**
     * Print out the link for the javascript PGP popup.
     *
     * @param string $actionid  The ActionID to perform.
     * @param mixed $reload     If true, reload base window on close. If text,
     *                          run this JS on close. If false, don't do
     *                          anything on close.
     * @param array $params     Additional parameters needed for the reload
     *                          page.
     *
     * @return string  The javascript link.
     */
    public function getJSOpenWinCode($actionid, $reload = true, $params = array())
    {
        $params['actionID'] = $actionid;
        if (!empty($reload)) {
            if (is_bool($reload)) {
                $params['reload'] = html_entity_decode(Util::removeParameter(Horde::selfUrl(true), array('actionID')));
            } else {
                require_once 'Horde/SessionObjects.php';
                $cacheSess = &Horde_SessionObjects::singleton();
                $params['passphrase_action'] = $cacheSess->storeOid($reload, false);
            }
        }

        return IMP::popupIMPString('pgp.php', $params, 450, 200);
    }

    /**
     * Provide the list of parameters needed for signing a message.
     *
     * @return array  The list of parameters needed by encrypt().
     */
    protected function _signParameters()
    {
        return array('pubkey' => $this->getPersonalPublicKey(), 'privkey' => $this->getPersonalPrivateKey(), 'passphrase' => $this->getPassphrase());
    }

    /**
     * Provide the list of parameters needed for encrypting a message.
     *
     * @param array $addresses    The e-mail address of the keys to use for
     *                            encryption.
     * @param boolean $symmetric  Whether to encrypt symmetrically.
     *
     * @return array  The list of parameters needed by encrypt().
     *                Returns PEAR_Error on error.
     */
    protected function _encryptParameters($addresses, $symmetric = false)
    {
        if ($symmetric) {
            return array('symmetric' => true,
                         'passphrase' => $this->getSymmetricPassphrase());
        }

        $addr_list = array();

        foreach ($addresses as $val) {
            $addrOb = Horde_Mime_Address::bareAddress($val, $_SESSION['imp']['maildomain'], true);
            $key_addr = array_pop($addrOb);

            /* Get the public key for the address. */
            $public_key = $this->getPublicKey($key_addr);
            if (is_a($public_key, 'PEAR_Error')) {
                return $public_key;
            }
            $addr_list[$key_addr] = $public_key;
        }

        return array('recips' => $addr_list);
    }

    /**
     * Sign a Horde_Mime_Part using PGP using IMP default parameters.
     *
     * @param Horde_Mime_Part $mime_part  The object to sign.
     *
     * @return Horde_Mime_Part  See Horde_Crypt_pgp::signMIMEPart(). Returns
     *                          PEAR_Error object on error.
     */
    public function IMPsignMIMEPart($mime_part)
    {
        return $this->signMIMEPart($mime_part, $this->_signParameters());
    }

    /**
     * Encrypt a Horde_Mime_Part using PGP using IMP default parameters.
     *
     * @param Horde_Mime_Part $mime_part  The object to encrypt.
     * @param array $addresses      The e-mail address of the keys to use for
     *                              encryption.
     * @param boolean $symmetric    Whether to encrypt symmetrically.
     *
     * @return Horde_Mime_Part  See Horde_Crypt_pgp::encryptMIMEPart(). Returns
     *                    PEAR_Error object on error.
     */
    public function IMPencryptMIMEPart($mime_part, $addresses,
                                       $symmetric = false)
    {
        $params = $this->_encryptParameters($addresses, $symmetric);
        if (is_a($params, 'PEAR_Error')) {
            return $params;
        }
        return $this->encryptMIMEPart($mime_part, $params);
    }

    /**
     * Sign and Encrypt a Horde_Mime_Part using PGP using IMP default parameters.
     *
     * @param Horde_Mime_Part $mime_part  The object to sign and encrypt.
     * @param array $addresses      The e-mail address of the keys to use for
     *                              encryption.
     * @param boolean $symmetric    Whether to encrypt symmetrically.
     *
     * @return Horde_Mime_Part  See Horde_Crypt_pgp::signAndencryptMIMEPart().
     *                    Returns PEAR_Error object on error.
     */
    public function IMPsignAndEncryptMIMEPart($mime_part, $addresses,
                                              $symmetric = false)
    {
        $encrypt_params = $this->_encryptParameters($addresses, $symmetric);
        if (is_a($encrypt_params, 'PEAR_Error')) {
            return $encrypt_params;
        }
        return $this->signAndEncryptMIMEPart($mime_part, $this->_signParameters(), $encrypt_params);
    }

    /**
     * Generate a Horde_Mime_Part object, in accordance with RFC 2015/3156, that
     * contains the user's public key.
     *
     * @return Horde_Mime_Part  See Horde_Crypt_pgp::publicKeyMIMEPart().
     */
    public function publicKeyMIMEPart()
    {
        return parent::publicKeyMIMEPart($this->getPersonalPublicKey());
    }

}
