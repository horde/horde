<?php
/**
 * The Horde_Auth_Passwd:: class provides a passwd-file implementation of
 * the Horde authentication system.
 *
 * Copyright 1997-2007 Rasmus Lerdorf <rasmus@php.net>
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Rasmus Lerdorf <rasmus@php.net>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Auth
 */
class Horde_Auth_Passwd extends Horde_Auth_Base
{
    /**
     * An array of capabilities, so that the driver can report which
     * operations it supports and which it doesn't.
     *
     * @var array
     */
    protected $_capabilities = array(
        'list' => true,
        'authenticate' => true,
    );

    /**
     * Hash list of users.
     *
     * @var array
     */
    protected $_users = null;

    /**
     * Array of groups and members.
     *
     * @var array
     */
    protected $_groups = array();

    /**
     * Filehandle for lockfile.
     *
     * @var resource
     */
    protected $_fplock;

    /**
     * Locking state.
     *
     * @var boolean
     */
    protected $_locked;

    /**
     * List of users that should be excluded from being listed/handled
     * in any way by this driver.
     *
     * @var array
     */
    protected $_exclude = array(
        'root', 'daemon', 'bin', 'sys', 'sync', 'games', 'man', 'lp', 'mail',
        'news', 'uucp', 'proxy', 'postgres', 'www-data', 'backup', 'operator',
        'list', 'irc', 'gnats', 'nobody', 'identd', 'sshd', 'gdm', 'postfix',
        'mysql', 'cyrus', 'ftp',
    );

    /**
     * Constructor.
     *
     * @param array $params  Connection parameters:
     * <pre>
     * 'encryption' - (string) The encryption to use to store the password in
     *                the table (e.g. plain, crypt, md5-hex, md5-base64, smd5,
     *                sha, ssha, aprmd5).
     *                DEFAULT: 'crypt-des'
     * 'filename' - (string) [REQUIRED] The passwd file to use.
     * 'lock' - (boolean) Should we lock the passwd file? The password file
     *          cannot be changed (add, edit, or delete users) unless this is
     *          true.
     *          DEFAULT: false
     * 'show_encryption' - (boolean) Whether or not to prepend the encryption
     *                     in the password field.
     *                     DEFAULT: false
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['filename'])) {
            throw new InvalidArgumentException('Missing filename parameter.');
        }

        $params = array_merge(array(
            'encryption' => 'crypt-des',
            'lock' => false,
            'show_encryption' => false
        ), $params);

        parent::__construct($params);
    }

    /**
     * Writes changes to passwd file and unlocks it.  Takes no arguments and
     * has no return value. Called on script shutdown.
     */
    public function __destruct()
    {
        if ($this->_locked) {
            foreach ($this->_users as $user => $pass) {
                $data = $user . ':' . $pass;
                if ($this->_users[$user]) {
                    $data .= ':' . $this->_users[$user];
                }
                fputs($this->_fplock, $data . "\n");
            }
            rename($this->_lockfile, $this->_params['filename']);
            flock($this->_fplock, LOCK_UN);
            $this->_locked = false;
            fclose($this->_fplock);
        }
    }

    /**
     * Queries the current Auth object to find out if it supports the given
     * capability.
     *
     * @param string $capability  The capability to test for.
     *
     * @return boolean  Whether or not the capability is supported.
     */
    public function hasCapability($capability)
    {
        if ($this->_params['lock']) {
            switch ($capability) {
            case 'add':
            case 'update':
            case 'resetpassword':
            case 'remove':
                return true;
            }
        }

        return parent::hasCapability($capability);
    }

