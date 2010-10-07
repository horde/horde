<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author Marko Djukic <marko@oblo.com>
 * @package Vilma
 */
class Vilma_Driver_sql extends Vilma_Driver {

    /**
     * @var DB
     */
    var $_db;

    function Vilma_Driver_sql($params)
    {
        parent::Vilma_Driver($params);
        $this->initialise();
    }

    /**
     * Construct an SQL WHERE fragment to filter domains by domain key.
     *
     * @access private
     *
     * @param string $join  Keyword to join expression to rest of SQL statement
     *                      (e.g. 'WHERE' or 'AND'). Default: 'WHERE'.
     *
     * @return array  An SQL fragment and a list of values suitable for
     *                binding.
     */
    function _getDomainKeyFilter($join = 'WHERE')
    {
        if (empty($this->_params['tables']['domainkey'])) {
            return array('', array());
        }

        return array(' ' . $join . ' domain_key = ?',
                     array($this->_params['tables']['domainkey']));
    }

    /**
     * Construct an SQL WHERE fragment to filter users by domain key.
     *
     * @access private
     *
     * @param string $join  Keyword to join expression to rest of SQL statement
     *                      (e.g. 'WHERE' or 'AND'). Default: 'WHERE'.
     *
     * @return array  An SQL fragment and a list of values suitable for
     *                binding.
     */
    function _getUserKeyFilter($join = 'WHERE')
    {
        if (empty($this->_params['tables']['domainkey'])) {
            return array('', array());
        }
        $binds = $this->_getDomainKeyFilter('AND');

        return array(' ' . $join . ' EXISTS (SELECT domain_name' .
                     ' FROM ' . $this->_params['tables']['domains'] .
                     ' WHERE ' . $this->_getTableField('users', 'user_name') .
                     ' LIKE ? || ' . $this->_getTableField('domains', 'domain_name') .
                     ' ' . $binds[0] . ' )',
                     array_unshift($binds[1], '%@'));
    }

    /**
     * Construct an SQL WHERE fragment to filter virtuals by domain key.
     *
     * @access private
     *
     * @param string $join  Keyword to join expression to rest of SQL statement
     *                      (e.g. 'WHERE' or 'AND').  Default: 'WHERE'.
     *
     * @return string  An SQL fragment.
     */
    function _getVirtualKeyFilter($join = 'WHERE')
    {
        if (empty($this->_params['tables']['domainkey'])) {
            return array('', array());
        }
        $binds = $this->_getDomainKeyFilter('AND');

        return array(' ' . $join . ' EXISTS (SELECT domain_name' .
                     ' FROM ' . $this->_params['tables']['domains'] .
                     ' WHERE ' . $this->_getTableField('virtuals', 'virtual_email') .
                     ' LIKE ? || ' . $this->_getTableField('domains', 'domain_name') .
                     ' ' . $binds[0] . ' )',
                     array_unshift($binds[1], '%@'));
    }

    /**
     * Gets the list of fields from specific table for sql statement.
     *
     * @return string
     */
    function _getTableFields($table)
    {
        if (empty($this->_params['tables'][$table . '_fields'])){
            return '*';
        }

        $domainsFields = $this->_params['tables'][$table . '_fields'];
        foreach ($domainsFields as $defaultName => $customName) {
            $fields[] = $customName . ' as ' . $defaultName;
        }
        return implode(', ', $fields);
    }

    /**
     * Gets the real name of the field from specific table for sql statement.
     *
     * @return string
     */
    function _getTableField($table, $field)
    {
        if (empty($this->_params['tables'][$table . '_fields'])) {
            return $field;
        } else {
            return $this->_params['tables'][$table . '_fields'][$field];
        }
    }

    /**
     *
     *
     * @return array
     */
    function _prepareRecord($table, $record)
    {
        if (empty($this->_params['tables'][$table . '_fields'])){
            return $record;
        }

        $domainsFields = $this->_params['tables'][$table . '_fields'];
        $newRecord = array();
        foreach ($record as $defaultName => $value) {
            $newRecord[$domainsFields[$defaultName]] = $record[$defaultName];
        }
        return $newRecord;
    }

