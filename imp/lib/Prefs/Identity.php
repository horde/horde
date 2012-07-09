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
        'from' => array(),
        'names' => array(),
        'signatures' => array()
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
            $ob = IMP::parseAddressList($this->getValue($val), array(
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
     * @return Horde_Mail_Rfc822_Address  The address to use for From header.
     * @throws Horde_Exception
     */
    public function getFromLine($ident = null, $from_address = '')
    {
        $address = is_null($ident)
            ? $from_address
            : null;

        if (empty($address) ||
            $this->_prefs->isLocked($this->_prefnames['from_addr'])) {
            return $this->getFromAddress($ident);
        }

        $result = IMP::parseAddressList($address);
        return $result[0];
    }

    /**
     * Returns an array with the necessary values for the identity select
     * box in the IMP compose window.
     *
     * @return array  The array with the necessary strings
     */
    public function getSelectList()
    {
        $list = array();

        foreach ($this->getAll($this->_prefnames['id']) as $k => $v) {
            $list[$k] = strval($this->getFromAddress($k)) . ' (' . $v . ')';
        }

        return $list;
    }

    /**
     * Returns true if the given address belongs to one of the identities.
     * This function will search aliases for an identity automatically.
     *
     * @param mixed $address  The address(es) to search for in the identities.
     *
     * @return boolean  True if the address was found.
     */
    public function hasAddress($address)
    {
        $from_addr = $this->getAllFromAddresses();

        foreach (IMP::parseAddressList($address)->bare_addresses as $val) {
            if ($from_addr->contains($val)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the from address based on the chosen identity. If no
     * address can be found it is built from the current user name and
     * the specified maildomain.
     *
     * @param integer $ident  The identity to retrieve the address from.
     *
     * @return Horde_Mail_Rfc822_Address  A valid from address.
     */
    public function getFromAddress($ident = null)
    {
        if (!isset($this->_cached['from'][$ident])) {
            $val = $this->getValue($this->_prefnames['from_addr'], $ident);
            if (!strlen($val)) {
                $val = $GLOBALS['registry']->getAuth();
            }

            if (!strstr($val, '@')) {
                $val .= '@' . $GLOBALS['session']->get('imp', 'maildomain');
            }

            $ob = new Horde_Mail_Rfc822_Address($val);

            if (is_null($ob->personal)) {
                $ob->personal = $this->getFullname($ident);
            }

            $this->_cached['from'][$ident] = $ob;
        }

        return $this->_cached['from'][$ident];
    }

    /**
     * Returns all aliases based on the chosen identity.
     *
     * @param integer $ident  The identity to retrieve the aliases from.
     *
     * @return Horde_Mail_Rfc822_List  Aliases for the identity.
     */
    public function getAliasAddress($ident)
    {
        if (!isset($this->_cached['aliases'][$ident])) {
            $list = new Horde_Mail_Rfc822_List($this->getValue('alias_addr', $ident));
            $list->add($this->getValue('replyto_addr', $ident));
            $this->_cached['aliases'][$ident] = $list;
        }

        return $this->_cached['aliases'][$ident];
    }

    /**
     * Returns all From addresses for one identity.
     *
     * @param integer $ident  The identity to retrieve the from addresses
     *                        from.
     *
     * @return Horde_Mail_Rfc822_List  Address list.
     */
    public function getFromAddresses($ident = null)
    {
        $list = new Horde_Mail_Rfc822_List($this->getFromAddress($ident));
        $list->add($this->getAliasAddress($ident));

        return $list;
    }

    /**
     * Returns all identities' From addresses.
     *
     * @return Horde_Mail_Rfc822_List  Address list.
     */
    public function getAllFromAddresses()
    {
        $list = new Horde_Mail_Rfc822_List();

        foreach (array_keys($this->_identities) as $key) {
            $list->add($this->getFromAddresses($key));
        }

        return $list;
    }

    /**
     * Get tie-to addresses.
     *
     * @param integer $ident  The identity to retrieve the tie-to addresses
     *                        from.
     *
     * @return array  Tie-to addresses.
     */
    public function getTieAddresses($ident = null)
    {
        return $this->getValue('tieto_addr', $ident);
    }

    /**
     * Get all 'tie to' address/identity pairs.
     *
     * @return Horde_Mail_Rfc822_List  A list of e-mail addresses.
     */
    public function getAllTieAddresses()
    {
        $list = new Horde_Mail_Rfc822_List();

        foreach (array_keys($this->_identities) as $key) {
            $list->add($this->getTieAddresses($key));
        }

        return $list;
    }

    /**
     * Returns a list of all e-mail addresses from all identities, including
     * both from addresses and tie addreses.
     *
     * @return Horde_Mail_Rfc822_List  A list of e-mail addresses.
     */
    public function getAllIdentityAddresses()
    {
        $list = $this->getAllFromAddresses();
        $list->add($this->getAllTieAddresses());

        return $list;
    }

    /**
     * Returns the list of identities with the default identity positioned
     * first.
     *
     * @return array  The identity keys with the default identity first.
     */
    protected function _identitiesWithDefaultFirst()
    {
        $ids = $this->_identities;
        $default = $this->getDefault();
        unset($ids[$default]);
        return array_merge(array($default), array_keys($ids));
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
        $addresses = IMP::parseAddressList($addresses);

        foreach ($this->_identitiesWithDefaultFirst() as $key) {
            $tie_addr = $this->getTieAddresses($key);

            /* Search 'tieto' addresses first. Check for address first
             * and, if not found, check for the domain. */
            foreach ($addresses as $val) {
                if ((array_search($val->bare_address, $tie_addr) !== false) ||
                    (array_search('@' . $val->host, $tie_addr) !== false)) {
                    return $key;
                }
            }

            /* Next, search all from addresses. */
            if ($search_own) {
                $from = $this->getFromAddresses($key);
                foreach ($addresses as $val) {
                    if ($from->contains($val)) {
                        return $key;
                    }
                }
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
