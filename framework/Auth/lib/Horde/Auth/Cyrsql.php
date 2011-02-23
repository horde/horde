<?php
/**
 * The Horde_Auth_Cyrsql class provides a SQL implementation of the Horde
 * authentication system for the Cyrus IMAP server. Most of the functionality
 * is the same as for the SQL class; only what is different overrides the
 * parent class implementations.
 *
 * The table structure for the auth system is as follows:
 * <pre>
 * CREATE TABLE accountuser (
 *     username    VARCHAR(255) BINARY NOT NULL DEFAULT '',
 *     password    VARCHAR(32) BINARY NOT NULL DEFAULT '',
 *     prefix      VARCHAR(50) NOT NULL DEFAULT '',
 *     domain_name VARCHAR(255) NOT NULL DEFAULT '',
 *     UNIQUE KEY username (username)
 * );
 *
 * CREATE TABLE adminuser (
 *     username    VARCHAR(50) BINARY NOT NULL DEFAULT '',
 *     password    VARCHAR(50) BINARY NOT NULL DEFAULT '',
 *     type        INT(11) NOT NULL DEFAULT '0',
 *     SID         VARCHAR(255) NOT NULL DEFAULT '',
 *     home        VARCHAR(255) NOT NULL DEFAULT '',
 *     PRIMARY KEY (username)
 * );
 *
 * CREATE TABLE alias (
 *     alias       VARCHAR(255) NOT NULL DEFAULT '',
 *     dest        LONGTEXT,
 *     username    VARCHAR(50) NOT NULL DEFAULT '',
 *     status      INT(11) NOT NULL DEFAULT '1',
 *     PRIMARY KEY (alias)
 * );
 *
 * CREATE TABLE domain (
 *     domain_name VARCHAR(255) NOT NULL DEFAULT '',
 *     prefix      VARCHAR(50) NOT NULL DEFAULT '',
 *     maxaccounts INT(11) NOT NULL DEFAULT '20',
 *     quota       INT(10) NOT NULL DEFAULT '20000',
 *     transport   VARCHAR(255) NOT NULL DEFAULT 'cyrus',
 *     freenames   ENUM('YES','NO') NOT NULL DEFAULT 'NO',
 *     freeaddress ENUM('YES','NO') NOT NULL DEFAULT 'NO',
 *     PRIMARY KEY (domain_name),
 *     UNIQUE KEY prefix (prefix)
 * );
 *
 * CREATE TABLE domainadmin (
 *     domain_name VARCHAR(255) NOT NULL DEFAULT '',
 *     adminuser   VARCHAR(255) NOT NULL DEFAULT ''
 * );
 *
 * CREATE TABLE search (
 *     search_id   VARCHAR(255) NOT NULL DEFAULT '',
 *     search_sql  TEXT NOT NULL,
 *     perpage     INT(11) NOT NULL DEFAULT '0',
 *     timestamp   TIMESTAMP(14) NOT NULL,
 *     PRIMARY KEY (search_id),
 *     KEY search_id (search_id)
 * );
 *
 * CREATE TABLE virtual (
 *     alias       VARCHAR(255) NOT NULL DEFAULT '',
 *     dest        LONGTEXT,
 *     username    VARCHAR(50) NOT NULL DEFAULT '',
 *     status      INT(11) NOT NULL DEFAULT '1',
 *     KEY alias (alias)
 * );
 *
 * CREATE TABLE log (
 *     id          INT(11) NOT NULL AUTO_INCREMENT,
 *     msg         TEXT NOT NULL,
 *     user        VARCHAR(255) NOT NULL DEFAULT '',
 *     host        VARCHAR(255) NOT NULL DEFAULT '',
 *     time        DATETIME NOT NULL DEFAULT '2000-00-00 00:00:00',
 *     pid         VARCHAR(255) NOT NULL DEFAULT '',
 *     PRIMARY KEY (id)
 * );
 * </pre>
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Ilya Krel <mail@krel.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Auth
 */
class Horde_Auth_Cyrsql extends Horde_Auth_Sql
{
    /**
     * Horde_Imap_Client object.
     *
     * @var Horde_Imap_Client_Base
     */
    protected $_imap;

