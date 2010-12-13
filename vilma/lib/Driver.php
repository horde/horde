<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author  Marko Djukic <marko@oblo.com>
 * @author  Daniel Collins <horde_dev@argentproductions.com>
 * @package Vilma
 */
abstract class Vilma_Driver
{
    /**
     * A hash containing any parameters for the current driver.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Constructor.
     *
     * @param array $params  Any parameters needed for this driver.
     */
    public function __construct(array $params)
    {
        $this->_params = $params;
    }

    /**
     * Returns the list of domains from the backend.
     *
     * @return array  All the domains and their data in an array.
     */
    abstract public function getDomains();

    /**
     * Returns the specified domain information from the backend.
     *
     * @param integer $domain_id  The id of the domain to fetch.
     *
     * @return array  The domain's information in an array.
     */
    abstract public function getDomain($domain_id);

    /**
     * Given a domain name returns the information from the backend.
     *
     * @param string $name  The name of the domain to fetch.
     *
     * @return array  The domain's information in an array.
     */
    abstract public function getDomainByName($domain_name);

    /**
     * Saves a domain with the provided information.
     *
     * @param array $info  Array of details to save the domain.
     *
     * @throws Vilma_Exception
     */
    public function saveDomain($info)
    {
        $this->_saveDomain($info);
        try {
            Horde::callHook('saveDomain', array($info), 'vilma');
        } catch (Horde_Exception_HookNotSet $e) {
        }
    }

    /**
     * Saves a domain with the provided information.
     *
     * @param array $info  Array of details to save the domain.
     */
    abstract protected function _saveDomain($info);

    /**
     * Deletes a domain and all the users and virtuals within it.
     *
     * @param integer $domain_id  The id of the domain to delete.
     *
     * @throws Vilma_Exception
     */
    public function deleteDomain($domain_id)
    {
        $domain_record = $this->getDomain($domain_id);
        $users = $this->getUsers($domain_record['domain_name']);
        foreach ($users as $user) {
            $this->_deleteUser($user['user_id']);
        }
        $this->_deleteDomain($domain_id);
        try {
            Horde::callHook('deleteDomain', array($domain_record['domain_name']), 'vilma');
        } catch (Horde_Exception_HookNotSet $e) {
        }
    }

    /**
     * Deletes a domain.
     *
     * @param integer $domain_id  The id of the domain to delete.
     *
     * @throws Vilma_Exception
     */
    abstract protected function _deleteDomain($domain_id);

    /**
     * Returns the user who is the domain administrator.
     *
     * @todo  This should be replaced by moving all permissions into Horde
     *        permissions.
     *
     * @param string $domain_name  The name of the domain for which to
     *                             return the administrator.
     *
     * @return string  The domain adminstrator.
     * @throws Vilma_Exception
     */
    public function getDomainAdmin($domain_name)
    {
        $domain = $this->getDomainByName($domain_name);
        return $domain['domain_admin'];
    }

    /**
     * Returns the configured quota for this domain.
     *
     * @param string $domain_name  The name of the domain for which to
     *                             return the quota.
     *
     * @return integer  The domain quota.
     * @throws Vilma_Exception
     */
    public function getDomainQuota($domain_name)
    {
        $domain = $this->getDomainByName($domain_name);
        return $domain['domain_quota'];
    }

    /**
     * Returns the maximum number of users allowed for a given domain.
     *
     * @param string $domain_name  The name of the domain for which to
     *                             return the maximum users.
     *
     * @return integer The maximum number of allowed users.
     * @throws Vilma_Exception
     */
    public function getDomainMaxUsers($domain_name)
    {
        $domain = $this->getDomainByName($domain_name);
        return $domain['max_users'];
    }

    /**
     * Returns the current number of users for a domain.
     *
     * @param string $domain_name  The name of the domain for which to
     *                             get the current number of users.
     *
     * @return integer  The current number of users.
     */
    abstract public function getDomainNumUsers($domain_name);

