<?php
/**
 * The Horde_Auth_Imap:: class provides an IMAP implementation of the Horde
 * authentication system.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, http://www.horde.org/licenses/lgpl21
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Gaudenz Steinlin <gaudenz@soziologie.ch>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package  Auth
 */
class Horde_Auth_Imap extends Horde_Auth_Base
{
    /**
     * Imap client objects.
     *
     * @var array()
     */
    protected $_imap = array();

    /**
     * Constructor.
     *
     * @param array $params  Optional parameters:
     *   - admin_password: (string) The password of the adminstrator.
     *                     DEFAULT: null
     *   - admin_user: (string) The name of a user with admin privileges.
     *                 DEFAULT: null
     *   - hostspec: (string) The hostname or IP address of the server.
     *               DEFAULT: 'localhost'
     *   - port: (integer) The server port to which we will connect.
     *           IMAP is generally 143, while IMAP-SSL is generally 993.
     *           DEFAULT: Encryption port default
     *   - secure: (string) The encryption to use.  Either 'none', 'ssl', or
     *             'tls'.
     *             DEFAULT: 'none'
     *   - userhierarchy: (string) The hierarchy where user mailboxes are
     *                    stored (UTF-8).
     *                    DEFAULT: 'user.'
     */
    public function __construct(array $params = array())
    {
        $params = array_merge(array(
            'admin_password' => null,
            'admin_user' => null,
            'hostspec' => '',
            'port' => null,
            'secure' => 'none',
            'userhierarchy' => 'user.'
        ), $params);

        parent::__construct($params);

        if (!empty($this->_params['admin_user'])) {
            $this->_capabilities = array_merge($this->_capabilities, array(
                'add' => true,
                'list' => true,
                'remove' => true
            ));
        }
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userId      The userId to check.
     * @param array $credentials  An array of login credentials. For IMAP,
     *                            this must contain a password entry.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        try {
            $ob = $this->_getOb($userId, $credentials['password']);
            $ob->login();
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }
    }

    /**
     * Add a set of authentication credentials.
     *
     * @param string $userId       The userId to add.
     * @param array  $credentials  The credentials to use.
     *
     * @throws Horde_Auth_Exception
     */
    public function addUser($userId, $credentials)
    {
        try {
            $ob = $this->_getOb($this->_params['admin_user'], $this->_params['admin_password']);
            $ob->createMailbox($this->_params['userhierarchy']);
            $ob->setACL($this->_params['userhierarchy'], $this->_params['admin_user'], 'lrswipcda');
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Auth_Exception($e);
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
        try {
            $ob = $this->_getOb($this->_params['admin_user'], $this->_params['admin_password']);
            $ob->setACL($this->_params['userhierarchy'], $this->_params['admin_user'], 'lrswipcda');
            $ob->deleteMailbox($this->_params['userhierarchy']);
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
    }

    /**
     * List all users in the system.
     *
     * @return array  The array of userIds.
     * @throws Horde_Auth_Exception
     */
    public function listUsers($sort = false)
    {
        try {
            $ob = $this->_getOb($this->_params['admin_user'], $this->_params['admin_password']);
            $list = $ob->listMailboxes($this->_params['userhierarchy'] . '%', Horde_Imap_Client::MBOX_ALL, array('flat' => true));
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }

        $users = empty($list)
            ? array()
            : preg_replace('/.*' . preg_quote($this->_params['userhierarchy'], '/') . '(.*)/', '\\1', $list);
        return $this->_sort($users, $sort);
    }

    /**
     * Get Horde_Imap_Client object.
     *
     * @param string $user  Username.
     * @param string $pass  Password.
     *
     * @return Horde_Imap_Client_Base  IMAP client object.
     * @throws Horde_Auth_Exception
     */
    protected function _getOb($user, $pass)
    {
        $sig = hash('md5', serialize(array($user, $pass)));

        if (!isset($this->_ob[$sig])) {
            $imap_config = array(
                'hostspec' => empty($this->_params['hostspec']) ? null : $this->_params['hostspec'],
                'password' => $pass,
                'port' => empty($this->_params['port']) ? null : $this->_params['port'],
                'secure' => ($this->_params['secure'] == 'none') ? null : $this->_params['secure'],
                'username' => $user
            );

            try {
                $this->_ob[$sig] = new Horde_Imap_Client_Socket($imap_config);
            } catch (InvalidArgumentException $e) {
                throw new Horde_Auth_Exception($e);
            }
        }

        return $this->_ob[$sig];
    }

}
