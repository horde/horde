<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */

/**
 * The vpopmail class attempts to change a user's password for vpopmail based
 * servers.  It is very similar to the more generic sql driver, and the two
 * should probably be merged into one driver if possible.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Mattias Webjörn Eriksson <mattias@webjorn.org>
 * @author    Ilya Krel <mail@krel.org>
 * @author    Ralf Lang <lang@b1-systems.de>
 * @author    Anton Nekhoroshikh <anton@valuehost.ru>
 * @author    Eric Jon Rostetter <eric.rostetter@physics.utexas.edu>
 * @author    Tjeerd van der Zee <admin@xar.nl>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
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
     */
    public function __construct($params = array())
    {
        if (!isset($params['db'])) {
            throw new Passwd_Exception('Missing required Horde_Db_Adapter object');
        }

        $this->_db = $params['db'];
        unset($params['db']);

        /* Use defaults from Horde. */
        parent::__construct(array_merge(
            Horde::getDriverConfig('', 'sql'),
            array(
                'clear_passwd' => 'pw_clear_passwd',
                'domain' => 'pw_domain',
                'encryption' => 'crypt',
                'name' => 'pw_name',
                'passwd' => 'pw_passwd',
                'show_encryption' => false,
                'table' => 'horde_users',
                'use_clear_passwd' => false
            ),
            $params
        ));
    }


    /**
     * Finds out if a username and password is valid.
     *
     * @param string $user     The username to check.
     * @param string $oldpass  An old password to check.
     *
     * @throws Passwd_Exception
     */
    protected function _lookup($user, $oldpass)
    {
        /* Only split up username if domain is set in backend configuration. */
        if (!empty($this->_params['domain'])) {
            list($name, $domain) = explode('@', $user);
        } else {
            $name = $user;
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

        if (!is_array($result)) {
            throw new Passwd_Exception(_("User not found"));
        }

        /* Check the passwords match. */
        $this->_comparePasswords($result[$this->_params['passwd']], $oldpass);
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
        /* Only split up username if domain is set in backend. */
        if ($this->_params['domain']) {
            list($name, $domain) = explode('@', $user);
        } else {
            $name = $user;
        }

        /* Encrypt the password. */
        $clear_password = $newpass;
        $newpass = $this->_encryptPassword($newpass);

        /* Build the SQL query. */
        $sql = 'UPDATE ' . $this->_params['table'] .
               ' SET ' . $this->_params['passwd'] . ' = ?';
        $values = array($newpass);
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
     */
    protected function _changePassword($user, $oldpass, $newpass)
    {
        $this->_lookup($user, $oldpass);
        $this->_modify($user, $newpass);
    }

}
