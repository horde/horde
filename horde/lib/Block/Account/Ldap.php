<?php
/**
 * The ldap class attempts to return user information stored in an ldap
 * directory service.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Eric Jon Rostetter <eric.rostetter@physics.utexas.edu>
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde
 */
class Horde_Block_Account_Ldap extends Horde_Block_Account_Base
{
    /**
     * Pointer to the LDAP connection.
     *
     * @var Horde_Ldap
     */
    protected $_ldap;

    /**
     * User information hash.
     *
     * @var array
     */
    protected $_information;

    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        $this->_ldap = $params['ldap'];
        unset($params['ldap']);
        parent::__construct($params);
    }

    /**
     * Returns the win32 AD epoch number of days the password may be unchanged.
     *
     * @return integer|boolean  Number of days or false if no limit.
     */
    protected function _getMaxPasswd()
    {
        $dn = Horde_Ldap_Util::explodeDN($this->_params['basedn']);
        $domaindn = array();
        foreach ($dn as $rdn) {
            $attribute = Horde_Ldap_Util::splitAttributeString($rdn);
            if ($attribute[0] == 'DC') {
                $domaindn[] = $rdn;
            }
        }
        $dn = Horde_Ldap_Util::canonicalDN($domaindn);

        $search = $this->_ldap->search($domaindn, 'objectClass=*');
        $entry = $search->shiftEntry();
        try {
            return $entry->getValue('maxPwdAge', 'single');
        } catch (Horde_Ldap_Exception $e) {
            return false;
        }
    }

    /**
     * Code from 'brudinie at yahoo dot co dot uk' at http://nl3.php.net/ldap/
     *
     * @param integer $dateLargeInt  The win32 active directory epoch time.
     *
     * @return integer  A unix timestamp.
     */
    protected function _convertWinTimeToUnix($dateLargeInt)
    {
        // Seconds since jan 1st 1601.
        $secsAfterADEpoch = $dateLargeInt / (10000000);

        // Unix epoch - AD epoch * number of tropical days * seconds in a day.
        $ADToUnixConvertor = ((1970 - 1601) * 365.242190) * 86400;

        return intval($secsAfterADEpoch - $ADToUnixConvertor);
    }

    /**
     * Returns the user account from the LDAP source.
     *
     * @return Horde_Ldap_Entry  An entry with complete account details.
     *
     * @throws Horde_Exception if user not found.
     * @throws Horde_Ldap_Exception on LDAP errors.
     */
    protected function _getAccount()
    {
        if (!isset($this->_information)) {
            $search = $this->_ldap->search($this->_params['basedn'],
                                           $this->_params['attr'] . '=' . $this->_params['user']);
            if (!$search->count()) {
                throw new Horde_Exception(_("User account not found"));
            }
            $this->_information = $search->shiftEntry();
        }
        return $this->_information;
    }

    /**
     * Returns the user's full name.
     *
     * @return string  The user's full name.
     *
     * @throws Horde_Exception if user not found.
     * @throws Horde_Ldap_Exception on LDAP errors.
     */
    public function getFullname()
    {
        $information = $this->_getAccount();
        try {
            return $information->getValue('cn', 'single');
        } catch (Horde_Ldap_Exception $e) {
            return '';
        }
    }

    /**
     * Returns the user's home (login) directory.
     *
     * @return string  The user's directory.
     *
     * @throws Horde_Exception if user not found.
     * @throws Horde_Ldap_Exception on LDAP errors.
     */
    public function getHome()
    {
        $information = $this->_getAccount();
        try {
            return $information->getValue('homedirectory', 'single');
        } catch (Horde_Ldap_Exception $e) {
            return '';
        }
    }

    /**
     * Returns the user's default shell.
     *
     * @return string  The user's shell.
     *
     * @throws Horde_Exception if user not found.
     * @throws Horde_Ldap_Exception on LDAP errors.
     */
    public function getShell()
    {
        $information = $this->_getAccount();
        try {
            return $information->getValue('useraccountcontrol', 'single');
        } catch (Horde_Ldap_Exception $e) {
        }
        try {
            return $information->getValue('loginshell', 'single');
        } catch (Horde_Ldap_Exception $e) {
            return '';
        }
    }

    /**
     * Returns the date of the user's last password change.
     *
     * @return string  Date string.
     *
     * @throws Horde_Exception if user not found.
     * @throws Horde_Ldap_Exception on LDAP errors.
     */
    public function getPasswordChange()
    {
        $information = $this->_getAccount();
        try {
            return strftime('%x', $information->getValue('shadowlastchange', 'single') * 86400);
        } catch (Horde_Ldap_Exception $e) {
        }
        try {
            return strftime('%x', $this->_convertWinTimeToUnix($information->getValue('pwdlastset', 'single')));
        } catch (Horde_Ldap_Exception $e) {
            return '';
        }
    }

    /**
     * Returns the status of the current password.
     *
     * @return string  A string with a warning message if the password is about
     *                 to expire.
     *
     * @throws Horde_Exception if user not found.
     * @throws Horde_Ldap_Exception on LDAP errors.
     */
    public function checkPasswordStatus()
    {
        $information = $this->_getAccount();

        // Active Directory.
        try {
            $accountControl = $information->getValue('useraccountcontrol', 'single');
            $pwdlastset     = $information->getValue('pwdlastset', 'single');
            $accountControl = $information[0]['useraccountcontrol'][0];
            if (($accountControl & 65536) != 0) {
                // ADS_UF_DONT_EXPIRE_PASSWD
                return '';
            }
            if (($accountControl & 524288) != 0) {
                // ADS_UF_PASSWORD_EXPIRED
                return _("Your password has expired");
            }

            $maxdays = $this->_getMaxPasswd();
            if ($maxdays === false) {
                return '';
            }

            $today = time();
            $lastset = $pwdlastset - $maxdays;
            $toexpire = floor(($this->_convertWinTimeToUnix($lastset) - $today) / 86400);
            if ($toexpire < 0) {
                return _("Your password has expired");
            }
            if ($toexpire < 14) {
                // Two weeks.
                return sprintf(_("%d days until your password expires."), $toexpire);
            }
        } catch (Horde_Ldap_Exception $e) {
        }

        // OpenLDAP.
        try {
            $shadowmax        = $information->getValue('shadowmax', 'single');
            $shadowlastchange = $information->getValue('shadowlastchange', 'single');
            $shadowwarning    = $information->getValue('shadowwarning', 'single');
            $today = floor(time() / 86400);
            $warnday = $shadowlastchange + $shadowmax - $shadowwarning;
            $toexpire = $shadowlastchange + $shadowmax - $today;

            if ($today >= $warnday) {
                return sprintf(_("%d days until your password expires."), $toexpire);
            }
        } catch (Horde_Ldap_Exception $e) {
        }

        return '';
    }
}
