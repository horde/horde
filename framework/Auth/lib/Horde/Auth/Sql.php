<?php
/**
 * The Horde_Auth_Sql class provides a SQL implementation of the Horde
 * authentication system.
 *
 * Required parameters:
 * <pre>
 * 'phptype' - (string) The database type (ie. 'pgsql', 'mysql', etc.).
 * </pre>
 *
 * Optional parameters:
 * <pre>
 * 'encryption' - (string) The encryption to use to store the password in
 *                the table (e.g. plain, crypt, md5-hex, md5-base64, smd5,
 *                sha, ssha, aprmd5).
 *                DEFAULT: 'md5-hex'
 * 'hard_expiration_field' - (string) The name of the field containing a date
 *                           after which the account is no longer valid and
 *                           the user will not be able to log in at all.
 *                           DEFAULT: none
 * 'password_field' - (string) The name of the password field in the auth
 *                    table.
 *                    DEFAULT: 'user_pass'
 * 'show_encryption' - (boolean) Whether or not to prepend the encryption in
 *                     the password field.
 *                     DEFAULT: false
 * 'soft_expiration_field' - (string) The name of the field containing a date
 *                           after which the system will request the user
 *                           change his or her password.
 *                           DEFAULT: none
 * 'table' - (string) The name of the SQL table to use in 'database'.
 *           DEFAULT: 'horde_users'
 * 'username_field' - (string) The name of the username field in the auth
 *                    table.
 *                    DEFAULT: 'user_uid'
 * </pre>
 *
 * Required by some database implementations:
 * <pre>
 * 'hostspec'     The hostname of the database server.
 * 'protocol'     The communication protocol ('tcp', 'unix', etc.).
 * 'database'     The name of the database.
 * 'username'     The username with which to connect to the database.
 * 'password'     The password associated with 'username'.
 * 'options'      Additional options to pass to the database.
 * 'port'         The port on which to connect to the database.
 * 'tty'          The TTY on which to connect to the database.
 * </pre>
 *
 * Optional values when using separate read and write servers, for example
 * in replication settings:
 * <pre>
 * 'splitread'   Boolean, whether to implement the separation or not.
 * 'read'        Array containing the parameters which are different for
 *               the read database connection, currently supported
 *               only 'hostspec' and 'port' parameters.
 * </pre>
 *
 * The table structure for the Auth system is in
 * horde/scripts/sql/horde_users.sql.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Auth
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
        'update'        => true
    );

    /**
     * Handle for the current database connection.
     *
     * @var DB
     */
    protected $_db;

    /**
     * Handle for the current database connection, used for writing. Defaults
     * to the same handle as $_db if a separate write database is not required.
     *
     * @var DB
     */
    protected $_write_db;

    /**
     * Boolean indicating whether or not we're connected to the SQL server.
     *
     * @var boolean
     */
    protected $_connected = false;

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
        try {
            $this->_connect();
        } catch (Horde_Auth_Exception $e) {
            Horde::logMessage($e, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
        }

        /* Build the SQL query. */
        $query = sprintf('SELECT * FROM %s WHERE %s = ?',
                         $this->_params['table'],
                         $this->_params['username_field']);
        $values = array($userId);

        Horde::logMessage('SQL Query by Auth_sql::_authenticate(): ' . $query, __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $result = $this->_db->query($query, $values);
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
        }

        $row = $result->fetchRow(DB_GETMODE_ASSOC);
        if (is_array($row)) {
            $result->free();
        } else {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }

        if (!$this->_comparePasswords($row[$this->_params['password_field']],
                                      $credentials['password'])) {
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
            $this->_authCredentials['changeRequested'] = true;
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
        $this->_connect();

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

        Horde::logMessage('SQL Query by Auth_sql::addUser(): ' . $query, __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $result = $this->_write_db->query($query, $values);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Auth_Exception($result);
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
        $this->_connect();

        /* Build the SQL query. */
        $tuple = array();
        $tuple[$this->_params['username_field']] = $newID;
        $tuple[$this->_params['password_field']] =
            Horde_Auth::getCryptedPassword($credentials['password'],
                                      '',
                                      $this->_params['encryption'],
                                      $this->_params['show_encryption']);

        if (empty($this->_params['soft_expiration_window'])) {
            if (!empty($this->_params['soft_expiration_field'])) {
                $tuple[$this->_params['soft_expiration_field']] = null;
            }
        } else {
            $date = time();
            $datea = localtime($date, true);
            $date = mktime($datea['tm_hour'], $datea['tm_min'],
                           $datea['tm_sec'], $datea['tm_mon'] + 1,
                           $datea['tm_mday'] + $this->_params['soft_expiration_window'],
                           $datea['tm_year']);

            $tuple[$this->_params['soft_expiration_field']] = $date;

            global $notification;
            if (!empty($notification)) {
                $notification->push(strftime(_("New password will expire on %s."), $date), 'horde.message');
            }

            if (empty($this->_params['hard_expiration_window'])) {
                $tuple[$this->_params['hard_expiration_field']] = null;
            } else {
                $datea = localtime($date, true);
                $date = mktime($datea['tm_hour'], $datea['tm_min'],
                               $datea['tm_sec'], $datea['tm_mon'] + 1,
                               $datea['tm_mday'] + $this->_params['soft_expiration_window'],
                               $datea['tm_year']);

                $tuple[$this->_params['hard_expiration_field']] = $date;
            }
        }

        $query = sprintf('UPDATE %s SET %s WHERE %s = ?',
                         $this->_params['table'],
                         Horde_SQL::updateValues($this->_write_db, $tuple),
                         $this->_params['username_field']);
        $values = array($oldID);

        Horde::logMessage('SQL Query by Auth_sql:updateUser(): ' . $query, __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $result = $this->_write_db->query($query, $values);
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Auth_Exception($result);
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
        $this->_connect();

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

        Horde::logMessage('SQL Query by Auth_sql::resetPassword(): ' . $query, __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $result = $this->_write_db->query($query, $values);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Auth_Exception($result);
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
        $this->_connect();

        /* Build the SQL query. */
        $query = sprintf('DELETE FROM %s WHERE %s = ?',
                         $this->_params['table'],
                         $this->_params['username_field']);
        $values = array($userId);

        Horde::logMessage('SQL Query by Auth_sql::removeUser(): ' . $query, __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $result = $this->_write_db->query($query, $values);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Auth_Exception($result);
        }

        Horde_Auth::removeUserData($userId);
    }

    /**
     * List all users in the system.
     *
     * @return array  The array of userIds.
     * @throws Horde_Auth_Exception
     */
    public function listUsers()
    {
        $this->_connect();

        /* Build the SQL query. */
        $query = sprintf('SELECT %s FROM %s',
                         $this->_params['username_field'],
                         $this->_params['table']);

        Horde::logMessage('SQL Query by Auth_sql::listUsers(): ' . $query, __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $res = $this->_db->getCol($query);
        if ($res instanceof PEAR_Error) {
            throw new Horde_Auth_Exception($res);
        }

        return $res;
    }

    /**
     * Checks if a userId exists in the system.
     *
     * @return boolean  Whether or not the userId already exists.
     */
    public function exists($userId)
    {
        $this->_connect();

        /* Build the SQL query. */
        $query = sprintf('SELECT 1 FROM %s WHERE %s = ?',
                         $this->_params['table'],
                         $this->_params['username_field']);
        $values = array($userId);

        Horde::logMessage('SQL Query by Auth_sql::exists(): ' . $query, __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $res = $this->_db->getOne($query, $values);
        return ($res instanceof PEAR_Error)
            ? false
            : $res;
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
     * Attempts to open a connection to the SQL server.
     *
     * @throws Horde_Auth_Exception
     */
    function _connect()
    {
        if ($this->_connected) {
            return;
        }

        Horde::assertDriverConfig($this->_params, 'auth', array('phptype'),
                                  'authentication SQL');

        if (!isset($this->_params['database'])) {
            $this->_params['database'] = '';
        }
        if (!isset($this->_params['username'])) {
            $this->_params['username'] = '';
        }
        if (!isset($this->_params['password'])) {
            $this->_params['password'] = '';
        }
        if (!isset($this->_params['hostspec'])) {
            $this->_params['hostspec'] = '';
        }
        if (empty($this->_params['encryption'])) {
            $this->_params['encryption'] = 'md5-hex';
        }
        if (!isset($this->_params['show_encryption'])) {
            $this->_params['show_encryption'] = false;
        }
        if (empty($this->_params['table'])) {
            $this->_params['table'] = 'horde_users';
        }
        if (empty($this->_params['username_field'])) {
            $this->_params['username_field'] = 'user_uid';
        } else {
            $this->_params['username_field'] = Horde_String::lower($this->_params['username_field']);
        }
        if (empty($this->_params['password_field'])) {
            $this->_params['password_field'] = 'user_pass';
        } else {
            $this->_params['password_field'] = Horde_String::lower($this->_params['password_field']);
        }

        /* Connect to the SQL server using the supplied parameters. */
        $this->_write_db = DB::connect($this->_params,
                                       array('persistent' => !empty($this->_params['persistent']),
                                             'ssl' => !empty($this->_params['ssl'])));
        if ($this->_write_db instanceof PEAR_Error) {
            throw new Horde_Auth_Exception($this->_write_db);
        }

        // Set DB portability options.
        switch ($this->_write_db->phptype) {
        case 'mssql':
            $this->_write_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
            break;
        default:
            $this->_write_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
        }

        /* Check if we need to set up the read DB connection
         * seperately. */
        if (!empty($this->_params['splitread'])) {
            $params = array_merge($this->_params, $this->_params['read']);
            $this->_db = DB::connect($params,
                                     array('persistent' => !empty($params['persistent']),
                                           'ssl' => !empty($params['ssl'])));
            if ($this->_db instanceof PEAR_Error) {
                throw new Horde_Auth_Exception($this->_db);
            }

            switch ($this->_db->phptype) {
            case 'mssql':
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
                break;
            default:
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
            }

        } else {
            /* Default to the same DB handle for reads. */
            $this->_db =& $this->_write_db;
        }

        $this->_connected = true;
    }

}
