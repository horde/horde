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
 * @todo - Convert to Horde_Ldap
 */
class Vilma_Driver_Qmailldap extends Vilma_Driver_Sql
{
    /**
     * Reference to initialized LDAP driver.
     *
     * @var resource
     */
    protected $_ldap;

    /**
     * Cache for retrieved getUsers() results.
     *
     * @var array
     */
    protected $_users = array();

    /**
     * Map of internal field names to LDAP attribute names.
     *
     * @var array
     */
    protected $_fieldmap = array(
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

    /**
     * Constructor.
     *
     * @param array $params  Any parameters needed for this driver.
     */
    public function __construct($params)
    {
        $params = array_merge(
            Horde::getDriverConfig('storage', 'sql'),
            $params);
        parent::__construct($params);
        $this->_connect();
    }

    /**
     * Deletes a domain.
     *
     * @todo Add logic to remove all users, aliases, and grpfwds for this
     *       domain.
     * @todo Use parent::_deleteDomain()
     *
     * @param integer $domain_id  The id of the domain to delete.
     *
     * @throws Vilma_Exception
     */
    protected function _deleteDomain($domain_id)
    {
        $domain_record = $this->getDomain($domain_id);
        $domain_name = $domain_record['domain_name'];

        /* Finally delete the domain. */
        $sql = 'DELETE FROM vilma_domains WHERE domain_id=?';
        $values = array((int)$domain_id);

        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->query($sql, $values);
    }

    /**
     * Returns the current number of users for a domain.
     *
     * @param string $domain_name  The name of the domain for which to
     *                             get the current number of users.
     *
     * @return integer  The current number of users.
     */
    public function getDomainNumUsers($domain_name)
    {
        return count($this->_getUsers($domain_name));
    }

    /**
     * Returns all available users, if a domain name is passed then limit the
     * list of users only to those users.
     *
     * @param string $domain  The name of the domain for which to fetch the
     *                        users.
     *
     * @return array  The available users and their stored information.
     * @throws Vilma_Exception
     */
    public function getUsers($domain = null)
    {
        // Cache for multiple calls.
        if (is_null($domain) && isset($this->_users['_all'])) {
            return $this->_users['_all'];
        }

        if (!is_null($domain) && isset($this->_users[$domain])) {
            return $this->_users[$domain];
        }

        $filter = '(&';
        if (!is_null($domain)) {
            $filter .= '(mail=*@' . $domain . ')';
        } else {
            $domain = '_all';
        }

        // Make sure we don't get any forwards.
        $filter .= '(!(mailForwardingAddress=*))';

        // FIXME: Check/add configured filter instead of objectclasses
        foreach ($this->_params['ldap']['objectclass'] as $objectclass) {
            $filter .= '(objectClass=' . $objectclass . ')';
        }
        $filter .= ')';

        Horde::logMessage($filter, 'DEBUG');
        $res = ldap_search($this->_ldap, $this->_params['ldap']['basedn'], $filter);
        if ($res === false) {
            throw new Vilma_Exception(sprintf(_("Error in LDAP search: %s"), ldap_error($this->LDAP)));
        }

        $res = ldap_get_entries($this->_ldap, $res);
        if ($res === false) {
            throw new Vilma_Exception(sprintf(_("Error in LDAP search: %s"), ldap_error($this->LDAP)));
        }

        $this->_users[$domain] = array();
        // Can't use foreach because of the array format returned by LDAP driver
        for ($i = 0; isset($res[$id]); $user = $res[$i++]) {
            $info = array(
                'id' => $user['dn'],
                'address' => $user[$this->_fieldmap['address']][0],
                'type' => 'user',
                'user_name' => $user[$this->_fieldmap['user_name']][0]);

            // We likely don't have read permission on the crypted password so
            // avoid any warnings/errors about missing array elements.
            if (isset($user[$this->_fieldmap['user_crypt']])) {
                $info['user_crypt'] = $user[$this->_fieldmap['user_crypt']][0];
            } else {
                $info['user_crypt'] = '';
            }
            $info['user_full_name'] = $user[$this->_fieldmap['user_full_name']][0];
            // Mute assignment errors on the following optional fields.
            // These may not be present if the mail is only forwarded.
            $info['user_uid'] = @$user[$this->_fieldmap['user_uid']][0];
            $info['user_gid'] = @$user[$this->_fieldmap['user_gid']][0];
            $info['user_home_dir'] = @$user[$this->_fieldmap['user_home_dir']][0];
            $info['user_mail_dir'] = @$user[$this->_fieldmap['user_mail_dir']][0];
            $info['user_mail_quota_bytes'] = @$user[$this->_fieldmap['user_mail_quota_bytes']][0];
            $info['user_mail_quota_count'] = @$user[$this->_fieldmap['user_mail_quota_count']][0];

            // If accountStatus is blank it's the same as active.
            if (!isset($user[$this->_fieldmap['user_enabled']][0]) ||
                $user[$this->_fieldmap['user_enabled']][0] == 'active') {
                $info['user_enabled'] = 'active';
            } else {
                // accountStatus can also be:
                // noaccess (receives but cannot pick up mail)
                // disabled (bounce incoming and deny pickup)
                // deleted (bounce incoming but allow pickup)
                $info['user_enabled'] = $user[$this->_fieldmap['user_enabled']][0];
            }

            $this->_users[$domain][$i] = $info;
        }

        return $this->_users[$domain];
    }

    /**
     * Returns the user information for a given user id.
     *
     * @param integer $user_id  The id of the user for which to fetch
     *                          information.
     *
     * @return array  The user information.
     */
    public function getUser($user_id)
    {
        $user = $this->getUserStatus($user_id);
        if (is_array($user)) {
            return $user;
        }
        throw new Vilma_Exception(_("Unable to qualify address."));
    }

    /**
     * Saves a user to the backend.
     *
     * @param array $info  The user information to save.
     *
     * @return array  The user information.
     * @throws Vilma_Exception
     */
    protected function _saveUser($info)
    {
        switch ($info['mode']) {
        case 'edit':
            return $this->_updateUser($info);
        case 'new':
            return $this->_createUser($info);
        }

        throw new Vilma_Exception(_("Unable to save user information."));
    }

    /**
     * Updates a user in the backend.
     *
     * @param array $info  The user information to save.
     *
     * @return array  The user information.
     * @throws Vilma_Exception
     */
    protected function _updateUser($info)
    {
        $address = $info['address'];
        if (empty($address)) {
            $user_name = $info['user_name'];
            $domain = $info['domain'];
            if (empty($user_name)) {
                throw new Vilma_Exception(_("Unable to acquire handle on address."));
            }
            $address = $info['user_name'] . $info['domain'];
        }
        $addrinfo = $this->getAddressInfo($address);
        $type = $addrinfo['type'];
        if ($type != 'user') {
            throw new Vilma_Exception(sprintf(_("Unable to save account of type \"%s\""), $type));
        }

        $user_info = $this->_searchForUser($address);
        if ($res['count'] === 0) {
            throw new Vilma_Exception(_("Error reading address information from backend."));
        }

        $objectClassData = null;
        if (isset($user_info[0]['objectclass'])) {
            $objectClassData = $user_info[0]['objectclass'];
        }

        // Don't want to save this to LDAP.
        unset($info['mode']);

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
            $attr = $this->_fieldmap[$key];
            $tmp[$attr] = $val;
        }

        // Bind with appropriate dn to give update access.
        $res = ldap_bind($this->_ldap, $this->_params['ldap']['binddn'],
                         $this->_params['ldap']['bindpw']);
        if (!$res) {
            throw new Vilma_Exception(_("Unable to bind to the LDAP server.  Check authentication credentials."));
        }

        // Prepare data.
        $entry['cn'] = $info['user_full_name'];
        // sn is not used operationally but we make an effort to be
        // something sensical.  No guarantees, though.
        $entry['sn'] = array_pop(explode(' ', $info['user_full_name']));
        $entry['mail'] = $info['user_name'] . $info['domain'];
        $entry['uid'] = $entry['mail'];
        $entry['homeDirectory'] = '/srv/vhost/mail/' . $info['domain'] .'/' . $info['user_name'];
        if ($type != 'group' && $type != 'forward') {
            $entry['qmailUID'] = $entry['qmailGID'] = 8;
        }
        $entry['accountstatus'] = $info['user_enabled'];
        if (isset($info['password']) && !empty($info['password'])) {
            // FIXME: Allow choice of hash
            $entry['userPassword'] = Horde_Auth::getCryptedPassword($info['password'], '', 'ssha', true);
        }
        if (isset($objectClassData)) {
            array_shift($objectClassData);
            $entry['objectclass'] = $objectClassData;
        } else {
            $entry['objectclass'] = array(
                'top',
                'person',
                'organizationalPerson',
                'inetOrgPerson',
                'hordePerson',
                'qmailUser');
        }

        // Stir in any site-local custom LDAP attributes.
        try {
            $entry = Horde::callHook('getLDAPAttrs', array($entry), 'vilma');
        } catch (Horde_Exception_HookNotSet $e) {
        }
        $rdn = 'mail=' . $entry['mail'];
        $dn = $rdn . ',' . $this->_params['ldap']['basedn'];
        $res = @ldap_modify($this->_ldap, $dn, $entry);
        if ($res === false) {
            throw new Vilma_Exception(sprintf(_("Error modifying account: %s"), @ldap_error($this->_ldap)));
        }

        return $dn;
    }

