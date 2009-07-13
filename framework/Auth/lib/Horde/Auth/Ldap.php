<?php
/**
 * The Horde_Auth_Ldap class provides an LDAP implementation of the Horde
 * authentication system.
 *
 * Required parameters:
 * <pre>
 * 'basedn'       The base DN for the LDAP server.
 * 'hostspec'     The hostname of the LDAP server.
 * 'uid'          The username search key.
 * 'filter'       The LDAP formatted search filter to search for users. This
 *                setting overrides the 'objectclass' method below.
 * 'objectclass'  The objectclass filter used to search for users. Can be a
 *                single objectclass or an array.
 * </pre>
 *
 * Optional parameters:
 * <pre>
 * 'binddn'       The DN used to bind to the LDAP server
 * 'password'     The password used to bind to the LDAP server
 * 'version'      The version of the LDAP protocol to use.
 *                DEFAULT: NONE (system default will be used)
 * </pre>
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author  Jon Parise <jon@horde.org>
 * @package Horde_Auth
 */
class Horde_Auth_Ldap extends Horde_Auth_Driver
{
    /**
     * An array of capabilities, so that the driver can report which
     * operations it supports and which it doesn't.
     *
     * @var array
     */
    protected $_capabilities = array(
        'add' => true,
        'update' => true,
        'remove' => true,
        'list' => true
    );

    /**
     * LDAP connection handle.
     *
     * @var resource
     */
    protected $_ds;

    /**
     * Construct.
     *
     * @param array $params  A hash containing connection parameters.
     *
     * @throws Horde_Exception
     */
    public function __construct($params = array())
    {
        if (!Horde_Util::extensionExists('ldap')) {
            throw new Horde_Exception(_("Horde_Auth_Ldap: Required LDAP extension not found."));
        }

        /* Ensure we've been provided with all of the necessary parameters. */
        Horde::assertDriverConfig($params, 'auth',
                                  array('hostspec', 'basedn', 'uid'),
                                  'authentication LDAP');

        parent::__construct($params);
    }

    /**
     * Does an ldap connect and binds as the guest user or as the optional dn.
     *
     * @throws Horde_Exception
     */
    protected function _connect()
    {
        /* Connect to the LDAP server. */
        $this->_ds = @ldap_connect($this->_params['hostspec']);
        if (!$this->_ds) {
            throw new Horde_Exception(_("Failed to connect to LDAP server."));
        }

        if (isset($this->_params['version'])) {
            if (!ldap_set_option($this->_ds, LDAP_OPT_PROTOCOL_VERSION,
                                 $this->_params['version'])) {
                Horde::logMessage(
                    sprintf('Set LDAP protocol version to %d failed: [%d] %s',
                            $this->_params['version'],
                            @ldap_errno($this->_ds),
                            @ldap_error($this->_ds)),
                    __FILE__, __LINE__, PEAR_LOG_ERR);
            }
        }

        /* Start TLS if we're using it. */
        if (!empty($this->_params['tls'])) {
            if (!@ldap_start_tls($this->_ds)) {
                Horde::logMessage(
                    sprintf('STARTTLS failed: [%d] %s',
                            @ldap_errno($this->_ds),
                            @ldap_error($this->_ds)),
                    __FILE__, __LINE__, PEAR_LOG_ERR);
            }
        }

        /* Work around Active Directory quirk. */
        if (!empty($this->_params['ad'])) {
            if (!ldap_set_option($this->_ds, LDAP_OPT_REFERRALS, false)) {
                Horde::logMessage(
                    sprintf('Unable to disable directory referrals on this connection to Active Directory: [%d] %s',
                            @ldap_errno($this->_ds),
                            @ldap_error($this->_ds)),
                    __FILE__, __LINE__, PEAR_LOG_ERR);
            }
        }

        $bind = isset($this->_params['binddn'])
            ? @ldap_bind($this->_ds, $this->_params['binddn'], $this->_params['password'])
            : @ldap_bind($this->_ds);

        if (!$bind) {
            throw new Horde_Exception(_("Could not bind to LDAP server."));
        }
    }

