<?php
/**
 * The smbpassd class attempts to change a user's password on a samba server.
 *
 * $Horde: passwd/lib/Driver/smbpasswd.php,v 1.22.2.5 2009/01/06 15:25:23 jan Exp $
 *
 * Copyright 2000-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Rene Lund Jensen <Rene@lundjensen.net>
 * @package Passwd
 */
class Passwd_Driver_smbpasswd extends Passwd_Driver {

    /**
     * Socket connection resource.
     *
     * @var resource
     */
    var $_fp;

    /**
     * Constructs a new smbpasswd Passwd_Driver object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    function Passwd_Driver_smbpasswd($params = array())
    {
        $this->_params = array_merge(array('host' => 'localhost',
                                           'program' => '/usr/bin/smbpasswd'),
                                     $params);
    }

    /**
     * Connects a pipe to the sambaserver using the smbpasswd program.
     *
     * @param string $user     The user to change the password for
     * @param string $tmpfile  The name of a temporary file in which to write
     *                         output.
     * @return mixed  True on success, PEAR_Error on failure
     */
    function _connect($user, $tmpfile)
    {
        if (!is_executable($this->_params['program'])) {
            return PEAR::raiseError(_("Passwd is not properly configured."));
        }

        $cmd = sprintf('%s -r %s -s -U "%s" > %s 2>&1',
                       $this->_params['program'],
                       $this->_params['host'],
                       $user,
                       $tmpfile);
        $this->_fp = @popen($cmd, 'w');
        if (!$this->_fp) {
            return PEAR::raiseError(_("Could not open pipe to smbpasswd."));
        }

        return true;
    }

    /**
     * Disconnects the pipe to the sambaserver.
     */
    function _disconnect()
    {
        @pclose($this->_fp);
    }

    /**
     * Sends a string to the waiting sambaserver.
     *
     * @param string $cmd  The string to send to the server.
     */
    function _sendCommand($cmd)
    {
        if (fputs($this->_fp, $cmd . "\n") == -1) {
            return PEAR::raiseError(_("Error sending data to smbpasswd."));
        }
        sleep(1);
        return true;
    }

    /**
     * Changes the user's password.
     *
     * @param string $username      The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @return mixed  True or PEAR_Error based on success of the change.
     */
    function changePassword($username, $old_password, $new_password)
    {
        $res = true;

        // Clean up user name in case evil characters are in it.
        $user = escapeshellcmd($username);

        $tmpfile = Horde::getTempFile('smbpasswd');
        if (is_a($tmpfile, 'PEAR_Error')) {
            return $tmpfile;
        }

        $res = $this->_connect($user, $tmpfile);
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        $res = $this->_sendCommand($old_password);
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        $res = $this->_sendCommand($new_password);
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        $res = $this->_sendCommand($new_password);
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        $this->_disconnect();

        $res = file($tmpfile);
        if (strstr($res[count($res) - 1], 'Password changed for user') === false) {
            return PEAR::raiseError(strrchr(trim($res[count($res) - 2]), ':'));
        }

        return true;
    }

}
