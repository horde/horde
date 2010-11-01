<?php
/**
 * The Horde_Auth_Msad class provides an experimental MSAD extension of the
 * LDAP implementation of the Horde authentication system.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @todo Use Horde_Ldap
 *
 * @author   Francois Helly <fhelly@bebop-design.net>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Auth
 */
class Horde_Auth_Msad extends Horde_Auth_Ldap
{
    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     *
     * @throws Horde_Auth_Exception
     */
    public function __construct($params = array())
    {
        $params = array_merge(array(
            'adduser' => true,
            'authId' => 'initials',
            'encryption' => 'msad',
            'newuser_objectclass' => 'user',
            'password_expiration' => 'no',
            'port' => 389,
            'ssl' => false,
            'uid' => array('samaccountname')
        ), $params);

        if (!is_array($params['uid'])) {
            $params['uid'] = array($params['uid']);
        }

        /* Ensure we've been provided with all of the necessary parameters. */
        //Horde::assertDriverConfig($params, 'auth',
        //    array('hostspec', 'basedn'), 'authentication MSAD');

        /* Adjust capabilities: depending on if SSL encryption is
         * enabled or not */
        $this->_capabilities = array(
            'add'           => ($params['ssl'] || $params['adduser']),
            'list'          => true,
            'remove'        => true,
            'resetpassword' => $params['ssl'],
            'update'        => $params['ssl']
        );

        parent::__construct($params);
    }

    /**
     * Add a set of authentication credentials.
     *
     * @param string $accountName  The user sAMAccountName to find.
     * @param array $credentials   The credentials to be set.
     *
     * @throws Horde_Auth_Exception
     */
    public function addUser($accountName, $credentials)
    {
        /* Connect to the MSAD server. */
        $this->_connect();

        if (isset($credentials['ldap'])) {
            $dn = $credentials['ldap']['dn'];
        } else {
            $basedn = isset($credentials['basedn'])
                ? $credentials['basedn']
                : $this->_params['basedn'];

            /* Set a default CN */
            $dn = 'cn=' . $accountName . ',' . $basedn;

            $entry['cn'] = $accountName;
            $entry['samaccountname'] = $accountName;

            $entry['objectclass'][0] = "top";
            $entry['objectclass'][1] = "person";
            $entry['objectclass'][2] = "organizationalPerson";
            $entry['objectclass'][3] = "user";

            $entry['description'] = (isset($credentials['description'])) ?
                $credentials['description'] : 'New horde user';

            if ($this->_params['ssl']) {
                $entry["AccountDisabled"] = false;
            }
            $entry['userPassword'] = Horde_Auth::getCryptedPassword($credentials['password'],'',
                                                               $this->_params['encryption'],
                                                               false);

            if (isset($this->_params['binddn'])) {
                $entry['manager'] = $this->_params['binddn'];
            }

        }

        $success = @ldap_add($this->_ds, $dn, $entry);

        if (!$success) {
           throw new Horde_Auth_Exception(sprintf(__CLASS__ . ': Unable to add user "%s". This is what the server said: ', $accountName) . ldap_error($this->_ds));
        }

        @ldap_close($this->_ds);
    }

    /**
     * Remove a set of authentication credentials.
     *
     * @param string $accountName  The user sAMAccountName to remove.
     * @param string $dn           TODO
     *
     * @throws Horde_Auth_Exception
     */
    public function removeUser($accountName, $dn = null)
    {
        /* Connect to the MSAD server. */
        $this->_connect();

        if (is_null($dn)) {
            /* Search for the user's full DN. */
            $dn = $this->_findDN($accountName);
        }

        if (!@ldap_delete($this->_ds, $dn)) {
            throw new Horde_Auth_Exception(sprintf(__CLASS__ . ': Unable to remove user "%s"', $accountName));
        }
        @ldap_close($this->_ds);

        /* Remove user data */
        Horde_Auth::removeUserData($accountName);
    }

