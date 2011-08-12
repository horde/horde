<?php
/**
 * The Horde_Auth_Sql class provides a SQL implementation of the Horde
 * authentication system.
 *
 * The table structure for the Auth system is in
 * horde/scripts/sql/horde_users.sql.
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Auth
 */
class Horde_Auth_Sql extends Horde_Auth_Base
{
    /**
     * An array of capabilities, so that the driver can report which
     * operations it supports and which it doesn't.
     *
     * @var array
     */
    protected $_capabilities = array(
        'add'           => true,
        'list'          => true,
        'remove'        => true,
        'resetpassword' => true,
        'update'        => true,
        'authenticate'  => true,
    );

    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Constructor
     *
     * @param array $params  Parameters:
     * 'db' - (Horde_Db_Adapter) [REQUIRED] Database object.
     * <pre>
     * 'encryption' - (string) The encryption to use to store the password in
     *                the table (e.g. plain, crypt, md5-hex, md5-base64, smd5,
     *                sha, ssha, aprmd5).
     *                DEFAULT: 'md5-hex'
     * 'hard_expiration_field' - (string) The name of the field containing a
     *                           date after which the account is no longer
     *                           valid and the user will not be able to log in
     *                           at all.
     *                           DEFAULT: none
     * 'password_field' - (string) The name of the password field in the auth
     *                    table.
     *                    DEFAULT: 'user_pass'
     * 'show_encryption' - (boolean) Whether or not to prepend the encryption
     *                     in the password field.
     *                     DEFAULT: false
     * 'soft_expiration_field' - (string) The name of the field containing a
     *                           date after which the system will request the
     *                           user change his or her password.
     *                           DEFAULT: none
     * 'bad_login_count_field' - (string) The name of the field containing a
     *                           number of failed logins since the last
     *                           successful login of the user
     *                           DEFAULT: none
     * 'bad_login_count_enable' - (boolean) Whether or not we count bad logins
     *                           This might affect lookup performance on 
     *                           very large horde installations
     *                           DEFAULT: false
     * 'bad_login_limit' - (integer) The number of bad logins which should
     *                           trigger the account to be locked.
     *                           0 disables this feature
     *                           DEFAULT: 0
     * 'lock_field' - (string) The name of the field containing 
     *                           the account lock status.
     *                           '' disables this feature
     *                           DEFAULT: ''
     * 'lock_expiration_field' - (string) The name of the field containing 
     *                           the time when a lock expires.
     *                           '' disables this feature
     *                           DEFAULT: ''
     * 'lock_duration' - (integer) The number of seconds a user will be locked 
     *                           after he has used wrong credentials too often.
     *                           0 means permanently
     *                           DEFAULT: 0
     * 'table' - (string) The name of the SQL table to use in 'database'.
     *           DEFAULT: 'horde_users'
     * 'username_field' - (string) The name of the username field in the auth
     *                    table.
     *                    DEFAULT: 'user_uid'
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['db'])) {
            throw new InvalidArgumentException('Missing db parameter.');
        }
        $this->_db = $params['db'];
        unset($params['db']);

        $params = array_merge(array(
            'encryption' => 'md5-hex',
            'password_field' => 'user_pass',
            'show_encryption' => false,
            'table' => 'horde_users',
            'username_field' => 'user_uid',
            'bad_login_limit' => 0,
            'bad_login_count_field' => '',
            'bad_login_count_enable' => false,
            'lock_field' => '',
            'lock_expiration_field' => '',
            'lock_duration' => '0'

        ), $params);

        $params['password_field']        = Horde_String::lower($params['password_field']);
        $params['username_field']        = Horde_String::lower($params['username_field']);
        $params['lock_field']            = Horde_String::lower($params['lock_field']);
        $params['lock_expiration_field'] = Horde_String::lower($params['lock_expiration_field']);
        $params['bad_login_count_field'] = Horde_String::lower($params['bad_login_count_field']);

        /* we can count regardless of lock configuration */
        if (($params['bad_login_count_enable'] === true) && (!empty($params['bad_login_count_field'])) ) {
            $this->_capabilities['badlogincount'] = true;
        }

        /* this should work even with we have no lock_expiration_field and don't define lock_duration */
        if (!empty($params['lock_field'])) {
            $this->_capabilities['lock'] = true;
        }

         /* however, we only allow limited locks if there is a field for it */
        if (empty($params['lock_expiration_field']) && ($params['lock_duration'] > 0)) {
            throw new InvalidArgumentException('You can only have expiring locks [lock_duration] when you have a [lock_expiration_field].');
        }
        if ((!$this->_capabilities['badlogincount']) &&
            ($params['bad_login_limit'] > 0)) {
            throw new InvalidArgumentException('You can only have [bad_login_limit] when you do count bad logins.');
        }
        if ((!$this->_capabilities['lock']) && 
            ($params['bad_login_limit'] > 0)) {
            throw new InvalidArgumentException('You cannot set [bad_login_limit] when you cannot lock accounts.');
        }
        /* Only allow limits when there is a storage configured */
        if (($params['soft_expiration_field'] == '') &&
            ($params['soft_expiration_window'] > 0)) {
            throw new InvalidArgumentException('You cannot set [soft_expiration_window] without [soft_expiration_field].');
        }

        if (($params['hard_expiration_field'] == '') && 
            ($params['hard_expiration_window'] > 0)) {
            throw new InvalidArgumentException('You cannot set [hard_expiration_window] without [hard_expiration_field].');
        }

        parent::__construct($params);
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userId      The userId to check.
     * @param array $credentials  The credentials to use.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        /* Build the SQL query. */
        $query = sprintf('SELECT * FROM %s WHERE %s = ?',
                         $this->_params['table'],
                         $this->_params['username_field']);
        $values = array($userId);

        try {
            $row = $this->_db->selectOne($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
        }

        if (!$row ||
            !$this->_comparePasswords($row[$this->_params['password_field']], $credentials['password'])) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }

        $now = time();
        if (!empty($this->_params['hard_expiration_field']) &&
            !empty($row[$this->_params['hard_expiration_field']]) &&
            ($now > $row[$this->_params['hard_expiration_field']])) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_EXPIRED);
        }

        if (!empty($this->_params['soft_expiration_field']) &&
            !empty($row[$this->_params['soft_expiration_field']]) &&
            ($now > $row[$this->_params['soft_expiration_field']])) {
            $this->setCredential('change', true);
            $this->setCredential('expire', $date);
        }
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
        /* Build the SQL query. */
        $query = sprintf('INSERT INTO %s (%s, %s',
                         $this->_params['table'],
                         $this->_params['username_field'],
                         $this->_params['password_field']);
        $query_values_part = ' VALUES (?, ?';
        $values = array($userId,
                        Horde_Auth::getCryptedPassword($credentials['password'],
                                                  '',
                                                  $this->_params['encryption'],
                                                  $this->_params['show_encryption']));
        if (!empty($this->_params['soft_expiration_field'])) {
            $query .= sprintf(', %s', $this->_params['soft_expiration_field']);
            $query_values_part .= ', ?';
            $values[] = $this->_calc_expiration('soft');
        }
        if (!empty($this->_params['hard_expiration_field'])) {
            $query .= sprintf(', %s', $this->_params['hard_expiration_field']);
            $query_values_part .= ', ?';
            $values[] = $this->_calc_expiration('hard');
        }
        $query .= ')' . $query_values_part . ')';

        try {
            $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
    }

    /**
     * Update a set of authentication credentials.
     *
     * @param string $oldID       The old userId.
     * @param string $newID       The new userId.
     * @param array $credentials  The new credentials
     *
     * @throws Horde_Auth_Exception
     */
    public function updateUser($oldID, $newID, $credentials)
    {
        $query = sprintf('UPDATE %s SET ', $this->_params['table']);
        $values = array();

        /* Build the SQL query. */
        $query .= $this->_params['username_field'] . ' = ?';
        $values[] = $newID;

        $query .= ', ' . $this->_params['password_field'] . ' = ?';
        $values[] = Horde_Auth::getCryptedPassword($credentials['password'], '', $this->_params['encryption'], $this->_params['show_encryption']);
        if (!empty($this->_params['soft_expiration_field'])) {
                $query .= ', ' . $this->_params['soft_expiration_field'] . ' = ?';
                $values[] =  $this->_calc_expiration('soft');
        }
        if (!empty($this->_params['hard_expiration_field'])) {
                $query .= ', ' . $this->_params['hard_expiration_field'] . ' = ?';
                $values[] =  $this->_calc_expiration('hard');
        }

        $query .= sprintf(' WHERE %s = ?', $this->_params['username_field']);
        $values[] = $oldID;

        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
    }

    /**
     * Reset a user's password. Used for example when the user does not
     * remember the existing password.
     *
     * @param string $userId  The user id for which to reset the password.
     *
     * @return string  The new password on success.
     * @throws Horde_Auth_Exception
     */
    public function resetPassword($userId)
    {
        /* Get a new random password. */
        $password = Horde_Auth::genRandomPassword();

        /* Build the SQL query. */
        $query = sprintf('UPDATE %s SET %s = ?',
                         $this->_params['table'],
                         $this->_params['password_field']);
        $values = array(Horde_Auth::getCryptedPassword($password,
                                                  '',
                                                  $this->_params['encryption'],
                                                  $this->_params['show_encryption']));
        if (!empty($this->_params['soft_expiration_field'])) {
                $query .= ', ' . $this->_params['soft_expiration_field'] . ' = ?';
                $values[] =  $this->_calc_expiration('soft');
        }
        if (!empty($this->_params['hard_expiration_field'])) {
                $query .= ', ' . $this->_params['hard_expiration_field'] . ' = ?';
                $values[] =  $this->_calc_expiration('hard');
        }
        $query .= sprintf(' WHERE %s = ?', $this->_params['username_field']);
        $values[] = $userId;
        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }

        return $password;
    }

    /**
     * Checks if $userId is currently locked.
     *
     * @param string  $userId      The userId to check.
     * @param boolean $details     Toggle array format with timeout.
     *
     * @throws Horde_Auth_Exception
     */
    public function isLocked($userId, $show_details = false)
    {
        $userId = trim($userId);
        if (!$this->hasCapability('lock')) {
            throw new Horde_Auth_Exception('No lock_field was configured');
        }
        /* Build the SQL query. */
        $query = sprintf('SELECT * FROM %s WHERE %s = ?',
                         $this->_params['table'],
                         $this->_params['username_field']);
        $values = array($userId);

        try {
            $row = $this->_db->selectOne($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Auth_Exception('User not found', Horde_Auth::REASON_MESSAGE);
        }
        
        $details = array('locked' => (bool)$row[$this->_params['lock_field']], 'lock_timeout' => 0 );

        if ($this->_params['lock_expiration_field']) {
            if ($row[$this->_params['lock_expiration_field']] > 0 ) {
                $now = time();
                if ($now > $row[$this->_params['lock_expiration_field']]) {
                    /* clean the table */
                    $this->unlockUser($userId, true);
                    $details = array('locked' => false, 'lock_timeout' => 0);
                } else {
                    $details['lock_timeout'] = $row[$this->_params['lock_expiration_field']];
                }
            }
        }
        if ($show_details == true) {
            return $details;
        } else {
            return $details['locked'];
        }
    }

    /**
     * Locks a user indefinitely or for a specified time
     *
     * @param string $userId      The userId to lock.
     * @param integer $time       The duration in seconds, 0 = permanent
     *
     * @throws Horde_Auth_Exception
     */
    public function lockUser($userId, $time = 0)
    {
        $userId = trim($userId);
        if (!$this->hasCapability('lock')) {
            throw new Horde_Auth_Exception('Tried to lock a user when no lock_field was configured', Horde_Auth::REASON_MESSAGE);
        }
        /* prevent users from shortening a permanent or long-running lock by triggering some other lock */
        if ($this->isLocked($userId)) {
            throw new Horde_Auth_Exception('User is already locked', Horde_Auth::REASON_MESSAGE);
        }
        /* Build the SQL query. */
        $query = sprintf('UPDATE %s SET %s = ?',
                         $this->_params['table'],
                         $this->_params['lock_field']);
        $values = array(true);

        if (!$this->_params['lock_expiration_field'] == '') {
            if ($time > 0) {
                $expiration_datetime = new DateTime;
                /* more elegant but php 5.3+: $now->add(); */
                $expiration_datetime->modify(sprintf("+%s second", $time));
                /* more elegant but php 5.3+: $now->getTimestamp(); */
                $time = $expiration_datetime->format("U");
            }

            $query .= ', ' . $this->_params['lock_expiration_field'] . ' = ?';
            $values[] =  $time;
        }
        $query .= sprintf(' WHERE %s = ?', $this->_params['username_field']);
        $values[] = $userId;
        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }

    }

    /**
     * Unlocks a user and optionally resets bad login count
     *
     * @param string  $userId          The userId to unlock.
     * @param boolean $resetBadLogins  Reset bad login counter, default no.
     *
     * @throws Horde_Auth_Exception
     */
    public function unlockUser($userId, $resetBadLogins = false)
    {
        $userId = trim($userId);
        if (!$this->hasCapability('lock')) {
            throw new Horde_Auth_Exception('No lock_field was configured', Horde_Auth::REASON_MESSAGE);
        }
        /* Build the SQL query. */
        $query = sprintf('UPDATE %s SET %s = ?',
                         $this->_params['table'],
                         $this->_params['lock_field']);
        $values = array(false);

        if (!$this->_params['lock_expiration_field'] == '') {
            $query .= ', ' . $this->_params['lock_expiration_field'] . ' = ?';
            $values[] =  0;
        }
        if (!$this->_params['bad_login_count_field'] == '' && $resetBadLogins) {
            $query .= ', ' . $this->_params['bad_login_count_field'] . ' = ?';
            $values[] =  0;
        }

        $query .= sprintf(' WHERE %s = ?', $this->_params['username_field']);
        $values[] = $userId;
        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
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
        /* Build the SQL query. */
        $query = sprintf('DELETE FROM %s WHERE %s = ?',
                         $this->_params['table'],
                         $this->_params['username_field']);
        $values = array($userId);

        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
    }

    /**
     * List all users in the system.
     *
     * @return array  The array of userIds.
     * @throws Horde_Auth_Exception
     */
    public function listUsers($sort = false)
    {
        /* Build the SQL query. */
        $query = sprintf('SELECT %s FROM %s',
                         $this->_params['username_field'],
                         $this->_params['table']);
        if ($sort) {
            $query .= sprintf('ORDER BY %s ASC',
                               $this->_params['username_field']);
        }

        try {
            return $this->_db->selectValues($query);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
    }

    /**
     * Checks if a userId exists in the system.
     *
     * @return boolean  Whether or not the userId already exists.
     */
    public function exists($userId)
    {
        /* Build the SQL query. */
        $query = sprintf('SELECT 1 FROM %s WHERE %s = ?',
                         $this->_params['table'],
                         $this->_params['username_field']);
        $values = array($userId);

        try {
            return (bool)$this->_db->selectValue($query, $values);
        } catch (Horde_Db_Exception $e) {
            return false;
        }
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

    /**
     * Handles a bad login
     *
     * @param string  $userId      The userId with bad login.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _badLogin($userId)
    {
        if (!$this->hasCapability('badlogincount')) {
            throw new Horde_Auth_Exception('Unsupported.');
        } else {
            $query = sprintf('UPDATE %s SET %s = %s + 1  WHERE %s = ?',
                                $this->_params['table'],
                                $this->_params['bad_login_count_field'],
                                $this->_params['bad_login_count_field'],
                                $this->_params['username_field']);

            $values = array($userId);
            try {
                $this->_db->update($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Auth_Exception($e);
            }
            if ($this->params['bad_login_limit'] > 0) {
                $query = sprintf('SELECT %s FROM %s WHERE %s = ?',
                    $this->_params['bad_login_count_field'],
                    $this->_params['table'],
                    $this->_params['username_field']);
                $values = array($userId);
                try {
                    $row = $this->_db->selectOne($query, $values);
                    } catch (Horde_Db_Exception $e) {
                    throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
                }
                if ($row[$this->_params['bad_login_count_field']] >= $this->_params['bad_login_limit']) {
                    $this->lockUser($userId, $this->_params['lock_duration']);
                }
            }
        }
    }

    /**
     * Reset the bad login counter
     *
     * @param string  $userId      The userId to reset.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _resetBadLogins($userId)
    {
        if (!$this->hasCapability('badlogincount')) {
            throw new Horde_Auth_Exception('Unsupported.');
        } else {
            $query = sprintf('UPDATE %s SET %s = ?  WHERE %s = ?',
                                $this->_params['table'],
                                $this->_params['bad_login_count_field'],
                                $this->_params['username_field']);

            $values = array(0, $userId);
            try {
                $this->_db->update($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Auth_Exception($e);
            }
        }
    }

    /**
     * Calculate a timestamp and return it along with the field name
     *
     * @param string $type The timestamp parameter.
     *
     * @return integer 'timestamp' intended field value or null
     */

    private function _calc_expiration($type)
    {
        if (!empty($this->_params[$type . '_expiration_field'])) {
            $return['field'] = $this->_params[$type.'_expiration_field'];
        }
        if (empty($this->_params[$type . '_expiration_window'])) {
            return null;
        } else {
            $expiration_datetime = new DateTime;
            /* more elegant but php 5.3+: $now->add(); */
            $expiration_datetime->modify(sprintf("+%s day", $this->_params[$type.'_expiration_window']));
            /* more elegant but php 5.3+: $now->getTimestamp(); */
            return $expiration_datetime->format("U");
        }
    }
}
