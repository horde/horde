<?php
/**
 * The ADSI class changes a user's password on any Windows Machine/NT-Domain
 * using the ADSI COM Interface.
 *
 * NOTES:
 *
 * - If you plan to implement passwd over Active Direcory you must use the
 *   LDAP driver and not this one! This driver is designed for standalone
 *   machines or NT4 domains, only.
 *
 * - The host server must be Win32 with ADSI support.
 *
 * WARNING: This driver has only formally been converted to Horde 4. 
 * No testing has been done. If this doesn't work, please file bugs at
 * bugs.horde.org
 * If you really need this to work reliably, think about sponsoring development
 *
 * Sample backend configuration:
 * <code>
 * $backends['adsi'] = array(
 *    'name' => 'Sample ADSI backend',
 *    'preferred' => 'localhost',
 *    'password policy' => array(
 *        'minLength' => 8,
 *        'maxLength' => 14
 *    ),
 *    'driver' => 'adsi',
 *    'params' => array(
 *        'target' => 'YOUR_MACHINE/DOMAIN_NAME_HERE'
 *    )
 * )
 * </code>
 *
 * Backend parameters:
 * target = Target Windows machine/domain name (Required)
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Luiz R Malheiros <malheiros@gmail.com>
 * @since   Passwd 3.0
 * @package Passwd
 */
class Passwd_Driver_adsi extends Passwd_Driver {

    function changePassword($user_name, $old_password, $new_password)
    {
        $target = isset($this->_params['target']) ? $this->_params['target'] : '';

        if (empty($target)) {
            throw new Passwd_Exception(_("Password module is missing target parameter."));
        }

        $root = new COM('WinNT:');

        if ($adsi = $root->OpenDSObject('WinNT://' . $target . '/' . $user_name . ',user', $target . '\\' . $user_name, $old_password, 1)) {
            $result = $adsi->ChangePassword($old_password, $new_password);
            if ($result == 0) {
                return true;
            } else {
                throw new Passwd_Exception(sprintf(_("ADSI error %s."), $result));
            }
        } else {
            throw new Passwd_Exception(_("Access Denied."));
        }
    }
}
