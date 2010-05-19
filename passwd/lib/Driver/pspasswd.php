<?php
/**
 * The PSPasswd class changes a user's password on any Windows Machine
 * (NT+) using the pspasswd free utility available at Sysinternals
 * website: http://www.sysinternals.com/ntw2k/freeware/pspasswd.shtml
 *
 * IMPORTANT!
 *
 * This driver should be used only as a last resort when there's no
 * possibility of using the ADSI or LDAP drivers, which are far more
 * secure and fast. This driver needs administrative credentials
 * exposed on the backends.php file, which is required by the
 * pspasswd.exe tool. It's an alternative driver that should be
 * avoided, but could also be the only option for a few scenarios.
 * (eg: When you don't have ADSI or LDAP support)
 *
 * Sample backend configuration:
 * <code>
 * $backends['pspasswd'] = array(
 *   'name' => 'Sample pspasswd backend',
 *   'preferred' => 'localhost',
 *   'password policy' => array(
 *       'minLength' => 8,
 *       'maxLength' => 14
 *   ),
 *   'driver' => 'pspasswd',
 *   'params' => array(
 *		 'server' => 'YOUR_SERVER_NAME',
 *		 'bin' => 'DRIVE:\\DIR\\pspasswd.exe', // Notice: "\\"
 *		 'admusr' => 'Administrator',
 *	  	 'admpwd' => 'Password',
 *       'domain' => 'YOUR_DOMAIN_NAME'
 *   )
 * );
 * </code>
 *
 * Backend parameters:<pre>
 * server	= Machine where you want to change the password (Required)
 * bin		= Full pathname of the pspasswd.exe program (Required)
 * admusr	= User with administrative privileges (Required)
 * admpwd	= Password of the administrative user (Required)
 * domain	= Windows domain name (Optional)
 * </pre>
 *
 * For example: Passing a NT4 PDC server name to the server parameter
 * means you can change the user's password on that NT4 Domain.
 *
 * Special thanks to Mark Russinovich (mark@sysinternals.com) for the
 * tool and helping me solve some questions about it.
 *
 * $Horde: passwd/lib/Driver/pspasswd.php,v 1.2.2.5 2009/01/06 15:25:23 jan Exp $
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Luiz R Malheiros (malheiros@gmail.com)
 * @since   Passwd 3.0
 * @package Passwd
 */
class Passwd_Driver_pspasswd extends Passwd_Driver {

    function changePassword($user_name, $old_password, $new_password)
    {
        $server = isset($this->_params['server']) ? $this->_params['server'] : '';
        $bin = isset($this->_params['bin']) ? $this->_params['bin'] : '';
        $admusr = isset($this->_params['admusr']) ? $this->_params['admusr'] : '';
        $admpwd = isset($this->_params['admpwd']) ? $this->_params['admpwd'] : '';
        $domain = isset($this->_params['domain']) ? $this->_params['domain'] : '';

        if ($server == '' || $bin == '' || $admusr == '' || $admpwd == '') {
            return PEAR::raiseError(_("Password module is missing required parameters."));
        } elseif (file_exists($bin) == false) {
            return PEAR::raiseError(_("Password module can't find the supplied bin."));
        }

        if ($domain != '') {
            $chpwd_adm = $domain . "\\" . $admusr;
            $chpwd_usr = $domain . "\\" . $user_name;
        } else {
            $chpwd_adm = $admusr;
            $chpwd_usr = $user_name;
        }

        exec('NET USE \\\\' . $server . '\\IPC$ /D >NUL 2>NUL');

        $cmdline = 'NET USE \\\\' . $server . '\\IPC$ "' . $old_password . '" /USER:' . $chpwd_usr;

        exec($cmdline, $cmdreply, $retval);

        if (strpos(implode(' ', $cmdreply), 'The command completed successfully.') === false) {
            return PEAR::raiseError(_("Failed to verify old password."));
        }

        exec('NET USE \\\\' . $server . '\\IPC$ /D >NUL 2>NUL');

        $cmdline = $bin . ' \\\\' . $server . ' -u ' . $chpwd_adm . ' -p ' . $admpwd . ' ' . $user_name . ' ' . $new_password;

        exec($cmdline, $cmdreply, $retval);

        exec('NET USE \\\\' . $server . '\\IPC$ /D >NUL 2>NUL');

        if (strpos(implode(' ', $cmdreply), 'Password for ' . $server . '\\' . $user_name . ' successfully changed.') === false) {
            return PEAR::raiseError(_("Access Denied."));
        }

        return true;
    }

}
