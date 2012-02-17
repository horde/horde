<?php
/**
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author Marko Djukic <marko@oblo.com>
 * @package Vilma
 */
class Vilma_Driver_Sql extends Vilma_Driver
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
        $sql = 'SELECT ' . $this->_getTableFields('domains')
            . ' FROM ' . $this->_params['tables']['domains']
            . ' ORDER BY ' . $this->_getTableField('domains', 'domain_name');
        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->getAll($sql, null, DB_FETCHMODE_ASSOC);
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
        $sql = 'SELECT ' . $this->_getTableFields('domains')
            . ' FROM ' . $this->_params['tables']['domains']
            . ' WHERE ' . $this->_getTableField('domains', 'domain_id') . ' = ?';
        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->getRow($sql, array((int)$domain_id), DB_FETCHMODE_ASSOC);
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
        $sql = 'SELECT ' . $this->_getTableFields('domains')
            . ' FROM ' . $this->_params['tables']['domains']
            . ' WHERE ' . $this->_getTableField('domains', 'domain_name') . ' = ?';
        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->getRow($sql, array($domain_name), DB_FETCHMODE_ASSOC);
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
            $values = $this->_prepareRecord('domains', $record);
            $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)',
                           $this->_params['tables']['domains'],
                           implode(', ', array_keys($values)),
                           implode(', ', array_fill(0, count($values), '?')));
            $this->_db->insert($sql, $values);
        } else {
            $values = $this->_prepareRecord('domains', $record);
            $sql = sprintf('UPDATE %s SET %s WHERE %s = ?',
                           $this->_params['tables']['domains'],
                           implode(' = ?, ', array_keys($values)) . ' = ?',
                           $this->_getTableField('domains', 'domain_id'));
            $values[] = (int)$info['domain_id'];
            $this->_db->update($sql, $values);
        }
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
        Horde_Exception_Pear::catchError($this->_db->query($sql, $values));

        /* Delete all users for this domain. */
        $sql = 'DELETE FROM ' . $this->_params['tables']['users'] .
               ' WHERE ' . $this->_getTableField('users', 'user_name') . ' LIKE ?';
        $values = array('%@' . $domain_name);
        Horde_Exception_Pear::catchError($this->_db->query($sql, $values));

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
        $sql = 'SELECT count(' . $this->_getTableField('users', 'user_name') . ')'
            . ' FROM ' . $this->_params['tables']['users']
            . ' WHERE ' . $this->_getTableField('users', 'user_name') . ' LIKE ?';
        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->getOne($sql, array('%@' . $domain_name));
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
            $sql = 'SELECT ' . $this->_getTableFields('users')
                . ' FROM ' . $this->_params['tables']['users'];
            $values = array();
        } else {
            /* Fetch only users for a domain. */
            $user_field =  $this->_getTableField('users', 'user_name');
            $sql = 'SELECT ' . $this->_getTableFields('users')
                . ' FROM ' . $this->_params['tables']['users']
                . ' WHERE ' . $user_field . ' LIKE ?'
                . ' ORDER BY ' . $user_field;
            $values = array('%@' . $domain);
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
        $sql = 'SELECT ' . $this->_getTableFields('users')
            . ' FROM ' . $this->_params['tables']['users']
            . ' WHERE ' . $this->_getTableField('users', 'user_id') . ' = ?';
        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->getRow($sql, array((int)$user_id), DB_FETCHMODE_ASSOC);
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
        $mail_dir_base = $mailboxes->getParam('mail_dir_base');
        if (is_null($mail_dir_base)) {
            $mail_dir_base = '?';
        }

        $tuple = array(
            'user_id'         => (int)$info['user_id'],
            'user_name'       => $info['user_name'],
            'user_full_name'  => $info['user_full_name'],
            'user_home_dir'   => $mail_dir_base,
            'user_mail_dir'   => $domain . '/' . Vilma::stripUser($info['user_name']) . '/',
            'user_mail_quota' => $this->getDomainQuota($domain) * 1024 * 1024,
            'user_enabled'    => (int)$info['user_enabled']);

        // UID and GID are slightly hackish (specific to maildrop driver), too.
        $tuple['user_uid'] = $mailboxes->getParam('uid');
        if (is_null($tuple['user_uid'])) {
            $tuple['user_uid'] = -1;
        }
        $tuple['user_gid'] = $mailboxes->getParam('gid');
        if (is_null($tuple['user_gid'])) {
            $tuple['user_gid'] = -1;
        }

        if (!empty($info['password'])) {
            $tuple['user_clear'] = $info['password'];
            $tuple['user_crypt'] = crypt($info['password'],
                                         substr($info['password'], 0, 2));
        } elseif ($create) {
            throw new Vilma_Exception(_("Password must be supplied when creating a new user."));
        }

        $values = $this->_prepareRecord('users', $tuple);
        if ($create) {
            $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)',
                           $this->_params['tables']['users'],
                           implode(', ', array_keys($values)),
                           implode(', ', array_fill(0, count($values), '?')));
            $this->_db->insert($sql, $values);
        } else {
            $sql = sprintf('UPDATE %s SET %s WHERE %s = ?',
                           $this->_params['tables']['users'],
                           implode(' = ?, ', array_keys($values)) . ' = ?',
                           $this->_getTableField('users', 'user_id'));
            $values[] = (int)$info['user_id'];
            $this->_db->update($sql, $values);
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
        Horde_Exception_Pear::catchError($user = $this->getUser($user_id));

        /* Delete all virtual emails for this user. */
        $sql = 'DELETE FROM ' . $this->_params['tables']['virtuals'] .
               ' WHERE ' . $this->_getTableField('virtuals', 'virtual_destination') . ' = ?';
        $values = array($user['user_name']);

        Horde::logMessage($sql, 'DEBUG');
        Horde_Exception_Pear::catchError($this->_db->query($sql, $values));

        /* Delete the actual user. */
        $sql = 'DELETE FROM ' . $this->_params['tables']['users'] .
               ' WHERE ' . $this->_getTableField('users', 'user_id') . ' = ?';
        $values = array((int)$user_id);

        Horde::logMessage($sql, 'DEBUG');
        Horde_Exception_Pear::catchError($this->_db->query($sql, $values));

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

        $sql = 'SELECT ' . $this->_getTableFields('virtuals')
            . ' FROM ' . $this->_params['tables']['virtuals']
            . ' WHERE ' . $where
            . ' ORDER BY ' . $destination_field . ', ' . $email_field;

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
        $sql = 'SELECT ' . $this->_getTableFields('virtuals')
            . ' FROM ' . $this->_params['tables']['virtuals']
            . ' WHERE ' . $this->_getTableField('virtuals', 'virtual_id') . ' = ?';

        Horde::logMessage($sql, 'DEBUG');
        $virtual = $this->_db->getRow($sql, array((int)$virtual_id), DB_FETCHMODE_ASSOC);
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
        $sql = 'DELETE FROM ' . $this->_params['tables']['virtuals']
            . ' WHERE ' . $this->_getTableField('virtuals', 'virtual_id') . ' = ?';
        Horde::logMessage($sql, 'DEBUG');
        return $this->_db->query($sql, array($virtual_id));
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
            throw new Vilma_Exception($e);
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
