<?php
/**
 * The Passwd_expect class provides an expect implementation of the passwd
 * system.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Gaudenz Steinlin <gaudenz@soziologie.ch>
 * @package Passwd
 */
class Passwd_Driver_Expect extends Passwd_Driver
{
    /**
     * Changes the user's password.
     *
     * @param string $user          The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @throws Passwd_Exception
     */
    public function changePassword($user, $old_password, $new_password)
    {
        // Sanity checks.
        if (!@is_executable($this->_params['program'])) {
            throw new Passwd_Exception(
                sprintf(_("%s does not exist or is not executable."),
                        $this->_params['program']));
        }

        // Temporary logfile for error messages.
        $log = Horde::getTempFile('passwd');

        // Open expect script for writing.
        $prog = 'LANG=C LC_ALL=C ' . $this->_params['program']
            . ' -f ' . $this->_params['script']
            . ' -- ' . $this->_params['params'] . ' -log ' . $log;

        $exp = @popen($prog, 'w');
        @fwrite($exp, "$user\n");
        @fwrite($exp, "$old_password\n");
        @fwrite($exp, "$new_password\n");

        if (@pclose($exp)) {
            $errormsg = implode(' ', @file($log));
            @unlink($log);
            if ($errormsg) {
                throw new Passwd_Exception($errormsg);
            }
        }
    }
}