    /**
     * Find the user dn
     *
     * @param string $userId  The userId to find.
     *
     * @return string  The users full DN
     * @throws Horde_Exception
     */
    protected function _findDN($userId)
    {
        /* Search for the user's full DN. */
        $filter = $this->_getParamFilter();
        $filter = '(&(' . $this->_params['uid'] . '=' . $userId . ')' .
                  $filter . ')';

        $func = ($this->_params['scope'] == 'one')
            ? 'ldap_list'
            : 'ldap_search';

        $search = @$func($this->_ds, $this->_params['basedn'], $filter,
                         array($this->_params['uid']));
        if (!$search) {
            Horde::logMessage(ldap_error($this->_ds), __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Exception(_("Could not search the LDAP server."));
        }

        $result = @ldap_get_entries($this->_ds, $search);
        if (is_array($result) && (count($result) > 1)) {
            $dn = $result[0]['dn'];
        } else {
            throw new Horde_Exception(_("Empty result."));
        }

        return $dn;
    }

    /**
     * Checks for shadowLastChange and shadowMin/Max support and returns their
     * values.  We will also check for pwdLastSet if Active Directory is
     * support is requested.  For this check to succeed we need to be bound
     * to the directory.
     *
     * @param string $dn  The dn of the user.
     *
     * @return array  Array with keys being "shadowlastchange", "shadowmin"
     *                "shadowmax", "shadowwarning" and containing their
     *                respective values or false for no support.
     */
    protected function _lookupShadow($dn)
    {
        /* Init the return array. */
        $lookupshadow = array(
            'shadowlastchange' => false,
            'shadowmin' => false,
            'shadowmax' => false,
            'shadowwarning' => false
        );

        /* According to LDAP standard, to read operational attributes, you
         * must request them explicitly. Attributes involved in password
         * expiration policy:
         *    pwdlastset: Active Directory
         *    shadow*: shadowUser schema
         *    passwordexpirationtime: Sun and Fedora Directory Server */
        $result = @ldap_read($this->_ds, $dn, '(objectClass=*)',
                             array('pwdlastset', 'shadowmax', 'shadowmin',
                                   'shadowlastchange', 'shadowwarning',
                                   'passwordexpirationtime'));
        if ($result) {
            $information = @ldap_get_entries($this->_ds, $result);

            if ($this->_params['ad']) {
                if (isset($information[0]['pwdlastset'][0])) {
                    /* Active Directory handles timestamps a bit differently.
                     * Convert the timestamp to a UNIX timestamp. */
                    $lookupshadow['shadowlastchange'] = floor((($information[0]['pwdlastset'][0] / 10000000) - 11644406783) / 86400) - 1;

                    /* Password expiry attributes are in a policy. We cannot
                     * read them so use the Horde config. */
                    $lookupshadow['shadowwarning'] = $this->_params['warnage'];
                    $lookupshadow['shadowmin'] = $this->_params['minage'];
                    $lookupshadow['shadowmax'] = $this->_params['maxage'];
                }
            } elseif (isset($information[0]['passwordexpirationtime'][0])) {
                /* Sun/Fedora Directory Server uses a special attribute
                 * passwordexpirationtime.  It has precedence over shadow*
                 * because it actually locks the expired password at the LDAP
                 * server level.  The correct way to check expiration should
                 * be using LDAP controls, unfortunately PHP doesn't support
                 * controls on bind() responses. */
                $ldaptimepattern = "/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})Z/";
                if (preg_match($ldaptimepattern, $information[0]['passwordexpirationtime'][0], $regs)) {
                    /* Sun/Fedora Directory Server return expiration time, not
                     * last change time. We emulate the behaviour taking it
                     * back to maxage. */
                    $lookupshadow['shadowlastchange'] = floor(mktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]) / 86400) - $this->_params['maxage'];

                    /* Password expiry attributes are in not accessible policy
                     * entry. */
                    $lookupshadow['shadowwarning'] = $this->_params['warnage'];
                    $lookupshadow['shadowmin']     = $this->_params['minage'];
                    $lookupshadow['shadowmax']     = $this->_params['maxage'];
                } else {
                    Horde::logMessage('Wrong time format: ' . $information[0]['passwordexpirationtime'][0], __FILE__, __LINE__, PEAR_LOG_ERR);
                }
            } else {
                if (isset($information[0]['shadowmax'][0])) {
                    $lookupshadow['shadowmax'] =
                        $information[0]['shadowmax'][0];
                }
                if (isset($information[0]['shadowmin'][0])) {
                    $lookupshadow['shadowmin'] =
                        $information[0]['shadowmin'][0];
                }
                if (isset($information[0]['shadowlastchange'][0])) {
                    $lookupshadow['shadowlastchange'] =
                        $information[0]['shadowlastchange'][0];
                }
                if (isset($information[0]['shadowwarning'][0])) {
                    $lookupshadow['shadowwarning'] =
                        $information[0]['shadowwarning'][0];
                }
            }
        }

