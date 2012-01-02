<?php
/**
 * The vpopmail class attempts to change a user's password for vpopmail based
 * servers.  It is very similar to the more generic sql driver, and the two
 * should probably be merged into one driver if possible.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
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
 * @author  Ralf Lang <lang@b1-systems.de>
 * @package Passwd
 */
class Passwd_Driver_Vpopmail extends Passwd_Driver
{
    /**
     * The Horde_Db object.
     *
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
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     *
     * @throws Passwd_Exception
     */
    public function __construct($params = array())
    {
        if (isset($params['db'])) {
            $this->_db = $params['db'];
            unset($params['db']);
        } else {
            throw new Passwd_Exception('Missing required Horde_Db_Adapter object');
        }

        /* Use defaults from Horde. */
        $this->_params = array_merge(
            Horde::getDriverConfig('', 'sql'),
            array('table'            => 'horde_users',
                  'encryption'       => 'crypt',
                  'name'             => 'pw_name',
                  'domain'           => 'pw_domain',
                  'passwd'           => 'pw_passwd',
                  'clear_passwd'     => 'pw_clear_passwd',
                  'use_clear_passwd' => false,
                  'show_encryption'  => false),
            $params);
    }


    /**
     * Finds out if a username and password is valid.
     *
     * @param string $username      The username to check.
     * @param string $old_password  An old password to check.
     *
     * @throws Passwd_Exception
     */
    protected function _lookup($username, $old_password)
    {
        /* Only split up username if domain is set in backend configuration. */
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
        $this->_comparePasswords($current_password, $old_password);
    }

    /**
     * Modifies a SQL password record for a user.
     *
     * @param string $username      The user whose record we will udpate.
     * @param string $new_password  The new password value to set.
     *
     * @throws Passwd_Exception
     */
    protected function _modify($username, $new_password)
    {
        /* Only split up username if domain is set in backend. */
        if ($this->_params['domain']) {
            list($name, $domain) = explode('@', $username);
        } else {
            $name = $username;
        }

        /* Encrypt the password. */
        $clear_password = $new_password;
        $new_password = $this->_encryptPassword($new_password);

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
    public function changePassword($username, $old_password, $new_password)
    {
        /* Check the current password. */
        $this->_lookup($username, $old_password);
        $this->_modify($username, $new_password);
    }
}