    /**
     * Creates a user in the backend.
     *
     * @param array $info  The user information to save.
     *
     * @return array  The user information.
     * @throws Vilma_Exception
     */
    protected function _createUser($info)
    {
        // Bind with appropriate dn to give update access.
        $res = ldap_bind($this->_ldap, $this->_params['ldap']['binddn'],
                         $this->_params['ldap']['bindpw']);
        if (!$res) {
            throw new Vilma_Exception(_("Unable to bind to the LDAP server.  Check authentication credentials."));
        }

        // Prepare data.
        $entry['cn'] = $info['user_full_name'];
        // sn is not used operationally but we make an effort to be
        // something sensical.  No guarantees, though.
        $entry['sn'] = array_pop(explode(' ', $info['user_full_name']));
        $entry['mail'] = $info['user_name'] . '@' . $info['domain'];
        // uid must match mail or SMTP auth fails.
        $entry['uid'] = $entry['mail'];
        $entry['homeDirectory'] = '/srv/vhost/mail/' . $info['domain'] .'/' . $info['user_name'];
        $entry['qmailUID'] = $entry['qmailGID'] = 8;
        $entry['objectclass'] = array(
            'top',
            'person',
            'organizationalPerson',
            'inetOrgPerson',
            'hordePerson',
            'qmailUser');
        $entry['accountstatus'] = $info['user_enabled'];
        // FIXME: Allow choice of hash
        $entry['userPassword'] = Horde_Auth::getCryptedPassword($info['password'], '', 'ssha', true);

        // Stir in any site-local custom LDAP attributes.
        try {
            $entry = Horde::callHook('getLDAPAttrs', array($entry), 'vilma');
        } catch (Horde_Exception_HookNotSet $e) {
        }
        $rdn = 'mail=' . $entry['mail'];
        $dn = $rdn . ',' . $this->_params['ldap']['basedn'];
        $res = @ldap_add($this->_ldap, $dn, $entry);
        if ($res === false) {
            throw new Vilma_Exception(sprintf(_("Error adding account to LDAP: %s"), @ldap_error($this->_ldap)));
        }

        return $dn;
    }

