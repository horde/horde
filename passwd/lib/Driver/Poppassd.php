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
 * Changes a password via a poppassd server.
 *
 * @author    Eric Jon Rostetter <eric.rostetter@physics.utexas.edu>
 * @category  Horde
 * @copyright 2000-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */
class Passwd_Driver_Poppassd extends Passwd_Driver
{
    /**
     */
    public function __construct(array $params = array())
    {
        parent::__construct(array_merge(array(
            'host' => 'localhost',
            'port' => 106
        ), $params));
    }

    /**
     * Connects to the server.
     *
     * @throws Passwd_Exception
     */
    protected function _connect()
    {
        $this->_fp = fsockopen(
            $this->_params['host'],
            $this->_params['port'],
            $errno,
            $errstr,
            30
        );
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
     *
     * @throws Passwd_Exception
     */
    protected function _getPrompt()
    {
        if (!($prompt = fgets($this->_fp, 4096))) {
            throw new Passwd_Exception(_("No prompt returned from server."));
        }

        if (!preg_match('/^[1-5][0-9][0-9]/', $prompt)) {
            throw new Passwd_Exception($prompt);
        }

        /* This should probably be a regex match for 2?0 or 3?0, no? */
        $rc = substr($prompt, 0, 3);
        if (!in_array($rc, array('200', '220', '250', '300'))) {
            throw new Passwd_Exception($prompt);
        }
    }

    /**
     * Sends a command to the server.
     *
     * @throws Passwd_Exception
     */
    protected function _sendCommand($cmd, $arg)
    {
        $line = $cmd . ' ' . $arg . "\n";
        if (!($res_fputs = fputs($this->_fp, $line))) {
            throw new Passwd_Exception(_("Cannot send command to server."));
        }
        $this->_getPrompt();
    }

    /**
     */
    protected function _changePassword($user, $oldpass, $newpass)
    {
        $this->_connect();

        try {
            $this->_sendCommand('user', $user);
        } catch (Passwd_Exception $e) {
            $this->_disconnect();
            throw new Passwd_Exception(_("User not found") . ': ' . $e->getMessage());
        }

        try {
            $this->_sendCommand('pass', $oldpass);
        } catch (Passwd_Exception $e) {
            $this->_disconnect();
            throw new Passwd_Exception(_("Incorrect old password.") . ': ' . $e->getMessage());
        }

        try {
            $this->_sendCommand('newpass', $newpass);
        } catch (Passwd_Exception $e) {
            $this->_disconnect();
            throw $e;
        }
    }

}
