<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Contains code related to handling PGP data within IMP.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Pgp
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
     * Pgp object.
     *
     * @var Horde_Crypt_Pgp
     */
    protected $_pgp;

    /**
     * Return whether PGP support is current enabled in IMP.
     *
     * @return boolean  True if PGP support is enabled.
     */
    public static function enabled()
    {
        global $conf, $prefs;

        return (!empty($conf['gnupg']['path']) && $prefs->getValue('use_pgp'));
    }

    /**
     * Constructor.
     *
     * @param Horde_Crypt_Pgp $pgp  PGP object.
     */
    public function __construct(Horde_Crypt_Pgp $pgp)
    {
        $this->_pgp = $pgp;
    }

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
     * @param string $name        See Horde_Crypt_Pgp::.
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
        $keys = $this->_pgp->generateKey(
            $name,
            $email,
            $passphrase,
            $comment,
            $keylength,
            $expire
        );

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
        $GLOBALS['prefs']->setValue('pgp_public_key', trim($public_key));
    }

    /**
     * Add the personal private key to the prefs.
     *
     * @param mixed $private_key  The private key to add (either string or
     *                            array).
     */
    public function addPersonalPrivateKey($private_key)
    {
        $GLOBALS['prefs']->setValue('pgp_private_key', trim($private_key));
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
        $key_info = $this->_pgp->pgpPacketInformation($public_key);
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
                $result = $this->getPublicKey($sig['email'], array(
                    'nocache' => true,
                    'nohooks' => true,
                    'noserver' => true
                ));
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
     *   - nohooks: (boolean) Don't trigger hook when retrieving public key?
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
        global $injector, $registry;

        $keyid = empty($options['keyid'])
            ? ''
            : $options['keyid'];

        /* If there is a cache driver configured, try to get the public key
         * from the cache. */
        if (empty($options['nocache']) &&
            ($cache = $injector->getInstance('Horde_Cache'))) {
            $result = $cache->get("PGPpublicKey_" . $address . $keyid, 3600);
            if ($result) {
                Horde::log('PGPpublicKey: ' . serialize($result), 'DEBUG');
                return $result;
            }
        }

        if (empty($options['nohooks'])) {
            try {
                $key = $injector->getInstance('Horde_Core_Hooks')->callHook(
                    'pgp_key',
                    'imp',
                    array($address, $keyid)
                );
                if ($key) {
                    return $key;
                }
            } catch (Horde_Exception_HookNotSet $e) {}
        }

        /* Try retrieving by e-mail only first. */
        $result = null;
        try {
            $result = $registry->call(
                'contacts/getField',
                array(
                    $address,
                    self::PUBKEY_FIELD,
                    $injector->getInstance('IMP_Contacts')->sources,
                    true,
                    true
                )
            );
        } catch (Horde_Exception $e) {}

        if (is_null($result)) {
            /* TODO: Retrieve by ID. */

            /* See if the address points to the user's public key. */
            $identity = $injector->getInstance('IMP_Identity');
            $personal_pubkey = $this->getPersonalPublicKey();
            if (!empty($personal_pubkey) && $identity->hasAddress($address)) {
                $result = $personal_pubkey;
            } elseif (empty($options['noserver'])) {
                $result = null;

                try {
                    foreach ($this->_keyserverList() as $val) {
                        try {
                            $result = $val->get(
                                empty($keyid) ? $val->getKeyId($address) : $keyid
                            );
                            break;
                        } catch (Exception $e) {}
                    }

                    if (is_null($result)) {
                        throw $e;
                    }

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
        $sources = $GLOBALS['injector']->getInstance('IMP_Contacts')->sources;

        return empty($sources)
            ? array()
            : $GLOBALS['registry']->call('contacts/getAllAttributeValues', array(self::PUBKEY_FIELD, $sources));
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
        return $GLOBALS['registry']->call(
            'contacts/deleteField',
            array(
                $email,
                self::PUBKEY_FIELD,
                $GLOBALS['injector']->getInstance('IMP_Contacts')->sources
            )
        );
    }

    /**
     * Send a public key to a public PGP keyserver.
     *
     * @param string $pubkey  The PGP public key.
     *
     * @throws Horde_Crypt_Exception
     */
    public function sendToPublicKeyserver($pubkey)
    {
        $servers = $this->_keyserverList();
        $servers[0]->put($pubkey);
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
            $packet_info = $this->_pgp->pgpPacketInformation($signature);
            if (isset($packet_info['keyid'])) {
                $keyid = $packet_info['keyid'];
            }
        }

        if (!isset($keyid)) {
            $keyid = $this->_pgp->getSignersKeyID($text);
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

        return $this->_pgp->decrypt($text, $options);
    }

    /**
     * Decrypt a message with user's public/private keypair or a passphrase.
     *
     * @param string $text  The text to decrypt.
     * @param string $type  Either 'literal', 'personal', or 'symmetric'.
     * @param array $opts   Additional options:
     *   - passphrase: (boolean) If $type is 'personal' or 'symmetrical', the
     *                 passphrase to use.
     *   - sender: (string) The sender of the message (used to check signature
     *             if message is both encrypted & signed).
     *
     * @return stdClass  See Horde_Crypt_Pgp::decrypt().
     * @throws Horde_Crypt_Exception
     */
    public function decryptMessage($text, $type, array $opts = array())
    {
        $opts = array_merge(array(
            'passphrase' => null
        ), $opts);

        $pubkey = $this->getPersonalPublicKey();
        if (isset($opts['sender'])) {
            try {
                $pubkey .= "\n" . $this->getPublicKey($opts['sender']);
            } catch (Horde_Crypt_Exception $e) {}
        }

        switch ($type) {
        case 'literal':
            return $this->_pgp->decrypt($text, array(
                'no_passphrase' => true,
                'pubkey' => $pubkey,
                'type' => 'message'
            ));
            break;

        case 'symmetric':
            return $this->_pgp->decrypt($text, array(
                'passphrase' => $opts['passphrase'],
                'pubkey' => $pubkey,
                'type' => 'message'
            ));
            break;

        case 'personal':
            return $this->_pgp->decrypt($text, array(
                'passphrase' => $opts['passphrase'],
                'privkey' => $this->getPersonalPrivateKey(),
                'pubkey' => $pubkey,
                'type' => 'message'
            ));
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

        return (($cache = $GLOBALS['session']->get('imp', 'pgp')) && isset($cache[$type][$id]))
            ? $cache[$type][$id]
            : null;
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
        global $session;

        if ($type == 'personal') {
            if ($this->_pgp->verifyPassphrase($this->getPersonalPublicKey(), $this->getPersonalPrivateKey(), $passphrase) === false) {
                return false;
            }
            $id = 'personal';
        }

        $cache = $session->get('imp', 'pgp', Horde_Session::TYPE_ARRAY);
        $cache[$type][$id] = $passphrase;
        $session->set('imp', 'pgp', $cache, $session::ENCRYPT);

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
    public function signMimePart($mime_part)
    {
        return $this->_pgp->signMimePart($mime_part, $this->_signParameters());
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
    public function encryptMimePart($mime_part,
                                    Horde_Mail_Rfc822_List $addresses,
                                    $symmetric = null)
    {
        return $this->_pgp->encryptMimePart(
            $mime_part,
            $this->_encryptParameters($addresses, $symmetric)
        );
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
    public function signAndEncryptMimePart($mime_part,
                                           Horde_Mail_Rfc822_List $addresses,
                                           $symmetric = null)
    {
        return $this->_pgp->signAndEncryptMimePart(
            $mime_part,
            $this->_signParameters(),
            $this->_encryptParameters($addresses, $symmetric)
        );
    }

    /**
     * Generate a Horde_Mime_Part object, in accordance with RFC 2015/3156,
     * that contains the user's public key.
     *
     * @return Horde_Mime_Part  See Horde_Crypt_Pgp::publicKeyMimePart().
     */
    public function publicKeyMimePart()
    {
        return $this->_pgp->publicKeyMimePart($this->getPersonalPublicKey());
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
        global $injector;

        $out = array(
            'public' => array(),
            'private' => array()
        );

        foreach ($injector->getInstance('Horde_Crypt_Pgp_Parse')->parse($data) as $val) {
            switch ($val['type']) {
            case Horde_Crypt_Pgp::ARMOR_PUBLIC_KEY:
            case Horde_Crypt_Pgp::ARMOR_PRIVATE_KEY:
                $key = implode("\n", $val['data']);
                if ($key_info = $this->_pgp->pgpPacketInformation($key)) {
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

        if (!empty($out['private']) &&
            empty($out['public']) &&
            $res = $this->_pgp->getPublicKeyFromPrivateKey(reset($out['private']))) {
            $out['public'][] = $res;
        }

        return $out;
    }

    /**
     * Returns human readable information on a PGP key.
     *
     * @param string $pgpdata  The PGP data block.
     *
     * @return string  Tabular information on the PGP key.
     * @throws Horde_Pgp_Exception
     */
    public function prettyKey($pgpdata)
    {
        $msg = '';
        $info = $this->_pgp->pgpPacketInformation($pgpdata);

        if (empty($info['signature'])) {
            return $msg;
        }

        $fingerprints = $this->_pgp->getFingerprintsFromKey($pgpdata);

        $getKeyIdString = function($keyid) {
            /* Get the 8 character key ID string. */
            if (strpos($keyid, '0x') === 0) {
                $keyid = substr($keyid, 2);
            }
            if (strlen($keyid) > 8) {
                $keyid = substr($keyid, -8);
            }
            return '0x' . $keyid;
        };

        /* Making the property names the same width for all localizations .*/
        $leftrow = array(
            _("Name"),
            _("Key Type"),
            _("Key Creation"),
            _("Expiration Date"),
            _("Key Length"),
            _("Comment"),
            _("E-Mail"),
            _("Hash-Algorithm"),
            _("Key ID"),
            _("Key Fingerprint")
        );

        array_walk(
            $leftrow,
            function (&$s, $k, $m) {
                $s .= ':' . str_repeat(' ', $m - Horde_String::length($s));
            },
            max(array_map('strlen', $leftrow)) + 2
        );

        foreach ($info['signature'] as $uid_idx => $val) {
            if ($uid_idx == '_SIGNATURE') {
                continue;
            }

            $key = $this->_pgp->pgpPacketSignatureByUidIndex(
                $pgpdata,
                $uid_idx
            );

            $keyid = empty($key['keyid'])
                ? null
                : $getKeyIdString($key['keyid']);
            $fingerprint = isset($fingerprints[$keyid])
                ? $fingerprints[$keyid]
                : null;
            $sig_key = 'sig_' . $key['keyid'];

            $msg .= $leftrow[0] . (isset($key['name']) ? stripcslashes($key['name']) : '') . "\n"
                . $leftrow[1] . (($key['key_type'] == 'public_key') ? _("Public Key") : _("Private Key")) . "\n"
                . $leftrow[2] . strftime("%D", $val[$sig_key]['created']) . "\n"
                . $leftrow[3] . (empty($val[$sig_key]['expires']) ? '[' . _("Never") . ']' : strftime("%D", $val[$sig_key]['expires'])) . "\n"
                . $leftrow[4] . $key['key_size'] . " Bytes\n"
                . $leftrow[5] . (empty($key['comment']) ? '[' . _("None") . ']' : $key['comment']) . "\n"
                . $leftrow[6] . (empty($key['email']) ? '[' . _("None") . ']' : $key['email']) . "\n"
                . $leftrow[7] . (empty($key['micalg']) ? '[' . _("Unknown") . ']' : $key['micalg']) . "\n"
                . $leftrow[8] . (empty($keyid) ? '[' . _("Unknown") . ']' : $keyid) . "\n"
                . $leftrow[9] . (empty($fingerprint) ? '[' . _("Unknown") . ']' : $fingerprint) . "\n\n";
        }

        return $msg;
    }

    /**
     * Return list of keyserver objects.
     *
     * @return array  List of Horde_Crypt_Pgp_Keyserver objects.
     * @throws Horde_Crypt_Exception
     */
    protected function _keyserverList()
    {
        global $conf, $injector;

        if (empty($conf['gnupg']['keyserver'])) {
            throw new Horde_Crypt_Exception(_("Public PGP keyserver support has been disabled."));
        }

        $http = $injector->getInstance('Horde_Core_Factory_HttpClient')->create();
        if (!empty($conf['gnupg']['timeout'])) {
            $http->{'request.timeout'} = $conf['gnupg']['timeout'];
        }

        $out = array();
        foreach ($conf['gnupg']['keyserver'] as $server) {
            $out[] = new Horde_Crypt_Pgp_Keyserver($this, array(
                'http' => $http,
                'keyserver' => $server
            ));
        }

        return $out;
    }

}
