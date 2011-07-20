<?php
/**
 * The Passwd_expect class provides an expect implementation of the passwd
 * system.
 *
 * $Horde: passwd/lib/Driver/expect.php,v 1.20.2.5 2009/01/06 15:25:23 jan Exp $
 *
 * Copyright 2000-2009 The Horde Project (http://www.horde.org/)
 * Horde 4 framework conversion Copyright 2011 The Horde Project
 *
 * WARNING: This driver has only formally been converted to Horde 4. 
 * No testing has been done. If this doesn't work, please file bugs at
 * bugs.horde.org
 * If you really need this to work reliably, think about sponsoring development
 * Please send a mail to lang -at- b1-systems.de if you can verify this driver to work
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Gaudenz Steinlin <gaudenz@soziologie.ch>
 * @since   Passwd 2.2
 * @package Passwd
 */
class Passwd_Driver_Expect extends Passwd_Driver {

    /**
     * Change the users password by executing an expect script.
     *
     * @param string $user          User ID.
     * @param string $old_password  Old password.
     * @param string $new_password  New password.
     *
     * @return boolean  True on success, false or error message on error.
     */
    function changePassword($user, $old_password, $new_password)
    {
        global $conf;

        // Sanity checks.
        if (!@is_executable($this->_params['program'])) {
            throw new Passwd_Exception(sprintf(_("%s does not exist or is not executable."), $this->_params['program']));
        }

        // Temporary logfile for error messages.
        $log = Horde::getTempFile('passwd');

        // Open expect script for writing.
        $prog = $this->_params['program'] . ' -f ' . $this->_params['script'] .
            ' -- ' . $this->_params['params'] . ' -log ' . $log;

        $exp = @popen($prog, 'w');
        @fwrite($exp, "$user\n");
        @fwrite($exp, "$old_password\n");
        @fwrite($exp, "$new_password\n");
        if (@pclose($exp)) {
            $errormsg = implode(' ', @file($log));
            if ($error_msg) {
                @unlink($log);
            } else {
                throw new Passwd_Exception($errormsg);
            }
        }

        return true;
    }

}
