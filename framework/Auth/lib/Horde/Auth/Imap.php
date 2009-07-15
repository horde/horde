<?php
/**
 * The Horde_Auth_Imap:: class provides an IMAP implementation of the Horde
 * authentication system.
 *
 * Optional parameters:
 * <pre>
 * 'admin_password'  The password of the adminstrator.
 *                   DEFAULT: null
 * 'admin_user'      The name of a user with admin privileges.
 *                   DEFAULT: null
 * 'hostspec'        The hostname or IP address of the server.
 *                   DEFAULT: 'localhost'
 * 'port'            The server port to which we will connect.
 *                   IMAP is generally 143, while IMAP-SSL is generally 993.
 *                   DEFAULT: Encryption port default
 * 'secure'          The encryption to use.  Either 'none', 'ssl', or 'tls'.
 *                   DEFAULT: 'none'
 * 'userhierarchy'   The hierarchy where user mailboxes are stored.
 *                   DEFAULT: 'user.'
 * </pre>
 *
 * If setting up as Horde auth handler in conf.php, this is a sample entry:
 * <pre>
 * $conf['auth']['params']['hostspec'] = 'imap.example.com';
 * $conf['auth']['params']['port'] = 143;
 * $conf['auth']['params']['secure'] = 'none';
 * </pre>
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Gaudenz Steinlin <gaudenz@soziologie.ch>
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Auth
 */
class Horde_Auth_Imap extends Horde_Auth_Base
{
    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        $params = array_merge(array(
            'admin_password' => null,
            'admin_user' => null,
            'hostspec' => '',
            'port' => '',
            'secure' => 'none',
            'userhierarchy' => 'user.'
        ), $params);

        parent::__construct(array_merge($default_params, $params));

        if (!empty($this->_params['admin_user'])) {
            $this->_capabilities['add'] = true;
            $this->_capabilities['remove'] = true;
            $this->_capabilities['list'] = true;
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
            $ob->_getOb($userId, $credentials['password']);
            $ob->login();
            $ob->logout();
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
            $ob->_getOb($this->_params['admin_user'], $this->_params['admin_password']);
            $mailbox = Horde_String::convertCharset($this->_params['userhierarchy'] . $userId, Horde_Nls::getCharset(), 'utf7-imap');
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
            $ob->_getOb($this->_params['admin_user'], $this->_params['admin_password']);
            $ob->setACL($mailbox, $this->_params['admin_user'], 'lrswipcda');
            $ob->deleteMailbox(Horde_String::convertCharset($this->_params['userhierarchy'] . $userId, Horde_Nls::getCharset(), 'utf7-imap'));
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
            $ob->_getOb($this->_params['admin_user'], $this->_params['admin_password']);
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
     * @return Horde_Imap_Client_Base  IMAP client object.
     */
    protected function _getOb($user, $pass)
    {
        $imap_config = array(
            'hostspec' => empty($this->_params['hostspec']) ? null : $this->_params['hostspec'],
            'password' => $pass,
            'port' => empty($this->_params['port']) ? null : $this->_params['port'],
            'secure' => ($this->_params['secure'] == 'none') ? null : $this->_params['secure'],
            'username' => $user
        );

        return Horde_Imap_Client::getInstance('Socket', $imap_config);
    }

}
