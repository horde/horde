<?php

/**
 * Lower boundary character.
 */
define('FIRSTCH', 0x20);

/**
 * Upper boundary character.
 */
define('LASTCH', 0x7e);

/**
 * Median character.
 */
define('TABSZ', LASTCH - FIRSTCH + 1);

/**
 * The pine class attempts to change a user's password on a in a pine password
 * file.
 *
 * $Horde: passwd/lib/Driver/pine.php,v 1.9.2.5 2009/01/06 15:25:23 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Max Kalika <max@horde.org>
 * @since   Passwd 3.0
 * @package Passwd
 */
class Passwd_Driver_pine extends Passwd_Driver {

    /**
     * FTP connection handle.
     *
     * @var VFS
     */
    var $_ftp;

    /**
     * Boolean which contains state of the ftp connection.
     *
     * @var boolean
     */
    var $_connected = false;

    /**
     * Contents array of the pine password file.
     *
     * @var array
     */
    var $_contents = array();

    /**
     * Constructs a new pine Passwd_Driver object.
     *
     * @param array  $params    A hash containing connection parameters.
     */
    function Passwd_Driver_pine($params = array())
    {
        /* We self-encrypt here, so plaintext is needed. */
        $this->_params['encryption'] = 'plain';
        $this->_params['show_encryption'] = false;

        /* Sensible FTP server parameters. */
        $this->_params['host'] = isset($params['host']) ? $params['host'] : 'localhost';
        $this->_params['port'] = isset($params['port']) ? $params['port'] : '21';
        $this->_params['path'] = isset($params['path']) ? $params['path'] : '';
        $this->_params['file'] = isset($params['file']) ? $params['file'] : '.pinepw';

        /* Connect to FTP server using just-passed-in credentials?
         * Only useful if using the composite driver and changing
         * system (FTP) password prior to this one. */
        $this->_params['use_new_passwd'] = isset($params['use_new_passwd']) ? $params['use_new_passwd'] : false;

        /* What host to look for on each line? */
        $this->_params['imaphost'] = isset($params['imaphost']) ? $params['imaphost'] : 'localhost';
    }

    /**
     * Connect to the FTP server.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function _connect($user, $password)
    {
        if (!$this->_connected) {
            /* Connect to the FTP server using the supplied
             * parameters. */
            require_once 'VFS.php';

            $params = array(
                'username' => $user,
                'password' => $password,
                'hostspec' => $this->_params['host'],
                'port' => $this->_params['port'],
            );

