<?php
/**
 * The vpopmail class attempts to change a user's password for vpopmail based
 * servers.  It is very similar to the more generic sql driver, and the two
 * should probably be merged into one driver if possible.
 *
 * $Horde: passwd/lib/Driver/vpopmail.php,v 1.17.2.6 2009/01/06 15:25:23 jan Exp $
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Anton Nekhoroshikh <anton@valuehost.ru>
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Ilya Krel <mail@krel.org>
 * @author  Tjeerd van der Zee <admin@xar.nl>
 * @author  Mattias Webjörn Eriksson <mattias@webjorn.org>
 * @author  Eric Jon Rostetter <eric.rostetter@physics.utexas.edu>
 * @since   Passwd 2.2
 * @package Passwd
 */
class Passwd_Driver_vpopmail extends Passwd_Driver {

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
     * Constructs a new Passwd_Driver_vpopmail object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    function Passwd_Driver_vpopmail($params = array())
    {
        if (isset($params['phptype'])) {
            $this->_params['phptype'] = $params['phptype'];
        } else {
            return PEAR::raiseError(_("Required 'phptype' not specified in SQL configuration."));
        }

        /* Use defaults from Horde. */
        $defaults = Horde::getDriverConfig('', 'sql');
        $this->_params['hostspec']   = isset($params['hostspec'])   ? $params['hostspec'] : $defaults['hostspec'];
        $this->_params['protocol']   = isset($params['protocol'])   ? $params['protocol'] : $defaults['protocol'];
        $this->_params['username']   = isset($params['username'])   ? $params['username'] : $defaults['username'];
        $this->_params['password']   = isset($params['password'])   ? $params['password'] : $defaults['password'];
        $this->_params['database']   = isset($params['database'])   ? $params['database'] : $defaults['database'];

        /* Defaults to match Auth::sql default. */
        $this->_params['table']      = isset($params['table'])      ? $params['table'] : 'horde_users';
        $this->_params['encryption'] = isset($params['encryption']) ? $params['encryption'] : 'crypt';
        $this->_params['name']       = isset($params['name'])       ? $params['name'] : 'pw_name';
        $this->_params['domain']     = isset($params['domain'])     ? $params['domain'] : 'pw_domain';
        $this->_params['passwd']     = isset($params['passwd'])     ? $params['passwd'] : 'pw_passwd';
        $this->_params['clear_passwd'] = isset($params['clear_passwd'])     ? $params['clear_passwd'] : 'pw_clear_passwd';
        $this->_params['use_clear_passwd'] = isset($params['use_clear_passwd'])     ? $params['use_clear_passwd'] : false;
        $this->_params['show_encryption'] = isset($params['show_encryption']) ? $params['show_encryption'] : false;
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
            $this->_db = &DB::connect($this->_params, true);
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
     * @param string $username      The username to check.
     * @param string $old_password  An old password to check.
     *
     * @return boolean              True on valid or PEAR_Error on invalid.
     */
    function _lookup($username, $old_password)
    {
        /* Connect to the database. */
        $res = $this->_connect();
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        /* Only split up username if domain is set in backend
         * configuration. */
        if (!empty($this->_params['domain'])) {
            list($name, $domain) = explode('@', $username);
        } else {
            $name = $username;
        }

        /* Build the SQL query. */
        $sql = 'SELECT ' . $this->_params['passwd'] .
               ' FROM ' . $this->_params['table'] .
               ' WHERE ' . $this->_params['name'] . ' = ?';
        $values = array($name);
        if ($this->_params['domain']) {
            $sql .= ' AND ' . $this->_params['domain'] . ' = ?';
            $values[] = $domain;
        }
        Horde::logMessage('SQL Query by Passwd_Driver_vpopmail::_lookup(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Execute the query. */
        $result = $this->_db->query($sql, $values);
        if (!is_a($result, 'PEAR_Error')) {
            $row = $result->fetchRow(DB_FETCHMODE_ASSOC);
            $result->free();
            if (is_array($row)) {
                /* Get the password from the database. */
                $current_password = $row[$this->_params['passwd']];

                /* See if the passwords match. */
                return $this->comparePasswords($current_password, $old_password);
            }
        }

        return PEAR::raiseError(_("User not found"));
    }

    /**
     * Modify (update) a mysql password record for a user.
     *
     * @param string $username      The user whose record we will udpate.
     * @param string $new_password  The new password value to set.
     *
     * @return boolean  True or False based on success of the modify.
     */
    function _modify($username, $new_password)
    {
        /* Connect to the database. */
        $res = $this->_connect();
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        /* Only split up username if domain is set in backend. */
        if ($this->_params['domain']) {
            list($name, $domain) = explode('@', $username);
        } else {
            $name = $username;
        }

        /* Encrypt the password. */
        $clear_password = $new_password;
        $new_password = $this->encryptPassword($new_password, $this->_params['show_encryption']);

        /* Build the SQL query. */
        $sql = 'UPDATE ' . $this->_params['table'] .
               ' SET ' . $this->_params['passwd'] . ' = ?';
        $values = array($new_password);
        if ($this->_params['use_clear_passwd']) {
            $sql .= ', ' . $this->_params['clear_passwd'] . ' = ?';
            $values[] = $clear_password;
        }
        $sql .= ' WHERE ' . $this->_params['name'] . ' = ?';
        $values[] = $name;
        if ($this->_params['domain']) {
            $sql .= ' AND ' . $this->_params['domain'] . ' = ?';
            $values[] = $domain;
        }
        Horde::logMessage('SQL Query by Passwd_Driver_vpopmail::_modify(): ' . $sql, __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Execute the query. */
        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        return true;
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
    function changePassword($username, $old_password, $new_password)
    {
        /* Check the current password. */
        $result = $this->_lookup($username, $old_password);
        if (is_a($result, 'PEAR_Error'))  {
            return $result;
        }

        return $this->_modify($username, $new_password);
    }

}
