<?php
/**
 * The vpopmail class attempts to change a user's password for vpopmail based
 * servers.  It is very similar to the more generic sql driver, and the two
 * should probably be merged into one driver if possible.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * WARNING: This driver has only formally been converted to Horde 4. 
 * No testing has been done. If this doesn't work, please file bugs at
 * bugs.horde.org
 * If you really need this to work reliably, think about sponsoring development
 * Please send a mail to lang -at- b1-systems.de if you can verify this driver to work
 *
 * @author  Anton Nekhoroshikh <anton@valuehost.ru>
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Ilya Krel <mail@krel.org>
 * @author  Tjeerd van der Zee <admin@xar.nl>
 * @author  Mattias Webjörn Eriksson <mattias@webjorn.org>
 * @author  Eric Jon Rostetter <eric.rostetter@physics.utexas.edu>
 * @author  Ralf Lang <lang@b1-systems.de>
 * @since   Passwd 2.2
 * @package Passwd
 */
class Passwd_Driver_Vpopmail extends Passwd_Driver {

    /**
     * The Horde_Db object
     * @var Horde_Db_Adapter
     */
    protected  $_db;

    /**
     * State of SQL connection.
     *
     * @var boolean
     */
    protected  $_connected = false;

    /**
     * Constructs a new Passwd_Driver_Vpopmail object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    function __construct($params = array())
    {
        if (isset($params['db'])) {
            $this->_db = $params['db'];
            unset($params['db']);
        } else {
            throw new Passwd_Exception('Missing required Horde_Db_Adapter object');
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
     * Find out if a username and password is valid.
     *
     * @param string $username      The username to check.
     * @param string $old_password  An old password to check.
     *
     * @return boolean              True on valid or throw Passwd_Exception on invalid.
     */
    function _lookup($username, $old_password)
    {

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

        /* Execute the query. */

        // This part is directly taken from Passwd_Driver_Sql. Maybe vpopmail should be based on it?
        try {
            $result = $this->_db->selectOne($sql, $values);
        } catch (Horde_Db_Exception $e) {
             throw new Passwd_Exception($e);
        }

        if (is_array($result)) {
            $current_password = $result[$this->_params['passwd']];
        } else {
            throw new Passwd_Exception(_("User not found"));
        }
        /* Check the passwords match. */
        return $this->comparePasswords($current_password, $old_password);

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

        /* Execute the query. */
        try {
            $this->_db->update($sql, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Passwd_Exception($e);
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
        $this->_lookup($username, $old_password);
        return $this->_modify($username, $new_password);
    }

}
