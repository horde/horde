<?php
/**
 * The Horde_Auth_Cyrsql class provides a SQL implementation of the Horde
 * authentication system for the Cyrus IMAP server. Most of the functionality
 * is the same as for the SQL class; only what is different overrides the
 * parent class implementations.
 *
 * Required parameters: See Horde_Auth_Sql driver.
 * <pre>
 * 'cyradmin'  The username of the cyrus administrator.
 * 'cyrpass'   The password for the cyrus administrator.
 * 'hostspec'        The hostname or IP address of the server.
 *                   DEFAULT: 'localhost'
 * 'port'            The server port to which we will connect.
 *                   IMAP is generally 143, while IMAP-SSL is generally 993.
 *                   DEFAULT: Encryption port default
 * 'secure'          The encryption to use.  Either 'none', 'ssl', or 'tls'.
 *                   DEFAULT: 'none'
 * </pre>
 *
 * Optional parameters: See Horde_Auth_Sql driver.
 * <pre>
 * 'domain_field'    If set to anything other than 'none' this is used as
 *                   field name where domain is stored.
 *                   DEFAULT: 'domain_name'
 * 'hidden_accounts' An array of system accounts to hide from the user
 *                   interface.
 * 'folders'         An array of folders to create under username.
 *                   DEFAULT: NONE
 * 'quota'           The quota (in kilobytes) to grant on the mailbox.
 *                   DEFAULT: NONE
 * 'unixhier'        The value of imapd.conf's unixhierarchysep setting.
 *                   Set this to true if the value is true in imapd.conf.
 * </pre>
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
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author  Ilya Krel <mail@krel.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Auth
 */
class Horde_Auth_Cyrsql extends Horde_Auth_Sql
{
    /**
     * Horde_Imap_Client object.
     *
     * @var Horde_Imap_Client_Base
     */
    protected $_ob;

    /**
     * Hierarchy separator to use (e.g., is it user/mailbox or user.mailbox)
     *
     * @var string
     */
    protected $_separator = '.';

    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        parent::__construct($params);

        $admin_params = array(
            'admin_user' => $params['cyradmin'],
            'admin_password' => $params['cyrpass'],
            'dsn' => $params['imap_dsn']
        );

        if (!empty($this->_params['unixhier'])) {
            $admin_params['userhierarchy'] = 'user/';
        }

        if (!empty($this->_params['unixhier'])) {
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
        try {
            $this->_connect();
        } catch (Horde_Auth_Exception $e) {
            Horde::logMessage($e, 'ERR');
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
        }

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

        Horde::logMessage('SQL Query by Horde_Auth_Cyrsql::_authenticate(): ' . $query, 'DEBUG');

        $result = $this->_db->query($query, $values);
        if ($result instanceof PEAR_Error) {
            Horde::logMessage($result, 'ERR');
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_FAILED);
        }

        $row = $result->fetchRow(DB_GETMODE_ASSOC);
        if (is_array($row)) {
            $result->free();
        } else {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }

        if (!$this->_comparePasswords($row[$this->_params['password_field']],
                                      $credentials['password'])) {
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
            $this->_credentials['params']['change'] = true;
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
        $this->_connect();

        if (!empty($this->_params['domain_field']) &&
            ($this->_params['domain_field'] != 'none')) {
            list($name, $domain) = explode('@', $userId);
            /* Build the SQL query. */
            $query = sprintf('INSERT INTO %s (%s, %s, %s) VALUES (?, ?, ?)',
                             $this->_params['table'],
                             $this->_params['username_field'],
                             $this->_params['domain_field'],
                             $this->_params['password_field']);
            $values = array($name,
                            $domain,
                            Horde_Auth::getCryptedPassword($credentials['password'],
                                                      '',
                                                      $this->_params['encryption'],
                                                      $this->_params['show_encryption']));

            Horde::logMessage('SQL Query by Horde_Auth_Cyrsql::addUser(): ' . $query, 'DEBUG');

            $dbresult = $this->_db->query($query, $values);
            $query = 'INSERT INTO virtual (alias, dest, username, status) VALUES (?, ?, ?, 1)';
            $values = array($userId, $userId, $name);

            Horde::logMessage('SQL Query by Horde_Auth_Cyrsql::addUser(): ' . $query, 'DEBUG');

            $dbresult2 = $this->_db->query($query, $values);
            if ($dbresult2 instanceof PEAR_Error) {
                throw new Horde_Auth_Exception($dbresult2);
            }
        } else {
            parent::addUser($userId, $credentials);
        }

        try {
            $mailbox = Horde_String::convertCharset($this->_params['userhierarchy'] . $userId, Horde_Nls::getCharset(), 'utf7-imap');
            $ob->createMailbox($mailbox);
            $ob->setACL($mailbox, $this->_params['cyradm'], 'lrswipcda');
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }

        foreach ($this->_params['folders'] as $folders) {
            if (!empty($this->_params['domain_field']) &&
                ($this->_params['domain_field'] != 'none')) {
                list($userName, $domain) = explode('@', $userName);
                $tmp = $userName . $this->_separator . $value . '@' . $domain;
Horde_String::convertCharset($userName . $this->_separator . $value . '@' . $domain, Horde_Nls::getCharset(), 'utf7-imap');
            } else {
                $tmp = $userName . $this->_separator . $value;
            }

            $tmp = Horde_String::convertCharset($tmp, Horde_Nls::getCharset(), 'utf7-imap');
            $ob->createMailbox($tmp);
            $ob->setACL($tmp, $this->_params['cyradm'], 'lrswipcda');
        }

        if (isset($this->_params['quota']) &&
            ($this->_params['quota'] >= 0)) {
            try {
                $this->_ob->setQuota($mailbox, array('storage' => $this->_params['quota']));
            } catch (Horde_Imap_Client_Exception $e) {
                throw new Horde_Auth_Exception($e);
            }
        }

        if (isset($this->_params['quota']) &&
            ($this->_params['quota'] >= 0) &&
            !@imap_set_quota($this->_imapStream, 'user' . $this->_separator . $userId, $this->_params['quota'])) {
            throw new Horde_Auth_Exception(sprintf(_("IMAP mailbox quota creation failed: %s"), imap_last_error()));
        }
    }

