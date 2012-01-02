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
 * Sample backend configuration:
 * <code>
 * $backends['adsi'] = array(
 *    'name' => 'Sample ADSI backend',
 *    'preferred' => 'localhost',
 *    'policy' => array(
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
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Luiz R Malheiros <malheiros@gmail.com>
 * @package Passwd
 */
class Passwd_Driver_Adsi extends Passwd_Driver
{
    /**
     * Changes the user's password.
     *
     * @param string $user_name     The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @throws Passwd_Exception
     */
    public function changePassword($user_name, $old_password, $new_password)
    {
        if (empty($this->_params['target'])) {
            throw new Passwd_Exception(_("Password module is missing target parameter."));
        }

        $root = new COM('WinNT:');
        $adsi = $root->OpenDSObject(
            'WinNT://' . $this->_params['target'] . '/' . $user_name . ',user',
            $this->_params['target'] . '\\' . $user_name,
            $old_password,
            1);

        if (!$adsi) {
            throw new Passwd_Exception(_("Access Denied."));
        }
        if ($result = $adsi->ChangePassword($old_password, $new_password)) {
            throw new Passwd_Exception(sprintf(_("ADSI error %s."), $result));
        }
    }
}
