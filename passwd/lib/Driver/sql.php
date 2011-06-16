<?php
/**
 * The SQL driver attempts to change a user's password stored in an SQL
 * database and implements the Passwd_Driver API.
 *
 * Copyright 2000-2011 The Horde Project (http://www.horde.org/)
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
class Passwd_Driver_sql extends Passwd_Driver {

    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */

    protected $_db;

    /**
     * Constructs a new Passwd_Driver_sql object.
     *
     * @param string $name   The source name
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
     */
    function __construct($params = array())
    {
        if (isset($params['db'])) {
            $this->_db = $params['db'];
            unset($params['db']);
        } else {
            throw new InvalidArgumentException('Missing required Horde_Db_Adapter object');
        }
        /* These default to matching the Auth_sql defaults. */
        $this->_params['table'] = isset($params['table']) ? $params['table'] : 'horde_users';
        $this->_params['encryption'] = isset($params['encryption']) ? $params['encryption'] : 'ssha';
        $this->_params['user_col'] = isset($params['user_col']) ? $params['user_col'] : 'user_uid';
        $this->_params['pass_col'] = isset($params['pass_col']) ? $params['pass_col'] : 'user_pass';
        $this->_params['show_encryption'] = isset($params['show_encryption']) ? $params['show_encryption'] : false;
        $this->_params['query_lookup'] = isset($params['query_lookup']) ? $params['query_lookup'] : false;
        $this->_params['query_modify'] = isset($params['query_modify']) ? $params['query_modify'] : false;
    }

     /**
     * Find out if a username and password is valid.
     *
     * @param string $userID        The userID to check.
     * @param string $old_password  An old password to check.
     *
     * @return boolean  True on valid
     */
    function _lookup($user, $old_password)
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
        Horde::logMessage('SQL Query by Passwd_Driver_sql::_lookup(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);

        if (is_array($result)) {
            $current_password = $result[$this->_params['pass_col']];
        } else {
            throw new Passwd_Exception(_("User not found"));
        }
        /* Check the passwords match. */
        return $this->comparePasswords($current_password, $old_password);
    }

    /**
     * Modify (update) a mysql password record for a user.
     *
     * @param string $user          The user whose record we will udpate.
     * @param string $new_password  The new password value to set.
     *
     * @return boolean  True or False based on success of the modify.
     */
    function _modify($user, $new_password)
    {
        if (!empty($this->_params['query_modify'])) {
            list($sql, $values) = $this->_parseQuery($this->_params['query_modify'], $user, $new_password);
        } else {
            /* Encrypt the password. */
            $new_password = $this->encryptPassword($new_password, $this->_params['show_encryption']);

            /* Build the SQL query. */
            $sql = 'UPDATE ' . $this->_params['table'] .
                   ' SET ' . $this->_params['pass_col'] . ' = ?' .
                   ' WHERE ' . $this->_params['user_col'] . ' = ?';
            $values = array($new_password, $user);
        }
        Horde::logMessage('SQL Query by Passwd_Driver_sql::_modify(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Execute the query. */

        try {
            $this->_db->update($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Passwd_Exception($e);
        }

        return true;
    }

    /**
     * Parse the string as an SQL query substituting placeholders for
     * their values.
     *
     * @param string $string    The string to process as a query.
     * @param string $user      The user to use for the %u placeholder.
     * @param string $password  The password to use for the %p and %e placeholders.
     *
     * @return string  The processed SQL query.
     */
    function _parseQuery($string, $user, $password)
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
                    $values[] = $this->encryptPassword($password, $this->_params['show_encryption']);
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
     * Change the user's password.
     *
     * @param string $username      The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @return boolean  True or false based on success of the change.
     */
    function changePassword($username,  $old_password, $new_password)
    {
        /* Check the current password. */
        $res = $this->_lookup($username, $old_password);

        return $this->_modify($username, $new_password);
    }

}