    /**
     * Delete a set of authentication credentials.
     *
     * @param string $userId  The userId to delete.
     *
     * @throws Horde_Auth_Exception
     */
    function removeUser($userId)
    {
        $this->_connect();

        if (!empty($this->_params['domain_field']) &&
            ($this->_params['domain_field'] != 'none')) {
            list($name, $domain) = explode('@', $userId);
            /* Build the SQL query. */
            $query = sprintf('DELETE FROM %s WHERE %s = ? and %s = ?',
                             $this->_params['table'],
                             $this->_params['username_field'],
                             $this->_params['domain_field']);
            $values = array($name, $domain);

            Horde::logMessage('SQL Query by Horde_Auth_Cyrsql::removeUser(): ' . $query, 'DEBUG');

            $dbresult = $this->_db->query($query, $values);
            $query = 'DELETE FROM virtual WHERE dest = ?';
            $values = array($userId);

            Horde::logMessage('SQL Query by Horde_Auth_Cyrsql::removeUser(): ' . $query, 'DEBUG');

            $dbresult2 = $this->_db->query($query, $values);
            if ($dbresult2 instanceof PEAR_Error) {
                return $dbresult2;
            }
        } else {
            parent::removeUser($userId);
        }

        /* Set ACL for mailbox deletion. */
        list($admin) = explode('@', $this->_params['cyradmin']);

        try {
            $this->_ob->setACL($mailbox, $admin, array('rights' => 'lrswipcda'));
            $this->_ob->deleteMailbox($mailbox);
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }

        Horde_Auth::removeUserData($userId);
    }

    /**
     * List all users in the system.
     *
     * @return mixed  The array of userIds.
     * @throws Horde_Auth_Exception
     */
    public function listUsers()
    {
        $this->_connect();

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

        Horde::logMessage('SQL Query by Horde_Auth_Cyrsql::listUsers(): ' . $query, 'DEBUG');

        $result = $this->_db->getAll($query, null, DB_FETCHMODE_ORDERED);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Auth_Exception($result);
        }

        /* Loop through and build return array. */
        $users = array();
        if (!empty($this->_params['domain_field'])
            && ($this->_params['domain_field'] != 'none')) {
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
     * @param string $newID       The new userId.
     * @param array $credentials  The new credentials
     *
     * @throws Horde_Auth_Exception
     */
    public function updateUser($oldID, $newID, $credentials)
    {
        $this->_connect();

        if (!empty($this->_params['domain_field']) &&
            ($this->_params['domain_field'] != 'none')) {
            list($name, $domain) = explode('@', $oldID);
            /* Build the SQL query with domain. */
            $query = sprintf('UPDATE %s SET %s = ? WHERE %s = ? and %s = ?',
                             $this->_params['table'],
                             $this->_params['password_field'],
                             $this->_params['username_field'],
                             $this->_params['domain_field']);
            $values = array(Horde_Auth::getCryptedPassword($credentials['password'],
                                                      '',
                                                      $this->_params['encryption'],
                                                      $this->_params['show_encryption']),
                            $name, $domain);
        } else {
            /* Build the SQL query. */
            $query = sprintf('UPDATE %s SET %s = ? WHERE %s = ?',
                             $this->_params['table'],
                             $this->_params['password_field'],
                             $this->_params['username_field']);
            $values = array(Horde_Auth::getCryptedPassword($credentials['password'],
                                                      '',
                                                      $this->_params['encryption'],
                                                      $this->_params['show_encryption']),
                            $oldID);
        }

        Horde::logMessage('SQL Query by Horde_Auth_Cyrsql::updateUser(): ' . $query, 'DEBUG');

        $res = $this->_db->query($query, $values);
        if ($res instanceof PEAR_Error) {
            throw new Horde_Auth_Exception($res);
        }
    }

    /**
     * Attempts to open connections to the SQL and IMAP servers.
     *
     * @throws Horde_Auth_Exception
     */
    public function _connect()
    {
        if ($this->_connected) {
            return;
        }

        parent::_connect();

        if (!isset($this->_params['hidden_accounts'])) {
            $this->_params['hidden_accounts'] = array('cyrus');
        }

        // Reset the $_connected flag; we haven't yet successfully
        // opened everything.
        $this->_connected = false;

        $imap_config = array(
            'hostspec' => empty($this->_params['hostspec']) ? null : $this->_params['hostspec'],
            'password' => $this->_params['cyrpass'],
            'port' => empty($this->_params['port']) ? null : $this->_params['port'],
            'secure' => ($this->_params['secure'] == 'none') ? null : $this->_params['secure'],
            'username' => $this->_params['cyradmin']
        );

        try {
            $this->_ob = Horde_Imap_Client::factory('Socket', $imap_config);
            $this->_ob->login();
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }

        $this->_connected = true;
    }

}
