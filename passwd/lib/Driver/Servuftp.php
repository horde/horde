<?php
/**
 * The serv-u ftp class attempts to change a user's password via the SITE PSWD
 * command used by Serv-u ftpd for windows.
 *
 * Copyright 2000-2011 Horde LLC (http://www.horde.org/)
 *
 * WARNING: This driver has only formally been converted to Horde 4.  No
 *          testing has been done. If this doesn't work, please file bugs at
 *          bugs.horde.org.  If you really need this to work reliably, think
 *          about sponsoring development. Please let the Horde developers know
 *          if you can verify this driver to work.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Lucas Nelan (screen@brainkrash.com)
 * @package Passwd
 */

//TODO: This never throws exceptions or a PEAR_Error: Is this sane?

class Passwd_Driver_Servuftp extends Passwd_Driver {

    protected $fp;
    protected $ftpd_connected   = '220';
    protected $ftpd_goodbye     = '221';
    protected $ftpd_passwordok  = '230';
    protected $ftpd_usernameok  = '331';
    protected $ftpd_passwordbad = '530';

    function connect($server, $port, $timeout = 30)
    {
        $this->fp = fsockopen($server, $port, $errno, $errstr, $timeout);

        if (!$this->fp) {
            $this->_errorstr = $errstr;
            return false;
        } else {
            return $this->getPrompt();
        }
    }

    function _disconnect()    {
        if ($this->fp) {
            fputs($this->fp, "quit\n");
            fclose($this->fp);
        }
    }

    function getPrompt()
    {
        $prompt = fgets($this->fp, 4096);
        $return = '';

        if (preg_match('/^[1-5][0-9][0-9]/', $prompt, $res)) {
            $return = $res[1];
        }

        return $return;
    }

    function sendCommand($cmd, $arg)
    {
        $line = $cmd . ' ' . $arg . "\r\n";
        fputs($this->fp, $line);
        return $this->getPrompt();
    }

    function changePassword($user_name, $old_password, $new_password)
    {
        $server = isset($this->_params['host']) ? $this->_params['host'] : '';
        $port = isset($this->_params['port']) ? $this->_params['port'] : '';
        $timeout = isset($this->_params['timeout']) ? $this->_params['timeout'] : '';

        if ($server == '' || $port == '') {
            $this->_errorstr = _("Password module is not properly configured");
            return false;
        }

        $return_value = false;
        if ($this->connect($server, $port, $timeout) == $this->ftpd_connected) {
            if ($this->sendCommand('user', $user_name) == $this->ftpd_usernameok) {
                if ($this->sendCommand('pass', $old_password) == $this->ftpd_passwordok) {
                    if ($this->sendCommand('site pswd', '"'.$old_password.'" "'.$new_password.'"') == $this->ftpd_passwordok) {
                        $return_value = true;
                    }
                }
            }

            $this->_disconnect();
        }

        return $return_value;
    }

    function checkPassword($user_name, $user_password)
    {
        $server = isset($this->_params['host']) ? $this->_params['host'] : '';
        $port = isset($this->_params['port']) ? $this->_params['port'] : '';
        $timeout = isset($this->_params['timeout']) ? $this->_params['timeout'] : '';

        if ($server == '' || $port == '') {
            $this->_errorstr = _("Password module is not properly configured.");
            return false;
        }

        $return_value = false;

        if ($this->connect($server, $port, $timeout) == $this->ftpd_connected) {
            if ($this->sendCommand('user', $user_name) == $this->ftpd_usernameok) {
                if ($this->sendCommand('pass', $user_password) == $this->ftpd_passwordok) {
                    $return_value = true;
                }
            }

            $this->_disconnect();
        } else {
            // Cannot connect.
            $return_value = -1;
        }

        return $return_value;
    }

}
