<?php
/**
 * The smbpassd class attempts to change a user's password on a samba server.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Rene Lund Jensen <Rene@lundjensen.net>
 * @package Passwd
 */
class Passwd_Driver_Smbpasswd extends Passwd_Driver
{
    /**
     * Socket connection resource.
     *
     * @var resource
     */
    protected $_fp;

    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
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
     *
     * @throws Passwd_Exception
     */
    protected function _connect($user, $tmpfile)
    {
        if (!is_executable($this->_params['program'])) {
            throw new Passwd_Exception(_("Passwd is not properly configured."));
        }

        $cmd = sprintf('%s -r %s -s -U "%s" > %s 2>&1',
                       $this->_params['program'],
                       $this->_params['host'],
                       $user,
                       $tmpfile);
        $this->_fp = @popen($cmd, 'w');
        if (!$this->_fp) {
            throw new Passwd_Exception(_("Could not open pipe to smbpasswd."));
        }
    }

    /**
     * Disconnects the pipe to the sambaserver.
     */
    protected function _disconnect()
    {
        @pclose($this->_fp);
    }

    /**
     * Sends a string to the waiting sambaserver.
     *
     * @param string $cmd  The string to send to the server.
     *
     * @throws Passwd_Exception
     */
    protected function _sendCommand($cmd)
    {
        if (fputs($this->_fp, $cmd . "\n") == -1) {
            throw new Passwd_Exception(_("Error sending data to smbpasswd."));
        }
        sleep(1);
    }

    /**
     * Changes the user's password.
     *
     * @param string $username      The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @throws Passwd_Exception
     */
    public function changePassword($username, $old_password, $new_password)
    {
        // Clean up user name in case evil characters are in it.
        $user = escapeshellcmd($username);

        $tmpfile = Horde::getTempFile('smbpasswd');

        $this->_connect($user, $tmpfile);
        $this->_sendCommand($old_password);
        $this->_sendCommand($new_password);
        $this->_sendCommand($new_password);
        $this->_disconnect();

        $res = file($tmpfile);
        if (strstr($res[count($res) - 1], 'Password changed for user') === false) {
            throw new Passwd_Exception(strrchr(trim($res[count($res) - 2]), ':'));
        }
    }
}
