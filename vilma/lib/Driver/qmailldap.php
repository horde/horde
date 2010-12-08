<?php
/**
 * Copyright 2006-2007 Alkaloid Networks <http://www.alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you did
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author Ben Klang <bklang@alkaloid.net>
 * @author David Cummings <davidcummings@acm.org>
 * @package Vilma
 */
class Vilma_Driver_qmailldap extends Vilma_Driver {

    /**
     * @var _LDAP Reference to initialized LDAP driver
     */
    var $_ldap;

    /**
     * @var _dbparams Configuration parameters for the LDAP driver
     */
    var $_ldapparams;

    /**
     * @var _db Reference to the initialized database driver
     */
    var $_db;

    /**
     * @var _dbparams Configuration parameters for the database driver
     */
    var $_dbparams;

    function Vilma_Driver_qmailldap($params)
    {
        parent::Vilma_Driver($params);
        $this->_ldapparams = $this->_params['ldap'];
        $this->_sqlparams = Horde::getDriverConfig('storage', 'sql');
        $res = $this->_connect();
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        /* Connect to the backend for tracking domains. */
        $this->_dbinit();
    }

    /**
     * Gets the list of domains from the backend.
     *
     * @return array  All the domains and their data in an array.
     */
    function getDomains()
    {
        $sql = 'SELECT domain_id, domain_name, domain_transport, ' .
               'domain_max_users, domain_quota FROM vilma_domains ' .
               'ORDER BY domain_name';

        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->getAll($sql, $values, DB_FETCHMODE_ASSOC);
    }

    /**
     * Gets the specified domain information from the backend.
     *
     * @param integer $domain_id  The id of the domain to fetch.
     *
     * @return array  The domain's information in an array.
     */
    function getDomain($domain_id)
    {
        $sql = 'SELECT domain_id, domain_name, domain_transport, ' .
               'domain_max_users, domain_quota FROM vilma_domains ' .
               'WHERE domain_id=? ORDER BY domain_name';
        $values = array($domain_id);

        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->getRow($sql, $values, DB_FETCHMODE_ASSOC);
    }

    /**
     * Given a domain name returns the information from the backend.
     *
     * @param string $name  The name of the domain to fetch.
     *
     * @return array  The domain's information in an array.
     */
    function getDomainByName($domain_name)
    {
        $sql = 'SELECT domain_id, domain_name, domain_transport, ' .
               'domain_max_users, domain_quota FROM vilma_domains ' .
               'WHERE domain_name=?';
        $values = array($domain_name);

        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->getRow($sql, $values, DB_FETCHMODE_ASSOC);
    }