    /**
     * Checks if the given domain is below the maximum allowed users.
     *
     * @param string $domain  The domain name to check.
     *
     * @return boolean  True if the domain does not have a maximum limit (0) or
     *                  current number of users is below the maximum number
     *                  allowed.
     */
    public function isBelowMaxUsers($domain)
    {
        /* Get the maximum number of users for this domain. */
        $max_users = $this->getDomainMaxUsers($domain);
        if (!$max_users) {
            /* No maximum. */
            return true;
        }

        /* Get the current number of users. */
        return $this->getDomainNumUsers($domain) < $max_users;
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
    abstract public function getUsers($domain = null);

    /**
     * Returns all the users sorted by domain and as arrays of each domain.
     *
     * @return array  An array of domains then users for each domain.
     */
    public function getAllUsers()
    {
        /* Determine the domain for each user and plug into array by domain. */
        $users = array();
        foreach ($this->getUsers() as $user) {
            $domain = Vilma::stripDomain($user['user_name']);
            $users[$domain][] = $user;
        }

        /* Sort by domain. */
        ksort($users);

        /* Sort each domain's users by user name. */
        foreach ($users as $key => $val) {
            Horde_Array::arraySort($users[$key], 'user_name');
        }

        return $users;
    }

    /**
     * Returns the user information for a given user id.
     *
     * @param integer $user_id  The id of the user for which to fetch
     *                          information.
     *
     * @return array  The user information.
     */
    abstract public function getUser($user_id);

    /**
     * Does a series of checks for a given user to determine the status.
     *
     * @param array $user  The user's details in an array as returned by the
     *                     getUser() function.
     *
     * @return array  Either an array of error messages found during the checks
     *                or an array with a single element stating that the user
     *                is ready.
     * @throws Vilma_Exception if an error occurs looking up the user status.
     */
    public function getUserStatus($user)
    {
        /* Some needed vars. */
        $error = false;
        $status = array();
        $domain_name = Vilma::stripDomain($user['user_name']);
        $user_name = Vilma::stripUser($user['user_name']);

        /* Check if user enabled. */
        if ($user['user_enabled'] !== 'active') {
            $error = true;
            $err_msg = _("User disabled.");
            $status[] = Horde::img('alerts/error.png', $err_msg) . '&nbsp;' . $err_msg;
        }

        /* Check if mailbox exists. */
        try {
            Vilma_MailboxDriver::factory()->checkMailbox($user_name, $domain_name);
        } catch (Exception $result) {
            $error = true;
            $err_msg = $result->getMessage();
            $status[] = Horde::img('alerts/warning.png', $err_msg) . '&nbsp;' . $err_msg;
        }

        /* TODO: Quota checking would be nice too. */

        /* If no errors have been found output a success message for this
         * user's status. */
        if (!$error) {
            $msg = _("User ready.");
            $status = array(Horde::img('alerts/success.png', $msg) . '&nbsp;' . $msg);
        }

        return $status;
    }

    /**
     * @throws Vilma_Exception
     */
    public function saveUser($info)
    {
        $create = empty($info['user_id']);
        $info['user_id'] = $this->_saveUser($info);

        if ($create) {
            try {
                Vilma_MailboxDriver::factory()
                    ->createMailbox(Vilma::stripUser($info['user_name']),
                                    Vilma::stripDomain($info['user_name']));
            } catch (Exception $e) {
                $this->_deleteUser($info['user_id']);
                throw $e;
            }
        }

        if (isset($GLOBALS['conf']['mta']['auth_update_script']) &&
            !empty($info['password'])) {
            $cmd = sprintf('%s set %s %s 2>&1',
                           $GLOBALS['conf']['mta']['auth_update_script'],
                           escapeshellarg($info['user_name']),
                           escapeshellarg($info['password']));
            $msg = system($cmd, $ec);
            if ($msg === false) {
                throw new Vilma_Exception(_("Error running authentication update script."));
            }
            if ($ec != 0) {
                throw new Vilma_Exception(_("Unknown error running authentication update script."));
            }
        }
    }

    /**
     * Saves a user to the backend.
     *
     * @param array $info  The user information to save.
     *
     * @return string  The user ID.
     * @throws Vilma_Exception
     */
    abstract protected function _saveUser($info);

    /**
     * Deletes a user.
     *
     * @param integer $user_id  The id of the user to delete.
     *
     * @throws Vilma_Exception
     */
    abstract public function deleteUser($user_id);

    public function getUserFormAttributes()
    {
    }

    /**
     * Returns a list of all users, aliases, or groups and forwards for a
     * domain.
     *
     * @param string $domain      Domain on which to search.
     * @param string $type        Only return a specific type. One of 'all',
     *                            'user', 'alias', 'forward', or 'group'.
     * @param string $key         Sort list by this key.
     * @param integer $direction  Sort direction.
     *
     * @return array  Account information for this domain.
     */
    public function getAddresses($domain, $type = 'all', $key = 'user_name',
                                 $direction = 0)
    {
        $addresses = $this->_getAddresses($domain, $type);
        Horde_Array::arraySort($addresses, $key, $direction, true);
        return $addresses;
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
     * @return array  Account information for this domain.
     */
    abstract protected function _getAddresses($domain, $type = 'all');

    /**
     * Returns an array of information related to the address passed in.
     *
     * This method may be overridden by the backend driver if there is a more
     * efficient way to do this than a linear array search.
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
        $domain = Vilma::stripDomain($address);
        $addresses = $this->getAddresses($domain, $type);
        foreach ($addresses as $addrinfo) {
            if ($addrinfo['id'] == $address ||
                $addrinfo['address'] == $address) {
                return $addrinfo;
            }
        }
        throw new Vilma_Exception(sprintf(_("No such address %s of type %s found."), $address, $type));
    }

    /**
     * Returns available virtual emails.
     *
     * @param string $filter  If passed a domain then return all virtual emails
     *                        for the domain, otherwise if passed a user name
     *                        return all virtual emails for that user.
     *
     * @return array  The available virtual emails.
     */
    public function getVirtuals($filter)
    {
    }

    /**
     * Returns information for a virtual id.
     *
     * @param integer $virtual_id  The virtual id for which to return
     *                             information.
     *
     * @return array  The virtual email information.
     */
    public function getVirtual($virtual_id)
    {
    }

    /**
     * Saves virtual email address to the backend.
     *
     * @param array $info     The virtual email data.
     * @param string $domain  The name of the domain for this virtual email.
     *
     * @throws Vilma_Exception
     */
    public function saveVirtual($info, $domain)
    {
    }

    /**
     * Deletes a virtual email.
     *
     * @param integer $virtual_id  The id of the virtual email to delete.
     */
    public function deleteVirtual($virtual_id)
    {
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
    }

    /**
     * Attempts to return a concrete Vilma_Driver instance based on $driver.
     *
     * @param string $driver  The type of concrete Vilma_Driver subclass to
     *                        return.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return Vilma_Driver  The newly created concrete Vilma_Driver instance.
     * @throws Vilma_Exception
     */
    static public function factory($driver = null, $params = null)
    {
        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }
        $driver = Horde_String::ucfirst(basename($driver));

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        $class = 'Vilma_Driver_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Vilma_Exception(sprintf(_("No such backend \"%s\" found"), $driver));
    }
}