    /**
     * Gets the list of domains from the backend.
     *
     * @return array  All the domains and their data in an array.
     */
    function getDomains()
    {
        $binds = $this->_getDomainKeyFilter();
        $sql = 'SELECT '. $this->_getTableFields('domains') . ' FROM ' . $this->_params['tables']['domains'] .
               $binds[0] . ' ORDER BY domain_name';
        $values = $binds[1];

        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->getAll($sql, $values, DB_FETCHMODE_ASSOC);
    }

    /**
     * Gets the specified domain information from the backend.
     *
     * @param integer $domain_id  The id of the domain to fetch.
     *
     * @return array  The domain's information in an array.
     */
    function getDomain($domain_id)
    {
        $binds = $this->_getDomainKeyFilter('AND');
        $sql = 'SELECT '. $this->_getTableFields('domains') . ' FROM ' . $this->_params['tables']['domains'] .
               ' WHERE ' . $this->_getTableField('domains', 'domain_id') . ' = ?' . $binds[0];
        array_unshift($binds[1], (int)$domain_id);

        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->getRow($sql, $binds[1], DB_FETCHMODE_ASSOC);
    }

    /**
     * Given a domain name returns the information from the backend.
     *
     * @param string $name  The name of the domain to fetch.
     *
     * @return array  The domain's information in an array.
     */
    function getDomainByName($domain_name)
    {
        $binds = $this->_getDomainKeyFilter('AND');
        $sql = 'SELECT '. $this->_getTableFields('domains') . ' FROM ' . $this->_params['tables']['domains'] .
               ' WHERE ' . $this->_getTableField('domains', 'domain_name') . ' = ?' . $binds[0];
        array_unshift($binds[1], $domain_name);

        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->getRow($sql, $binds[1], DB_FETCHMODE_ASSOC);
    }

    /**
     * Returns all available users, if a domain name is passed then limit the
     * list of users only to those users.
     *
     * @param string $domain  The name of the domain for which to fetch the
     *                        users.
     *
     * @return array  The available users and their stored information.
     */
    function getUsers($domain = null)
    {
        /* Put together the SQL statement. */
        if (is_null($domain)) {
            /* Fetch all users. */
            $binds = $this->_getUserKeyFilter();
            $sql = 'SELECT '. $this->_getTableFields('users') . ' FROM ' . $this->_params['tables']['users'] .
                   $binds[0];
            $values = $binds[1];
        } else {
            /* Fetch only users for a domain. */
            $binds = $this->_getUserKeyFilter('AND');
            $sql = 'SELECT '. $this->_getTableFields('users') . ' FROM ' . $this->_params['tables']['users'] .
                   ' WHERE ' . $this->_getTableField('users', 'user_name') . ' LIKE ?' . $binds[0] .
                   ' ORDER BY user_name';
            array_unshift($binds[1], '%@' . $domain);
            $values = $binds[1];
        }

        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->getAll($sql, $values, DB_FETCHMODE_ASSOC);
    }

    /**
     * Returns the user information for a given user id.
     *
     * @param integer $user_id  The id of the user for which to fetch
     *                          information.
     *
     * @return array  The user information.
     */
    function getUser($user_id)
    {
        $binds = $this->_getUserKeyFilter('AND');
        $sql = 'SELECT '. $this->_getTableFields('users') . ' FROM ' . $this->_params['tables']['users'] .
               ' WHERE ' . $this->_getTableField('users', 'user_id') . ' = ?' . $binds[0];
        array_unshift($binds[1], (int)$user_id);

        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->getRow($sql, $binds[1], DB_FETCHMODE_ASSOC);
    }