    /**
     * Returns a list of all users, aliases, or groups and forwards for a
     * domain.
     *
     * @param string $domain      Domain on which to search.
     * @param string $type        Only return a specific type. One of 'all',
     *                            'user', 'alias','forward', or 'group'.
     * @param string $key         Sort list by this key.
     * @param integer $direction  Sort direction.
     *
     * @return array Account information for this domain
     */
    protected function _getAddresses($domain, $type = 'all')
    {
        Horde::logMessage("Get Addresses Called for $domain with type $type and key $key", 'DEBUG');
        $addresses = array();
        if ($type == 'all' || $type == 'user') {
            $addresses += $this->_getUsers($domain);
        }
        if ($type == 'all' || $type == 'alias') {
            $addresses += $this->_getAliases($domain);
        }
        if ($type == 'all' || $type == 'forward') {
            $addresses += $this->_getGroupsAndForwards('forward', $domain);
        }
        if ($type == 'all' || $type == 'group') {
            $addresses += $this->_getGroupsAndForwards('group', $domain));
        }
        return $addresses;
    }

    function getUser($user_id) {
        $user = $this->getUserStatus($user_id);

        if (is_a($user, 'PEAR_Error')) {
            return PEAR::raiseError(sprintf(_("Unable to qualify address: %s"), $user->getMessage()));
        }
        if(isset($user) && is_array($user)) {
            return $user;
        } else {
            return PEAR::raiseError(_("Unable to qualify address."));
        }
    }

    /**
     * Returns an array of all users, aliases, groups and forwards for this
     * domain.
     *
     * @param string $domain Domain on which to search
     * @param optional string $type Only return a specific type
     *
     * @return array Account information for this domain
     */
    function _getUsers($domain = null)
    {
        //$domain = $domain['domain_name'];
        // Cache for multiple calls
        static $users = array();
        if (is_null($domain) && isset($users['_all'])) {
            return $users['_all'];
        }

        if (!is_null($domain)
               && isset($users[$domain])) {
            return $users[$domain];
        }

        $filter = '(&';
        if (!is_null($domain)) {
            $filter .= '(mail=*@' . $domain . ')';
        } else {
            $domain = '_all';
        }

        // Make sure we don't get any forwards
        $filter .= '(!(mailForwardingAddress=*))';

        // FIXME: Check/add configured filter instead of objectclasses
        foreach ($this->_ldapparams['objectclass'] as $objectclass) {
            $filter .= '(objectClass=' . $objectclass . ')';
        }
        $filter .= ')';

        Horde::logMessage($filter, 'DEBUG');
        $res = ldap_search($this->_ldap, $this->_ldapparams['basedn'], $filter);
        if ($res === false) {
            return PEAR::raiseError(sprintf(_("Error in LDAP search: %s"), ldap_error($this->LDAP)));
        }

        $res = ldap_get_entries($this->_ldap, $res);
        if ($res === false) {
            return PEAR::raiseError(sprintf(_("Error in LDAP search: %s"), ldap_error($this->LDAP)));
        }

        $users[$domain] = array();
        $i = 0;
        // Can't use foreach because of the array format returned by LDAP driver
        while ($user = @$res[$i]) {
            $users[$domain][$i]['id'] = $user['dn'];
            $users[$domain][$i]['address'] = $user[$this->_getAttrByField('address')][0];
            $users[$domain][$i]['type'] = 'user';
            $users[$domain][$i]['user_name'] =
                $user[$this->_getAttrByField('user_name')][0];
            // We likely don't have read permission on the crypted password so
            // avoid any warnings/errors about missing array elements
            if (isset($user[$this->_getAttrByField('user_crypt')])) {
                $users[$domain][$i]['user_crypt'] =
                    $user[$this->_getAttrByField('user_crypt')][0];
            } else {
                $users[$domain][$i]['user_crypt'] = '';
            }
            $users[$domain][$i]['user_full_name'] =
                $user[$this->_getAttrByField('user_full_name')][0];
            // Mute assignment errors on the following optional fields
            // These may not be present if the mail is only forwarded
            $users[$domain][$i]['user_uid'] =
                @$user[$this->_getAttrByField('user_uid')][0];
            $users[$domain][$i]['user_gid'] =
                @$user[$this->_getAttrByField('user_gid')][0];
            $users[$domain][$i]['user_home_dir'] =
                @$user[$this->_getAttrByField('user_home_dir')][0];
            $users[$domain][$i]['user_mail_dir'] =
                @$user[$this->_getAttrByField('user_mail_dir')][0];
            $users[$domain][$i]['user_mail_quota_bytes'] =
                @$user[$this->_getAttrByField('user_mail_quota_bytes')][0];
            $users[$domain][$i]['user_mail_quota_count'] =
                @$user[$this->_getAttrByField('user_mail_quota_count')][0];

            // If accountStatus is blank it's the same as active
            if (!isset($user[$this->_getAttrByField('user_enabled')][0]) ||
                ($user[$this->_getAttrByField('user_enabled')][0] == 'active')) {
                $users[$domain][$i]['user_enabled'] = 'active';
            } else {
                // accountStatus can also be:
                // noaccess (receives but cannot pick up mail)
                // disabled (bounce incoming and deny pickup)
                // deleted (bounce incoming but allow pickup)
                $users[$domain][$i]['user_enabled'] =
                    $user[$this->_getAttrByField('user_enabled')][0];
            }

            $i++;
        }

        return $users[$domain];
    }

    function _getFields()
    {
        // LDAP attributes are always returned lower case!
        static $fields = array(
            'address' => 'mail',
            'user_name' => 'uid',
            'user_crypt' => 'userpassword',
            'user_full_name' => 'cn',
            'user_uid' => 'qmailuid',
            'user_gid' => 'qmailgid',
            'user_home_dir' => 'homedirectory',
            'user_mail_dir' => 'mailmessagestore',
            'user_mail_quota_bytes' => 'mailquotasize',
            'user_mail_quota_count' => 'mailquotacount',
            'user_enabled' => 'accountstatus',
        );

        return $fields;

    }

    function _getAttrByField($field) {
        $fields = $this->_getFields();
        return $fields[$field];
    }

    function _getFieldByAttr($attr) {
        $attrs = array_flip($this->_getFields());
        return $attrs[$attr];
    }

    /**
     * Returns available email address aliases.  This method should not be
     * called directly but rather by way of getAddresses().
     *
     * @access private
     *
     * @param string $target  If passed a domain then return all alias emails
     *                        for the domain, otherwise if passed a user name
     *                        return all virtual emails for that user.
     *
     * @return array  The used email aliases
     */
    function _getAliases($target = null)
    {
        // FIXME: Add static cache

        $filter  = '(&'; // Begin filter (cumulative AND)
        foreach ($this->_ldapparams['objectclass'] as $objectclass) {
            // Add each objectClass from parameters
            $filter .= '(objectClass=' . $objectclass . ')';
        }
        // FIXME: Add preconfigured filter from $this->_ldapparams

        // Check if filtering only for domain.
        if (($pos = strpos($target, '@')) === false && !empty($target)) {
            $filter .= '(mailAlternateAddress=*@' . $target . ')';
        // Otherwise filter for all aliases
        } else {
            $filter .= '(mailAlternateAddress=*)';
            // Restrict the results to $target
            if (!empty($target)) {
                $filter .= '(mail=' . $target . ')'; // Add user's email
            }
        }
        $filter .= ')'; // End filter

        Horde::logMessage($filter, 'DEBUG');
        $res = @ldap_search($this->_ldap, $this->_ldapparams['basedn'], $filter);
        if ($res === false) {
            return PEAR::raiseError(sprintf(_("Error searching LDAP: %s"),
                @ldap_error($this->_ldap)));
        }

        $res = @ldap_get_entries($this->_ldap, $res);
        if ($res === false) {
            return PEAR::raiseError(sprintf(_("Error returning LDAP results: %s"), @ldap_error($this->_ldap)));
        }

        $aliases = array();
        // Can't use foreach because of the array format returned by LDAP driver
        $i = 0; // Virtual address index
        $e = 0; // Entry counter
        while ($entry = @$res[$e]) {
            // If accountStatus is blank it's the same as active
            if (!isset($entry[$this->_getAttrByField('user_enabled')][0]) ||
                ($entry[$this->_getAttrByField('user_enabled')][0] == 'active')) {
                $curstatus = 'active';
            } else {
                // accountStatus can also be:
                // noaccess (receives but cannot pick up mail)
                // disabled (bounce incoming and deny pickup)
                // deleted (bounce incoming but allow pickup)
                $curstatus = $entry[$this->_getAttrByField('user_enabled')][0];
            }
            $a = 0; // Attribute counter
            while ($mail = @$entry['mailalternateaddress'][$a]) {
                $aliases[$i]['id'] = $mail;
                $aliases[$i]['type'] = 'alias';
                $aliases[$i]['user_name'] = $mail;
                $aliases[$i]['user_full_name'] = sprintf(_("Alias for %s"), $entry['mail'][0]);
                $aliases[$i]['destination'] = $entry['mail'][0];
                $aliases[$i]['user_enabled'] = $curstatus;
                $a++;
                $i++;
            }
            $e++;
        }

        return $aliases;
    }

    /**
     * Returns all available groups and forwards unless otherwise specified.
     * If a domain name is passed then limit the results to groups or forwards
     * in that domain.  This method should not be called directly, but rather by
     * way of getAddresses()
     *
     * @access private
     *
     * @param string $acquire The default behavior is to acquire both
     *                        groups and forwards; a value of 'group'
     *                        will return only groups and a value of
     *                        'forward' will return only forwards.
     * @param string $domain  The name of the domain from which to fetch
     *
     * @return array  The available groups and forwards with details
     */
    function _getGroupsAndForwards($acquire = null, $domain = null)
    {
        // Cache
        static $grpfwds;
        // TODO ?
        /*
        if (is_null($domain) && isset($grpfwds['_all'])) {
            return $grpfwds['_all'];
        }
        if (!is_null($domain) && isset($grpfwds[$domain])) {
            return $grpfwds[$domain];
        }
        */
        $filter  = '(&'; // Begin filter (cumulative AND)
        foreach ($this->_ldapparams['objectclass'] as $objectclass) {
            // Add each objectClass from parameters
            $filter .= '(objectClass=' . $objectclass . ')';
        }
        // FIXME: Add preconfigured filter from $this->_ldapparams

        // Only return results which have a forward configured
        $filter .= '(mailForwardingAddress=*)';

        if (!empty($domain)) {
            $filter .= '(|'; // mail or mailAlternateAddress
            $filter .= '(mail=*@' . $domain . ')';
            $filter .= '(mailAlternateAddress=*@' . $domain . ')';
            $filter .= ')'; // end mail or mailAlternateAddress
        } else {
            $domain = '_all';
        }
        $filter .= ')'; // End filter
        Horde::logMessage($filter, 'DEBUG');
        $res = @ldap_search($this->_ldap, $this->_ldapparams['basedn'], $filter);
        if ($res === false) {
            return PEAR::raiseError(sprintf(_("Error searching LDAP: %s"),
                @ldap_error($this->_ldap)));
        }

        $res = @ldap_get_entries($this->_ldap, $res);
        if ($res === false) {
            return PEAR::raiseError(sprintf(_("Error returning LDAP results: %s"), @ldap_error($this->_ldap)));
        }

        $grpfwds[$domain] = array();
        // Can't use foreach because of the array format returned by LDAP driver
        $i = 0; // Address index
        $e = 0; // Entry counter

        while ($entry = @$res[$e]) {
            $targets = array();
            $a = 0; // Attribute counter
            while ($attr = @$res[$e]['mailforwardingaddress'][$a]) {
                $targets[] = $attr;
                $a++;
            }
            $type = $entry['mailforwardingaddress']["count"];
            if($type > 1) {
                $type = 'group';
            } else {
                $type = 'forward';
            }
            if(($acquire == 'all') || ($type == $acquire)) {
                $grpfwds[$domain][$i] = array(
                    'id' => $entry['dn'],
                    'type' => $type,
                    'address' => $entry[$this->_getAttrByField('address')][0],
                    'targets' => $targets,
                    'user_name' => $entry[$this->_getAttrByField('user_name')][0],
                    'user_full_name' => @$entry[$this->_getAttrByField('user_name')][0],
                );
                // If accountStatus is blank it's the same as active
                if (!isset($entry[$this->_getAttrByField('user_enabled')][0]) ||
                    ($entry[$this->_getAttrByField('user_enabled')][0] == 'active')) {
                    $grpfwds[$domain][$i]['user_enabled'] = 'active';
                } else {
                    // accountStatus can also be:
                    // noaccess (receives but cannot pick up mail)
                    // disabled (bounce incoming and deny pickup)
                    // deleted (bounce incoming but allow pickup)
                    $grpfwds[$domain][$i]['user_enabled'] =
                        $entry[$this->_getAttrByField('user_enabled')][0];
                }
            } else {
                $e++;
                continue;
            }
            $e++;
            $i++;
        }
        return $grpfwds[$domain];
    }

    /**
     * Returns information for an email alias
     *
     * @param string $id  The email alias id for which to return information.
     *
     * @return array  The virtual email information.
     */
    function getAddressInfo($address, $type = 'all')
    {
        Horde::logMessage("Get Addresses Called for $address with type $type and key $key", 'DEBUG');
        if ($type != 'alias') {
            return parent::getAddressInfo($address, $type);
        } else {
            // FIXME: Which is faster?  A linear array search or an LDAP search?
            // I think LDAP in this case because we can't assume the domain.
            $filter = '(&'; // Begin filter (cumulative AND)
            foreach ($this->_ldapparams['objectclass'] as $objectclass) {
                // Add each objectClass from parameters
                $filter .= '(objectClass=' . $objectclass . ')';
            }
            $filter .= '(mailAlternateAddress=' . $address . ')';
            $filter .= ')'; // End filter
            Horde::logMessage($filter, 'DEBUG');
            $res = @ldap_search($this->_ldap, $this->_ldapparams['basedn'], $filter);
            if ($res === false) {
                return PEAR::raiseError(sprintf(_("Error searching LDAP: %s"),
                    @ldap_error($this->_ldap)));
            }
            $res = @ldap_get_entries($this->_ldap, $res);
            if ($res === false) {
                return PEAR::raiseError(sprintf(_("Error returning LDAP results: %s"), @ldap_error($this->_ldap)));
            }

            if ($res['count'] !== 1) {
                return PEAR::raiseError(_("More than one DN returned for this alias.  Please contact an administrator to resolve this error."));
            }

            return array(
                'id' => $res[0]['dn'],
                'address' => $address,
                'destination' => $res[0]['mail'][0],
            );
        }
    }

    /**
     * Returns the current number of set up users for a domain.
     *
     * @param string $domain_name  The name of the domain for which to
     *                             get the current number of users.
     *
     * @return integer  The current number of users.
     */
    function getDomainNumUsers($domain_name)
    {
        return count($this->_getUsers($domain_name));
    }

    /**
     * Saves a domain to the backend.
     *
     * @param array $info  The domain information to save to the backend.
     *
     * @return mixed  True on success or PEAR error otherwise.
     */
    function _saveDomain(&$info)
    {
        // We store the records within Horde's configured SQL database for
        // Vilma because LDAP has no mechanism for tracking domains
        // that are valid for this system.
        $values = array($info['name'], $info['transport'],
                        (int)$info['max_users'], (int)$info['quota']);

        if (empty($info['domain_id'])) {
            $nextid = $this->_db->nextId('vilma_domains');
            $sql = 'INSERT INTO vilma_domains (domain_id, domain_name, ' .
                   'domain_transport, domain_max_users, domain_quota) VALUES ' .
                   '(?, ?, ?, ?, ?)';
            array_unshift($values, $nextid);
        } else {
            $sql = 'UPDATE vilma_domains SET domain_name=?, ' .
                   'domain_transport=?, domain_max_users=?, domain_quota=? ' .
                   'WHERE domain_id=?';
            array_push($values, $info['domain_id']);
        }
        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->query($sql, $values);
    }

    /**
     * Deletes a given domain.
     *
     * @param string $domain_id  The id of the domain to delete.
     *
     * @return mixed  True on success or PEAR error otherwise.
     */
    function _deleteDomain($domain_id)
    {
        $domain_record = $this->getDomain($domain_id);
        if (is_a($domain_record, 'PEAR_Error')) {
            return $domain_record;
        }

        $domain_name = $domain_record['domain_name'];

        // FIXME: Add logic to remove all users, aliases, and grpfwds for this
        // domain

        /* Finally delete the domain. */
        $sql = 'DELETE FROM vilma_domains WHERE domain_id=?';
        $values = array((int)$domain_id);

        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->query($sql, $values);
    }

    /**
     * Searchs for a given email account.
     *
     * @param string $email_id The id of the account to be searched for.
     *
     * @return Array of data for given email account on success or no
     *     information found; and for an error a PEAR::Error otherwise.
     */
    function searchForAliases($email_id) {
        // Get the user's DN
        $filter  = '(&'; // Begin filter (cumulative AND)
        foreach ($this->_ldapparams['objectclass'] as $objectclass) {
            // Add each objectClass from parameters
            $filter .= '(objectClass=' . $objectclass . ')';
        }
        /*
        // Check if filtering only for domain.
        if (($pos = strpos($target, '@')) === false && !empty($email_id)) {
            $filter .= '(mailAlternateAddress=*@' . $email_id . ')';
        // Otherwise filter for all aliases
        } else {
            $filter .= '(mailAlternateAddress=*)';
            // Restrict the results to $target
            if (!empty($email_id)) {
                $filter .= '(mail=' . $email_id . ')'; // Add user's email
            }
        }
        */
        $filter .= '(mailAlternateAddress=' . $email_id . ')';
        $filter .= ')'; // End filter

        //echo $filter;
        Horde::logMessage($filter, 'DEBUG');
        $res = @ldap_search($this->_ldap, $this->_ldapparams['basedn'], $filter);
        if ($res === false) {
            return PEAR::raiseError(sprintf(_("Error searching LDAP: %s"),
                @ldap_error($this->_ldap)));
        }
        $res = @ldap_get_entries($this->_ldap, $res);
        if ($res === false) {
            return PEAR::raiseError(sprintf(_("Error retrieving LDAP results: %s"), @ldap_error($this->_ldap)));
        }

        return $res;
    }

    /**
     * Searchs for a given email account.
     *
     * @param string $email_id The id of the account to be searched for.
     *
     * @return Array of data for given email account on success or no
     *     information found; and for an error a PEAR::Error otherwise.
     */
    function searchForUser($email_id)
    {
        // Get the user's DN
        $filter  = '(&';
        foreach ($this->_ldapparams['objectclass'] as $objectclass) {
            // Add each objectClass from parameters
            $filter .= '(objectclass=' . $objectclass . ')';
        }
        $filter .= '(mail=' . $email_id . '))';

        Horde::logMessage($filter, 'DEBUG');
        $res = @ldap_search($this->_ldap, $this->_ldapparams['basedn'], $filter);
        if ($res === false) {
            return PEAR::raiseError(sprintf(_("Error searching LDAP: %s"),
                @ldap_error($this->_ldap)));
        }
        $res = @ldap_get_entries($this->_ldap, $res);
        if ($res === false) {
            return PEAR::raiseError(sprintf(_("Error retrieving LDAP results: %s"), @ldap_error($this->_ldap)));
        }

        if ($res['count'] === 0) {
            return PEAR::raiseError(_("Unable to acquire handle on DN.  Aborting delete operation."));
        } else if($res['count'] !== 1) {
            return PEAR::raiseError(_("More than one DN returned.  Aborting delete operation."));
        }
        return $res;
    }

    /**
     * Deletes a given email account.
     *
     * @param string $email_id The id of the account to delete (not an alias)
     *
     * @return mixed True on success or PEAR::Error otherwise.
     */
    function deleteUser($email_id)
    {
        // Get the user's DN
        $filter  = '(&';
        foreach ($this->_ldapparams['objectclass'] as $objectclass) {
            // Add each objectClass from parameters
            $filter .= '(objectclass=' . $objectclass . ')';
        }
        $filter .= '(mail=' . $email_id . ')';
        //echo $email_id . '<br>';
        $filter .= ')';
        Horde::logMessage($filter, 'DEBUG');
        $res = @ldap_search($this->_ldap, $this->_ldapparams['basedn'], $filter);
        if ($res === false) {
            return PEAR::raiseError(sprintf(_("Error searching LDAP: %s"),
                @ldap_error($this->_ldap)));
        }
        $res = @ldap_get_entries($this->_ldap, $res);
        if ($res === false) {
            return PEAR::raiseError(sprintf(_("Error retrieving LDAP results: %s"), @ldap_error($this->_ldap)));
        }

        if ($res['count'] === 0) {
            return PEAR::raiseError(_("Unable to acquire handle on DN.  Aborting delete operation."));
        } else if($res['count'] !== 1) {
            return PEAR::raiseError(_("More than one DN returned.  Aborting delete operation."));
        }
        // We now have one unique DN to delete.
        $res = @ldap_delete($this->_ldap, $res[0]['dn']);
        if ($res === false) {
            return PEAR::raiseError(sprintf(_("Error deleting account from LDAP: %s"), @ldap_error($this->_ldap)));
        }

        return true;
    }

    /**
     * Modifies alias data on the backend.  See Driver::saveAlias() for parameter info.
     *
     * @param mixed $info     The alias, or an array containing the alias and supporting data.
     *
     * @return mixed  True on success or PEAR error otherwise.
     */
    function _saveAlias($info)
    {
        Horde::logMessage("_saveAlias called with info: " . print_r($info, true), 'DEBUG');
        $address = $info['address'];
        if (!empty($info['alias'])) {
          $alias = $info['alias'];
          $create = false;
        } else {
          $create = true;
        } // if
        $alias_address = $info['alias_address'];

        $user_res = $this->searchForUser($address);
        if (is_a($user_res, 'PEAR_Error') || ($res['count'] === 0) ) {
          return PEAR::raiseError(_("Error reading address information from backend."));
        } // if
        $user = $user_res[0];

        // Retrieve the current MAA values
        if (array_key_exists('mailalternateaddress', $user_res[0])) {
          $maa = $user['mailalternateaddress'];
          unset($maa['count']);
        } else {
          $maa = array();
        } // if

        Horde::logMessage("Resource contains: " . print_r($maa, true), 'DEBUG');

        $update = false;
        $oldmaa = $maa;
        if ($create) {
          // Verify that it does not already exist
          if (in_array($alias_address, $maa) === false) {
            // Not there, we create it
            // return PEAR::raiseError(_("We would create a new entry here."));
            $maa[] = $alias_address;
            // usort($maa, "compareEmailSort");
            sort($maa);
            $update = true;
          } else {
            // Already exists, throw a notification
            return PEAR::raiseError(_("That alias already exists!"));
          } // if

        } else {
          if ($alias == $alias_address) {
            /* do nothing */;
          } else {
            $key = array_search($alias, $maa);
            if ($key > 0 || $key === 0) {
              $maa[$key] = $alias_address;
              // usort($maa, "compareEmailSort");
              sort($maa);
              $update = true;
            } else {
              return PEAR::raiseError(sprintf(_("Existing entry \"%s\" could not be found: " . print_r($key, true)), $alias));
            } // if
          }
        } // if


        if ($update) {
          $dn = $user['dn'];
          Horde::logMessage("UPDATING: $dn \nOld MAA: " . print_r($oldmaa, true) . "\nNew MAA: " . print_r($maa, true), 'DEBUG');
          // return PEAR::raiseError(sprintf(_("Update Code Not Written."), $alias));
          if ($this->_ldap) {
            // bind with appropriate dn to give update access
            $res = ldap_bind($this->_ldap, $this->_ldapparams['binddn'],
                             $this->_ldapparams['bindpw']);
            if (!$res) {
                return PEAR::raiseError(_("Unable to bind to the LDAP server. Check authentication credentials."));
            }
            $entry["mailAlternateAddress"] = $maa;

            $res = @ldap_modify($this->_ldap, $dn, $entry);
            if ($res === false) {
                return PEAR::raiseError(sprintf(_("Error modifying account: %s"), @ldap_error($this->_ldap)));
            } else {
                return TRUE;
            } // if
          } // if
        } // if

        return true;
    }

    function _deleteAlias($info)
    {
        Horde::logMessage("_deleteAlias called with info: " . print_r($info, true), 'DEBUG');
        $address = $info['address'];
        $alias = $info['alias'];

        $user_res = $this->searchForUser($address);
        if (is_a($user_res, 'PEAR_Error') || ($res['count'] === 0) ) {
          return PEAR::raiseError(_("Error reading address information from backend."));
        } // if
        $user = $user_res[0];

        // Retrieve the current MAA values
        if (array_key_exists('mailalternateaddress', $user_res[0])) {
          $maa = $user['mailalternateaddress'];
          unset($maa['count']);
        } else {
          $maa = array();
        } // if

        Horde::logMessage("Resource contains: " . print_r($maa, true), 'DEBUG');

        $update = false;
        $oldmaa = $maa;
        $key = array_search($alias, $maa);
        if ($key > 0 || $key === 0) {
          unset($maa[$key]);
          sort($maa);
          $update = true;
        } else {
          /* skip */;
        } // if

        if ($update) {
          $dn = $user['dn'];
          Horde::logMessage("UPDATING: $dn \nOld MAA: " . print_r($oldmaa, true) . "\nNew MAA: " . print_r($maa, true), 'DEBUG');
          // return PEAR::raiseError(sprintf(_("Update Code Not Written."), $alias));
          if ($this->_ldap) {
            // bind with appropriate dn to give update access
            $res = ldap_bind($this->_ldap, $this->_ldapparams['binddn'],
                             $this->_ldapparams['bindpw']);
            if (!$res) {
                return PEAR::raiseError(_("Unable to bind to the LDAP server. Check authentication credentials."));
            }
            $entry["mailAlternateAddress"] = $maa;

            $res = @ldap_modify($this->_ldap, $dn, $entry);
            if ($res === false) {
                return PEAR::raiseError(sprintf(_("Error modifying account: %s"), @ldap_error($this->_ldap)));
            } else {
                return TRUE;
            } // if
          } // if
        } // if

        return true;
    }

    /**
     * Modifies forward data on the backend.  See Driver::saveForward() for parameter info.
     *
     * @param mixed $info     An array containing the alias and supporting data.
     *
     * @return mixed  True on success or PEAR error otherwise.
     */
    function _saveForward($info)
    {
      Horde::logMessage("_saveForward called with info: " . print_r($info, true), 'DEBUG');
        $address = $info['address'];
        if (!empty($info['forward'])) {
          $forward = $info['forward'];
          $create = false;
        } else {
          $create = true;
        } // if
        $forward_address = $info['forward_address'];

        $user_res = $this->searchForUser($address);
        if (is_a($user_res, 'PEAR_Error') || ($res['count'] === 0) ) {
          return PEAR::raiseError(_("Error reading address information from backend."));
        } // if
        $user = $user_res[0];

        // Retrieve the current MAA values
        if (array_key_exists('mailforwardingaddress', $user_res[0])) {
          $mfa = $user['mailforwardingaddress'];
          unset($mfa['count']);
        } else {
          $mfa = array();
        } // if

        Horde::logMessage("Resource contains: " . print_r($mfa, true), 'DEBUG');

        $update = false;
        $oldmfa = $mfa;
        if ($create) {
          // Verify that it does not already exist
          if (in_array($forward_address, $mfa) === false) {
            // Not there, we create it
            // return PEAR::raiseError(_("We would create a new entry here."));
            $mfa[] = $forward_address;
            // usort($mfa, "compareEmailSort");
            sort($mfa);
            $update = true;
          } else {
            // Already exists, throw a notification
            return PEAR::raiseError(sprintf(_("That forward, \"%s\", already exists!"), $forward_address));
          } // if

        } else {
          if ($forward == $forward_address) {
            /* do nothing */;
          } else {
            $key = array_search($forward, $mfa);
            if ($key > 0 || $key === 0) {
              $mfa[$key] = $forward_address;
              // usort($mfa, "compareEmailSort");
              sort($mfa);
              $update = true;
            } else {
              return PEAR::raiseError(sprintf(_("Existing entry \"%s\" could not be found: " . print_r($key, true)), $forward));
            } // if
          }
        } // if


        if ($update) {
          $dn = $user['dn'];
          Horde::logMessage("UPDATING: $dn \nOld MFA: " . print_r($oldmfa, true) . "\nNew MFA: " . print_r($mfa, true), 'DEBUG');
          // return PEAR::raiseError(sprintf(_("Update Code Not Written."), $alias));
          if ($this->_ldap) {
            // bind with appropriate dn to give update access
            $res = ldap_bind($this->_ldap, $this->_ldapparams['binddn'],
                             $this->_ldapparams['bindpw']);
            if (!$res) {
                return PEAR::raiseError(_("Unable to bind to the LDAP server. Check authentication credentials."));
            }
            $entry["mailForwardingAddress"] = $mfa;

            $res = @ldap_modify($this->_ldap, $dn, $entry);
            if ($res === false) {
                return PEAR::raiseError(sprintf(_("Error modifying account: %s"), @ldap_error($this->_ldap)));
            } else {
                return TRUE;
            } // if
          } // if
        } // if

        return true;
    }

    /**
     * Deletes forward data on the backend.  See Driver::deleteForward() for parameter info.
     *
     * @param mixed $info     An array containing the forward and supporting data.
     *
     * @return mixed  True on success or PEAR error otherwise.
     */
    function _deleteForward($info)
    {
      Horde::logMessage("_deleteForward called with info: " . print_r($info, true), 'DEBUG');
        $address = $info['address'];
        $forward = $info['forward'];

        $user_res = $this->searchForUser($address);
        if (is_a($user_res, 'PEAR_Error') || ($res['count'] === 0) ) {
          return PEAR::raiseError(_("Error reading address information from backend."));
        } // if
        $user = $user_res[0];

        // Retrieve the current MFA values
        if (array_key_exists('mailforwardingaddress', $user_res[0])) {
          $mfa = $user['mailforwardingaddress'];
          unset($mfa['count']);
        } else {
          $mfa = array();
        } // if

        Horde::logMessage("Resource contains: " . print_r($mfa, true), 'DEBUG');

        $update = false;
        $oldmfa = $mfa;
        $key = array_search($forward, $mfa);
        if ($key > 0 || $key === 0) {
          unset($mfa[$key]);
          sort($mfa);
          $update = true;
        } else {
          /* skip */;
        } // if

        if ($update) {
          $dn = $user['dn'];
          Horde::logMessage("UPDATING: $dn \nOld MFA: " . print_r($oldmfa, true) . "\nNew MFA: " . print_r($mfa, true), 'DEBUG');
          // return PEAR::raiseError(sprintf(_("Update Code Not Written."), $alias));
          if ($this->_ldap) {
            // bind with appropriate dn to give update access
            $res = ldap_bind($this->_ldap, $this->_ldapparams['binddn'],
                             $this->_ldapparams['bindpw']);
            if (!$res) {
                return PEAR::raiseError(_("Unable to bind to the LDAP server. Check authentication credentials."));
            }
            $entry["mailForwardingAddress"] = $mfa;

            $res = @ldap_modify($this->_ldap, $dn, $entry);
            if ($res === false) {
                return PEAR::raiseError(sprintf(_("Error modifying account: %s"), @ldap_error($this->_ldap)));
            } else {
                return TRUE;
            } // if
          } // if
        } // if

        return true;
    }

    /* Sorting function to sort aliases, forwards, and accounts by domain name first,
     * then by user component.
     */
    function compareEmailSort($a, $b) {
      $a_comp = split("@", $a);
      $b_comp = split("@", $b);
      // not finished.
    }

    function _saveUser(&$info)
    {
        if ($info['mode'] == 'edit') {
            $address = $info['address'];
            if(!isset($address) || empty($address)) {
                $user_name = $info['user_name'];
                $domain = $info['domain'];
                if(!(!isset($user_name) || empty($user_name)) && !(!isset($user_name) || empty($user_name))) {
                    $address = $info['user_name'] . $info['domain'];
                } else {
                    return PEAR::raiseError(_("Unable to acquire handle on address."));
                }
            }
            $addrinfo = $this->getAddressInfo($address);
            if (is_a($addrinfo, 'PEAR_Error')) {
                return $addrinfo;
            }
            $type = $addrinfo['type'];
            if($type == 'user') {
                 //continue, this is a user.
            } else {
                 //return PEAR::raiseError(_("Unable to save account of type " . $type));
            }

            $user_info = $this->searchForUser($address);
            if (is_a($user_info, 'PEAR_Error') || ($res['count'] === 0) ) {
                return PEAR::raiseError(_("Error reading address information from backend."));
            }

            $objectClassData = null;
            if(isset($user_info[0]['objectclass'])) {
                $objectClassData = $user_info[0]['objectclass'];
            }

            unset($info['mode']); // Don't want to save this to LDAP
            // Special case for the password:  If it was provided, it needs
            // to be crypted.  Otherwise, ignore it.
            if (isset($info['password'])) {
                if (!empty($user['password'])) {
                    // FIXME: Allow choice of hash
                    $info['user_password'] = Horde_Auth::getCryptedPassowrd($info['password'], '', 'ssha', true);
                }
                unset($info['password']);
            }

            $tmp['dn'] = $addrinfo['id'];
            foreach ($info as $key => $val) {
                $attr = $this->_getAttrByField($key);
                $tmp[$attr] = $val;
            }

            if ($this->_ldap) {
                // bind with appropriate dn to give update access
                $res = ldap_bind($this->_ldap, $this->_ldapparams['binddn'],
                                 $this->_ldapparams['bindpw']);
                if (!$res) {
                    return PEAR::raiseError(_("Unable to bind to the LDAP server.  Check authentication credentials."));
                }

                // prepare data
                $entry['cn'] = $info['user_full_name'];
                // sn is not used operationally but we make an effort to be
                // something sensical.  No guarantees, though.
                $entry['sn'] = array_pop(explode(' ', $info['user_full_name']));
// The next two lines were reversed:  which is right?
                $entry['mail'] = $info['user_name'] . $info['domain'];
                //                 $tmp['mail'];
                $entry['uid'] = $entry['mail'];
                $entry['homeDirectory'] = '/srv/vhost/mail/' . $info['domain'] .'/' . $info['user_name'];
                if(($type != 'group') && ($type != 'forward')) {
                    $entry["qmailUID"] = 8;
                    $entry["qmailGID"] = 8;
                }
                $entry["accountstatus"] = $info["user_enabled"];
                if(isset($info['password']) && !empty($info['password'])) {
                    // FIXME: Allow choice of hash
                    $entry["userPassword"] = Horde_Auth::getCryptedPassword($info['password'], '', 'ssha', true);
                }
                if(isset($objectClassData)) {
                    array_shift($objectClassData);
                    $entry['objectclass'] = $objectClassData;
                } else {
                    $entry['objectclass'] = array();
                    $entry['objectclass'][] = 'top';
                    $entry['objectclass'][] = 'person';
                    $entry['objectclass'][] = 'organizationalPerson';
                    $entry['objectclass'][] = 'inetOrgPerson';
                    $entry['objectclass'][] = 'hordePerson';
                    $entry['objectclass'][] = 'qmailUser';
                }

                // Stir in any site-local custom LDAP attributes
                $entry = Horde::callHook('_vilma_hook_getldapattrs',
                                         array($entry), 'vilma');

                $rdn = 'mail=' . $entry['mail'];
                $dn = $rdn . ',' . $this->_ldapparams['basedn'];
                $res = @ldap_modify($this->_ldap, $dn, $entry);
                if ($res === false) {
                    return PEAR::raiseError(sprintf(_("Error modifying account: %s"), @ldap_error($this->_ldap)));
                } else {
                    return TRUE;
                }
            }
        } else if($info['mode'] == 'new') {
            if ($this->_ldap) {
                // bind with appropriate dn to give update access
                $res = ldap_bind($this->_ldap, $this->_ldapparams['binddn'],
                                 $this->_ldapparams['bindpw']);
                if (!$res) {
                    return PEAR::raiseError(_("Unable to bind to the LDAP server.  Check authentication credentials."));
                }

                // prepare data
                $entry['cn'] = $info['user_full_name'];
                // sn is not used operationally but we make an effort to be
                // something sensical.  No guarantees, though.
                $entry['sn'] = array_pop(explode(' ', $info['user_full_name']));
                $entry['mail'] = $info['user_name'] . '@' . $info['domain'];
                // uid must match mail or SMTP auth fails
                $entry['uid'] = $entry['mail'];
                $entry['homeDirectory'] = '/srv/vhost/mail/' . $info['domain'] .'/' . $info['user_name'];
                $entry['qmailUID'] = 8;
                $entry['qmailGID'] = 8;
                $entry['objectclass'] = array();
                $entry['objectclass'][] = 'top';
                $entry['objectclass'][] = 'person';
                $entry['objectclass'][] = 'organizationalPerson';
                $entry['objectclass'][] = 'inetOrgPerson';
                $entry['objectclass'][] = 'hordePerson';
                $entry['objectclass'][] = 'qmailUser';
                $entry["accountstatus"] = $info["user_enabled"];
                // FIXME: Allow choice of hash
                $entry["userPassword"] = Horde_Auth::getCryptedPassword($info['password'], '', 'ssha', true);

                // Stir in any site-local custom LDAP attributes
                $entry = Horde::callHook('_vilma_hook_getldapattrs',
                                         array($entry), 'vilma');

                $rdn = 'mail=' . $entry['mail'];
                $dn = $rdn . ',' . $this->_ldapparams['basedn'];
                $res = @ldap_add($this->_ldap, $dn, $entry);
                if ($res === false) {
                    return PEAR::raiseError(sprintf(_("Error adding account to LDAP: %s"), @ldap_error($this->_ldap)));
                } else {
                    return TRUE;
                }
            } else {
                return  PEAR::raiseError(_("Unable to connect to LDAP server"));
            }
        }

        return  PEAR::raiseError(_("Unable to save user information."));
    }

    /**
     * Deletes a virtual email.
     *
     * @param integer $virtual_id  The id of the virtual email to delete.
     */
    function deleteVirtual($virtual_id)
    {
        die("deleteVirtual()");
    }

    public function getUserFormAttributes()
    {
        return array(array(
            'label' => _("Account Status"),
            'name' => 'user_enabled',
            'type' => 'enum',
            'required' => true,
            'readonly' => false,
            'description' => null,
            'params' => array(
                array(
                    'active' => _("Account is active"),
                    'noaccess' => _("Disable Delivery Only"),
                    'disabled' => _("Bounce Incoming Only"),
                    'deleted' => _("Account is disabled"),
                ),
             ),
             'default' => 'active',
        ));
    }

    function _connect()
    {
        if (!is_null($this->_ldap)) {
            return true;
        }

        Horde::assertDriverConfig($this->_ldapparams, 'storage',
            array('ldaphost', 'basedn', 'binddn', 'dn'));

        if (!isset($this->_ldapparams['bindpw'])) {
            $this->_ldapparams['bindpw'] = '';
        }

        $port = (isset($this->_ldapparams['port'])) ?
            $this->_ldapparams['port'] : 389;

        $this->_ldap = ldap_connect($this->_ldapparams['ldaphost'], $port);
        if (!$this->_ldap) {
            throw new Vilma_Exception("Unable to connect to LDAP server $hostname on $port");
        }
        $res = ldap_set_option($this->_ldap, LDAP_OPT_PROTOCOL_VERSION,
                               $this->_ldapparams['version']);
        if (!$res) {
            return PEAR::raiseError(_("Unable to set LDAP protocol version"));
        }
        $res = ldap_bind($this->_ldap, $this->_ldapparams['binddn'],
                         $this->_ldapparams['bindpw']);
        if (!$res) {
            return PEAR::raiseError(_("Unable to bind to the LDAP server.  Check authentication credentials."));
        }

    }

    /**
     * Initialise this backend, connect to the SQL database.
     *
     * @return mixed  True on success or PEAR error otherwise.
     */
    function _dbinit()
    {
        global $registry;

        try {
            $this->_db = $GLOBALS['injector']->getInstance('Horde_Core_Factory_DbPear')->create('rw', 'vilma', 'storage');
        } catch (Horde_Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }
    }
}
