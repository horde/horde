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
 * @author    Luiz R Malheiros <malheiros@gmail.com>
 * @category  Horde
 * @copyright 2004-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */
class Passwd_Driver_Adsi extends Passwd_Driver
{
    /**
     */
    protected function _changePassword($user, $oldpass, $newpass)
    {
        if (empty($this->_params['target'])) {
            throw new Passwd_Exception(_("Password module is missing target parameter."));
        }

        $root = new COM('WinNT:');
        $adsi = $root->OpenDSObject(
            'WinNT://' . $this->_params['target'] . '/' . $user . ',user',
            $this->_params['target'] . '\\' . $user,
            $oldpass,
            1
        );

        if (!$adsi) {
            throw new Passwd_Exception(_("Access Denied."));
        }
        if ($result = $adsi->ChangePassword($oldpass, $newpass)) {
            throw new Passwd_Exception(sprintf(_("ADSI error %s."), $result));
        }
    }

}