    /**
     * Returns available virtual emails.
     *
     * @param string $filter  If passed a domain then return all virtual emails
     *                        for the domain, otherwise if passed a user name
     *                        return all virtual emails for that user.
     *
     * @return array  The available virtual emails.
     */
    function getVirtuals($filter)
    {
        /* Check if filtering only for domain. */
        if (($pos = strpos($filter, '@')) === false) {
            $where = $this->_getTableField('virtuals', 'virtual_email') . ' LIKE ?';
            $values = array('%@' . $filter);
        } else {
            $where = $this->_getTableField('virtuals', 'virtual_destination') . ' = ?';
            $values = array($filter);
        }

        $binds = $this->_getVirtualKeyFilter('AND');
        $sql = 'SELECT '. $this->_getTableFields('virtuals') . ' FROM ' . $this->_params['tables']['virtuals'] .
               ' WHERE ' . $where . $binds[0] .
               ' ORDER BY virtual_destination, virtual_email';
        $values = array_merge($values, $binds[1]);

        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->getAll($sql, $values, DB_FETCHMODE_ASSOC);
    }

    /**
     * Returns information for a virtual id.
     *
     * @param integer $virtual_id  The virtual id for which to return
     *                             information.
     *
     * @return array  The virtual email information.
     */
    function getVirtual($virtual_id)
    {
        $binds = $this->_getVirtualKeyFilter('AND');
        $sql = 'SELECT '. $this->_getTableFields('virtuals') . ' FROM ' . $this->_params['tables']['virtuals'] .
               ' WHERE ' . $this->_getTableField('virtuals', 'virtual_id') . ' = ?' . $binds[0];
        array_unshift($binds[1], (int)$virtual_id);

        Horde::logMessage($sql, 'DEBUG');
        $virtual = $this->_db->getRow($sql, $binds[1], DB_FETCHMODE_ASSOC);
        $virtual['stripped_email'] = Vilma::stripUser($virtual['virtual_email']);

        return $virtual;
    }

    /**
     * Returns the current number of set up users for a domain.
     *
     * @param string $domain_name  The name of the domain for which to
     *                             get the current number of users.
     *
     * @return integer  The current number of users.
     */
    function getDomainNumUsers($domain_name)
    {
        $binds = $this->_getUserKeyFilter('AND');
        $sql = 'SELECT count(' . $this->_getTableField('users', 'user_name') . ')' .
               ' FROM ' . $this->_params['tables']['users'] .
               ' WHERE ' . $this->_getTableField('users', 'user_name') . ' LIKE ?' . $binds[0];
        array_unshift($binds[1], '%@' . $domain_name);

        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->getOne($sql, $binds[1]);
    }

    /**
     * Saves a domain to the backend.
     *
     * @param array $info  The domain information to save to the backend.
     *
     * @return mixed  True on success or PEAR error otherwise.
     */
    function _saveDomain($info)
    {
        require_once 'Horde/SQL.php';

        $record = array('domain_name' => $info['name'],
                        'domain_transport' => $info['transport'],
                        'domain_max_users' => (int)$info['max_users'],
                        'domain_quota' => (int)$info['quota']);

        if (empty($info['domain_id'])) {
            $record['domain_id'] = $this->_db->nextId($this->_params['tables']['domains']);
            if (!empty($this->_params['tables']['domainkey'])) {
                $record['domain_key'] = $this->_params['tables']['domainkey'];
            }

            $sql = 'INSERT INTO ' . $this->_params['tables']['domains'] .
                ' ' . Horde_SQL::insertValues($this->_db, $this->_prepareRecord('domains', $record));
            $values = array();
        } else {
            $binds = $this->_getDomainKeyFilter('AND');
            $sql = 'UPDATE ' . $this->_params['tables']['domains'] .
                   ' SET ' . Horde_SQL::updateValues($this->_db, $this->_prepareRecord('domains', $record)) .
                   ' WHERE ' . $this->_getTableField('domains', 'domain_id') . ' = ?' . $binds[0];
            array_unshift($binds[1], $info['domain_id']);
            $values = $binds[1];
        }

        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->query($sql, $values);
    }