        return $lookupshadow;
    }

    /**
     * Find out if the given set of login credentials are valid.
     *
     * @param string $userId       The userId to check.
     * @param array  $credentials  An array of login credentials.
     *
     * @throws Horde_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        /* Connect to the LDAP server. */
        $this->_connect();

        /* Search for the user's full DN. */
        $dn = $this->_findDN($userId);

        /* Attempt to bind to the LDAP server as the user. */
        $bind = @ldap_bind($this->_ds, $dn, $credentials['password']);
        if ($bind == false) {
            @ldap_close($this->_ds);
            throw new Horde_Exception('', Horde_Auth::REASON_FAILED);
        }

        if ($this->_params['password_expiration'] == 'yes') {
            $shadow = $this->_lookupShadow($dn);
            if ($shadow['shadowmax'] && $shadow['shadowlastchange'] &&
                $shadow['shadowwarning']) {
                $today = floor(time() / 86400);
                $warnday = $shadow['shadowlastchange'] +
                           $shadow['shadowmax'] - $shadow['shadowwarning'];
                $toexpire = $shadow['shadowlastchange'] +
                            $shadow['shadowmax'] - $today;

                if ($today >= $warnday) {
                    $GLOBALS['notification']->push(sprintf(ngettext("%d day until your password expires.", "%d days until your password expires.", $toexpire), $toexpire), 'horde.warning');
                }

                if ($toexpire == 0) {
                    $this->_authCredentials['changeRequested'] = true;
                } elseif ($toexpire < 0) {
                    throw new Horde_Exception('', Horde_Auth::REASON_EXPIRED);
                }
            }
        }

        @ldap_close($this->_ds);
    }

    /**
     * Add a set of authentication credentials.
     *
     * @param string $userId      The userId to add.
     * @param array $credentials  The credentials to be set.
     *
     * @throws Horde_Exception
     */
    public function addUser($userId, $credentials)
    {
        if ($this->_params['ad']) {
            throw new Horde_Exception(_("Horde_Auth_Ldap: Adding users is not supported for Active Directory"));
        }

        /* Connect to the LDAP server. */
        $this->_connect();

        global $conf;
        if (!empty($conf['hooks']['authldap'])) {
            $entry = Horde::callHook('_horde_hook_authldap', array($userId, $credentials));
            $dn = $entry['dn'];
            /* Remove the dn entry from the array. */
            unset($entry['dn']);
        } else {
            /* Try this simple default and hope it works. */
            $dn = $this->_params['uid'] . '=' . $userId . ','
                . $this->_params['basedn'];
            $entry['cn'] = $userId;
            $entry['sn'] = $userId;
            $entry[$this->_params['uid']] = $userId;
            $entry['objectclass'] = array_merge(
                array('top'),
                $this->_params['newuser_objectclass']);
            $entry['userPassword'] = Horde_Auth::getCryptedPassword(
                $credentials['password'], '',
                $this->_params['encryption'],
                'true');

            if ($this->_params['password_expiration'] == 'yes') {
                $entry['shadowMin'] = $this->_params['minage'];
                $entry['shadowMax'] = $this->_params['maxage'];
                $entry['shadowWarning'] = $this->_params['warnage'];
                $entry['shadowLastChange'] = floor(time() / 86400);
            }
        }
        $result = @ldap_add($this->_ds, $dn, $entry);

        if (!$result) {
           throw new Horde_Exception(sprintf(_("Horde_Auth_Ldap: Unable to add user \"%s\". This is what the server said: "), $userId) . @ldap_error($this->_ds));
        }

        @ldap_close($this->_ds);
    }

    /**
     * Remove a set of authentication credentials.
     *
     * @param string $userId  The userId to add.
     *
     * @throws Horde_Exception
     */
    public function removeUser($userId)
    {
        if ($this->_params['ad']) {
           throw new Horde_Exception(_("Horde_Auth_Ldap: Removing users is not supported for Active Directory"));
        }

        /* Connect to the LDAP server. */
        $this->_connect();

        if (!empty($GLOBALS['conf']['hooks']['authldap'])) {
            $entry = Horde::callHook('_horde_hook_authldap', array($userId));
            $dn = $entry['dn'];
        } else {
            /* Search for the user's full DN. */
            $dn = $this->_findDN($userId);
        }

        $result = @ldap_delete($this->_ds, $dn);
        if (!$result) {
           throw new Horde_Exception(sprintf(_("Auth_ldap: Unable to remove user \"%s\""), $userId));
        }

        @ldap_close($this->_ds);

        Horde_Auth::removeUserData($userId);
    }

    /**
     * Update a set of authentication credentials.
     *
     * @param string $oldID       The old userId.
     * @param string $newID       The new userId.
     * @param array $credentials  The new credentials
     *
     * @throws Horde_Exception
     */
    public function updateUser($oldID, $newID, $credentials)
    {
        if ($this->_params['ad']) {
           throw new Horde_Exception(_("Horde_Auth_Ldap: Updating users is not supported for Active Directory."));
        }

        /* Connect to the LDAP server. */
        $this->_connect();

        if (!empty($GLOBLS['conf']['hooks']['authldap'])) {
            $entry = Horde::callHook('_horde_hook_authldap', array($oldID, $credentials));
            $olddn = $entry['dn'];
            $entry = Horde::callHook('_horde_hook_authldap', array($newID, $credentials));
            $newdn = $entry['dn'];
            unset($entry['dn']);
        } else {
            /* Search for the user's full DN. */
            $dn = $this->_findDN($oldID);

            $olddn = $dn;
            $newdn = preg_replace('/uid=.*?,/', 'uid=' . $newID . ',', $dn, 1);
            $shadow = $this->_lookupShadow($dn);

            /* If shadowmin hasn't yet expired only change when we are
               administrator */
            if ($shadow['shadowlastchange'] &&
                $shadow['shadowmin'] &&
                ($shadow['shadowlastchange'] + $shadow['shadowmin'] > (time() / 86400))) {
                throw new Horde_Exception(_("Minimum password age has not yet expired"));
            }

            /* Set the lastchange field */
            if ($shadow['shadowlastchange']) {
                $entry['shadowlastchange'] =  floor(time() / 86400);
            }

            /* Encrypt the new password */
            $entry['userpassword'] = Horde_Auth::getCryptedPassword(
                $credentials['password'], '',
                $this->_params['encryption'],
                'true');
        }

        if ($oldID != $newID) {
            if (LDAP_OPT_PROTOCOL_VERSION == 3) {
                ldap_rename($this->_ds, $olddn, $newdn,
                            $this->_params['basedn'], true);

                $result = ldap_modify($this->_ds, $newdn, $entry);
            } else {
                /* Get the complete old record first */
                $result = @ldap_read($this->_ds, $olddn, 'objectClass=*');

                if ($result) {
                    $information = @ldap_get_entries($this->_ds, $result);

                    /* Remove the count elements from the array */
                    $counter = 0;
                    $newrecord = array();
                    while (isset($information[0][$counter])) {
                        if ($information[0][$information[0][$counter]]['count'] == 1) {
                            $newrecord[$information[0][$counter]] = $information[0][$information[0][$counter]][0];
                        } else {
                            $newrecord[$information[0][$counter]] = $information[0][$information[0][$counter]];
                            unset($newrecord[$information[0][$counter]]['count']);
                        }
                        $counter++;
                    }

                    /* Adjust the changed parameters */
                    unset($newrecord['dn']);
                    $newrecord[$this->_params['uid']] = $newID;
                    $newrecord['userpassword'] = $entry['userpassword'];
                    if (isset($entry['shadowlastchange'])) {
                        $newrecord['shadowlastchange'] = $entry['shadowlastchange'];
                    }

                    $result = ldap_add($this->_ds, $newdn, $newrecord);
                    if ($result) {
                        $result = @ldap_delete($this->_ds, $olddn);
                    }
                }
            }
        } else {
            $result = @ldap_modify($this->_ds, $olddn, $entry);
        }

        if (!$result) {
            throw new Horde_Exception(sprintf(_("Horde_Auth_Ldap: Unable to update user \"%s\""), $newID));
        }

        @ldap_close($this->_ds);
    }

    /**
     * List Users
     *
     * @return array  List of Users
     * @throws Horde_Exception
     */
    public function listUsers()
    {
        /* Connect to the LDAP server. */
        $this->_connect();

        $filter = $this->_getParamFilter();

        $func = ($this->_params['scope'] == 'one')
            ? 'ldap_list'
            : 'ldap_search';

        /* Add a sizelimit, if specified. Default is 0, which means no limit.
         * Note: You cannot override a server-side limit with this. */
        $sizelimit = isset($this->_params['sizelimit']) ? $this->_params['sizelimit'] : 0;
        $search = @$func($this->_ds, $this->_params['basedn'], $filter,
                         array($this->_params['uid']), 0, $sizelimit);

        $entries = @ldap_get_entries($this->_ds, $search);
        $userlist = array();
        $uid = Horde_String::lower($this->_params['uid']);
        for ($i = 0; $i < $entries['count']; $i++) {
            $userlist[$i] = $entries[$i][$uid][0];
        }

        return $userlist;
    }

    /**
     * Return a formatted LDAP filter as configured within the parameters.
     *
     * @return string  LDAP search filter
     */
    protected function _getParamFilter()
    {
        if (!empty($this->_params['filter'])) {
            $filter = $this->_params['filter'];
        } elseif (!is_array($this->_params['objectclass'])) {
            $filter = 'objectclass=' . $this->_params['objectclass'];
        } else {
            $filter = '';
            if (count($this->_params['objectclass']) > 1) {
                $filter = '(&' . $filter;
                foreach ($this->_params['objectclass'] as $objectclass) {
                    $filter .= '(objectclass=' . $objectclass . ')';
                }
                $filter .= ')';
            } elseif (count($this->_params['objectclass']) == 1) {
                $filter = '(objectClass=' . $this->_params['objectclass'][0] . ')';
            }
        }
        return $filter;
    }
}
