<?php
/**
 * The SQL driver attempts to change a user's password stored in an SQL
 * database and implements the Passwd_Driver API.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Ilya Krel <mail@krel.org>
 * @author  Tjeerd van der Zee <admin@xar.nl>
 * @author  Mattias Webjörn Eriksson <mattias@webjorn.org>
 * @author  Eric Jon Rostetter <eric.rostetter@physics.utexas.edu>
 * @author  Ralf Lang <lang@b1-systems.de> (H4 conversion)
 * @package Passwd
 */
class Passwd_Driver_Sql extends Passwd_Driver
{
    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Constructor.
     *
     * @param array $params  Additional parameters needed:
     * <pre>
     * 'db' - (Horde_Db_Adapter) A DB Adapter object.
     * optional:
     * 'table'           - (string)  The name of the user database table
     * 'encryption'      - (string)  The encryption type
     * 'user_col'        - (string)  The table column for user name
     * 'pass_col'        - (string)  The table column for password
     * 'show_encryption' - (boolean) Prepend the encryption type to the password?
     * 'query_lookup'    - (string)  Should we use a custom query for lookup?
     * 'query_modify'    - (string)  Should we use a custom query for changing?
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct($params = array())
    {
        if (isset($params['db'])) {
            $this->_db = $params['db'];
            unset($params['db']);
        } else {
            throw new InvalidArgumentException('Missing required Horde_Db_Adapter object');
        }
        /* These default to matching the Auth_sql defaults. */
        $this->_params = array_merge(
            array('table'           => 'horde_users',
                  'encryption'      => 'ssha',
                  'user_col'        => 'user_uid',
                  'pass_col'        => 'user_pass',
                  'show_encryption' => false,
                  'query_lookup'    => false,
                  'query_modify'    => false),
            $params);
    }

     /**
      * Finds out if a username and password is valid.
      *
      * @param string $userID        The userID to check.
      * @param string $old_password  An old password to check.
     *
     * @throws Passwd_Exception
      */
    protected function _lookup($user, $old_password)
    {
        if (!empty($this->_params['query_lookup'])) {
            list($sql, $values) = $this->_parseQuery($this->_params['query_lookup'], $user, $old_password);
        } else {
            /* Build the SQL query. */
            $sql  = 'SELECT ' . $this->_params['pass_col'] . ' FROM ' . $this->_params['table'] .
                    ' WHERE ' . $this->_params['user_col'] . ' = ?';
            $values = array($user);
        }

        /* Run query. */
        try {
            $result = $this->_db->selectOne($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Passwd_Exception($e);
        }

        if (is_array($result)) {
            $current_password = $result[$this->_params['pass_col']];
        } else {
            throw new Passwd_Exception(_("User not found"));
        }

        /* Check the passwords match. */
        $this->_comparePasswords($current_password, $old_password);
    }

    /**
     * Modifies a SQL password record for a user.
     *
     * @param string $user          The user whose record we will udpate.
     * @param string $new_password  The new password value to set.
     *
     * @throws Passwd_Exception
     */
    protected function _modify($user, $new_password)
    {
        if (!empty($this->_params['query_modify'])) {
            list($sql, $values) = $this->_parseQuery($this->_params['query_modify'], $user, $new_password);
        } else {
            /* Encrypt the password. */
            $new_password = $this->_encryptPassword($new_password);

            /* Build the SQL query. */
            $sql = 'UPDATE ' . $this->_params['table'] .
                   ' SET ' . $this->_params['pass_col'] . ' = ?' .
                   ' WHERE ' . $this->_params['user_col'] . ' = ?';
            $values = array($new_password, $user);
        }

        /* Execute the query. */
        try {
            $this->_db->update($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Passwd_Exception($e);
        }
    }

    /**
     * Parses the string as an SQL query substituting placeholders for
     * their values.
     *
     * @param string $string    The string to process as a query.
     * @param string $user      The user to use for the %u placeholder.
     * @param string $password  The password to use for the %p and %e placeholders.
     *
     * @return string  The processed SQL query.
     */
    protected function _parseQuery($string, $user, $password)
    {
        $query = '';
        $values = array();
        $length = strlen($string);
        @list($username, $domain) = explode('@', $user);
        for ($i = 0; $i < $length; $i++) {
            if ($string[$i] == '%' && !empty($string[$i + 1])) {
                switch ($string[++$i]) {
                case 'd':
                    $query .= '?';
                    $values[] = $domain;
                    break;

                case 'u':
                    $query .= '?';
                    $values[] = $user;
                    break;

                case 'U':
                    $query .= '?';
                    $values[] = $username;
                    break;

                case 'p':
                    $query .= '?';
                    $values[] = $password;
                    break;

                case 'e':
                    $query .= '?';
                    $values[] = $this->_encryptPassword($password);
                    break;

                case '%':
                    $query .= '%';
                    break;

                default:
                    $query .= '%' . $string[$i];
                    break;
                }
            } else {
                $query .= $string[$i];
            }
        }

        return array($query, $values);
    }

    /**
     * Changes the user's password.
     *
     * @param string $username      The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @throws Passwd_Exception
     */
    public function changePassword($username,  $old_password, $new_password)
    {
        /* Check the current password. */
        $this->_lookup($username, $old_password);
        $this->_modify($username, $new_password);
    }
}
