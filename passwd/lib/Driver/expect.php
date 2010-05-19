<?php
/**
 * The Passwd_expect class provides an expect implementation of the passwd
 * system.
 *
 * $Horde: passwd/lib/Driver/expect.php,v 1.20.2.5 2009/01/06 15:25:23 jan Exp $
 *
 * Copyright 2000-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Gaudenz Steinlin <gaudenz@soziologie.ch>
 * @since   Passwd 2.2
 * @package Passwd
 */
class Passwd_Driver_expect extends Passwd_Driver {

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
            return PEAR::raiseError(sprintf(_("%s does not exist or is not executable."), $this->_params['program']));
        }

        // Temporary logfile for error messages.
        $log = tempnam(ini_get('upload_tmp_dir') ?
                       ini_get('upload_tmp_dir') :
                       '/tmp',
                       'passwd');

        // Open expect script for writing.
        $prog = $this->_params['program'] . ' -f ' . $this->_params['script'] .
            ' -- ' . $this->_params['params'] . ' -log ' . $log;

        $exp = @popen($prog, 'w');
        @fwrite($exp, "$user\n");
        @fwrite($exp, "$old_password\n");
        @fwrite($exp, "$new_password\n");
        if (@pclose($exp)) {
            $errormsg = implode(' ', @file($log));
            @unlink($log);
            return $errormsg ? PEAR::raiseError($errormsg) : false;
        }

        return true;
    }

}