    /**
     * Update a set of authentication credentials.
     *
     * @param string $oldID       The old userId.
     * @param string $newID       The new userId.
     * @param array $credentials  The new credentials
     *
     * @throws Horde_Auth_Exception
     */
    public function updateUser($oldId, $newId, $credentials)
    {
        /* Connect to the MSAD server. */
        $this->_connect();

        if (isset($credentials['ldap'])) {
            $olddn = $credentials['ldap']['dn'];
        } else {
            /* Search for the user's full DN. */
            $dn = $this->_findDN($oldId);

            /* Encrypt the new password */
            if (isset($credentials['password'])) {
                $entry['userpassword'] = Horde_Auth::getCryptedPassword($credentials['password'],'',
                                                                   $this->_params['encryption'],
                                                                   true);
            }
        }

        if ($oldID != $newID) {
            $newdn = str_replace($oldId, $newID, $dn);
            ldap_rename($this->_ds, $olddn, $newdn, $this->_params['basedn'], true);
            $success = @ldap_modify($this->_ds, $newdn, $entry);
        } else {
            $success = @ldap_modify($this->_ds, $olddn, $entry);
        }

        if (!$success) {
            throw new Horde_Auth_Exception(sprintf(__CLASS__ . ': Unable to update user "%s"', $newID));
        }

        @ldap_close($this->_ds);
    }

    /**
     * Reset a user's password. Used for example when the user does not
     * remember the existing password.
     *
     * @param string $user_id  The user id for which to reset the password.
     *
     * @return string  The new password on success.
     * @throws Horde_Auth_Exception
     */
    public function resetPassword($user_id)
    {
        /* Get a new random password. */
        $password = Horde_Auth::genRandomPassword() . '/';
        $this->updateUser($user_id, $user_id, array('userPassword' => $password));

        return $password;
    }

    /**
     * Does an ldap connect and binds as the guest user.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _connect()
    {
        /* Connect to the MSAD server. */
        $ssl = ($this->_params['ssl']) ? 'ldaps://' : '';
        $this->_ds = ldap_connect($ssl . $this->_params['hostspec'], $this->_params['port']);
        if (!$this->_ds) {
            throw new Horde_Auth_Exception('Failed to connect to MSAD server.');
        }

        if ($this->_logger) {
            if (!ldap_set_option($this->_ds, LDAP_OPT_PROTOCOL_VERSION, 3)) {
                $this->_logger->log(sprintf('Set MSAD protocol version to %d failed: [%d] %s', 3, ldap_errno($conn), ldap_error($conn)));
            }
            if (!ldap_set_option($this->_ds, LDAP_OPT_REFERRALS, 0)) {
                $this->_logger->log(sprintf('Set MSAD referrals option to %d failed: [%d] %s', 0, ldap_errno($conn), ldap_error($conn)));
            }
        }

        if (isset($this->_params['binddn'])) {
            $bind = ldap_bind($this->_ds,
                              $this->_params['binddn'],
                              $this->_params['password']);
        } else {
            $bind = ldap_bind($this->_ds);
        }

        if (!$bind) {
            throw new Horde_Auth_Exception('Could not bind to MSAD server.');
        }
    }

    /**
     * Find the user dn
     *
     * @param string $userId  The user UID to find.
     *
     * @return string  The user's full DN
     */
    protected function _findDN($userId)
    {
        /* Search for the user's full DN. */
        foreach ($this->_params['uid'] as $uid) {
            $entries = array($uid);
            if ($uid != $this->_params['authId']) {
                array_push($entries, $this->_params['authId']);
            }
            $search = @ldap_search($this->_ds, $this->_params['basedn'],
                               $uid . '=' . $userId,
                               $entries
                               );
            /* Searching the tree is not successful */
            if (!$search) {
                throw new Horde_Auth_Exception('Could not search the MSAD server.');
            }

            /* Fetch the search result */
            $result = @ldap_get_entries($this->_ds, $search);
            /* The result isn't empty: the DN was found */
            if (is_array($result) && (count($result) > 1)) {
                break;
            }
        }

        if (!is_array($result) || (count($result) <= 1)) {
            throw new Horde_Auth_Exception('Empty result.');
        }

        /* Be sure the horde userId is the configured one */
        $this->_credentials['userId'] = $result[0][$this->_params['authId']][0];

        return $result[0]['dn'];
    }

}
