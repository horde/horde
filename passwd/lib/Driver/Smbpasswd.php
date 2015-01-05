<?php
/**
 * Copyright 2000-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2000-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */

/**
 * Changes a password on a samba server.
 *
 * @author    Rene Lund Jensen <Rene@lundjensen.net>
 * @category  Horde
 * @copyright 2000-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
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
     */
    public function __construct(array $params = array())
    {
        parent::__construct(array_merge(array(
            'host' => 'localhost',
            'program' => '/usr/bin/smbpasswd'
        ), $params));
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

        $cmd = sprintf(
            '%s -r %s -s -U "%s" > %s 2>&1',
            $this->_params['program'],
            $this->_params['host'],
            $user,
            $tmpfile
        );

        if (!($this->_fp = @popen($cmd, 'w'))) {
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
     */
    protected function _changePassword($user, $oldpass, $newpass)
    {
        // Clean up user name in case evil characters are in it.
        $user = escapeshellcmd($user);

        $tmpfile = Horde::getTempFile('smbpasswd');

        $this->_connect($user, $tmpfile);
        $this->_sendCommand($oldpass);
        $this->_sendCommand($newpass);
        $this->_sendCommand($newpass);
        $this->_disconnect();

        $res = file($tmpfile);
        if (strstr($res[count($res) - 1], 'Password changed for user') === false) {
            throw new Passwd_Exception(strrchr(trim($res[count($res) - 2]), ':'));
        }
    }

}