    /**
     * Deletes a user.
     *
     * @param integer $user_id  The id of the user to delete.
     *
     * @throws Vilma_Exception
     */
    public function deleteUser($user_id)
    {
        // Get the user's DN.
        $filter  = '(&';
        foreach ($this->_params['ldap']['objectclass'] as $objectclass) {
            // Add each objectClass from parameters.
            $filter .= '(objectclass=' . $objectclass . ')';
        }
        $filter .= '(mail=' . $user_id . ')';
        $filter .= ')';

        Horde::logMessage($filter, 'DEBUG');
        $res = @ldap_search($this->_ldap, $this->_params['ldap']['basedn'], $filter);
        if ($res === false) {
            throw new Vilma_Exception(sprintf(_("Error searching LDAP: %s"), @ldap_error($this->_ldap)));
        }
        $res = @ldap_get_entries($this->_ldap, $res);
        if ($res === false) {
            throw new Vilma_Exception(sprintf(_("Error retrieving LDAP results: %s"), @ldap_error($this->_ldap)));
        }

        if ($res['count'] === 0) {
            throw new Vilma_Exception(_("Unable to acquire handle on DN.  Aborting delete operation."));
        }
        if ($res['count'] !== 1) {
            throw new Vilma_Exception(_("More than one DN returned.  Aborting delete operation."));
        }

        // We now have one unique DN to delete.
        $res = @ldap_delete($this->_ldap, $res[0]['dn']);
        if ($res === false) {
            throw new Vilma_Exception(sprintf(_("Error deleting account from LDAP: %s"), @ldap_error($this->_ldap)));
        }
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
            $addresses += $this->_getGroupsAndForwards('group', $domain);
        }
        return $addresses;
    }

    /**
     * Returns available email address aliases.
     *
     * @param string $target  If passed a domain then return all alias emails
     *                        for the domain, otherwise if passed a user name
     *                        return all virtual emails for that user.
     *
     * @return array  The used email aliases.
     */
    protected function _getAliases($target = null)
    {
        // FIXME: Add static cache

        // FIXME: Add preconfigured filter from $this->_params['ldap']
        // Begin filter (cumulative AND).
        $filter  = '(&';
        foreach ($this->_params['ldap']['objectclass'] as $objectclass) {
            // Add each objectClass from parameters
            $filter .= '(objectClass=' . $objectclass . ')';
        }

        // Check if filtering only for domain.
        if (strpos($target, '@') === false && !empty($target)) {
            $filter .= '(mailAlternateAddress=*@' . $target . ')';
        } else {
            // Otherwise filter for all aliases.
            $filter .= '(mailAlternateAddress=*)';
            // Restrict the results to $target.
            if (!empty($target)) {
                 // Add user's email.
                $filter .= '(mail=' . $target . ')';
            }
        }
        // End filter.
        $filter .= ')';

        Horde::logMessage($filter, 'DEBUG');
        $res = @ldap_search($this->_ldap, $this->_params['ldap']['basedn'], $filter);
        if ($res === false) {
            throw new Vilma_Exception(sprintf(_("Error searching LDAP: %s"), @ldap_error($this->_ldap)));
        }

        $res = @ldap_get_entries($this->_ldap, $res);
        if ($res === false) {
            throw new Vilma_Exception(sprintf(_("Error returning LDAP results: %s"), @ldap_error($this->_ldap)));
        }

        $aliases = array();
        // Can't use foreach because of the array format returned by LDAP driver
        for ($e = 0; isset($res[$e]); $entry = $res[$e++]) {
            // If accountStatus is blank it's the same as active
            if (!isset($entry[$this->_fieldmap['user_enabled']][0]) ||
                $entry[$this->_fieldmap['user_enabled']][0] == 'active') {
                $curstatus = 'active';
            } else {
                // accountStatus can also be:
                // noaccess (receives but cannot pick up mail)
                // disabled (bounce incoming and deny pickup)
                // deleted (bounce incoming but allow pickup)
                $curstatus = $entry[$this->_fieldmap['user_enabled']][0];
            }
            for ($a = 0; isset($entry['mailalternateaddress'][$a]); $mail = @$entry['mailalternateaddress'][$a++]) {
                $aliases[] = array(
                    'id' => $mail,
                    'type' => 'alias',
                    'user_name' => $mail,
                    'user_full_name' => sprintf(_("Alias for %s"), $entry['mail'][0]),
                    'destination' => $entry['mail'][0],
                    'user_enabled' => $curstatus);
            }
        }

        return $aliases;
    }

    /**
     * Returns all available groups and forwards unless otherwise specified.
     *
     * If a domain name is passed then limit the results to groups or forwards
     * in that domain.
     *
     * @param string $acquire The default behavior is to acquire both
     *                        groups and forwards; a value of 'group'
     *                        will return only groups and a value of
     *                        'forward' will return only forwards.
     * @param string $domain  The name of the domain from which to fetch.
     *
     * @return array  The available groups and forwards with details.
     */
    protected function _getGroupsAndForwards($acquire = null, $domain = null)
    {
        // FIXME: Add preconfigured filter from $this->_params['ldap']
        // Begin filter (cumulative AND).
        $filter  = '(&';
        foreach ($this->_params['ldap']['objectclass'] as $objectclass) {
            // Add each objectClass from parameters.
            $filter .= '(objectClass=' . $objectclass . ')';
        }

        // Only return results which have a forward configured.
        $filter .= '(mailForwardingAddress=*)';

        if (!empty($domain)) {
            // mail or mailAlternateAddress.
            $filter .= '(|';
            $filter .= '(mail=*@' . $domain . ')';
            $filter .= '(mailAlternateAddress=*@' . $domain . ')';
            $filter .= ')';
        } else {
            $domain = '_all';
        }

        // End filter
        $filter .= ')';

        Horde::logMessage($filter, 'DEBUG');
        $res = @ldap_search($this->_ldap, $this->_params['ldap']['basedn'], $filter);
        if ($res === false) {
            throw new Vilma_Exception(sprintf(_("Error searching LDAP: %s"), @ldap_error($this->_ldap)));
        }

        $res = @ldap_get_entries($this->_ldap, $res);
        if ($res === false) {
            throw new Vilma_Exception(sprintf(_("Error returning LDAP results: %s"), @ldap_error($this->_ldap)));
        }

        $grpfwds[$domain] = array();
        // Can't use foreach because of the array format returned by LDAP driver
        for ($e = 0; isset($res[$e]); $entry = $res[$e++]) {
            $targets = array();
            for ($a = 0; isset($res[$e]['mailforwardingaddress'][$a]); $attr = $res[$e]['mailforwardingaddress'][$a++]) {
                $targets[] = $attr;
            }
            $type = $entry['mailforwardingaddress']['count'];
            if ($type > 1) {
                $type = 'group';
            } else {
                $type = 'forward';
            }
            if ($acquire == 'all' || $type == $acquire) {
                $grpfwds[$domain][$e] = array(
                    'id'             => $entry['dn'],
                    'type'           => $type,
                    'address'        => $entry[$this->_fieldmap['address']][0],
                    'targets'        => $targets,
                    'user_name'      => $entry[$this->_fieldmap['user_name']][0],
                    'user_full_name' => @$entry[$this->_fieldmap['user_name']][0],
                );
                // If accountStatus is blank it's the same as active
                if (!isset($entry[$this->_fieldmap['user_enabled']][0]) ||
                    $entry[$this->_fieldmap['user_enabled']][0] == 'active') {
                    $grpfwds[$domain][$e]['user_enabled'] = 'active';
                } else {
                    // accountStatus can also be:
                    // noaccess (receives but cannot pick up mail)
                    // disabled (bounce incoming and deny pickup)
                    // deleted (bounce incoming but allow pickup)
                    $grpfwds[$domain][$e]['user_enabled'] =
                        $entry[$this->_fieldmap['user_enabled']][0];
                }
            }
        }

        return $grpfwds[$domain];
    }

    /**
     * Returns an array of information related to the address passed in.
     *
     * @param string $address  Address for which information will be pulled.
     * @param string $type     Address type to request.
     *                         One of 'all', 'user', 'alias', 'forward' or
     *                         'group'.
     *
     * @return array  Array of user information on success or empty array
     *                if the user does not exist.
     * @throws Vilma_Exception if address of that type doesn't exist.
     */
    public function getAddressInfo($address, $type = 'all')
    {
        if ($type != 'alias') {
            return parent::getAddressInfo($address, $type);
        }

        // FIXME: Which is faster?  A linear array search or an LDAP search?
        // I think LDAP in this case because we can't assume the domain.
        // Begin filter (cumulative AND).
        $filter = '(&';
        foreach ($this->_params['ldap']['objectclass'] as $objectclass) {
            // Add each objectClass from parameters.
            $filter .= '(objectClass=' . $objectclass . ')';
        }
        $filter .= '(mailAlternateAddress=' . $address . ')';
        // End filter.
        $filter .= ')';

        Horde::logMessage($filter, 'DEBUG');
        $res = @ldap_search($this->_ldap, $this->_params['ldap']['basedn'], $filter);
        if ($res === false) {
            throw new Vilma_Exception(sprintf(_("Error searching LDAP: %s"), @ldap_error($this->_ldap)));
        }
        $res = @ldap_get_entries($this->_ldap, $res);
        if ($res === false) {
            throw new Vilma_Exception(sprintf(_("Error returning LDAP results: %s"), @ldap_error($this->_ldap)));
        }

        if ($res['count'] !== 1) {
            throw new Vilma_Exception(_("More than one DN returned for this alias.  Please contact an administrator to resolve this error."));
        }

        return array(
            'id' => $res[0]['dn'],
            'address' => $address,
            'destination' => $res[0]['mail'][0],
        );
    }

    /**
     * Saves or creates alias records for a user.
     *
     * @param array $info  The info used to store the information.
     *                     Required fields are:
     *                     - 'address': The destination address (used for LDAP
     *                       ID lookup).
     *                     - 'alias_address': The alias to create or the new
     *                       data for the modified entry.
     *                     - 'alias': The alias we are modifying, if we are
     *                       modifying an existing one.
     *
     * @throws Vilma_Exception
     */
    public function saveAlias($info)
    {
        $address = $info['address'];
        if (!empty($info['alias'])) {
            $alias = $info['alias'];
            $create = false;
        } else {
            $create = true;
        }
        $alias_address = $info['alias_address'];

        $user_res = $this->_searchForUser($address);
        if ($res['count'] === 0) {
            throw new Vilma_Exception(_("Error reading address information from backend."));
        }
        $user = $user_res[0];

        // Retrieve the current MAA values.
        if (isset($user_res[0]['mailalternateaddress'])) {
            $maa = $user['mailalternateaddress'];
            unset($maa['count']);
        } else {
            $maa = array();
        }

        $oldmaa = $maa;
        if ($create) {
            // Verify that it does not already exist.
            if (in_array($alias_address, $maa)) {
                throw new Vilma_Exception(_("That alias already exists!"));
            }

            // Not there, we create it.
            $maa[] = $alias_address;
        } elseif ($alias != $alias_address) {
            $key = array_search($alias, $maa);
            if ($key === false) {
                throw new Vilma_Exception(sprintf(_("Existing entry \"%s\" could not be found."), $alias));
            }
            $maa[$key] = $alias_address;
        } else {
            return;
        }
        sort($maa);

        $dn = $user['dn'];
        Horde::logMessage("UPDATING: $dn \nOld MAA: " . print_r($oldmaa, true) . "\nNew MAA: " . print_r($maa, true), 'DEBUG');

        // Bind with appropriate dn to give update access.
        $res = ldap_bind($this->_ldap, $this->_params['ldap']['binddn'],
                         $this->_params['ldap']['bindpw']);
        if (!$res) {
            throw new Vilma_Exception(_("Unable to bind to the LDAP server. Check authentication credentials."));
        }

        $entry['mailAlternateAddress'] = $maa;
        $res = @ldap_modify($this->_ldap, $dn, $entry);
        if ($res === false) {
            throw new Vilma_Exception(sprintf(_("Error modifying account: %s"), @ldap_error($this->_ldap)));
        }
    }

    /**
     * Deletes alias records for a given user.
     *
     * @param array $info  The info used to store the information.
     *                     Required fields are:
     *                     - 'address': The destination address (used for LDAP
     *                       ID lookup).
     *                     - 'alias': The alias we are deleting.
     *
     * @throws Vilma_Exception
     */
    public function deleteAlias($info)
    {
        $address = $info['address'];
        $alias = $info['alias'];

        $user_res = $this->_searchForUser($address);
        if ($res['count'] === 0) {
            throw new Vilma_Exception(_("Error reading address information from backend."));
        }
        $user = $user_res[0];

        // Retrieve the current MAA values.
        if (!isset($user['mailalternateaddress'])) {
            return;
        }

        $maa = $user['mailalternateaddress'];
        unset($maa['count']);
        $oldmaa = $maa;
        $key = array_search($alias, $maa);
        if ($key === false) {
            return;
        }

        unset($maa[$key]);
        sort($maa);

        $dn = $user['dn'];
        Horde::logMessage("UPDATING: $dn \nOld MAA: " . print_r($oldmaa, true) . "\nNew MAA: " . print_r($maa, true), 'DEBUG');

        // Bind with appropriate dn to give update access.
        $res = ldap_bind($this->_ldap, $this->_params['ldap']['binddn'],
                         $this->_params['ldap']['bindpw']);
        if (!$res) {
            throw new Vilma_Exception(_("Unable to bind to the LDAP server. Check authentication credentials."));
        }

        $entry['mailAlternateAddress'] = $maa;
        $res = @ldap_modify($this->_ldap, $dn, $entry);
        if ($res === false) {
            throw new Vilma_Exception(sprintf(_("Error modifying account: %s"), @ldap_error($this->_ldap)));
        }
    }

    /**
     * Saves or creates forward records for a given user.
     *
     * @param array $info  The info used to store the information.
     *                     Required fields are:
     *                     - 'address': The destination address (used for LDAP
     *                       ID lookup).
     *                     - 'forward_address': The forward to create or the
     *                       new data for the modified entry.
     *                     - 'forward': The forward we are modifying, if we are
     *                       modifying an existing one.
     *
     * @throws Vilma_Exception
     */
    public function saveForward($info)
    {
        $address = $info['address'];
        if (!empty($info['forward'])) {
            $forward = $info['forward'];
            $create = false;
        } else {
            $create = true;
        }
        $forward_address = $info['forward_address'];

        $user_res = $this->_searchForUser($address);
        if ($res['count'] === 0) {
            throw new Vilma_Exception(_("Error reading address information from backend."));
        }
        $user = $user_res[0];

        // Retrieve the current MAA values.
        if (isset($user['mailforwardingaddress'])) {
            $mfa = $user['mailforwardingaddress'];
            unset($mfa['count']);
        } else {
            $mfa = array();
        }

        $oldmfa = $mfa;
        if ($create) {
            // Verify that it does not already exist
            if (in_array($forward_address, $mfa)) {
                throw new Vilma_Exception(sprintf(_("That forward, \"%s\", already exists!"), $forward_address));
            }

            // Not there, we create it.
            $mfa[] = $forward_address;
        } elseif ($forward != $forward_address) {
            $key = array_search($forward, $mfa);
            if ($key === false) {
                throw new Vilma_Exception(sprintf(_("Existing entry \"%s\" could not be found."), $forward));
            }
            $mfa[$key] = $forward_address;
        } else {
            return;
        }
        sort($mfa);

        $dn = $user['dn'];
        Horde::logMessage("UPDATING: $dn \nOld MFA: " . print_r($oldmfa, true) . "\nNew MFA: " . print_r($mfa, true), 'DEBUG');

        // Bind with appropriate dn to give update access.
        $res = ldap_bind($this->_ldap, $this->_params['ldap']['binddn'],
                         $this->_params['ldap']['bindpw']);
        if (!$res) {
            throw new Vilma_Exception(_("Unable to bind to the LDAP server. Check authentication credentials."));
        }

        $entry['mailForwardingAddress'] = $mfa;
        $res = @ldap_modify($this->_ldap, $dn, $entry);
        if ($res === false) {
            throw new Vilma_Exception(sprintf(_("Error modifying account: %s"), @ldap_error($this->_ldap)));
        }
    }

    /**
     * Deletes forward records for a given user.
     *
     * @param array $info  The info used to store the information.
     *                     Required fields are:
     *                     - 'address': The destination address (used for LDAP
     *                       ID lookup).
     *                     - 'forward': The forward we are deleting.
     *
     * @throws Vilma_Exception
     */
    public function deleteForward($info)
    {
        $address = $info['address'];
        $forward = $info['forward'];

        $user_res = $this->_searchForUser($address);
        if ($res['count'] === 0) {
            throw new Vilma_Exception(_("Error reading address information from backend."));
        }
        $user = $user_res[0];

        // Retrieve the current MFA values.
        if (!isset($user['mailforwardingaddress'])) {
            return;
        }

        $mfa = $user['mailforwardingaddress'];
        unset($mfa['count']);
        $oldmfa = $mfa;
        $key = array_search($forward, $mfa);
        if ($key === false) {
            return;
        }
        unset($mfa[$key]);
        sort($mfa);

        $dn = $user['dn'];
        Horde::logMessage("UPDATING: $dn \nOld MFA: " . print_r($oldmfa, true) . "\nNew MFA: " . print_r($mfa, true), 'DEBUG');
        // Bind with appropriate dn to give update access.
        $res = ldap_bind($this->_ldap, $this->_params['ldap']['binddn'],
                         $this->_params['ldap']['bindpw']);
        if (!$res) {
            throw new Vilma_Exception(_("Unable to bind to the LDAP server. Check authentication credentials."));
        }

        $entry['mailForwardingAddress'] = $mfa;
        $res = @ldap_modify($this->_ldap, $dn, $entry);
        if ($res === false) {
            throw new Vilma_Exception(sprintf(_("Error modifying account: %s"), @ldap_error($this->_ldap)));
        }
    }

    /**
     * Searchs for a given email account.
     *
     * @param string $email_id The id of the account to be searched for.
     *
     * @return array  Data for given email account on success or no
     *                information found.
     */
    protected function _searchForUser($email_id)
    {
        // Get the user's DN
        $filter  = '(&';
        foreach ($this->_params['ldap']['objectclass'] as $objectclass) {
            // Add each objectClass from parameters.
            $filter .= '(objectclass=' . $objectclass . ')';
        }
        $filter .= '(mail=' . $email_id . '))';

        Horde::logMessage($filter, 'DEBUG');
        $res = @ldap_search($this->_ldap, $this->_params['ldap']['basedn'], $filter);
        if ($res === false) {
            throw new Vilma_Exception(sprintf(_("Error searching LDAP: %s"), @ldap_error($this->_ldap)));
        }
        $res = @ldap_get_entries($this->_ldap, $res);
        if ($res === false) {
            throw new Vilma_Exception(sprintf(_("Error retrieving LDAP results: %s"), @ldap_error($this->_ldap)));
        }

        if ($res['count'] === 0) {
            throw new Vilma_Exception(_("Unable to acquire handle on DN.  Aborting delete operation."));
        }
        if($res['count'] !== 1) {
            throw new Vilma_Exception(_("More than one DN returned.  Aborting delete operation."));
        }

        return $res;
    }

    function _connect()
    {
        if (!is_null($this->_ldap)) {
            return;
        }

        Horde::assertDriverConfig($this->_params['ldap'], 'storage',
                                  array('ldaphost', 'basedn', 'binddn', 'dn'));

        if (!isset($this->_params['ldap']['bindpw'])) {
            $this->_params['ldap']['bindpw'] = '';
        }

        $port = isset($this->_params['ldap']['port'])
            ? $this->_params['ldap']['port']
            : 389;

        $this->_ldap = ldap_connect($this->_params['ldap']['ldaphost'], $port);
        if (!$this->_ldap) {
            throw new Vilma_Exception("Unable to connect to LDAP server $hostname on $port");
        }
        $res = ldap_set_option($this->_ldap, LDAP_OPT_PROTOCOL_VERSION,
                               $this->_params['ldap']['version']);
        if (!$res) {
            throw new Vilma_Exception(_("Unable to set LDAP protocol version"));
        }
        $res = ldap_bind($this->_ldap, $this->_params['ldap']['binddn'],
                         $this->_params['ldap']['bindpw']);
        if (!$res) {
            throw new Vilma_Exception(_("Unable to bind to the LDAP server.  Check authentication credentials."));
        }
    }
}
