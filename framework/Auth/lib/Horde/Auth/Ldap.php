<?php
/**
 * The Horde_Auth_Ldap class provides an LDAP implementation of the Horde
 * authentication system.
 *
 * 'preauthenticate' hook should return LDAP connection information in the
 * 'ldap' credentials key.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, http://www.horde.org/licenses/lgpl21
 *
 * @author   Jon Parise <jon@horde.org>
 * @category Horde
 * @license http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package  Auth
 */
class Horde_Auth_Ldap extends Horde_Auth_Base
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
        'resetpassword' => true,
        'remove' => true,
        'list' => true,
        'authenticate' => true,
    );

    /**
     * LDAP object
     *
     * @var Horde_Ldap
     */
    protected $_ldap;

    /**
     * Constructor.
     *
     * @param array $params  Required parameters:
     * <pre>
     * 'basedn' - (string) [REQUIRED] The base DN for the LDAP server.
     * 'filter' - (string) The LDAP formatted search filter to search for
     *            users. This setting overrides the 'objectclass' parameter.
     * 'ldap' - (Horde_Ldap) [REQUIRED] Horde LDAP object.
     * 'objectclass - (string|array): The objectclass filter used to search
     *                for users. Either a single or an array of objectclasses.
     * 'uid' - (string) [REQUIRED] The username search key.
     * </pre>
     *
     * @throws Horde_Auth_Exception
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        foreach (array('basedn', 'ldap', 'uid') as $val) {
            if (!isset($params[$val])) {
                throw new InvalidArgumentException(__CLASS__ . ': Missing ' . $val . ' parameter.');
            }
        }

        if (!empty($this->_params['ad'])) {
            $this->_capabilities['resetpassword'] = false;
        }

        $this->_ldap = $params['ldap'];
        unset($params['ldap']);

        parent::__construct($params);
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
        try {
            $result = $this->_ldap->search(null, '(objectClass=*)', array(
                'attributes' => array(
                    'pwdlastset',
                    'shadowmax',
                    'shadowmin',
                    'shadowlastchange',
                    'shadowwarning',
                    'passwordexpirationtime'
                ),
                'scope' => 'base'
            ));
        } catch (Horde_Ldap_Exception $e) {
            return $lookupshadow;
        }

        if (!$result) {
            return $lookupshadow;
        }

        $info = reset($result);

        // TODO: 'ad'?
        if (!empty($this->_params['ad'])) {
            if (isset($info['pwdlastset'][0])) {
                /* Active Directory handles timestamps a bit differently.
                 * Convert the timestamp to a UNIX timestamp. */
                $lookupshadow['shadowlastchange'] = floor((($info['pwdlastset'][0] / 10000000) - 11644406783) / 86400) - 1;

                /* Password expiry attributes are in a policy. We cannot
                 * read them so use the Horde config. */
                $lookupshadow['shadowwarning'] = $this->_params['warnage'];
                $lookupshadow['shadowmin'] = $this->_params['minage'];
                $lookupshadow['shadowmax'] = $this->_params['maxage'];
            }
        } elseif (isset($info['passwordexpirationtime'][0])) {
            /* Sun/Fedora Directory Server uses a special attribute
             * passwordexpirationtime.  It has precedence over shadow*
             * because it actually locks the expired password at the LDAP
             * server level.  The correct way to check expiration should
             * be using LDAP controls, unfortunately PHP doesn't support
             * controls on bind() responses. */
            $ldaptimepattern = "/([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})Z/";
            if (preg_match($ldaptimepattern, $info['passwordexpirationtime'][0], $regs)) {
                /* Sun/Fedora Directory Server return expiration time, not
                 * last change time. We emulate the behaviour taking it
                 * back to maxage. */
                $lookupshadow['shadowlastchange'] = floor(mktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]) / 86400) - $this->_params['maxage'];

                /* Password expiry attributes are in not accessible policy
                 * entry. */
                $lookupshadow['shadowwarning'] = $this->_params['warnage'];
                $lookupshadow['shadowmin']     = $this->_params['minage'];
                $lookupshadow['shadowmax']     = $this->_params['maxage'];
            } elseif ($this->_logger) {
                $this->_logger->log('Wrong time format: ' . $info['passwordexpirationtime'][0], 'ERR');
            }
        } else {
            if (isset($info['shadowmax'][0])) {
                $lookupshadow['shadowmax'] = $info['shadowmax'][0];
            }
            if (isset($info['shadowmin'][0])) {
                $lookupshadow['shadowmin'] = $info['shadowmin'][0];
            }
            if (isset($info['shadowlastchange'][0])) {
                $lookupshadow['shadowlastchange'] = $info['shadowlastchange'][0];
            }
            if (isset($info['shadowwarning'][0])) {
                $lookupshadow['shadowwarning'] = $info['shadowwarning'][0];
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
     * @throws Horde_Auth_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        /* Search for the user's full DN. */
        $this->_ldap->bind();
        try {
            $dn = $this->_ldap->findUserDN($userId);
        } catch (Horde_Exception_NotFound $e) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        } catch (Horde_Exception_Ldap $e) {
            throw new Horde_Auth_Exception($e->getMessage(), Horde_Auth::REASON_MESSAGE);
        }

        /* Attempt to bind to the LDAP server as the user. */
        try {
            $this->_ldap->bind($dn, $credentials['password']);
        } catch (Horde_Ldap_Exception $e) {
            if (Horde_Ldap::errorName($e->getCode() == 'LDAP_INVALID_CREDENTIALS')) {
                throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
            }
            throw new Horde_Auth_Exception($e->getMessage(), Horde_Auth::REASON_MESSAGE);
        }

        if ($this->_params['password_expiration'] == 'yes') {
            $shadow = $this->_lookupShadow($dn);
            if ($shadow['shadowmax'] && $shadow['shadowlastchange'] &&
                $shadow['shadowwarning']) {
                $today = floor(time() / 86400);
                $toexpire = $shadow['shadowlastchange'] +
                            $shadow['shadowmax'] - $today;

                $warnday = $shadow['shadowlastchange'] + $shadow['shadowmax'] - $shadow['shadowwarning'];
                if ($today >= $warnday) {
                    $this->setCredential('expire', $toexpire);
                }

                if ($toexpire == 0) {
                    $this->setCredential('change', true);
                } elseif ($toexpire < 0) {
                    throw new Horde_Auth_Exception('', Horde_Auth::REASON_EXPIRED);
                }
            }
        }
    }

    /**
     * Add a set of authentication credentials.
     *
     * @param string $userId      The userId to add.
     * @param array $credentials  The credentials to be set.
     *
     * @throws Horde_Auth_Exception
     */
    public function addUser($userId, $credentials)
    {
        if (!empty($this->_params['ad'])) {
            throw new Horde_Auth_Exception(__CLASS__ . ': Adding users is not supported for Active Directory.');
        }

        if (isset($credentials['ldap'])) {
            $entry = $credentials['ldap'];
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

        try {
            $this->_ldap->add(Horde_Ldap_Entry::createFresh($dn, $entry));
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Auth_Exception(sprintf(__CLASS__ . ': Unable to add user "%s". This is what the server said: ', $userId) . $e->getMessage());
        }
    }

    /**
     * Remove a set of authentication credentials.
     *
     * @param string $userId  The userId to add.
     * @param string $dn      TODO
     *
     * @throws Horde_Auth_Exception
     */
    public function removeUser($userId, $dn = null)
    {
        if (!empty($this->_params['ad'])) {
            throw new Horde_Auth_Exception(__CLASS__ . ': Removing users is not supported for Active Directory');
        }

        if (is_null($dn)) {
            /* Search for the user's full DN. */
            try {
                $dn = $this->_ldap->findUserDN($userId);
            } catch (Horde_Exception_Ldap $e) {
                throw new Horde_Auth_Exception($e);
            }
        }

        try {
            $this->_ldap->delete($dn);
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Auth_Exception(sprintf(__CLASS__ . ': Unable to remove user "%s"', $userId));
        }
    }

    /**
     * Update a set of authentication credentials.
     *
     * @todo Clean this up for Horde 5.
     *
     * @param string $oldID       The old userId.
     * @param string $newID       The new userId.
     * @param array $credentials  The new credentials.
     * @param string $olddn       The old user DN.
     * @param string $newdn       The new user DN.
     *
     * @throws Horde_Auth_Exception
     */
    public function updateUser($oldID, $newID, $credentials, $olddn = null,
                               $newdn = null)
    {
        if (!empty($this->_params['ad'])) {
            throw new Horde_Auth_Exception(__CLASS__ . ': Updating users is not supported for Active Directory.');
        }

        if (is_null($olddn)) {
            /* Search for the user's full DN. */
            try {
                $dn = $this->_ldap->findUserDN($oldID);
            } catch (Horde_Exception_Ldap $e) {
                throw new Horde_Auth_Exception($e);
            }

            $olddn = $dn;
            $newdn = preg_replace('/uid=.*?,/', 'uid=' . $newID . ',', $dn, 1);
            $shadow = $this->_lookupShadow($dn);

            /* If shadowmin hasn't yet expired only change when we are
               administrator */
            if ($shadow['shadowlastchange'] &&
                $shadow['shadowmin'] &&
                ($shadow['shadowlastchange'] + $shadow['shadowmin'] > (time() / 86400))) {
                throw new Horde_Auth_Exception('Minimum password age has not yet expired');
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
        } else {
            $entry = $credentials;
            unset($entry['dn']);
        }

        try {
            if ($oldID != $newID) {
                $this->_ldap->move($olddn, $newdn);
                $this->_ldap->modify($newdn, $entry);
            } else {
                $this->_ldap->modify($olddn, $entry);
            }
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Auth_Exception(sprintf(__CLASS__ . ': Unable to update user "%s"', $newID));
        }
    }

    /**
     * Reset a user's password. Used for example when the user does not
     * remember the existing password.
     *
     * @param string $userId  The user id for which to reset the password.
     *
     * @return string  The new password on success.
     * @throws Horde_Auth_Exception
     */
    public function resetPassword($userId)
    {
        if (!empty($this->_params['ad'])) {
            throw new Horde_Auth_Exception(__CLASS__ . ': Updating users is not supported for Active Directory.');
        }

        /* Search for the user's full DN. */
        try {
            $dn = $this->_ldap->findUserDN($userId);
        } catch (Horde_Exception_Ldap $e) {
            throw new Horde_Auth_Exception($e);
        }

        /* Get a new random password. */
        $password = Horde_Auth::genRandomPassword();

        /* Encrypt the new password */
        $entry = array(
            'userpassword' => Horde_Auth::getCryptedPassword($password,
                                                             '',
                                                             $this->_params['encryption'],
                                                             'true'));

        /* Set the lastchange field */
        $shadow = $this->_lookupShadow($dn);
        if ($shadow['shadowlastchange']) {
            $entry['shadowlastchange'] = floor(time() / 86400);
        }

        /* Update user entry. */
        try {
            $this->_ldap->modify($dn, $entry);
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }

        return $password;
    }

    /**
     * List Users
     *
     * @return array  List of Users
     * @throws Horde_Auth_Exception
     */
    public function listUsers($sort = false)
    {
        $params = array(
            'attributes' => array($this->_params['uid']),
            'scope' => $this->_params['scope'],
            'sizelimit' => isset($this->_params['sizelimit']) ? $this->_params['sizelimit'] : 0
        );

        /* Add a sizelimit, if specified. Default is 0, which means no limit.
         * Note: You cannot override a server-side limit with this. */
        $userlist = array();
        try {
            $search = $this->_ldap->search(
                $this->_params['basedn'],
                Horde_Ldap_Filter::build(array('filter' => $this->_params['filter'])),
                $params);
            $uid = Horde_String::lower($this->_params['uid']);
            foreach ($search as $val) {
                $userlist[] = $val->getValue($uid, 'single');
            }
        } catch (Horde_Ldap_Exception $e) {}
        return $this->_sort($userlist, $sort);
    }

    /**
     * Checks if $userId exists in the LDAP backend system.
     *
     * @author Marco Ferrante, University of Genova (I)
     *
     * @param string $userId  User ID for which to check
     *
     * @return boolean  Whether or not $userId already exists.
     */
    public function exists($userId)
    {
        $params = array(
            'scope' => $this->_params['scope']
        );

        try {
            $uidfilter = Horde_Ldap_Filter::create($this->_params['uid'], 'equals', $userId);
            $classfilter = Horde_Ldap_Filter::build(array('filter' => $this->_params['filter']));

            $search = $this->_ldap->search(
                $this->_params['basedn'],
                Horde_Ldap_Filter::combine('and', array($uidfilter, $classfilter)),
                $params);
            if ($search->count() < 1) {
                return false;
            }
            if ($search->count() > 1 && $this->_logger) {
                $this->_logger->log('Multiple LDAP entries with user identifier ' . $userId, 'WARN');
            }
            return true;
        } catch (Horde_Ldap_Exception $e) {
            if ($this->_logger) {
                $this->_logger->log('Error searching LDAP user: ' . $e->getMessage(), 'ERR');
            }
            return false;
        }
    }
}
