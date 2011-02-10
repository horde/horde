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
            'username_field' => 'user_uid'
        ), $params);

        $params['password_field'] = Horde_String::lower($params['password_field']);
        $params['username_field'] = Horde_String::lower($params['username_field']);

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
        $query = sprintf('INSERT INTO %s (%s, %s) VALUES (?, ?)',
                         $this->_params['table'],
                         $this->_params['username_field'],
                         $this->_params['password_field']);
        $values = array($userId,
                        Horde_Auth::getCryptedPassword($credentials['password'],
                                                  '',
                                                  $this->_params['encryption'],
                                                  $this->_params['show_encryption']));

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

        if (empty($this->_params['soft_expiration_window'])) {
            if (!empty($this->_params['soft_expiration_field'])) {
                $query .= ', ' . $this->_params['soft_expiration_field'] . ' = ?';
                $values[] = null;
            }
        } else {
            $datea = localtime(time(), true);
            $date = mktime($datea['tm_hour'], $datea['tm_min'],
                           $datea['tm_sec'], $datea['tm_mon'] + 1,
                           $datea['tm_mday'] + $this->_params['soft_expiration_window'],
                           $datea['tm_year']);

            $query .= ', ' . $this->_params['soft_expiration_field'] . ' = ?';
            $values[] = $date;

            if (empty($this->_params['hard_expiration_window'])) {
                $values[] = null;
            } else {
                $datea = localtime($date, true);
                $values[] = mktime($datea['tm_hour'], $datea['tm_min'],
                            $datea['tm_sec'], $datea['tm_mon'] + 1,
                            $datea['tm_mday'] + $this->_params['soft_expiration_window'],
                            $datea['tm_year']);
             }
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
        $query = sprintf('UPDATE %s SET %s = ? WHERE %s = ?',
                         $this->_params['table'],
                         $this->_params['password_field'],
                         $this->_params['username_field']);
        $values = array(Horde_Auth::getCryptedPassword($password,
                                                  '',
                                                  $this->_params['encryption'],
                                                  $this->_params['show_encryption']),
                        $userId);

        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }

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
    public function listUsers()
    {
        /* Build the SQL query. */
        $query = sprintf('SELECT %s FROM %s',
                         $this->_params['username_field'],
                         $this->_params['table']);

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

}
