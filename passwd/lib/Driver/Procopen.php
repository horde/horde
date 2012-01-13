<?php
/**
 * The Passwd_Driver_Procopen class provides a procopen implementation of
 * the passwd system.
 *
 * Any script or program can be supplied as the 'program' attribute value of
 * the params argument.  The username, old password and new password are
 * written to the stdin of the process and then the stdout and stderr of the
 * process are read and combined with the exit status value and returned to
 * the caller if the status code is not 0.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Samuel Nicolary <sam@nicolary.org>
 * @package Passwd
 */
class Passwd_Driver_Procopen extends Passwd_Driver
{
    /**
     * Changes the user's password.
     *
     * @param string $user     The user for which to change the password.
     * @param string $oldpass  The old (current) user password.
     * @param string $newpass  The new user password to set.
     *
     * @throws Passwd_Exception
     */
    public function changePassword($user, $oldpass, $newpass)
    {
        $descriptorspec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'));

        $output = '';

        $process = @proc_open($this->_params['program'], $descriptorspec, $pipes);
        if (is_resource($process)) {
            fwrite($pipes[0], "$user\n");
            fwrite($pipes[0], "$oldpass\n");
            fwrite($pipes[0], "$newpass\n");
            fclose($pipes[0]);
            while (!feof($pipes[1])) {
                $output .= fgets($pipes[1], 1024);
            }
            fclose($pipes[1]);
            while (!feof($pipes[2])) {
                $output .= fgets($pipes[2], 1024);
            }
            fclose($pipes[2]);
            $return_value = proc_close($process);
        } else {
            $return_value = -1;
        }

        $output .= " (Exit Status: $return_value)";

        if ($return_value != 0) {
            throw new Passwd_Exception($output);
        }
    }
}
