<?php
/**
 * The Pine class attempts to change a user's password on a in a pine password
 * file.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Max Kalika <max@horde.org>
 * @package Passwd
 */
class Passwd_Driver_Pine extends Passwd_Driver
{
    /**
     * Lower boundary character.
     */
    const FIRSTCH = 0x20;

    /**
     * Upper boundary character.
     */
    const LASTCH = 0x7e;

    /**
     * Median character.
     */
    const TABSZ = 0x5f;

    /**
     * Horde_Vfs instance.
     *
     * @var VFS
     */
    protected $_ftp;

    /**
     * Boolean which contains state of the ftp connection.
     *
     * @var boolean
     */
    protected $_connected = false;

    /**
     * Contents array of the pine password file.
     *
     * @var array
     */
    protected $_contents = array();

    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        $this->_params = array_merge(
            array(
                /* We self-encrypt here, so plaintext is needed. */
                'encryption' => 'plain',
                'show_encryption' => false,

                /* Sensible FTP server parameters. */
                'host' => 'localhost',
                'port' => 21,
                'path' => '',
                'file' => '.pinepw',

                /* Connect to FTP server using just-passed-in credentials?
                 * Only useful if using the composite driver and changing
                 * system (FTP) password prior to this one. */
                'use_new_passwd' => false,

                /* What host to look for on each line? */
                'imaphost' => 'localhost'),
            $params);
    }

    /**
     * Connects to the FTP server.
     *
     * @throws Passwd_Exception
     */
    protected function _connect($user, $password)
    {
        if ($this->_connected) {
            return;
        }

        $params = array(
            'username' => $user,
            'password' => $password,
            'hostspec' => $this->_params['host'],
            'port' => $this->_params['port'],
        );

        try {
            $this->_ftp = Horde_Vfs::factory('ftp', $params);
            $this->_ftp->checkCredentials();
        } catch (Horde_Vfs_Exception $e) {
            throw new Passwd_Exception($e); 
        }

        $this->_connected = true;
    }

    /**
     * Disconnect from the FTP server.
     *
     * @throws Passwd_Exception
     */
    protected function _disconnect()
    {
        if (!$this->_connected) {
            return;
        }

        try {
            $this->_ftp->disconnect();
        } catch (Horde_Vfs_Exception $e) {
            throw new Passwd_Exception($e); 
        }
        $this->_connected = false;
    }

    /**
     * Decodes a Pine-encoded password string.
     *
     * The algorithm is borrowed from read_passfile() and xlate_out() functions
     * in pine/imap.c file distributed in the Pine source archive.
     *
     * @param string $string  The contents of a pine-encoded password file.
     *
     * @return array  List of lines of decoded elements.
     */
    protected function _decode($string)
    {
        $list = array();

        $lines = explode("\n", $string);
        for ($n = 0; $n < sizeof($lines); $n++) {
            $key = $n;
            $tmp = $lines[$n];
            for ($i = 0; $i < strlen($tmp); $i++) {
                if ((ord($tmp[$i]) >= self::FIRSTCH) &&
                    (ord($tmp[$i]) <= self::LASTCH)) {
                    $xch  = ord($tmp[$i]) - ($dti = $key);
                    $xch += ($xch < self::FIRSTCH - self::TABSZ)
                        ? 2 * self::TABSZ
                        : ($xch < self::FIRSTCH) ? self::TABSZ : 0;
                    $dti  = ($xch - self::FIRSTCH) + $dti;
                    $dti -= ($dti >= 2 * self::TABSZ)
                        ? 2 * self::TABSZ
                        : ($dti >= self::TABSZ) ? self::TABSZ : 0;
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
     *
     * The algorith is borrowed from write_passfile() and xlate_in() functions
     * in pine/imap.c file distributed in the Pine source archive.
     *
     * @param array  $lines  List of lines of decoded elements
     *
     * @return array  Contents of a pine-readable password file.
     */
    protected function _encode($lines)
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
                if ((ord($tmp[$i]) >= self::FIRSTCH) &&
                    (ord($tmp[$i]) <= self::LASTCH)) {
                    $eti += ord($tmp[$i]) - self::FIRSTCH;
                    $eti -= ($eti >= 2 * self::TABSZ)
                        ? 2 * self::TABSZ
                        : ($eti >= self::TABSZ) ? self::TABSZ : 0;
                    $key  = $eti;
                    $tmp[$i] = chr($eti + self::FIRSTCH);
                 }
            }

            $string .= $tmp;
        }

        return $string;
    }

    /**
     * Finds out if a username and password is valid.
     *
     * @param string $user         The userID to check.
     * @param string $oldPassword  An old password to check.
     *
     * @throws Passwd_Exception
     */
    protected function _lookup($user, $oldPassword)
    {
        try {
            $contents = $this->_ftp->read($this->_params['path'],
                                          $this->_params['file']);
        } catch (Horde_Vfs_Exception $e) {
            throw new Passwd_Exception($e); 
        }

        $this->_contents = $this->_decode($contents);
        foreach ($this->_contents as $line) {
            if ($line[1] == $user &&
                (($line[2] == $this->_params['imaphost']) ||
                 (!empty($line[4]) && $line[4] == $this->_params['imaphost']))) {
                $this->_comparePasswords($line[0], $oldPassword);
                return;
            }
        }

        throw new Passwd_Exception(_("User not found."));
    }

    /**
     * Modifies a pine password record for a user.
     *
     * @param string $user         The user whose record we will udpate.
     * @param string $newPassword  The new password value to set.
     *
     * @throws Passwd_Exception
     */
    protected function _modify($user, $newPassword)
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
        try {
            $this->_ftp->writeData($this->_params['path'],
                                   $this->_params['file'],
                                   $string);
        } catch (Horde_Vfs_Exception $e) {
            throw new Passwd_Exception($e); 
        }
    }

    /**
     * Changes the user's password.
     *
     * @param string $username     The user for which to change the password.
     * @param string $oldPassword  The old (current) user password.
     * @param string $newPassword  The new user password to set.
     *
     * @throws Passwd_Exception
     */
    public function changePassword($username,  $oldPassword, $newPassword)
    {
        /* Connect to the ftp server. */
        $this->_connect($username, $this->_params['use_new_passwd'] ? $newPassword : $oldPassword);

        /* Check the current password. */
        $this->_lookup($username, $oldPassword);

        $this->_modify($username, $newPassword);

        $this->_disconnect();
    }
}
