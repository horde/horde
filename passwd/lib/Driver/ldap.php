<?php
/**
 * The LDAP class attempts to change a user's password stored in an LDAP
 * directory service.
 *
 * $Horde: passwd/lib/Driver/ldap.php,v 1.41.2.13 2009/11/09 13:31:12 jan Exp $
 *
 * Copyright 2000-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Tjeerd van der Zee <admin@xar.nl>
 * @author  Mattias Webjörn Eriksson <mattias@webjorn.org>
 * @author  Eric Jon Rostetter <eric.rostetter@physics.utexas.edu>
 * @package Passwd
 */
class Passwd_Driver_ldap extends Passwd_Driver {

    /**
     * LDAP connection handle.
     *
     * @var resource
     */
    var $_ds = false;

    /**
     * The user's DN.
     *
     * @var string
     */
    var $_userdn;

    /**
     * Constructs a new Passwd_Driver_ldap object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    function Passwd_Driver_ldap($params = array())
    {
        $this->_params = array_merge(
            array('host' => 'localhost',
                  'port' => 389,
                  'encryption' => 'crypt',
                  'show_encryption' => 'true',
                  'uid' => 'uid',
                  'basedn' => '',
                  'admindn' => '',
                  'adminpw' => '',
                  'realm' => '',
                  'filter' => '',
                  'tls' => false,
                  'attribute' => 'userPassword',
                  'shadowlastchange' => 'shadowLastChange',
                  'shadowmin' => 'shadowMin'),
            $params);

        if (!empty($this->_params['tls']) &&
            empty($this->_params['sslhost'])) {
            $this->_params['sslhost'] = $this->_params['host'];
        }
    }

    /**
     * Does an LDAP connect and binds as the guest user or as the optional
     * userdn.
     *
     * @param string $userdn       The dn to use when binding non-anonymously.
     * @param string $oldpassword  The password for $userdn.
     *
     * @return boolean  True or False based on success of connect and bind.
     */
    function _connect()
    {
        // See if we already have an open connection
        if ($this->_ds) {
            return true;
        }

        if (!empty($this->_params['sslhost']) && empty($this->_params['tls'])) {
            $this->_ds = ldap_connect('ldaps://' . $this->_params['sslhost']);
        } else {
            $this->_ds = ldap_connect($this->_params['host'], $this->_params['port']);
        }
        if (!$this->_ds) {
            return PEAR::raiseError(_("Could not connect to LDAP server"));
        }

        if (ldap_set_option($this->_ds, LDAP_OPT_PROTOCOL_VERSION, 3) &&
            $this->_params['tls']) {
            if (!ldap_start_tls($this->_ds)) {
                return PEAR::raiseError(_("Could not start TLS connection to LDAP server"));
            }
        }

        if (!empty($this->_params['referrals'])) {
            if (!ldap_set_option($this->_ds, LDAP_OPT_REFERRALS, $this->_params['referrals'])) {
                return PEAR::raiseError(_("Unable to disable directory referrals"));
            }
        }

        return true;
    }


    /**
     * Bind (or re-bind) to an LDAP server with the given credentials.
     *
     * @param string $userdn    Bind DN
     * @param string $password  Bind password
     *
     * @return mixed            True on success; PEAR_Error on error
     */
    function _bind($userdn = '', $password = '')
    {
        $result = false;
        // Try to bind as the current userdn with password.
         if (!empty($userdn)) {
            $result = @ldap_bind($this->_ds, $userdn, $password);
        } else {
            $result = @ldap_bind($this->_ds);
        }

        // If none of the bind attempts succeed, return error.
        if (!$result) {
            return PEAR::raiseError(_("Could not bind to LDAP server"));
        }
    }

