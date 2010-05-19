<?php
/**
 * @package Passwd
 *
 * $Horde: passwd/lib/Driver/smbldap.php,v 1.7.2.7 2009/11/09 13:31:12 jan Exp $
 */

/** Passwd_Driver_ldap */
require_once dirname(__FILE__) . '/ldap.php';

/**
 * The LDAP class attempts to change a user's LDAP password and Samba password
 * stored in an LDAP directory service.
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Shane Boulter <sboulter@ariasolutions.com>
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Tjeerd van der Zee <admin@xar.nl>
 * @author  Mattias Webjörn Eriksson <mattias@webjorn.org>
 * @author  Eric Jon Rostetter <eric.rostetter@physics.utexas.edu>
 * @since   Passwd 3.0
 * @package Passwd
 */
class Passwd_Driver_smbldap extends Passwd_Driver_ldap {

    /**
     * Constructs a new Passwd_Driver_smbldap object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    function Passwd_Driver_smbldap($params = array())
    {
        $params = array_merge(array('lm_attribute' => null,
                                    'nt_attribute' => null,
                                    'pw_set_attribute' => null,
                                    'pw_expire_attribute' => null,
                                    'pw_expire_time' => null,
                                    'smb_objectclass' => 'sambaSamAccount'),
                              $params);
        parent::Passwd_Driver_ldap($params);
    }

    /**
     * Change the user's password.
     *
     * @param string $username      The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @return boolean  True or false based on success of the change.
     */
    function changePassword($username, $old_password, $new_password)
    {
        $result = parent::changePassword($username, $old_password, $new_password);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        // Return success if the user is not a Samba user
        if (!@ldap_compare($this->_ds, $this->_userdn, 'objectClass', $this->_params['smb_objectclass'])) {
            return true;
        }

        require_once 'Crypt/CHAP.php';
        $hash = new Crypt_CHAP_MSv2();
        $hash->password = $new_password;
        $lmpasswd = strtoupper(bin2hex($hash->lmPasswordHash()));
        $ntpasswd = strtoupper(bin2hex($hash->ntPasswordHash()));
        $settime = time();

        if (!is_null($this->_params['pw_expire_time'])) {
            // 24 hours/day * 60 min/hour * 60 secs/min = 86400 seconds/day
            $expiretime = $settime + ($this->_params['pw_expire_time'] * 86400);
        } else {
            // This is NT's version of infinity time:
            // http://lists.samba.org/archive/samba/2004-January/078175.html
            $expiretime = 2147483647;
        }

        // All changes must succeed or fail together.  Attributes with
        // null name are not updated.
        $changes = array();
        if (!is_null($this->_params['lm_attribute'])) {
            $changes[$this->_params['lm_attribute']] = $lmpasswd;
        }
        if (!is_null($this->_params['nt_attribute'])) {
            $changes[$this->_params['nt_attribute']] = $ntpasswd;
        }
        if (!is_null($this->_params['pw_set_attribute'])) {
            $changes[$this->_params['pw_set_attribute']] = $settime;
        }
        if (!is_null($this->_params['pw_expire_attribute'])) {
            $changes[$this->_params['pw_expire_attribute']] = $expiretime;
        }

        if (count($changes) > 0) {
            if (!ldap_mod_replace($this->_ds, $this->_userdn, $changes)) {
                return PEAR::raiseError(ldap_error($this->_ds));
            }
        }

        return true;
    }

}