    /**
     * Read and, if requested, lock the password file.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _read()
    {
        if (is_array($this->_users)) {
            return;
        }

        if (empty($this->_params['filename'])) {
            throw new Horde_Auth_Exception('No password file set.');
        }

        if ($this->_params['lock']) {
            $this->_fplock = fopen(Horde::getTempDir() . '/passwd.lock', 'w');
            flock($this->_fplock, LOCK_EX);
            $this->_locked = true;
        }

        $fp = fopen($this->_params['filename'], 'r');
        if (!$fp) {
            throw new Horde_Auth_Exception("Couldn't open '" . $this->_params['filename'] . "'.");
        }

        $this->_users = array();
        while (!feof($fp)) {
            $line = trim(fgets($fp, 128));
            if (empty($line)) {
                continue;
            }

            $parts = explode(':', $line);
            if (!count($parts)) {
                continue;
            }

            $user = $parts[0];
            $userinfo = array();
            if (strlen($user) && !in_array($user, $this->_exclude)) {
                if (isset($parts[1])) {
                    $userinfo['password'] = $parts[1];
                }
                if (isset($parts[2])) {
                    $userinfo['uid'] = $parts[2];
                }
                if (isset($parts[3])) {
                    $userinfo['gid'] = $parts[3];
                }
                if (isset($parts[4])) {
                    $userinfo['info'] = $parts[4];
                }
                if (isset($parts[5])) {
                    $userinfo['home'] = $parts[5];
                }
                if (isset($parts[6])) {
                    $userinfo['shell'] = $parts[6];
                }

                $this->_users[$user] = $userinfo;
            }
        }

        fclose($fp);

        if (!empty($this->_params['group_filename'])) {
            $fp = fopen($this->_params['group_filename'], 'r');
            if (!$fp) {
                throw new Horde_Auth_Exception("Couldn't open '" . $this->_params['group_filename'] . "'.");
            }

            $this->_groups = array();
            while (!feof($fp)) {
                $line = trim(fgets($fp));
                if (empty($line)) {
                    continue;
                }

                $parts = explode(':', $line);
                $group = array_shift($parts);
                $users = array_pop($parts);
                $this->_groups[$group] = array_flip(preg_split('/\s*[,\s]\s*/', trim($users), -1, PREG_SPLIT_NO_EMPTY));
            }

            fclose($fp);
        }
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userId      The userId to check.
     * @param array $credentials  An array of login credentials.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        if (empty($credentials['password'])) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }

        try {
            $this->_read();
        } catch (Horde_Auth_Exception $e) {
            if ($this->_logger) {
                $this->_logger->log($e, 'ERR');
            }
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
        }

        if (!isset($this->_users[$userId]) ||
            !$this->_comparePasswords($this->_users[$userId]['password'], $credentials['password'])) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }

        if (!empty($this->_params['required_groups'])) {
            $allowed = false;
            foreach ($this->_params['required_groups'] as $group) {
                if (isset($this->_groups[$group][$userId])) {
                    $allowed = true;
                    break;
                }
            }

            if (!$allowed) {
                throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
            }
        }
    }

    /**
     * List all users in the system.
     *
     * @return array  The array of userIds.
     * @throws Horde_Auth_Exception
     */
    public function listUsers()
    {
        $this->_read();

        $users = array_keys($this->_users);
        if (empty($this->_params['required_groups'])) {
            return $users;
        }

        $groupUsers = array();
        foreach ($this->_params['required_groups'] as $group) {
            $groupUsers = array_merge($groupUsers, array_intersect($users, array_keys($this->_groups[$group])));
        }

        return $groupUsers;
    }

    /**
     * Add a set of authentication credentials.
     *
     * @param string $userId      The userId to add.
     * @param array $credentials  The credentials to add.
     *
     * @throws Horde_Auth_Exception
     */
    public function addUser($userId, $credentials)
    {
        $this->_read();

        if (!$this->_locked) {
            throw new Horde_Auth_Exception('Password file not locked');
        }

        if (isset($this->_users[$userId])) {
            throw new Horde_Auth_Exception("Couldn't add user '$user', because the user already exists.");
        }

        $this->_users[$userId] = array(
            'password' => Horde_Auth::getCryptedPassword($credentials['password'],
                                                    '',
                                                    $this->_params['encryption'],
                                                    $this->_params['show_encryption']),

        );
    }

    /**
     * Update a set of authentication credentials.
     *
     * @param string $oldID        The old userId.
     * @param string $newID        The new userId.
     * @param array  $credentials  The new credentials
     *
     * @throws Horde_Auth_Exception
     */
    public function updateUser($oldID, $newID, $credentials)
    {
        $this->_read();

        if (!$this->_locked) {
            throw new Horde_Auth_Exception('Password file not locked');
        }

        if (!isset($this->_users[$userId])) {
            throw new Horde_Auth_Exception("Couldn't modify user '$oldID', because the user doesn't exist.");
        }

        $this->_users[$newID] = array(
            'password' => Horde_Auth::getCryptedPassword($credentials['password'],
                                                    '',
                                                    $this->_params['encryption'],
                                                    $this->_params['show_encryption']),
        );
        return true;
    }

    /**
     * Reset a user's password. Used for example when the user does not
     * remember the existing password.
     *
     * @param string $userId  The user id for which to reset the password.
     *
     * @return string  The new password.
     * @throws Horde_Auth_Exception
     */
    public function resetPassword($userId)
    {
        /* Get a new random password. */
        $password = Horde_Auth::genRandomPassword();
        $this->updateUser($userId, $userId, array('password' => $password));

        return $password;
    }

    /**
     * Delete a set of authentication credentials.
     *
     * @param string $userId  The userId to delete.
     *
     * @throws Horde_Auth_Exception
     */
    public function removeUser($userId)
    {
        $this->_read();

        if (!$this->_locked) {
            throw new Horde_Auth_Exception('Password file not locked');
        }

        if (!isset($this->_users[$userId])) {
            throw new Horde_Auth_Exception("Couldn't delete user '$oldID', because the user doesn't exist.");
        }

        unset($this->_users[$userId]);

        Horde_Auth::removeUserData($userId);
    }


    /**
     * Compare an encrypted password to a plaintext string to see if
     * they match.
     *
     * @param string $encrypted  The crypted password to compare against.
     * @param string $plaintext  The plaintext password to verify.
     *
     * @return boolean  True if matched, false otherwise.
     */
    protected function _comparePasswords($encrypted, $plaintext)
    {
        return $encrypted == Horde_Auth::getCryptedPassword($plaintext,
                                                       $encrypted,
                                                       $this->_params['encryption'],
                                                       $this->_params['show_encryption']);
    }

}