    /**
     * Changes the user's password.
     *
     * @param string $username      The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @return boolean  True or PEAR_Error based on success of the change.
     */
    function changePassword($username, $old_password, $new_password)
    {
        // See if the old password matches before allowing the change
        if ($old_password !== Auth::getCredential('password')) {
            return PEAR::raiseError(_("Incorrect old password."));
        }

        // Bind as current user. _connect will try as guest if no user realm
        // is found or auth error.
        $result = $this->_connect();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        // Append realm as username@realm if 'realm' parameter is set.
        if (!empty($this->_params['realm'])) {
            $username .= '@' . $this->_params['realm'];
        }

        // Get the user's dn.
        try {
            $this->_userdn = Horde::callHook('userdn', array($username), 'passwd');
        } catch (Horde_Exception_HookNotSet $e) {
            $this->_userdn = $this->_lookupdn($username, $old_password);
            if ($this->_userdn instanceof PEAR_Error) {
                return $this->_userdn;
            }
        }

        // Connect as the admin DN if configured; otherwise as the user
        if (!empty($this->_params['admindn'])) {
            $result = $this->_bind($this->_params['admindn'],
                                   $this->_params['adminpw']);
        } else {
            $result = $this->_bind($this->_userdn, $old_password);
        }

        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        // Get existing user information
        $result = ldap_read($this->_ds, $this->_userdn, 'objectClass=*');
        $entry = ldap_first_entry($this->_ds, $result);
        if ($entry === false) {
            return PEAR::raiseError(_("User not found."));
        }

        // Init the shadow policy array
        $lookupshadow = array('shadowlastchange' => false,
                              'shadowmin' => false);

        $information = @ldap_get_values($this->_ds, $entry,
                                        $this->_params['shadowlastchange']);
        if ($information) {
            $lookupshadow['shadowlastchange'] = $information[0];
        }

        $information = @ldap_get_values($this->_ds, $entry,
                                        $this->_params['shadowmin']);
        if ($information) {
            $lookupshadow['shadowmin'] = $information[0];
        }

        // Check if we may change the password
        if ($lookupshadow['shadowlastchange'] &&
            $lookupshadow['shadowmin'] &&
            ($lookupshadow['shadowlastchange'] + $lookupshadow['shadowmin'] > (time() / 86400))) {
            return PEAR::raiseError(_("Minimum password age has not yet expired"));
        }

        // Change the user's password and update lastchange
        $new_details[$this->_params['attribute']] = $this->encryptPassword($new_password);

        if (!empty($this->_params['shadowlastchange']) &&
            $lookupshadow['shadowlastchange']) {
            $new_details[$this->_params['shadowlastchange']] = floor(time() / 86400);
        }

        if (!@ldap_mod_replace($this->_ds, $this->_userdn, $new_details)) {
            return PEAR::raiseError(ldap_error($this->_ds));
        }

        // Update the stored credential within the session
        $GLOBALS['registry']->setAuthCredential('password', $new_password);

        return true;
    }

    /**
     * Looks up and returns the user's dn.
     *
     * @param string $user    The username of the user.
     * @param string $passw   The password of the user.
     *
     * @return string  The ldap dn for the user.
     */
    function _lookupdn($user, $passw)
    {
        // Search as an admin if so configured
        if (!empty($this->_params['admindn'])) {
            $this->_bind($this->_params['admindn'], $this->_params['adminpw']);
        } else {
            $this->_bind();
        }

        // Construct search.
        $search = '(' . $this->_params['uid'] . '=' . $user . ')';

        if (!empty($this->_params['filter'])) {
            $search = '(&' . $search . '(' .  $this->_params['filter'] . '))';
        }

        // Get userdn.
        $result = ldap_search($this->_ds, $this->_params['basedn'], $search);
        $entry = ldap_first_entry($this->_ds, $result);
        if ($entry === false) {
            return PEAR::raiseError(_("User not found."));
        }

        // If we used admin bindings, we have to check the password here.
        if (!empty($this->_params['admindn'])) {
            $ldappasswd = ldap_get_values($this->_ds, $entry,
                                          $this->_params['attribute']);
            $result = $this->comparePasswords($ldappasswd[0], $passw);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
        }

        return ldap_get_dn($this->_ds, $entry);
    }

}
