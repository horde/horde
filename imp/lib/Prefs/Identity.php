<?php
/**
 * This class provides an IMP-specific interface to all identities a
 * user might have. Its methods take care of any site-specific
 * restrictions configured in prefs.php and conf.php.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class Imp_Prefs_Identity extends Horde_Core_Prefs_Identity
{
    /**
     * Cached data.
     *
     * @var array
     */
    protected $_cached = array(
        'aliases' => array(),
        'fromList' => array(),
        'froms' => array(),
        'names' => array(),
        // 'own_addresses'
        'signatures' => array()
        // 'tie_addresses'
    );

    /**
     * Identity preferences added by IMP.
     *
     * @var array
     */
    protected $_impPrefs = array(
        'replyto_addr', 'alias_addr', 'tieto_addr', 'bcc_addr', 'signature',
        'signature_html', 'save_sent_mail', 'sent_mail_folder'
    );

    /**
     * Reads all the user's identities from the prefs object or builds
     * a new identity from the standard values given in prefs.php.
     *
     * @see __construct()
     */
    public function __construct($params)
    {
        $this->_prefnames['properties'] = array_merge(
            $this->_prefnames['properties'],
            $this->_impPrefs
        );

        parent::__construct($params);
    }

    /**
     * Verifies and sanitizes all identity properties.
     *
     * @param integer $identity  The identity to verify.
     *
     * @throws Horde_Prefs_Exception
     */
    public function verify($identity = null)
    {
        if (!isset($identity)) {
            $identity = $this->_default;
        }

        /* Fill missing IMP preferences with default values. */
        foreach ($this->_impPrefs as $pref) {
            if (!isset($this->_identities[$identity][$pref])) {
                $this->_identities[$identity][$pref] = $this->_prefs->getValue($pref);
            }
        }

        parent::verify($identity);

        /* Clean up Reply-To, Alias, Tie-to, and BCC addresses. */
        foreach (array('replyto_addr', 'alias_addr', 'tieto_addr', 'bcc_addr') as $val) {
            $ob = IMP::parseAddressList($val, array(
                'limit' => ($val == 'replyto_addr') ? 1 : 0
            ));

            /* Validate addresses */
            foreach ($ob as $address) {
                try {
                    IMP::parseAddressList($address, array(
                        'validate' => true
                    ));
                } catch (Horde_Mail_Exception $e) {
                    throw new Horde_Prefs_Exception(sprintf(_("\"%s\" is not a valid email address.", strval($address))));
                }
            }

            $this->setValue($val, $ob->addresses, $identity);
        }
    }

    /**
     * Returns a complete From: header based on all relevant factors (fullname,
     * from address, input fields, locks etc.)
     *
     * @param integer $ident        The identity to retrieve the values from.
     * @param string $from_address  A default from address to use if no
     *                              identity is selected and the from_addr
     *                              preference is locked.
     *
     * @return string  A full From: header in the format
     *                 'Fullname <user@example.com>'.
     * @throws Horde_Exception
     */
    public function getFromLine($ident = null, $from_address = '')
    {
        if (isset($this->_cached['froms'][$ident])) {
            return $this->_cached['froms'][$ident];
        }

        if (!isset($ident)) {
            $address = $from_address;
        }

        if (empty($address) ||
            $this->_prefs->isLocked($this->_prefnames['from_addr'])) {
            $address = $this->getFromAddress($ident);
        }

        $result = IMP::parseAddressList($address);
        $ob = $result[0];

        if (is_null($ob->personal)) {
            $ob->personal = $this->getFullname($ident);
        }

        $from = $ob->writeAddress();
        $this->_cached['froms'][$ident] = $from;

        return $from;
    }

    /**
     * Returns an array with From: headers from all identities
     *
     * @return array  The From: headers from all identities
     */
    public function getAllFromLines()
    {
        foreach (array_keys($this->_identities) as $ident) {
            $list[$ident] = $this->getFromAddress($ident);
        }
        return $list;
    }

    /**
     * Returns an array with the necessary values for the identity select
     * box in the IMP compose window.
     *
     * @return array  The array with the necessary strings
     */
    public function getSelectList()
    {
        $ids = $this->getAll($this->_prefnames['id']);
        foreach ($ids as $key => $id) {
            $list[$key] = $this->getFromAddress($key) . ' (' . $id . ')';
        }
        return $list;
    }

    /**
     * Returns true if the given address belongs to one of the identities.
     * This function will search aliases for an identity automatically.
     *
     * @param string $address  The address to search for in the identities.
     *
     * @return boolean  True if the address was found.
     */
    public function hasAddress($address)
    {
        $list = $this->getAllFromAddresses(true);
        return isset($list[Horde_String::lower($address)]);
    }

    /**
     * Returns the from address based on the chosen identity. If no
     * address can be found it is built from the current user name and
     * the specified maildomain.
     *
     * @param integer $ident  The identity to retrieve the address from.
     *
     * @return string  A valid from address.
     */
    public function getFromAddress($ident = null)
    {
        if (!isset($this->_cached['fromList'][$ident])) {
            $val = $this->getValue($this->_prefnames['from_addr'], $ident);
            if (empty($val)) {
                $val = $GLOBALS['registry']->getAuth();
            }

            if (!strstr($val, '@')) {
                $val .= '@' . $GLOBALS['session']->get('imp', 'maildomain');
            }

            $this->_cached['fromList'][$ident] = $val;
        }

        return $this->_cached['fromList'][$ident];
    }

    /**
     * Returns all aliases based on the chosen identity.
     *
     * @param integer $ident  The identity to retrieve the aliases from.
     *
     * @return array  Aliases for the identity.
     */
    public function getAliasAddress($ident)
    {
        if (!isset($this->_cached['aliases'][$ident])) {
            $this->_cached['aliases'][$ident] = @array_merge(
                (array)$this->getValue('alias_addr', $ident),
                array($this->getValue('replyto_addr', $ident))
            );
        }

        return $this->_cached['aliases'][$ident];
    }

    /**
     * Returns an array with all identities' from addresses.
     *
     * @param boolean $alias  Include aliases?
     *
     * @return array  The array with
     *                KEY - address
     *                VAL - identity number
     */
    public function getAllFromAddresses($alias = false)
    {
        $list = array();

        foreach ($this->_identitiesWithDefaultLast() as $key => $identity) {
            /* Get From Addresses. */
            $list[Horde_String::lower($this->getFromAddress($key))] = $key;

            /* Get Aliases. */
            if ($alias) {
                $addrs = $this->getAliasAddress($key);
                if (!empty($addrs)) {
                    foreach (array_filter($addrs) as $val) {
                        $list[Horde_String::lower($val)] = $key;
                    }
                }
            }
        }

        return $list;
    }

    /**
     * Get all 'tie to' address/identity pairs.
     *
     * @return array  The array with
     *                KEY - address
     *                VAL - identity number
     */
    public function getAllTieAddresses()
    {
        $list = array();

        foreach ($this->_identitiesWithDefaultLast() as $key => $identity) {
            $tieaddr = $this->getValue('tieto_addr', $key);
            if (!empty($tieaddr)) {
                foreach ($tieaddr as $val) {
                    $list[$val] = $key;
                }
            }
        }

        return $list;
    }

    /**
     * Returns a list of all e-mail addresses from all identities, including
     * both from addresses and tie addreses.
     *
     * @return array  A list of e-mail addresses.
     */
    public function getAllIdentityAddresses()
    {
        /* Combine the keys (which contain the e-mail addresses). */
        return array_merge(
            array_keys($this->getAllFromAddresses(true)),
            array_keys($this->getAllTieAddresses())
        );
    }

    /**
     * Returns the list of identities with the default identity positioned
     * last.
     *
     * @return array  The identities list with the default identity last.
     */
    protected function _identitiesWithDefaultLast()
    {
        $ids = $this->_identities;
        $default = $this->getDefault();
        $tmp = $ids[$default];
        unset($ids[$default]);
        $ids[$default] = $tmp;
        return $ids;
    }

    /**
     * Returns the BCC addresses for a given identity.
     *
     * @param integer $ident  The identity to retrieve the Bcc addresses from.
     *
     * @return Horde_Mail_Rfc822_List  BCC addresses.
     */
    public function getBccAddresses($ident = null)
    {
        return IMP::parseAddressList($this->getValue('bcc_addr', $ident));
    }

    /**
     * Returns the identity's id that matches the passed addresses.
     *
     * @param mixed $addresses     Either an array or a single string or a
     *                             comma-separated list of email addresses.
     * @param boolean $search_own  Search for a matching identity in own
     *                             addresses also?
     *
     * @return integer  The id of the first identity that from or alias
     *                  addresses match (one of) the passed addresses or
     *                  null if none matches.
     */
    public function getMatchingIdentity($addresses, $search_own = true)
    {
        if (!isset($this->_cached['tie_addresses'])) {
            $this->_cached['tie_addresses'] = $this->getAllTieAddresses();
            $this->_cached['own_addresses'] = $this->getAllFromAddresses(true);
        }

        foreach (IMP::parseAddressList($addresses) as $address) {
            $bare_address = $address->bare_address;

            /* Search 'tieto' addresses first. Check for this address
             * explicitly. */
            if (isset($this->_cached['tie_addresses'][$bare_address])) {
                return $this->_cached['tie_addresses'][$bare_address];
            }

            /* If we didn't find the address, check for the domain. */
            if (!is_null($address->host) &&
                isset($this->_cached['tie_addresses']['@' . $address->host])) {
                return $this->_cached['tie_addresses']['@' . $address->host];
            }

            /* Next, search all from addresses. */
            if ($search_own &&
                isset($this->_cached['own_addresses'][$bare_address])) {
                return $this->_cached['own_addresses'][$bare_address];
            }
        }

        return null;
    }

    /**
     * Returns the user's full name.
     *
     * @param integer $ident  The identity to retrieve the name from.
     *
     * @return string  The user's full name.
     */
    public function getFullname($ident = null)
    {
        if (isset($this->_cached['names'][$ident])) {
            return $this->_cached['names'][$ident];
        }

        $this->_cached['names'][$ident] = $this->getValue($this->_prefnames['fullname'], $ident);

        return $this->_cached['names'][$ident];
    }

    /**
     * Returns the full signature based on the current settings for the
     * signature itself, the dashes and the position.
     *
     * @param string $type    Either 'text' or 'html'.
     * @param integer $ident  The identity to retrieve the signature from.
     *
     * @return string  The full signature.
     * @throws Horde_Exception
     */
    public function getSignature($type = 'text', $ident = null)
    {
        $convert = false;
        $key = $ident . '|' . $type;
        $val = null;

        if (isset($this->_cached['signatures'][$key])) {
            return $this->_cached['signatures'][$key];
        }

        if ($type == 'html') {
            $val = $this->getValue('signature_html', $ident);
            if (!strlen($val)) {
                $convert = true;
                $val = null;
            }
        }

        if (is_null($val)) {
            $val = $this->getValue('signature', $ident);

            if (strlen($val) && ($type == 'text')) {
                $val = str_replace("\r\n", "\n", $val);
                $val = ($this->getValue('sig_dashes', $ident))
                    ? "\n-- \n" . $val
                    : "\n" . $val;
            }
        }

        if ($val && ($type == 'html')) {
            if ($convert) {
                $val = IMP_Compose::text2html(trim($val));
            }

            $val = '<div>' . $val . '</div>';
        }

        try {
            $val = Horde::callHook('signature', array($val), 'imp');
        } catch (Horde_Exception_HookNotSet $e) {}

        $this->_cached['signatures'][$key] = $val;

        return $val;
    }

    /**
     * Returns an array with the signatures from all identities
     *
     * @param string $type  Either 'text' or 'html'.
     *
     * @return array  The array with all the signatures.
     */
    public function getAllSignatures($type = 'text')
    {
        foreach ($this->_identities as $key => $identity) {
            $list[$key] = $this->getSignature($type, $key);
        }

        return $list;
    }

    /**
     * Returns a property from one of the identities.
     *
     * @see getValue()
     */
    public function getValue($key, $identity = null)
    {
        $val = parent::getValue($key, $identity);

        switch ($key) {
        case 'sent_mail_folder':
            return (is_string($val) && strlen($val))
                ? IMP_Mailbox::get(IMP_Mailbox::prefFrom($val))
                : null;

        default:
            return $val;
        }
    }

    /**
     * Sets a property with a specified value.
     *
     * @see setValue()
     */
    public function setValue($key, $val, $identity = null)
    {
        if ($key == 'sent_mail_folder') {
            if ($val) {
                $val->expire(IMP_Mailbox::CACHE_SPECIALMBOXES);
            } else {
                IMP_Mailbox::get('INBOX')->expire(IMP_Mailbox::CACHE_SPECIALMBOXES);
            }
            $val = IMP_Mailbox::prefTo($val);
        }
        return parent::setValue($key, $val, $identity);
    }

    /**
     * Returns an array with the sent-mail mailboxes from all identities.
     *
     * @return array  The array with the sent-mail IMP_Mailbox objects.
     */
    public function getAllSentmail()
    {
        $list = array();

        foreach (array_keys($this->_identities) as $key) {
            if ($mbox = $this->getValue('sent_mail_folder', $key)) {
                $list[strval($mbox)] = 1;
            }
        }

        return IMP_Mailbox::get(array_keys($list));
    }

    /**
     * Returns true if the mail should be saved and the user is allowed to.
     *
     * @param integer $ident  The identity to retrieve the setting from.
     *
     * @return boolean  True if the sent mail should be saved.
     */
    public function saveSentmail($ident = null)
    {
        return $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_FOLDERS)
            ? $this->getValue('save_sent_mail', $ident)
            : false;
    }

}