    /**
     * Deletes a given domain.
     *
     * @param integer $domain_id  The id of the domain to delete.
     *
     * @return mixed  True on success or PEAR error otherwise.
     */
    function _deleteDomain($domain_id)
    {
        $domain_record = $this->getDomain($domain_id);
        if (is_a($domain_record, 'PEAR_Error')) {
            return $domain_record;
        }

        $domain_name = $domain_record['domain_name'];

        /* Delete all virtual emails for this domain. */
        $sql = 'DELETE FROM ' . $this->_params['tables']['virtuals'] .
               ' WHERE ' . $this->_getTableField('virtuals', 'virtual_email') . ' LIKE ?';
        $values = array('%@' . $domain_name);
        $delete = $this->_db->query($sql, $values);
        if (is_a($delete, 'PEAR_Error')) {
            return $delete;
        }

        /* Delete all users for this domain. */
        $sql = 'DELETE FROM ' . $this->_params['tables']['users'] .
               ' WHERE ' . $this->_getTableField('users', 'user_name') . ' LIKE ?';
        $values = array('%@' . $domain_name);
        $delete = $this->_db->query($sql, $values);
        if (is_a($delete, 'PEAR_Error')) {
            return $delete;
        }

        /* Finally delete the domain. */
        $sql = 'DELETE FROM ' . $this->_params['tables']['domains'] .
               ' WHERE ' . $this->_getTableField('domains', 'domain_id') . ' = ?';
        $values = array((int)$domain_id);

        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->query($sql, $values);
    }

