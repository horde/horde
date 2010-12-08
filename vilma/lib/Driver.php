<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author Marko Djukic <marko@oblo.com>
 * @author Daniel Collins <horde_dev@argentproductions.com>
 * @package Vilma
 */
abstract class Vilma_Driver
{
    /**
     * A hash containing any parameters for the current driver.
     *
     * @var array
     */
    var $_params = array();

    /**
     * Constructor
     *
     * @param array $params  Any parameters needed for this driver.
     */
    function Vilma_Driver($params)
    {
        $this->_params = $params;
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
     * @return array Account information for this domain
     */
    abstract protected function _getAddresses($domain, $type = 'all');

    /**
     * Returns all the users sorted by domain and as arrays of each domain.
     *
     * @return array  An array of domains then users for each domain.
     */
    function getAllUsers()
    {
        /* Get all users from backend. */
        $users = &$this->getUsers();

        /* Determine the domain for each user and plug into array by domain. */
        $users_by_domain = array();
        foreach ($users as $user) {
            $domain_name = Vilma::stripDomain($user['user_name']);
            $users_by_domain[$domain_name][] = $user;
        }

        /* Sort by domain. */
        ksort($users_by_domain);
        /* Sort each domain's users by user name. */
        require_once 'Horde/Array.php';
        foreach ($users_by_domain as $key => $val) {
            Horde_Array::arraySort($users_by_domain[$key], 'user_name');
        }

        return $users_by_domain;
    }

    /**
     * Checks if the given domain is below the maximum allowed users.
     *
     * @param string $domain  The domain name to check.
     *
     * @return boolean  True if the domain does not have a maximum limit (0) or
     *                  current number of users is below the maximum number
     *                  allowed. Otherwise false.
     */
    function isBelowMaxUsers($domain)
    {
        /* Get the maximum number of users for this domain. */
        $max_users = $this->getDomainMaxUsers($domain);
        if ($max_users == '0') {
            /* No maximum. */
            return true;
        }

        /* Get the current number of users. */
        $num_users = $this->getDomainNumUsers($domain);

        return ($num_users < $max_users);
    }

    /**
     * Gets an array of information related to the address passed in.
     * This method may be overridden by the backend driver if there is a more
     * efficient way to do this than a linear array search
     *
     * @param string $address  Address for which information will be pulled
     * @param string $type     Address type to request
     *                         One of 'user', 'alias', 'grpfwd' or 'any'
     *                         Defaults to 'any'
     *
     * @return mixed  Array of user information on success, empty array
     *                if the user does not exist, PEAR_Error on failure
     */
    function getAddressInfo($address, $type = 'all')
    {
        Horde::logMessage("Get Addresses Called for $domain with type $type and key $key", 'DEBUG');
        $domain = Vilma::stripDomain($address);
        $addresses = $this->getAddresses($domain, $type);
        foreach($addresses as $addrinfo) {
            if ($addrinfo['id'] == $address) {
                return $addrinfo;
            } else if ($addrinfo['address'] == $address) {
                return $addrinfo;
            }
        }
        throw new Vilma_Exception(sprintf(_("No such address %s of type %s found."), $address, $type));
    }

    /**
     * Does a series of checks for a given user to determine the status.
     *
     * @param array $user  The user's details in an array as returned by the
     *                     getUser() function.
     *
     * @return array  Either an array of error messages found during the checks
     *                or an array with a single element stating that the user
     *                is ready.
     */
    function getUserStatus($user)
    {
        /* Some needed vars. */
        $no_error = true;
        $status = array();
        $domain_name = Vilma::stripDomain($user['user_name']);
        $user_name = Vilma::stripUser($user['user_name']);

        /* Check if user enabled. */
        if ($user['user_enabled'] !== 'active') {
            $no_error = false;
            $err_msg = _("User disabled.");
            $status[] = Horde::img('alerts/error.png', $err_msg) . '&nbsp;' . $err_msg;
        }

        /* Check if mailbox exists. */
        $mailboxes = &Vilma::getMailboxDriver();
        if (is_a($mailboxes, 'PEAR_Error')) {
            $no_error = false;
            $err_msg = $mailboxes->getMessage();
            $status[] = Horde::img('alerts/warning.png', $err_msg) . '&nbsp;' . $err_msg;
        }
        try {
            $mailboxes->checkMailbox($user_name, $domain_name);
        } catch (Exception $result) {
            $no_error = false;
            $err_msg = $result->getMessage();
            $status[] = Horde::img('alerts/warning.png', $err_msg) . '&nbsp;' . $err_msg;
        }

        /* TODO: Quota checking would be nice too. */

        /* If no errors have been found output a success message for this
         * user's status. */
        if ($no_error) {
            $msg = _("User ready.");
            $status = array(Horde::img('alerts/success.png', $msg) . '&nbsp;' . $msg);
        }
        return $status;
    }

    /* Saves or creates alias records for a given user.
     *
     * @param array info The info used to store the information.
     *                   Required fields are:
     *                    'address' => The destination address (used for LDAP ID lookup)
     *                    'alias_address'   => The alias to create or the new data for the modified entry
     *                    'alias'  => The alias we are modifying, if we are modifying an existing one.
     */
    function saveAlias(&$info)
    {
        Horde::logMessage("saveAlias called with info: " . print_r($info, true), 'DEBUG');
        $result = $this->_saveAlias($info);
        if (is_a($result, 'PEAR_Error')) {
          return $result;
        }

        return true;
    }

    /* Deletes alias records for a given user.
     *
     * @param array info The info used to store the information.
     *                   Required fields are:
     *                    'address' => The destination address (used for LDAP ID lookup)
     *                    'alias'  => The alias we are deleting.
     */
    function deleteAlias(&$info)
    {
        Horde::logMessage("deleteAlias called with info: " . print_r($info, true), 'DEBUG');
        $result = $this->_deleteAlias($info);
        if (is_a($result, 'PEAR_Error')) {
          return $result;
        }

        return true;
    }

    /* Saves or creates forward records for a given user.
     *
     * @param array info The info used to store the information.
     *                   Required fields are:
     *                    'address' => The destination address (used for LDAP ID lookup)
     *                    'forward_address'   => The forward to create or the new data for the modified entry
     *                    'forward'  => The forward we are modifying, if we are modifying an existing one.
     */
    function saveForward(&$info)
    {
        Horde::logMessage("saveForward called with info: " . print_r($info, true), 'DEBUG');
        $result = $this->_saveForward($info);
        if (is_a($result, 'PEAR_Error')) {
          return $result;
        }

        return true;
    }

    /* Deletes forward records for a given user.
     *
     * @param array info The info used to store the information.
     *                   Required fields are:
     *                    'address' => The destination address (used for LDAP ID lookup)
     *                    'forward'  => The forward we are deleting.
     */
    function deleteForward(&$info)
    {
        Horde::logMessage("deleteForward called with info: " . print_r($info, true), 'DEBUG');
        $result = $this->_deleteForwrd($info);
        if (is_a($result, 'PEAR_Error')) {
          return $result;
        }

        return true;
    }

    function saveUser(&$info)
    {
        $create = false;
        if (empty($info['user_id'])) {
            $create = true;
        }

        $result = $this->_saveUser($info);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        if ($create) {
            $mailboxes = &Vilma::getMailboxDriver();
            if (is_a($mailboxes, 'PEAR_Error')) {
                $this->_deleteUser($result['user_id']);
                return $mailboxes;
            }

            $mailbox = $mailboxes->createMailbox(Vilma::stripUser($info['user_name']), Vilma::stripDomain($info['user_name']));
            if (is_a($mailbox, 'PEAR_Error')) {
                //echo $mailbox->getMessage() . '<br />';
                //No 'system_user' parameter specified to maildrop driver.
                //$this->_deleteUser($result['user_id']);
                return $mailbox;
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
                if (empty($msg)) {
                    $msg = _("Unknown error running authentication update script.");
                }
                throw new Vilma_Exception($msg);
            }
        }

        return true;
    }

    function deleteUser($user_id)
    {
        throw new Vilma_Exception(_("Vilma_Driver::deleteUser(): Method Not Implemented."));
    }

    /**
     * Saves a given domain with the provided information.
     *
     * @param array $info  Array of details to save the domain
     *
     * @return mixed  True on success, or PEAR_Error on failure.
     */
    function saveDomain(&$info)
    {
        $domain_id = $this->_saveDomain($info);
        if (is_a($domain_id, 'PEAR_Error')) {
            return $domain_id;
        }

        $ret = Horde::callHook('_vilma_hook_savedomain', array($info), 'vilma');
        if (!$ret) {
            throw new Vilma_Exception(_("Domain added but an error was encountered while calling the configured hook.  Contact your administrator for futher assistance."));
        }

        return $domain_id;
    }

    /**
     * Saves the domain record.
     *
     * @abstract
     * @param array $info  Information to save the domain
     *
     * @return mixed True on success, or PEAR_Error on failure.
     */
    function _saveDomain(&$info)
    {
        throw new Vilma_Exception(_("Not implemented."));
    }

    /**
     * Deletes a given domain and all the users and virtuals under it.
     *
     * @param integer $domain_id  The id of the domain to delete.
     *
     * @return mixed  True on success, or PEAR_Error on failure.
     */
    function deleteDomain($domain_id)
    {
        $domain_record = $this->getDomain($domain_id);
        if (is_a($domain_record, 'PEAR_Error')) {
            return $domain_record;
        }

        $users = $this->getUsers($domain_record['domain_name']);
        if (is_a($users, 'PEAR_Error')) {
            return $users;
        }

        foreach ($users as $user) {
            $this->_deleteUser($user['user_id']);
        }

        $ret = $this->_deleteDomain($domain_id);
        if (is_a($ret, 'PEAR_Error')) {
            return $ret;
        }

        $ret = Horde::callHook('_vilma_hook_deletedomain',
                               array($domain_record['domain_name']),
                               'vilma');
        if (!$ret) {
            throw new Vilma_Exception(_("Error while calling hook to delete domain."));
        }
    }

    /**
     * Deletes the domain record.
     *
     * @abstract
     * @param integer $domain_id  The ID of the domain to delete.
     *
     * @return mixed True on success, or PEAR_Error on failure.
     */
    function _deleteDomain($domain_id)
    {
        throw new Vilma_Exception(_("Not implemented."));
    }

    /**
     * Get the user who is the domain admin.
     *
     * @todo  This should be replaced by moving all permissions into Horde
     *        permissions.
     */
    function getDomainAdmin($domain_name)
    {
        $domain = $this->getDomainByName($domain_name);
        if (is_a($domain, 'PEAR_Error')) {
            return $domain;
        }
        return $domain['domain_admin'];
    }

    /**
     * Returns the configured quota for this domain.
     *
     * @param string $domain_name  The name of the domain for which to
     *                             return the quota.
     *
     * @return integer  The domain's quota.
     */
    function getDomainQuota($domain_name)
    {
        $domain = $this->getDomainByName($domain_name);
        if (is_a($domain, 'PEAR_Error')) {
            return $domain;
        }
        return $domain['domain_quota'];
    }

    /**
     * Returns the maximum number of users allowed for a given domain.
     *
     * @param string $domain_name  The name of the domain for which to
     *                             return the maximum users.
     *
     * @return integer The maximum number of allowed users or PEAR_Error on
     *                 failure.
     */
    function getDomainMaxUsers($domain_name)
    {
        $domain = $this->getDomainByName($domain_name);
        if (is_a($domain, 'PEAR_Error')) {
            return $domain;
        }
        return $domain['max_users'];
    }

    public function getUserFormAttributes()
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
     * @return Vilma_Driver  The newly created concrete Vilma_Driver instance,
     *                       or false on error.
     * @throws Vilma_Exception
     */
    function factory($driver = null, $params = null)
    {
        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }
        $driver = basename($driver);

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        include_once dirname(__FILE__) . '/Driver/' . $driver . '.php';
        $class = 'Vilma_Driver_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Vilma_Exception(sprintf(_("No such backend \"%s\" found"), $driver));
    }

    /**
     * Attempts to return a reference to a concrete Vilma_Driver instance
     * based on $driver.
     *
     * It will only create a new instance if no Vilma_Driver instance with the
     * same parameters currently exists.
     *
     * This should be used if multiple storage sources are required.
     *
     * This method must be invoked as: $var = &Vilma_Driver::singleton()
     *
     * @param string $driver  The type of concrete Vilma_Driver subclass to
     *                        return.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return mixed  The created concrete Vilma_Driver instance, or false on
     *                error.
     */
    function &singleton($driver = null, $params = null)
    {
        static $instances;

        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        if (!isset($instances)) {
            $instances = array();
        }

        $signature = serialize(array($driver, $params));
        if (!isset($instances[$signature])) {
            $instances[$signature] = Vilma_Driver::factory($driver, $params);
        }

        return $instances[$signature];
    }

}
