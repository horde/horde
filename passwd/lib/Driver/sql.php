<?php
/**
 * The SQL driver attempts to change a user's password stored in an SQL
 * databse and implements the Passwd_Driver API.
 *
 * $Horde: passwd/lib/Driver/sql.php,v 1.24.2.8 2009/01/06 15:25:23 jan Exp $
 *
 * Copyright 2000-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Ilya Krel <mail@krel.org>
 * @author  Tjeerd van der Zee <admin@xar.nl>
 * @author  Mattias Webjörn Eriksson <mattias@webjorn.org>
 * @author  Eric Jon Rostetter <eric.rostetter@physics.utexas.edu>
 * @package Passwd
 */
class Passwd_Driver_sql extends Passwd_Driver {

    /**
     * SQL connection object.
     *
     * @var DB
     */
    var $_db;

    /**
     * State of SQL connection.
     *
     * @var boolean
     */
    var $_connected = false;

    /**
     * Constructs a new Passwd_Driver_sql object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    function Passwd_Driver_sql($params = array())
    {
        if (isset($params['phptype'])) {
            $this->_params['phptype'] = $params['phptype'];
        } else {
            return PEAR::raiseError(_("Required 'phptype' not specified in Passwd SQL configuration."));
        }

        /* Use defaults from Horde, but allow overriding in backends.php. */
        $this->_params = array_merge(Horde::getDriverConfig('', 'sql'), $params);

        /* These default to matching the Auth_sql defaults. */
        $this->_params['table'] = isset($params['table']) ? $params['table'] : 'horde_users';
        $this->_params['encryption'] = isset($params['encryption']) ? $params['encryption'] : 'md5';
        $this->_params['user_col'] = isset($params['user_col']) ? $params['user_col'] : 'user_uid';
        $this->_params['pass_col'] = isset($params['pass_col']) ? $params['pass_col'] : 'user_pass';
        $this->_params['show_encryption'] = isset($params['show_encryption']) ? $params['show_encryption'] : false;
        $this->_params['query_lookup'] = isset($params['query_lookup']) ? $params['query_lookup'] : false;
        $this->_params['query_modify'] = isset($params['query_modify']) ? $params['query_modify'] : false;
    }

    /**
     * Connect to the database.
     *
     * @return boolean  True on success or PEAR_Error on failure.
     */
    function _connect()
    {
        if (!$this->_connected) {
            /* Connect to the SQL server using the supplied parameters. */
            include_once 'DB.php';
            $this->_db = &DB::connect($this->_params,
                                      array('persistent' => !empty($this->_params['persistent'])));
            if (is_a($this->_db, 'PEAR_Error')) {
                return PEAR::raiseError(_("Unable to connect to SQL server."));
            }

            // Set DB portability options.
            switch ($this->_db->phptype) {
            case 'mssql':
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
                break;
            default:
                $this->_db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
            }

            $this->_connected = true;
        }

        return true;
    }

    /**
     * Find out if a username and password is valid.
     *
     * @param string $userID        The userID to check.
     * @param string $old_password  An old password to check.
     *
     * @return boolean  True on valid or PEAR_Error on invalid.
     */
    function _lookup($user, $old_password)
    {
        /* Connect to the database */
        $res = $this->_connect();
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        if (!empty($this->_params['query_lookup'])) {
            list($sql, $values) = $this->_parseQuery($this->_params['query_lookup'], $user, $old_password);
        } else {
            /* Build the SQL query. */
            $sql  = 'SELECT ' . $this->_params['pass_col'] . ' FROM ' . $this->_params['table'] .
                    ' WHERE ' . $this->_params['user_col'] . ' = ?';
            $values = array($user);
        }
        Horde::logMessage('SQL Query by Passwd_Driver_sql::_lookup(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Execute the query. */
        $result = $this->_db->query($sql, $values);
        if (!is_a($result, 'PEAR_Error')) {
            $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
            $result->free();
            if (is_array($row)) {
                /* Get the password from the database. */
                if (!isset($row[$this->_params['pass_col']])) {
                    return PEAR::raiseError(sprintf(_("Password column \"%s\" not found in password table."), $this->_params['pass_col']));
                }
                $current_password = $row[$this->_params['pass_col']];

                /* Check the passwords match. */
                return $this->comparePasswords($current_password, $old_password);
            }
        }
        return PEAR::raiseError(_("User not found"));
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
        /* Connect to the database. */
        $res = $this->_connect();
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

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
        $result = $this->_db->query($sql, $values);

        if (is_a($result, 'PEAR_Error')) {
            return $result;
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
        if (is_a($res, 'PEAR_Error'))  {
            return $res;
        }

        return $this->_modify($username, $new_password);
    }

}
