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
 *   'policy' => array(
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
 * @author    Luiz R Malheiros (malheiros@gmail.com)
 * @category  Horde
 * @copyright 2000-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */
class Passwd_Driver_Pspasswd extends Passwd_Driver
{
    /**
     */
    public function __construct(array $params = array())
    {
        if (empty($params['server']) ||
            empty($params['bin']) ||
            empty($params['admusr']) ||
            empty($params['admpwd'])) {
            throw new Passwd_Exception(_("Password module is missing required parameters."));
        }

        if (!file_exists($params['bin'])) {
            throw new Passwd_Exception(_("Password module can't find the supplied bin."));
        }

        parent::__construct($params);
    }

    /**
     */
    protected function _changePassword($user, $oldpass, $newpass)
    {
        $server = $this->_params['server'];
        $chpwd_adm = $this->_params['admusr'];
        $chpwd_usr = $user;

        if (!empty($this->_params['domain'])) {
            $chpwd_adm = $this->_params['domain'] . "\\" . $chpwd_adm;
            $chpwd_usr = $this->_params['domain'] . "\\" . $chpwd_usr_name;
        }

        exec('NET USE \\\\' . $server . '\\IPC$ /D >NUL 2>NUL');

        $cmdline = 'NET USE \\\\' . $server . '\\IPC$ "' . $oldpass
            . '" /USER:' . $chpwd_usr;
        exec($cmdline, $cmdreply, $retval);

        if (strpos(implode(' ', $cmdreply), 'The command completed successfully.') === false) {
            throw new Passwd_Exception(_("Failed to verify old password."));
        }

        exec('NET USE \\\\' . $server . '\\IPC$ /D >NUL 2>NUL');

        $cmdline = $this->_params['bin'] . ' \\\\' . $server . ' -u ' . $chpwd_adm . ' -p ' . $this->_params['admpwd'] . ' ' . $user. ' ' . $newpass;
        exec($cmdline, $cmdreply, $retval);
        exec('NET USE \\\\' . $server . '\\IPC$ /D >NUL 2>NUL');

        if (strpos(implode(' ', $cmdreply), 'Password for ' . $server . '\\' . $user. ' successfully changed.') === false) {
            throw new Passwd_Exception(_("Access Denied."));
        }
    }

}
