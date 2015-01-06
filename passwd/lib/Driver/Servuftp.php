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
 * Changes a password via the SITE PSWD command used by Serv-u ftpd for
 * Windows.
 *
 * @author    Lucas Nelan (screen@brainkrash.com)
 * @category  Horde
 * @copyright 2000-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */
class Passwd_Driver_Servuftp extends Passwd_Driver
{
    const CONNECTED   = '220';
    const GOODBYE     = '221';
    const PASSWORDOK  = '230';
    const USERNAMEOK  = '331';
    const PASSWORDBAD = '530';

    /**
     */
    protected $_fp;

    /**
     */
    public function __construct(array $params = array())
    {
        if (empty($params['host']) || empty($params['port'])) {
            throw new Passwd_Exception(_("Password module is missing required parameters."));
        }

        parent::__construct(array_merge(array(
            'timeout' => 30
        ), $params));
    }

    /**
     */
    protected function _changePassword($user, $oldpass, $newpass)
    {
        if ($this->_connect() != self::CONNECTED) {
            throw new Passwd_Exception(_("Connection failed"));
        }

        if ($this->_sendCommand('user', $user) != self::USERNAMEOK) {
            $this->_disconnect();
            throw new Passwd_Exception(_("Unknown user"));
        }

        if ($this->_sendCommand('pass', $oldpass) != self::PASSWORDOK) {
            $this->_disconnect();
            throw new Passwd_Exception(_("Incorrect password"));
        }

        if ($this->_sendCommand('site pswd', '"' . $oldpass. '" "' . $newpass. '"') != self::PASSWORDOK) {
            $this->_disconnect();
            throw new Passwd_Exception(_("Cannot change password"));
        }

        $this->_disconnect();
    }

    /**
     * @throws Passwd_Exception
     */
    protected function _connect()
    {
        $this->_fp = fsockopen(
            $this->_params['host'],
            $this->_params['port'],
            $errno,
            $errstr,
            $this->_params['timeout']
        );
        if (!$this->_fp) {
            throw new Passwd_Exception($errstr);
        }
        return $this->_getPrompt();
    }

    /**
     * @throws Passwd_Exception
     */
    protected function _disconnect()
    {
        if ($this->_fp) {
            fputs($this->_fp, "quit\n");
            fclose($this->_fp);
        }
    }

    /**
     * @throws Passwd_Exception
     */
    protected function _getPrompt()
    {
        $prompt = fgets($this->_fp, 4096);

        if (preg_match('/^[1-5][0-9][0-9]/', $prompt, $res)) {
            return $res[1];
        }
    }

    /**
     * @throws Passwd_Exception
     */
    protected function _sendCommand($cmd, $arg)
    {
        $line = $cmd . ' ' . $arg . "\r\n";
        fputs($this->_fp, $line);
        return $this->_getPrompt();
    }

}
