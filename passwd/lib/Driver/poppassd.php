<?php
/**
 * The poppassd class attempts to change a user's password via a poppassd
 * server.
 *
 * Copyright 2000-2011 The Horde Project (http://www.horde.org/
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
 * @author  Eric Jon Rostetter <eric.rostetter@physics.utexas.edu>
 * @package Passwd
 */
class Passwd_Driver_poppassd extends Passwd_Driver {

    /**
     * Constructs a new poppassd Passwd_Driver object.
     *
     * @param array  $params    A hash containing connection parameters.
     */
    function __construct($params = array())
    {
        $this->_params['host'] = array_key_exists('host', $params) ? $params['host'] : 'localhost';
        $this->_params['port'] = array_key_exists('port', $params) ? $params['port'] : 106;
    }

    /**
     * Connect to the server
     */
    function _connect()
    {
        $this->_fp = fsockopen($this->_params['host'], $this->_params['port'], $errno, $errstr, 30);
        if (!$this->_fp) {
            throw new Passwd_Exception($errstr);
        } else {
            return $this->_getPrompt();
        }
    }

    /**
     * Disconnect from the server
     */
    function _disconnect()
    {
        if (isset($this->_fp)) {
            fputs($this->_fp, "quit\n");
            fclose($this->_fp);
        }
    }

    /**
     * Parse a response from the server to see what it was
     */
    function _getPrompt()
    {
        $prompt = fgets($this->_fp, 4096);
        if (!$prompt) {
            return throw new Passwd_Exception(_("No prompt returned from server."));
        }
        if (preg_match('/^[1-5][0-9][0-9]/', $prompt)) {
            $rc = substr($prompt, 0, 3);
            /* This should probably be a regex match for 2?0 or 3?0, no? */
            if ($rc == '200' || $rc == '220' || $rc == '250' || $rc == '300' ) {
                return true;
            } else {
                throw new Passwd_Exception($prompt);
            }
        } else {
            return true;
        }
    }

    /**
     * Send a command to the server.
     */
    function _sendCommand($cmd, $arg)
    {
        $line = $cmd . ' ' . $arg . "\n";
        $res_fputs = fputs($this->_fp, $line);
        if (!$res_fputs) {
            throw new Passwd_Exception(_("Cannot send command to server."));
        }
        return $this->_getPrompt();
    }

    /**
     * Change the user's password.
     *
     * @param string $username      The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @return boolean   True or false based on success of the change.
     */
    function changePassword($username, $old_password, $new_password)
    {
        $res = $this->_connect();

        try {
            $res = $this->_sendCommand('user', $username);
        }
        catch (Passwd_Exception $e) {
            $this->_disconnect();
            throw new Passwd_Exception(_('User not found') . ' ' . $e->getMessage());
        }

        try {
            $res = $this->_sendCommand('pass', $old_password);
        }
        catch (Passwd_Exception $e) {
            $this->_disconnect();
            throw new Passwd_Exception($e->getMessage() . _("Incorrect old password."));
        }
        try {
            $res = $this->_sendCommand('newpass', $new_password);
        } catch (Passwd_Exception $e) {
            $this->_disconnect();
            throw $e;
        }

        return true;
    }

}
