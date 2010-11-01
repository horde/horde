<?php
/**
 * The Passwd_procopen class provides a procopen implementation of the passwd
 * system.
 *
 * Any script or program can be supplied as the 'program' attribute value of
 * the params argument.  The username, old password and new password are
 * written to the stdin of the process and then the stdout and stderr of the
 * process are read and combined with the exit status value and returned to
 * the caller if the status code is not 0.
 *
 * $Horde: passwd/lib/Driver/procopen.php,v 1.4.2.6 2009/02/23 20:01:48 chuck Exp $
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Samuel Nicolary <sam@nicolary.org>
 * @since   Passwd 3.0
 * @package Passwd
 */
class Passwd_Driver_procopen extends Passwd_Driver {

    /**
     * Change the user's password by executing a user supplied command.
     *
     * @param string $user     User ID.
     * @param string $oldpass  Old password.
     * @param string $newpass  New password.
     *
     * @return boolean  True on success, false or error message on error.
     */
    function changePassword($user, $oldpass, $newpass)
    {
        global $conf;

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
            $output = '';
            $return_value = -1;
        }

        $output .= " (Exit Status: $return_value)";

        if ($return_value != 0) {
            return $output ? PEAR::raiseError($output) : false;
        } else {
            return true;
        }
    }

}
