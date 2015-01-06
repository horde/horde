<?php
/**
 * Copyright 2004-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2004-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */

/**
 * Changes an LDAP password and Samba password stored in an LDAP directory
 * service.
 *
 * @author    Shane Boulter <sboulter@ariasolutions.com>
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Mattias Webjörn Eriksson <mattias@webjorn.org>
 * @author    Eric Jon Rostetter <eric.rostetter@physics.utexas.edu>
 * @author    Tjeerd van der Zee <admin@xar.nl>
 * @category  Horde
 * @copyright 2004-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */
class Passwd_Driver_Smbldap extends Passwd_Driver_Ldap
{
    /**
     */
    public function __construct(array $params = array())
    {
        parent::__construct(array_merge(array(
            'lm_attribute' => null,
            'nt_attribute' => null,
            'pw_set_attribute' => null,
            'pw_expire_attribute' => null,
            'pw_expire_time' => null,
            'smb_objectclass' => 'sambaSamAccount'
        ), $params));
    }

    /**
     */
    protected function _changePassword($user, $oldpass, $newpass)
    {
        parent::_changePassword($user, $oldpass, $newpass);

        // Get existing user information.
        $entry = $this->_ldap->getEntry($this->_userdn);

        // Return if the user is not a Samba user.
        if (!in_array($this->_params['smb_objectclass'], $entry->getValue('objectClass', 'all'))) {
            return;
        }

        // Crypt_CHAP is not PSR-0 compatible.
        require_once 'Crypt/CHAP.php';
        $hash = new Crypt_CHAP_MSv2();
        $hash->password = $newpass;
        $lmpasswd = Horde_String::upper(bin2hex($hash->lmPasswordHash()));
        $ntpasswd = Horde_String::upper(bin2hex($hash->ntPasswordHash()));
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
            try {
                $entry->replace($changes, true);
                $entry->update();
            } catch (Horde_Ldap_Exception $e) {
                throw new Passwd_Exception($e);
            }
        }
    }

}