    /**
     * Hierarchy separator to use (e.g., is it user/mailbox or user.mailbox)
     *
     * @var string
     */
    protected $_separator = '.';

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     * 'charset' - (string) Default charset.
     *             DEFAULT: NONE
     * 'domain_field' - (string) If set to anything other than 'none' this is
     *                  used as field name where domain is stored.
     *                  DEFAULT: 'domain_name'
     * 'folders' - (array) An array of folders to create under username.
     *             DEFAULT: NONE
     * 'hidden_accounts' - (array) An array of system accounts to hide from
     *                     the user interface.
     *                     DEFAULT: None.
     * 'imap' - (Horde_Imap_Client_Base) [REQUIRED] An IMAP client object.
     * 'quota' - (integer) The quota (in kilobytes) to grant on the mailbox.
     *           DEFAULT: NONE
     * 'unixhier' - (boolean) The value of imapd.conf's unixhierarchysep
     *              setting. Set this to true if the value is true in
     *              imapd.conf.
     *              DEFAULT: false
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['imap']) ||
            !($params['imap'] instanceof Horde_Imap_Client_Base)) {
            throw new InvalidArgumentException('Missing imap parameter.');
        }
        $this->_imap = $params['imap'];
        unset($params['imap']);

        $params = array_merge(array(
            'charset' => null,
            'domain_field' => 'domain_name',
            'folders' => array(),
            'hidden_accounts' => array('cyrus'),
            'quota' => null
        ), $params);

        parent::__construct($params);

        if (!empty($this->_params['unixhier'])) {
            $this->_params['userhierarchy'] = 'user/';
            $this->_separator = '/';
        }
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userId      The userId to check.
     * @param array $credentials  The credentials to use.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        if (!empty($this->_params['domain_field']) &&
            ($this->_params['domain_field'] != 'none')) {
            /* Build the SQL query with domain. */
            $query = sprintf('SELECT * FROM %s WHERE %s = ? AND %s = ?',
                             $this->_params['table'],
                             $this->_params['username_field'],
                             $this->_params['domain_field']);
            $values = explode('@', $userId);
        } else {
            /* Build the SQL query without domain. */
            $query = sprintf('SELECT * FROM %s WHERE %s = ?',
                             $this->_params['table'],
                             $this->_params['username_field']);
            $values = array($userId);
        }

        try {
            $row = $this->_db->selectOne($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
        }

        if (!$row ||
            !$this->_comparePasswords($row[$this->_params['password_field']], $credentials['password'])) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }

        $now = time();
        if (!empty($this->_params['hard_expiration_field']) &&
            !empty($row[$this->_params['hard_expiration_field']]) &&
            ($now > $row[$this->_params['hard_expiration_field']])) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_EXPIRED);
        }

        if (!empty($this->_params['soft_expiration_field']) &&
            !empty($row[$this->_params['soft_expiration_field']]) &&
            ($now > $row[$this->_params['soft_expiration_field']])) {
            $this->setCredential('change', true);
        }
    }

    /**
     * Add a set of authentication credentials.
     *
     * @param string $userId       The userId to add.
     * @param array  $credentials  The credentials to add.
     *
     * @throw Horde_Auth_Exception
     */
    public function addUser($userId, $credentials)
    {
        if (!empty($this->_params['domain_field']) &&
            ($this->_params['domain_field'] != 'none')) {
            list($name, $domain) = explode('@', $userId);

            $query = sprintf('INSERT INTO %s (%s, %s, %s) VALUES (?, ?, ?)',
                             $this->_params['table'],
                             $this->_params['username_field'],
                             $this->_params['domain_field'],
                             -$this->_params['password_field']);
            $values = array(
                $name,
                $domain,
                Horde_Auth::getCryptedPassword($credentials['password'],
                                               '',
                                               $this->_params['encryption'],
                                               $this->_params['show_encryption'])
            );

            $query2 = 'INSERT INTO virtual (alias, dest, username, status) VALUES (?, ?, ?, 1)';
            $values2 = array($userId, $userId, $name);

            try {
                $this->_db->insert($query, $values);
                $this->_db->insert($query2, $values2);
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Auth_Exception($e);
            }
        } else {
            parent::addUser($userId, $credentials);
        }

        try {
            $mailbox = Horde_String::convertCharset($this->_params['userhierarchy'] . $userId, $this->_params['charset'], 'utf7-imap');
            $this->_imap->createMailbox($mailbox);
            $this->_imap->setACL($mailbox, $this->_params['cyradm'], 'lrswipcda');
            if (isset($this->_params['quota']) &&
                ($this->_params['quota'] >= 0)) {
                $this->_imap->setQuota($mailbox, array('storage' => $this->_params['quota']));
            }
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }

        foreach ($this->_params['folders'] as $val) {
            try {
                $this->_imap->createMailbox($val);
                $this->_imap->setACL($val, $this->_params['cyradm'], 'lrswipcda');
            } catch (Horde_Imap_Client_Exception $e) {}
        }
    }

    /**
     * Delete a set of authentication credentials.
     *
     * @param string $userId  The userId to delete.
     *
     * @throws Horde_Auth_Exception
     */
    public function removeUser($userId)
    {
        if (!empty($this->_params['domain_field']) &&
            ($this->_params['domain_field'] != 'none')) {
            list($name, $domain) = explode('@', $userId);

            /* Build the SQL query. */
            $query = sprintf('DELETE FROM %s WHERE %s = ? and %s = ?',
                             $this->_params['table'],
                             $this->_params['username_field'],
                             $this->_params['domain_field']);
            $values = array($name, $domain);

            $query2 = 'DELETE FROM virtual WHERE dest = ?';
            $values2 = array($userId);

            try {
                $this->_db->delete($query, $values);
                $this->_db->delete($query2, $values2);
            } catch (Horde_Db_Exception $e) {
                throw new Horde_Auth_Exception($e);
            }
        } else {
            parent::removeUser($userId);
        }

        /* Set ACL for mailbox deletion. */
        list($admin) = explode('@', $this->_params['cyradmin']);

        try {
            $this->_imap->setACL($mailbox, $admin, array('rights' => 'lrswipcda'));
            $this->_imap->deleteMailbox($mailbox);
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
    }

    /**
     * List all users in the system.
     *
     * @return mixed  The array of userIds.
     * @throws Horde_Auth_Exception
     */
    public function listUsers()
    {
        if (!empty($this->_params['domain_field']) &&
            ($this->_params['domain_field'] != 'none')) {
            /* Build the SQL query with domain. */
            $query = sprintf('SELECT %s, %s FROM %s ORDER BY %s',
                             $this->_params['username_field'],
                             $this->_params['domain_field'],
                             $this->_params['table'],
                             $this->_params['username_field']);
        } else {
            /* Build the SQL query without domain. */
            $query = sprintf('SELECT %s FROM %s ORDER BY %s',
                             $this->_params['username_field'],
                             $this->_params['table'],
                             $this->_params['username_field']);
        }

        try {
            $result = $this->_db->selectAll($query);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }

        /* Loop through and build return array. */
        $users = array();
        if (!empty($this->_params['domain_field']) &&
            ($this->_params['domain_field'] != 'none')) {
            foreach ($result as $ar) {
                if (!in_array($ar[0], $this->_params['hidden_accounts'])) {
                    $users[] = $ar[0] . '@' . $ar[1];
                }
            }
        } else {
            foreach ($result as $ar) {
                if (!in_array($ar[0], $this->_params['hidden_accounts'])) {
                    $users[] = $ar[0];
                }
            }
        }

        return $users;
    }

    /**
     * Update a set of authentication credentials.
     *
     * @param string $oldID       The old userId.
     * @param string $newID       The new userId. [NOT SUPPORTED]
     * @param array $credentials  The new credentials
     *
     * @throws Horde_Auth_Exception
     */
    public function updateUser($oldID, $newID, $credentials)
    {
        if (!empty($this->_params['domain_field']) &&
            ($this->_params['domain_field'] != 'none')) {
            list($name, $domain) = explode('@', $oldID);
            /* Build the SQL query with domain. */
            $query = sprintf(
                'UPDATE %s SET %s = ? WHERE %s = ? and %s = ?',
                $this->_params['table'],
                $this->_params['password_field'],
                $this->_params['username_field'],
                $this->_params['domain_field']
            );
            $values = array(
                Horde_Auth::getCryptedPassword($credentials['password'], '', $this->_params['encryption'], $this->_params['show_encryption']),
                $name,
                $domain
            );
        } else {
            /* Build the SQL query. */
            $query = sprintf(
                'UPDATE %s SET %s = ? WHERE %s = ?',
                $this->_params['table'],
                $this->_params['password_field'],
                $this->_params['username_field']
            );
            $values = array(
                Horde_Auth::getCryptedPassword($credentials['password'], '', $this->_params['encryption'], $this->_params['show_encryption']),
                $oldID
            );
        }

        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
    }

}