    /**
     * Saves a user to the backend.
     *
     * @param array $info  The user information to save.
     *
     * @return array  The user information.
     */
    function _saveUser($info)
    {
        global $conf;

        require_once 'Horde/SQL.php';

        /* Access check (for domainkey). */
        $res = $this->getDomainByName(Vilma::stripDomain($info['user_name']));
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        $mailboxes = &Vilma::getMailboxDriver();
        if (is_a($mailboxes, 'PEAR_Error')) {
            return $mailboxes;
        }

        if (empty($info['user_id'])) {
            $info['user_id'] = $this->_db->nextId($this->_params['tables']['users']);
            $create = true;
        } else {
            $create = false;
        }

        // Slightly hackish.
        $mail_dir_base = isset($mailboxes->_params['mail_dir_base']) ?
                         $mailboxes->_params['mail_dir_base'] : '?';

        $tuple = array('user_id' =>         (int)$info['user_id'],
                       'user_name' =>       $info['user_name'],
                       'user_full_name' =>  $info['user_full_name'],
                       'user_home_dir' =>   $mail_dir_base,
                       'user_mail_dir' =>   Vilma::stripDomain($info['user_name']) . '/' . Vilma::stripUser($info['user_name']) . '/',
                       'user_mail_quota' => $this->getDomainQuota(Vilma::stripDomain($info['user_name'])) * 1024 * 1024,
                       'user_enabled' =>    (int)$info['user_enabled']);

        // UID and GID are slightly hackish (specific to maildrop driver), too
        if (!isset($mailboxes->_params['uid'])) {
            $tuple['user_uid'] = -1;
        } else {
            $tuple['user_uid'] = $mailboxes->_params['uid'];
        }
        if (!isset($mailboxes->_params['gid'])) {
            $tuple['user_gid'] = -1;
        } else {
            $tuple['user_gid'] = $mailboxes->_params['gid'];
        }

        if (!empty($info['password'])) {
            $tuple['user_clear'] = $info['password'];
            $tuple['user_crypt'] = crypt($info['password'],
                                         substr($info['password'], 0, 2));
        } elseif ($create) {
            return PEAR::raiseError(_("Password must be supplied when creating a new user."));
        }

        if ($create) {
            $sql = 'INSERT INTO ' .
                $this->_params['tables']['users'] . ' ' .
                Horde_SQL::insertValues($this->_db, $this->_prepareRecord('users', $tuple));
        } else {
            $sql = sprintf('UPDATE %s SET %s WHERE ' . $this->_getTableField('users', 'user_id') . ' = %d',
                           $this->_params['tables']['users'],
                           Horde_SQL::updateValues($this->_db, $this->_prepareRecord('users', $tuple)),
                           (int)$info['user_id']);
        }

        Horde::logMessage($sql, 'DEBUG');
        $result = $this->_db->query($sql);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, 'ERR');
            return $result;
        }

        return $info;
    }

    /**
     * Deletes a requested user.
     *
     * @param integer $user_id  The id of the user to delete.
     *
     * @return mixed  True, or PEAR_Error on failure.
     */
    function _deleteUser($user_id)
    {
        $user = $this->getUser($user_id);
        if (is_a($user, 'PEAR_Error')) {
            return $user;
        }

        /* Delete all virtual emails for this user. */
        $sql = 'DELETE FROM ' . $this->_params['tables']['virtuals'] .
               ' WHERE ' . $this->_getTableField('virtuals', 'virtual_destination') . ' = ?';
        $values = array($user['user_name']);

        Horde::logMessage($sql, 'DEBUG');
        $delete = $this->_db->query($sql, $values);
        if (is_a($delete, 'PEAR_Error')) {
            return $delete;
        }

        /* Delete the actual user. */
        $sql = 'DELETE FROM ' . $this->_params['tables']['users'] .
               ' WHERE ' . $this->_getTableField('users', 'user_id') . ' = ?';
        $values = array((int)$user_id);

        Horde::logMessage($sql, 'DEBUG');
        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $mailboxes = &Vilma::getMailboxDriver();
        if (is_a($mailboxes, 'PEAR_Error')) {
            return $mailboxes;
        }

        return $mailboxes->deleteMailbox(Vilma::stripUser($user['user_name']),
                                         Vilma::stripDomain($user['user_name']));
    }

    /**
     * Saves virtual email address to the backend.
     *
     * @param array $info     The virtual email data.
     * @param string $domain  The name of the domain for this virtual email.
     *
     * @return mixed  True on success or PEAR error otherwise.
     */
    function saveVirtual(&$info, $domain)
    {
        /* Access check (for domainkey) */
        $res = $this->getDomainByName($domain);
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        if (empty($info['virtual_id'])) {
            $info['virtual_id'] = $this->_db->nextId($this->_params['tables']['virtuals']);
            $sql = 'INSERT INTO ' . $this->_params['tables']['virtuals'] .
                ' (' . $this->_getTableField('virtuals', 'virtual_email') . ', ' .
                       $this->_getTableField('virtuals', 'virtual_destination') . ', ' .
                       $this->_getTableField('virtuals', 'virtual_id') . ') VALUES (?, ?, ?)';
        } else {
            $sql = 'UPDATE ' . $this->_params['tables']['virtuals'] .
                ' SET ' . $this->_getTableField('virtuals', 'virtual_email') . ' = ?, '.
                          $this->_getTableField('virtuals', 'virtual_destination') . ' = ?' .
                ' WHERE ' . $this->_getTableField('virtuals', 'virtual_id') . ' = ?';
        }
        $values = array($info['stripped_email'] . '@' . $domain,
                        $info['virtual_destination'],
                        $info['virtual_id']);

        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->query($sql, $values);
    }

    /**
     * Deletes a virtual email.
     *
     * @param integer $virtual_id  The id of the virtual email to delete.
     */
    function deleteVirtual($virtual_id)
    {
        $binds = $this->_getVirtualKeyFilter('AND');
        $sql = 'DELETE FROM ' . $this->_params['tables']['virtuals'] .
               ' WHERE ' . $this->_getTableField('virtuals', 'virtual_id') . ' = ?' . $binds[0];
        array_unshift($binds[1], $virtual_id);

        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->query($sql, $binds[1]);
    }

    /**
     * Initialise this backend, connect to the SQL database.
     *
     * @return mixed  True on success or PEAR error otherwise.
     */
    function initialise()
    {
        try {
            $this->_db = $GLOBALS['injector']->getInstance('Horde_Core_Factory_DbPear')->create('rw', 'vilma', 'storage');
        } catch (Horde_Exception $e) {
            return PEAR::raiseError($e->getMessage());
        }

        /* Use default table names if these are not set. */
        if (!isset($this->_params['tables']['domains'])) {
            $this->_params['tables']['domains'] = 'vilma_domains';
        }
        if (!isset($this->_params['tables']['users'])) {
            $this->_params['tables']['users'] = 'vilma_users';
        }
        if (!isset($this->_params['tables']['virtuals'])) {
            $this->_params['tables']['virtuals'] = 'vilma_virtuals';
        }

        return true;
    }

}
