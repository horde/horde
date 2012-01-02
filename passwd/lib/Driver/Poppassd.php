<?php
/**
 * The Poppassd class attempts to change a user's password via a poppassd
 * server.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Eric Jon Rostetter <eric.rostetter@physics.utexas.edu>
 * @package Passwd
 */
class Passwd_Driver_Poppassd extends Passwd_Driver
{
    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        $this->_params = array_merge(
            array('host' => 'localhost',
                  'port' => 106),
            $params);
    }

    /**
     * Connects to the server.
     */
    protected function _connect()
    {
        $this->_fp = fsockopen($this->_params['host'],
                               $this->_params['port'],
                               $errno,
                               $errstr,
                               30);
        if (!$this->_fp) {
            throw new Passwd_Exception($errstr);
        }

        $this->_getPrompt();
    }

    /**
     * Disconnects from the server.
     */
    protected function _disconnect()
    {
        if (isset($this->_fp)) {
            fputs($this->_fp, "quit\n");
            fclose($this->_fp);
        }
    }

    /**
     * Parses a response from the server to see what it was.
     */
    protected function _getPrompt()
    {
        $prompt = fgets($this->_fp, 4096);
        if (!$prompt) {
            throw new Passwd_Exception(_("No prompt returned from server."));
        }

        if (!preg_match('/^[1-5][0-9][0-9]/', $prompt)) {
            throw new Passwd_Exception($prompt);
        }

        /* This should probably be a regex match for 2?0 or 3?0, no? */
        $rc = substr($prompt, 0, 3);
        if ($rc != '200' && $rc != '220' && $rc != '250' && $rc != '300' ) {
            throw new Passwd_Exception($prompt);
        }
    }

    /**
     * Sends a command to the server.
     */
    protected function _sendCommand($cmd, $arg)
    {
        $line = $cmd . ' ' . $arg . "\n";
        $res_fputs = fputs($this->_fp, $line);
        if (!$res_fputs) {
            throw new Passwd_Exception(_("Cannot send command to server."));
        }
        $this->_getPrompt();
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
        $this->_connect();

        try {
            $this->_sendCommand('user', $username);
        } catch (Passwd_Exception $e) {
            $this->_disconnect();
            throw new Passwd_Exception(_("User not found") . ': ' . $e->getMessage());
        }

        try {
            $this->_sendCommand('pass', $old_password);
        } catch (Passwd_Exception $e) {
            $this->_disconnect();
            throw new Passwd_Exception(_("Incorrect old password.") . ': ' . $e->getMessage());
        }

        try {
            $this->_sendCommand('newpass', $new_password);
        } catch (Passwd_Exception $e) {
            $this->_disconnect();
            throw $e;
        }
    }
}
