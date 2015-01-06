<?php
/**
 * Copyright 2000-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2000-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */

/**
 * Changes a password stored in an SQL database.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Mattias Webjörn Eriksson <mattias@webjorn.org>
 * @author    Ilya Krel <mail@krel.org>
 * @author    Ralf Lang <lang@b1-systems.de> (H4 conversion)
 * @author    Eric Jon Rostetter <eric.rostetter@physics.utexas.edu>
 * @author    Tjeerd van der Zee <admin@xar.nl>
 * @category  Horde
 * @copyright 2000-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
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
     * @param array $params  Driver parameters:
     *   - db: (Horde_Db_Adapter; REQUIRED) A DB Adapter object.
     *   - encryption: (string) The encryption type.
     *   - pass_col: (string) The table column for password.
     *   - query_lookup: (string) Should we use a custom query for lookup?
     *   - query_modify: (string) Should we use a custom query for changing?
     *   - show_encryption: (boolean) Prepend the encryption type to the
     *                      password?
     *   - table: (string) The name of the user database table.
     *   - user_col: (string) The table column for user name.
     */
    public function __construct(array $params = array())
    {
        if (isset($params['db'])) {
            $this->_db = $params['db'];
            unset($params['db']);
        } else {
            throw new InvalidArgumentException('Missing required Horde_Db_Adapter object');
        }

        /* These default to matching the Auth_Sql defaults. */
        parent::__construct(array_merge(array(
            'encryption' => 'ssha',
            'pass_col' => 'user_pass',
            'query_lookup' => false,
            'query_modify' => false,
            'show_encryption' => false,
            'table' => 'horde_users',
            'user_col' => 'user_uid'
        ), $params));
    }

    /**
     * Finds out if a username and password is valid.
     *
     * @param string $userID   The userID to check.
     * @param string $oldpass  An old password to check.
     *
     * @throws Passwd_Exception
     */
    protected function _lookup($user, $oldpass)
    {
        if (!empty($this->_params['query_lookup'])) {
            list($sql, $values) = $this->_parseQuery($this->_params['query_lookup'], $user, $oldpass);
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

        if (!is_array($result)) {
            throw new Passwd_Exception(_("User not found"));
        }

        /* Check the passwords match. */
        $this->_comparePasswords($result[$this->_params['pass_col']], $oldpass);
    }

    /**
     * Modifies a SQL password record for a user.
     *
     * @param string $user     The user whose record we will udpate.
     * @param string $newpass  The new password value to set.
     *
     * @throws Passwd_Exception
     */
    protected function _modify($user, $newpass)
    {
        if (!empty($this->_params['query_modify'])) {
            list($sql, $values) = $this->_parseQuery($this->_params['query_modify'], $user, $newpass);
        } else {
            /* Encrypt the password. */
            $newpass= $this->_encryptPassword($newpass);

            /* Build the SQL query. */
            $sql = 'UPDATE ' . $this->_params['table'] .
                   ' SET ' . $this->_params['pass_col'] . ' = ?' .
                   ' WHERE ' . $this->_params['user_col'] . ' = ?';
            $values = array($newpass, $user);
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
     * @param string $password  The password to use for the %p and %e
     *                          placeholders.
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
     */
    protected function _changePassword($user, $oldpass, $newpass)
    {
        $this->_lookup($user, $oldpass);
        $this->_modify($user, $newpass);
    }

}
