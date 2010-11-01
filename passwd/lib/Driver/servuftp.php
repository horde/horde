<?php
/**
 * The serv-u ftp class attempts to change a user's password via the SITE PSWD
 * command used by Serv-u ftpd for windows.
 *
 * $Horde: passwd/lib/Driver/servuftp.php,v 1.14.2.5 2009/01/06 15:25:23 jan Exp $
 *
 * Copyright 2000-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Lucas Nelan (screen@brainkrash.com)
 * @package Passwd
 */
class Passwd_Driver_servuftp extends Passwd_Driver {

    var $fp;
    var $ftpd_connected   = '220';
    var $ftpd_goodbye     = '221';
    var $ftpd_passwordok  = '230';
    var $ftpd_usernameok  = '331';
    var $ftpd_passwordbad = '530';

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

    function _disconnect()
    {
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
