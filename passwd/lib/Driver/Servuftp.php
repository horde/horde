<?php
/**
 * The serv-u ftp class attempts to change a user's password via the SITE PSWD
 * command used by Serv-u ftpd for windows.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Lucas Nelan (screen@brainkrash.com)
 * @package Passwd
 */
class Passwd_Driver_Servuftp extends Passwd_Driver
{
    const CONNECTED   = '220';
    const GOODBYE     = '221';
    const PASSWORDOK  = '230';
    const USERNAMEOK  = '331';
    const PASSWORDBAD = '530';

    protected $_fp;

    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     *
     * @throws Passwd_Exception
     */
    public function __construct($params = array())
    {
        if (empty($params['host']) || empty($params['port'])) {
            throw new Passwd_Exception(_("Password module is missing required parameters."));
        }
        parent::__construct(array_merge(array('timeout' => 30), $params));
    }

    /**
     * Changes the user's password.
     *
     * @param string $user_name     The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @throws Passwd_Exception
     */
    protected function changePassword($user_name, $old_password, $new_password)
    {
        if ($this->_connect() != self::CONNECTED) {
            throw new Passwd_Exception(_("Connection failed"));
        }
        if ($this->_sendCommand('user', $user_name) != self::USERNAMEOK) {
            $this->_disconnect();
            throw new Passwd_Exception(_("Unknown user"));
        }
        if ($this->_sendCommand('pass', $old_password) != self::PASSWORDOK) {
            $this->_disconnect();
            throw new Passwd_Exception(_("Incorrect password"));
        }
        if ($this->_sendCommand('site pswd', '"' . $old_password . '" "' . $new_password . '"') != self::PASSWORDOK) {
            $this->_disconnect();
            throw new Passwd_Exception(_("Cannot change password"));
        }
        $this->_disconnect();
    }

    protected function _connect()
    {
        $this->_fp = fsockopen($this->_params['host'], $this->_params['port'],
                               $errno, $errstr, $this->_params['timeout']);
        if (!$this->_fp) {
            throw new Passwd_Exception($errstr);
        }
        return $this->_getPrompt();
    }

    protected function _disconnect()
    {
        if ($this->_fp) {
            fputs($this->_fp, "quit\n");
            fclose($this->_fp);
        }
    }

    protected function _getPrompt()
    {
        $prompt = fgets($this->_fp, 4096);

        if (preg_match('/^[1-5][0-9][0-9]/', $prompt, $res)) {
            return $res[1];
        }
    }

    protected function _sendCommand($cmd, $arg)
    {
        $line = $cmd . ' ' . $arg . "\r\n";
        fputs($this->_fp, $line);
        return $this->_getPrompt();
    }
}
