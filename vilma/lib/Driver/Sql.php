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
class Vilma_Driver_sql extends Vilma_Driver
{
    /**
     * @var DB
     */
    protected $_db;

    /**
     * Constructor.
     *
     * @param array $params  Any parameters needed for this driver.
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->_initialize();
    }

    /**
     * Returns the list of domains from the backend.
     *
     * @return array  All the domains and their data in an array.
     */
    public function getDomains()
    {
        $binds = $this->_getDomainKeyFilter();
        $sql = 'SELECT ' . $this->_getTableFields('domains')
            . ' FROM ' . $this->_params['tables']['domains']
            . $binds[0] . ' ORDER BY domain_name';
        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->getAll($sql, $binds[1], DB_FETCHMODE_ASSOC);
    }

    /**
     * Returns the specified domain information from the backend.
     *
     * @param integer $domain_id  The id of the domain to fetch.
     *
     * @return array  The domain's information in an array.
     */
    public function getDomain($domain_id)
    {
        $binds = $this->_getDomainKeyFilter('AND');
        $sql = 'SELECT ' . $this->_getTableFields('domains')
            . ' FROM ' . $this->_params['tables']['domains']
            . ' WHERE ' . $this->_getTableField('domains', 'domain_id') . ' = ?' . $binds[0];
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
    public function getDomainByName($domain_name)
    {
        $binds = $this->_getDomainKeyFilter('AND');
        $sql = 'SELECT ' . $this->_getTableFields('domains')
            . ' FROM ' . $this->_params['tables']['domains']
            . ' WHERE ' . $this->_getTableField('domains', 'domain_name') . ' = ?' . $binds[0];
        array_unshift($binds[1], $domain_name);
        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->getRow($sql, $binds[1], DB_FETCHMODE_ASSOC);
    }

    /**
     * Saves a domain with the provided information.
     *
     * @param array $info  Array of details to save the domain.
     */
    protected function _saveDomain($info)
    {
        $record = array('domain_name' => $info['name'],
                        'domain_transport' => $info['transport'],
                        'domain_max_users' => (int)$info['max_users'],
                        'domain_quota' => (int)$info['quota']);

        if (empty($info['domain_id'])) {
            $record['domain_id'] = $this->_db->nextId($this->_params['tables']['domains']);
            if (!empty($this->_params['tables']['domainkey'])) {
                $record['domain_key'] = $this->_params['tables']['domainkey'];
            }

            $sql = 'INSERT INTO ' . $this->_params['tables']['domains'] . ' '
                . Horde_SQL::insertValues($this->_db, $this->_prepareRecord('domains', $record));
            $values = array();
        } else {
            $binds = $this->_getDomainKeyFilter('AND');
            $sql = 'UPDATE ' . $this->_params['tables']['domains']
                . ' SET ' . Horde_SQL::updateValues($this->_db, $this->_prepareRecord('domains', $record))
                . ' WHERE ' . $this->_getTableField('domains', 'domain_id') . ' = ?' . $binds[0];
            array_unshift($binds[1], $info['domain_id']);
            $values = $binds[1];
        }

        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->query($sql, $values);
    }

    /**
     * Deletes a domain.
     *
     * @param integer $domain_id  The id of the domain to delete.
     *
     * @throws Vilma_Exception
     */
    protected function _deleteDomain($domain_id)
    {
        $domain_record = $this->getDomain($domain_id);
        $domain_name = $domain_record['domain_name'];

        /* Delete all virtual emails for this domain. */
        $sql = 'DELETE FROM ' . $this->_params['tables']['virtuals'] .
               ' WHERE ' . $this->_getTableField('virtuals', 'virtual_email') . ' LIKE ?';
        $values = array('%@' . $domain_name);
        $delete = $this->_db->query($sql, $values);
        if (is_a($delete, 'PEAR_Error')) {
            throw new Vilma_Exception($delete);
        }

        /* Delete all users for this domain. */
        $sql = 'DELETE FROM ' . $this->_params['tables']['users'] .
               ' WHERE ' . $this->_getTableField('users', 'user_name') . ' LIKE ?';
        $values = array('%@' . $domain_name);
        $delete = $this->_db->query($sql, $values);
        if (is_a($delete, 'PEAR_Error')) {
            throw new Vilma_Exception($delete);
        }

        /* Finally delete the domain. */
        $sql = 'DELETE FROM ' . $this->_params['tables']['domains'] .
               ' WHERE ' . $this->_getTableField('domains', 'domain_id') . ' = ?';
        $values = array((int)$domain_id);

        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->query($sql, $values);
    }

    /**
     * Returns the current number of users for a domain.
     *
     * @param string $domain_name  The name of the domain for which to
     *                             get the current number of users.
     *
     * @return integer  The current number of users.
     */
    public function getDomainNumUsers($domain_name)
    {
        $binds = $this->_getUserKeyFilter('AND');
        $sql = 'SELECT count(' . $this->_getTableField('users', 'user_name') . ')'
            . ' FROM ' . $this->_params['tables']['users']
            . ' WHERE ' . $this->_getTableField('users', 'user_name') . ' LIKE ?' . $binds[0];
        array_unshift($binds[1], '%@' . $domain_name);
        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->getOne($sql, $binds[1]);
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
    public function getUsers($domain = null)
    {
        /* Put together the SQL statement. */
        if (is_null($domain)) {
            /* Fetch all users. */
            $binds = $this->_getUserKeyFilter();
            $sql = 'SELECT ' . $this->_getTableFields('users')
                . ' FROM ' . $this->_params['tables']['users'] . $binds[0];
            $values = $binds[1];
        } else {
            /* Fetch only users for a domain. */
            $binds = $this->_getUserKeyFilter('AND');
            $user_field =  $this->_getTableField('users', 'user_name');
            $sql = 'SELECT ' . $this->_getTableFields('users')
                . ' FROM ' . $this->_params['tables']['users']
                . ' WHERE ' . $user_field . ' LIKE ?' . $binds[0]
                . ' ORDER BY ' . $user_field;
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
    public function getUser($user_id)
    {
        $binds = $this->_getUserKeyFilter('AND');
        $sql = 'SELECT ' . $this->_getTableFields('users')
            . ' FROM ' . $this->_params['tables']['users']
            . ' WHERE ' . $this->_getTableField('users', 'user_id') . ' = ?' . $binds[0];
        array_unshift($binds[1], (int)$user_id);
        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->getRow($sql, $binds[1], DB_FETCHMODE_ASSOC);
    }

    /**
     * Saves a user to the backend.
     *
     * @param array $info  The user information to save.
     *
     * @return string  The user ID.
     * @throws Vilma_Exception
     */
    protected function _saveUser($info)
    {
        /* Access check (for domainkey). */
        $domain = Vilma::stripDomain($info['user_name']);
        $this->getDomainByName($domain);

        if (empty($info['user_id'])) {
            $info['user_id'] = $this->_db->nextId($this->_params['tables']['users']);
            $create = true;
        } else {
            $create = false;
        }

        // Slightly hackish.
        $mailboxes = Vilma_MailboxDriver::factory();
        $mail_dir_base = isset($mailboxes->_params['mail_dir_base'])
            ? $mailboxes->_params['mail_dir_base']
            : '?';

        $tuple = array(
            'user_id'         => (int)$info['user_id'],
            'user_name'       => $info['user_name'],
            'user_full_name'  => $info['user_full_name'],
            'user_home_dir'   => $mail_dir_base,
            'user_mail_dir'   => $domain . '/' . Vilma::stripUser($info['user_name']) . '/',
            'user_mail_quota' => $this->getDomainQuota($domain) * 1024 * 1024,
            'user_enabled'    => (int)$info['user_enabled']);

        // UID and GID are slightly hackish (specific to maildrop driver), too.
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
            throw new Vilma_Exception(_("Password must be supplied when creating a new user."));
        }

        if ($create) {
            $sql = 'INSERT INTO ' . $this->_params['tables']['users'] . ' '
                . Horde_SQL::insertValues($this->_db, $this->_prepareRecord('users', $tuple));
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
            throw new Vilma_Exception($result);
        }

        return $info['user_id'];
    }

    /**
     * Deletes a user.
     *
     * @param integer $user_id  The id of the user to delete.
     *
     * @throws Vilma_Exception
     */
    public function deleteUser($user_id)
    {
        $user = $this->getUser($user_id);

        /* Delete all virtual emails for this user. */
        $sql = 'DELETE FROM ' . $this->_params['tables']['virtuals'] .
               ' WHERE ' . $this->_getTableField('virtuals', 'virtual_destination') . ' = ?';
        $values = array($user['user_name']);

        Horde::logMessage($sql, 'DEBUG');
        $delete = $this->_db->query($sql, $values);
        if (is_a($delete, 'PEAR_Error')) {
            throw new Vilma_Exception($delete);
        }

        /* Delete the actual user. */
        $sql = 'DELETE FROM ' . $this->_params['tables']['users'] .
               ' WHERE ' . $this->_getTableField('users', 'user_id') . ' = ?';
        $values = array((int)$user_id);

        Horde::logMessage($sql, 'DEBUG');
        $result = $this->_db->query($sql, $values);
        if (is_a($result, 'PEAR_Error')) {
            throw new Vilma_Exception($result);
        }

        Vilma_MailboxDriver::factory()
            ->deleteMailbox(Vilma::stripUser($user['user_name']),
                            Vilma::stripDomain($user['user_name']));
    }

    /**
     * Returns a list of all users, aliases, or groups and forwards for a
     * domain.
     *
     * @param string $domain      Domain on which to search.
     * @param string $type        Only return a specific type. One of 'all',
     *                            'user', 'alias','forward', or 'group'.
     * @param string $key         Sort list by this key.
     * @param integer $direction  Sort direction.
     *
     * @return array Account information for this domain
     */
    protected function _getAddresses($domain, $type = 'all')
    {
        $addresses = array();
        if ($type == 'all' || $type == 'user') {
            $addresses += $this->getUsers($domain);
        }
        if ($type == 'all' || $type == 'alias') {
            $addresses += $this->getVirtuals($domain);
        }
        return $addresses;
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
    public function getVirtuals($filter)
    {
        $email_field = $this->_getTableField('virtuals', 'virtual_email');
        $destination_field = $this->_getTableField('virtuals', 'virtual_destination');

        /* Check if filtering only for domain. */
        if (strpos($filter, '@') === false) {
            $where = $email_field . ' LIKE ?';
            $values = array('%@' . $filter);
        } else {
            $where = $destination_field . ' = ?';
            $values = array($filter);
        }

        $binds = $this->_getVirtualKeyFilter('AND');
        $sql = 'SELECT ' . $this->_getTableFields('virtuals')
            . ' FROM ' . $this->_params['tables']['virtuals']
            . ' WHERE ' . $where . $binds[0]
            . ' ORDER BY ' . $destination_field . ', ' . $email_field;
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
    public function getVirtual($virtual_id)
    {
        $binds = $this->_getVirtualKeyFilter('AND');
        $sql = 'SELECT ' . $this->_getTableFields('virtuals')
            . ' FROM ' . $this->_params['tables']['virtuals']
            . ' WHERE ' . $this->_getTableField('virtuals', 'virtual_id') . ' = ?' . $binds[0];
        array_unshift($binds[1], (int)$virtual_id);

        Horde::logMessage($sql, 'DEBUG');
        $virtual = $this->_db->getRow($sql, $binds[1], DB_FETCHMODE_ASSOC);
        $virtual['stripped_email'] = Vilma::stripUser($virtual['virtual_email']);

        return $virtual;
    }

    /**
     * Saves virtual email address to the backend.
     *
     * @param array $info     The virtual email data.
     * @param string $domain  The name of the domain for this virtual email.
     *
     * @throws Vilma_Exception
     */
    public function saveVirtual($info, $domain)
    {
        /* Access check (for domainkey) */
        $this->getDomainByName($domain);

        if (empty($info['virtual_id'])) {
            $info['virtual_id'] = $this->_db->nextId($this->_params['tables']['virtuals']);
            $sql = 'INSERT INTO ' . $this->_params['tables']['virtuals']
                . ' (' . $this->_getTableField('virtuals', 'virtual_email') . ', '
                       . $this->_getTableField('virtuals', 'virtual_destination') . ', '
                       . $this->_getTableField('virtuals', 'virtual_id') . ') VALUES (?, ?, ?)';
        } else {
            $sql = 'UPDATE ' . $this->_params['tables']['virtuals']
                . ' SET ' . $this->_getTableField('virtuals', 'virtual_email') . ' = ?, '
                          . $this->_getTableField('virtuals', 'virtual_destination') . ' = ?'
                . ' WHERE ' . $this->_getTableField('virtuals', 'virtual_id') . ' = ?';
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
    public function deleteVirtual($virtual_id)
    {
        $binds = $this->_getVirtualKeyFilter('AND');
        $sql = 'DELETE FROM ' . $this->_params['tables']['virtuals']
            . ' WHERE ' . $this->_getTableField('virtuals', 'virtual_id') . ' = ?' . $binds[0];
        array_unshift($binds[1], $virtual_id);
        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->query($sql, $binds[1]);
    }

    /**
     * Constructs an SQL WHERE fragment to filter domains by domain key.
     *
     * @param string $join  Keyword to join expression to rest of SQL statement
     *                      (e.g. 'WHERE' or 'AND').
     *
     * @return array  An SQL fragment and a list of values suitable for
     *                binding.
     */
    protected function _getDomainKeyFilter($join = 'WHERE')
    {
        if (empty($this->_params['tables']['domainkey'])) {
            return array('', array());
        }

        return array(' ' . $join . ' domain_key = ?',
                     array($this->_params['tables']['domainkey']));
    }

    /**
     * Constructs an SQL WHERE fragment to filter users by domain key.
     *
     * @param string $join  Keyword to join expression to rest of SQL statement
     *                      (e.g. 'WHERE' or 'AND'). Default: 'WHERE'.
     *
     * @return array  An SQL fragment and a list of values suitable for
     *                binding.
     */
    protected function _getUserKeyFilter($join = 'WHERE')
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
     * Constructs an SQL WHERE fragment to filter virtuals by domain key.
     *
     * @param string $join  Keyword to join expression to rest of SQL statement
     *                      (e.g. 'WHERE' or 'AND').  Default: 'WHERE'.
     *
     * @return array  An SQL fragment and a list of values suitable for
     *                binding.
     */
    protected function _getVirtualKeyFilter($join = 'WHERE')
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
     * Returns the list of fields from a specific table for SQL statements.
     *
     * @return string
     */
    protected function _getTableFields($table)
    {
        if (empty($this->_params['tables'][$table . '_fields'])) {
            switch ($table) {
            case 'domains':
                return 'domain_id, domain_name, domain_transport, domain_max_users, domain_quota';
            default:
                return '*';
            }
        }

        $domainsFields = $this->_params['tables'][$table . '_fields'];
        foreach ($domainsFields as $defaultName => $customName) {
            $fields[] = $customName . ' AS ' . $defaultName;
        }

        return implode(', ', $fields);
    }

    /**
     * Returns the real name of a field from a specific table for SQL
     * statements.
     *
     * @return string
     */
    protected function _getTableField($table, $field)
    {
        if (empty($this->_params['tables'][$table . '_fields'])) {
            return $field;
        }
        return $this->_params['tables'][$table . '_fields'][$field];
    }

    /**
     *
     *
     * @return array
     */
    protected function _prepareRecord($table, $record)
    {
        if (empty($this->_params['tables'][$table . '_fields'])) {
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
     * Initializes this backend, connects to the SQL database.
     *
     * @throws Vilma_Exception
     */
    protected function _initialize()
    {
        try {
            $this->_db = $GLOBALS['injector']->getInstance('Horde_Core_Factory_DbPear')->create('rw', 'vilma', 'storage');
        } catch (Exception $e) {
            throw Vilma_Exception($e);
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
    }
}
