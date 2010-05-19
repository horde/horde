<?php
/**
 * The poppassd class attempts to change a user's password via a poppassd
 * server.
 *
 * $Horde: passwd/lib/Driver/poppassd.php,v 1.24.2.7 2009/01/06 15:25:23 jan Exp $
 *
 * Copyright 2000-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Eric Jon Rostetter <eric.rostetter@physics.utexas.edu>
 * @package Passwd
 */
class Passwd_Driver_poppassd extends Passwd_Driver {

    /**
     * Socket connection.
     *
     * @var resource
     */
    var $_fp;

    /**
     * Constructs a new poppassd Passwd_Driver object.
     *
     * @param array  $params    A hash containing connection parameters.
     */
    function Passwd_Driver_poppassd($params = array())
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
            return PEAR::raiseError($errstr);
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
            return PEAR::raiseError(_("No prompt returned from server."));
        }
        if (preg_match('/^[1-5][0-9][0-9]/', $prompt)) {
            $rc = substr($prompt, 0, 3);
            /* This should probably be a regex match for 2?0 or 3?0, no? */
            if ($rc == '200' || $rc == '220' || $rc == '250' || $rc == '300' ) {
                return true;
            } else {
                return PEAR::raiseError($prompt);
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
            return PEAR::raiseError(_("Cannot send command to server."));
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
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        $res = $this->_sendCommand('user', $username);
        if (is_a($res, 'PEAR_Error')) {
            $this->_disconnect();
            return PEAR::raiseError(_("User not found"));
        }

        $res = $this->_sendCommand('pass', $old_password);
        if (is_a($res, 'PEAR_Error')) {
            $this->_disconnect();
            return PEAR::raiseError(_("Incorrect old password."));
        }

        $res = $this->_sendCommand('newpass', $new_password);
        $this->_disconnect();
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        return true;
    }

}