            $res = $this->_ftp = &VFS::singleton('ftp', $params);
            if (is_a($res, 'PEAR_Error')) {
                return $res;
            }
            $res = $this->_ftp->checkCredentials();
            if (is_a($res, 'PEAR_Error')) {
                return $res;
            }
        }

        return true;
    }

    /**
     * Disconnect from the FTP server and clean up the connection.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function _disconnect()
    {
        if ($this->_connected) {
            $this->_connected = false;
            return $this->_ftp->disconnect();
        }

        return true;
    }

    /**
     * Decodes a Pine-encoded password string. The algorithm is
     * borrowed from read_passfile() and xlate_out() functions in
     * pine/imap.c file distributed in the Pine source archive.
     *
     * @param string $string  The contents of a pine-encoded password file.
     *
     * @return array  List of lines of decoded elements.
     */
    function _decode($string)
    {
        $list = array();

        $lines = explode("\n", $string);
        for ($n = 0; $n < sizeof($lines); $n++) {
            $key = $n;
            $tmp = $lines[$n];
            for ($i = 0; $i < strlen($tmp); $i++) {
                if ((ord($tmp[$i]) >= FIRSTCH) && (ord($tmp[$i]) <= LASTCH)) {
                    $xch  = ord($tmp[$i]) - ($dti = $key);
                $xch += ($xch < FIRSTCH - TABSZ) ? 2 * TABSZ : ($xch < FIRSTCH) ? TABSZ : 0;
                $dti  = ($xch - FIRSTCH) + $dti;
                $dti -= ($dti >= 2 * TABSZ) ? 2 * TABSZ : ($dti >= TABSZ) ? TABSZ : 0;
                $key  = $dti;
                $tmp[$i] = chr($xch);
                }
            }

            if ($i && $tmp[$i - 1] == "\n") {
                $tmp = substr($tmp, 0, -1);
            }

            $parts = explode("\t", $tmp);
            if (count($parts) >= 4) {
                $list[] = $parts;
            }
        }

        return $list;
    }

    /**
     * Encodes an array of elements into a Pine-readable password string.
     * The algorith is borrowed from write_passfile() and xlate_in() functions
     * in pine/imap.c file distributed in the Pine source archive.
     *
     * @param array  $lines  List of lines of decoded elements
     *
     * @return array  Contents of a pine-readable password file.
     */
    function _encode($lines)
    {
        $string = '';
        for ($n = 0; $n < sizeof($lines); $n++) {
            if (isset($lines[$n][4])) {
                $lines[$n][4] = "\t" . $lines[$n][4];
            } else {
                $lines[$n][4] = '';
            }

            $key = $n;
            $tmp = vsprintf("%.100s\t%.100s\t%.100s\t%d%s\n", $lines[$n]);
            for ($i = 0; $i < strlen($tmp); $i++) {
                $eti = $key;
                if ((ord($tmp[$i]) >= FIRSTCH) && (ord($tmp[$i]) <= LASTCH)) {
                    $eti += ord($tmp[$i]) - FIRSTCH;
                    $eti -= ($eti >= 2 * TABSZ) ? 2 * TABSZ : ($eti >= TABSZ) ? TABSZ : 0;
                    $key  = $eti;
                    $tmp[$i] = chr($eti + FIRSTCH);
                 }
            }

            $string .= $tmp;
        }

        return $string;
    }

    /**
     * Find out if a username and password is valid.
     *
     * @param string $user         The userID to check.
     * @param string $oldPassword  An old password to check.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function _lookup($user, $oldPassword)
    {
        $contents = $this->_ftp->read($this->_params['path'], $this->_params['file']);
        if (is_a($contents, 'PEAR_Error')) {
            return $contents;
        }

        $this->_contents = $this->_decode($contents);
        foreach ($this->_contents as $line) {
            if ($line[1] == $user &&
                (($line[2] == $this->_params['imaphost']) ||
                 (!empty($line[4]) && $line[4] == $this->_params['imaphost']))) {
                return $this->comparePasswords($line[0], $oldPassword);
            }
        }

        return PEAR::raiseError(_("User not found."));
    }

    /**
     * Modify (update) a pine password record for a user.
     *
     * @param string $user         The user whose record we will udpate.
     * @param string $newPassword  The new password value to set.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function _modify($user, $newPassword)
    {
        for ($i = 0; $i < sizeof($this->_contents); $i++) {
            if ($this->_contents[$i][1] == $user &&
                (($this->_contents[$i][2] == $this->_params['imaphost']) ||
                 (!empty($this->_contents[$i][4]) &&
                  $this->_contents[$i][4] == $this->_params['imaphost']))) {
                $this->_contents[$i][0] = $newPassword;
            }
        }

        $string = $this->_encode($this->_contents);
        return $this->_ftp->writeData($this->_params['path'], $this->_params['file'], $string);
    }

    /**
     * Change the user's password.
     *
     * @param string $username     The user for which to change the password.
     * @param string $oldPassword  The old (current) user password.
     * @param string $newPassword  The new user password to set.
     *
     * @return mixed  True on success or a PEAR_Error object on failure.
     */
    function changePassword($username,  $oldPassword, $newPassword)
    {
        /* Connect to the ftp server. */
        $res = $this->_connect($username, $this->_params['use_new_passwd'] ? $newPassword : $oldPassword);
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        /* Check the current password. */
        $res = $this->_lookup($username, $oldPassword);
        if (is_a($res, 'PEAR_Error'))  {
            return $res;
        }

        $res = $this->_modify($username, $newPassword);

        $this->_disconnect();

        return $res;
    }

}
