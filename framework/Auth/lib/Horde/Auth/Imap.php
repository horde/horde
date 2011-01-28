<?php
/**
 * The Horde_Auth_Imap:: class provides an IMAP implementation of the Horde
 * authentication system.
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Gaudenz Steinlin <gaudenz@soziologie.ch>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
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
     * <pre>
     * 'admin_password' - (string) The password of the adminstrator.
     *                    DEFAULT: null
     * 'admin_user' - (string) The name of a user with admin privileges.
     *                DEFAULT: null
     * 'charset' - (string) Default charset.
     *             DEFAULT: NONE
     * 'hostspec' - (string) The hostname or IP address of the server.
     *              DEFAULT: 'localhost'
     * 'port' - (integer) The server port to which we will connect.
     *          IMAP is generally 143, while IMAP-SSL is generally 993.
     *          DEFAULT: Encryption port default
     * 'secure' - (string) The encryption to use.  Either 'none', 'ssl', or
     *            'tls'.
     *            DEFAULT: 'none'
     * 'userhierarchy' - (string) The hierarchy where user mailboxes are
     *                   stored.
     *                   DEFAULT: 'user.'
     * </pre>
     */
    public function __construct(array $params = array())
    {
        $params = array_merge(array(
            'admin_password' => null,
            'admin_user' => null,
            'charset' => null,
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
            $mailbox = Horde_String::convertCharset($this->_params['userhierarchy'] . $userId, $this->_params['charset'], 'utf7-imap');
            $ob->createMailbox($mailbox);
            $ob->setACL($mailbox, $this->_params['admin_user'], 'lrswipcda');
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
            $ob->setACL($mailbox, $this->_params['admin_user'], 'lrswipcda');
            $ob->deleteMailbox(Horde_String::convertCharset($this->_params['userhierarchy'] . $userId, $this->_params['charset'], 'utf7-imap'));
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }

        Horde_Auth::removeUserData($userId);
    }

    /**
     * List all users in the system.
     *
     * @return array  The array of userIds.
     * @throws Horde_Auth_Exception
     */
    public function listUsers()
    {
        try {
            $ob = $this->_getOb($this->_params['admin_user'], $this->_params['admin_password']);
            $list = $ob->listMailboxes($this->_params['userhierarchy'] . '%', Horde_Imap_Client::MBOX_ALL, array('flat' => true));
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }

        return empty($list)
            ? array()
            : preg_replace('/.*' . preg_quote($this->_params['userhierarchy'], '/') . '(.*)/', '\\1', $list);
    }

    /**
     * Get Horde_Imap_Client object.
     *
     * @param string $user  Username.
     * @param string $pass  Password.
     *
     * @return Horde_Imap_Client_Base  IMAP client object.
     * @throws Horde_Exception
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

            $this->_ob[$sig] = Horde_Imap_Client::factory('Socket', $imap_config);
        }

        return $this->_ob[$sig];
    }

}
