<?php
/**
 * This class provides an IMP-specific interface to all identities a
 * user might have. Its methods take care of any site-specific
 * restrictions configured in prefs.php and conf.php.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
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
     * Reads all the user's identities from the prefs object or builds
     * a new identity from the standard values given in prefs.php.
     *
     * @see __construct()
     */
    public function __construct($params)
    {
        parent::__construct($params);

        $this->_prefnames['properties'] = array_merge(
            $this->_prefnames['properties'],
            array(
                'replyto_addr', 'alias_addr', 'tieto_addr', 'bcc_addr',
                'signature', 'signature_html', 'sig_first', 'sig_dashes',
                'save_sent_mail', 'sent_mail_folder'
            )
        );
    }

    /**
     * Verifies and sanitizes all identity properties.
     *
     * @param integer $identity  The identity to verify.
     *
     * @throws Horde_Exception
     */
    public function verify($identity = null)
    {
        parent::verify($identity);

        if (!isset($identity)) {
            $identity = $this->_default;
        }

        /* Prepare email validator */
        require_once 'Horde/Form.php';
        $email = new Horde_Form_Type_email();
        $vars = new Horde_Variables();
        $var = new Horde_Form_Variable('', 'replyto_addr', $email, false);

        /* Verify Reply-to address. */
        if (!$email->isValid($var, $vars, $this->getValue('replyto_addr', $identity), $error_message)) {
            throw new Horde_Exception($error_message);
        }

        /* Clean up Alias, Tie-to, and BCC addresses. */
        foreach (array('alias_addr', 'tieto_addr', 'bcc_addr') as $val) {
            $data = $this->getValue($val, $identity);
            if (is_array($data)) {
                $data = implode("\n", $data);
            }
            $data = trim($data);
            $data = (empty($data)) ? array() : Horde_Array::prepareAddressList(preg_split("/[\n\r]+/", $data));

            /* Validate addresses */
            foreach ($data as $address) {
                if (!$email->isValid($var, $vars, $address, $error_message)) {
                    throw new Horde_Exception($error_message);
                }
            }

            $this->setValue($val, $data, $identity);
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

        if (empty($address) || $this->_prefs->isLocked($this->_prefnames['from_addr'])) {
            $address = $this->getFromAddress($ident);
            $name = $this->getFullname($ident);
        }

        try {
            $ob = Horde_Mime_Address::parseAddressList($address, array('defserver' => $GLOBALS['session']['imp:maildomain']));
        } catch (Horde_Mime_Exception $e) {
            throw new Horde_Exception (_("Your From address is not a valid email address. This can be fixed in your Personal Information preferences page."));
        }

        if (empty($name)) {
            if (!empty($ob[0]['personal'])) {
                $name = $ob[0]['personal'];
            } else {
                $name = $this->getFullname($ident);
            }
        }

        $from = Horde_Mime_Address::writeAddress($ob[0]['mailbox'], $ob[0]['host'], $name);

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
                $val = $GLOBALS['registry']->getAuth('bare');
            }

            if (!strstr($val, '@')) {
                $val .= '@' . $GLOBALS['session']['imp:maildomain'];
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
                $this->getValue('alias_addr', $ident),
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
     * @return array  The array of objects (IMAP addresses).
     */
    public function getBccAddresses($ident = null)
    {
        $bcc = $this->getValue('bcc_addr', $ident);
        if (empty($bcc)) {
            return array();
        } else {
            if (!is_array($bcc)) {
                $bcc = array($bcc);
            }
            try {
                return Horde_Mime_Address::parseAddressList(implode(', ', $bcc));
            } catch (Horde_Mime_Exception $e) {
                return array();
            }
        }
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

        /* Normalize address list. */
        if (is_array($addresses)) {
            $addresses = array_filter($addresses);
        } else {
            $addresses = array($addresses);
        }

        try {
            $addr_list = Horde_Mime_Address::parseAddressList(implode(', ', $addresses));
        } catch (Horde_Mime_Exception $e) {
            return null;
        }

        foreach ($addr_list as $address) {
            if (empty($address['mailbox'])) {
                continue;
            }

            $find_address = $address['mailbox'];
            if (!empty($address['host'])) {
                $find_address .= '@' . $address['host'];
            }
            $find_address = Horde_String::lower($find_address);

            /* Search 'tieto' addresses first. */
            /* Check for this address explicitly. */
            if (isset($this->_cached['tie_addresses'][$find_address])) {
                return $this->_cached['tie_addresses'][$find_address];
            }

            /* If we didn't find the address, check for the domain. */
            if (!empty($address['host']) &&
                isset($this->_cached['tie_addresses']['@' . $address['host']])) {
                return $this->_cached['tie_addresses']['@' . $address['host']];
            }

            /* Next, search all from addresses. */
            if ($search_own &&
                isset($this->_cached['own_addresses'][$find_address])) {
                return $this->_cached['own_addresses'][$find_address];
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

            if (!empty($val) && ($type == 'text')) {
                $sig_first = $this->getValue('sig_first', $ident);
                $sig_dashes = $this->getValue('sig_dashes', $ident);

                $val = str_replace("\r\n", "\n", $val);

                if ($sig_dashes) {
                    $val = "-- \n" . $val . "\n";
                } else {
                    $val = "\n" . $val;
                }

                if ($sig_first) {
                    $val .= "\n\n\n";
                }
            }
        }

        if ($val && ($type == 'html')) {
            if ($convert) {
                $val = IMP_Compose::text2html(trim($val));
            }

            $val = '<div class="impComposeSignature">' . $val . '</div>';
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
        return (($key == 'sent_mail_folder') && strlen($val))
            ? IMP::folderPref($val, true)
            : $val;
    }

    /**
     * Sets a property with a specified value.
     *
     * @see setValue()
     */
    public function setValue($key, $val, $identity = null)
    {
        if ($key == 'sent_mail_folder') {
            $val = IMP::folderPref($val, false);
        }
        return parent::setValue($key, $val, $identity);
    }

    /**
     * Returns an array with the sent-mail folder names from all the
     * identities.
     *
     * @return array  The array with the folder names.
     */
    public function getAllSentmailFolders()
    {
        $list = array();
        foreach ($this->_identities as $key => $identity) {
            if ($folder = $this->getValue('sent_mail_folder', $key)) {
                $list[$folder] = 1;
            }
        }

        /* Get rid of duplicates and empty folders. */
        return array_filter(array_keys($list));
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
        if (!$GLOBALS['conf']['user']['allow_folders']) {
            return false;
        }

        return $this->getValue('save_sent_mail', $ident);
    }

}
