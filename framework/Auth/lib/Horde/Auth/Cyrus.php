<?php
/**
 * The Horde_Auth_Cyrus class provides Horde with the ability of
 * administrating a Cyrus mail server authentications against another backend
 * that Horde can update (eg SQL or LDAP).
 *
 * Required parameters:
 * <pre>
 * 'backend'    The complete hash for the Auth_* driver that cyrus
 *              authenticates against (eg SQL, LDAP).
 * 'cyradmin'   The username of the cyrus administrator
 * 'cyrpass'    The password for the cyrus administrator
 * 'hostspec'        The hostname or IP address of the server.
 *                   DEFAULT: 'localhost'
 * 'port'            The server port to which we will connect.
 *                   IMAP is generally 143, while IMAP-SSL is generally 993.
 *                   DEFAULT: Encryption port default
 * 'secure'          The encryption to use.  Either 'none', 'ssl', or 'tls'.
 *                   DEFAULT: 'none'
 * </pre>
 *
 * Optional values:
 * <pre>
 * 'folders'    An array of folders to create under username.
 *              Doesn't create subfolders by default.
 * 'quota'      The quota (in kilobytes) to grant on the mailbox.
 *              Does not establish quota by default.
 * 'separator'  Hierarchy separator to use (e.g., is it user/mailbox or
 *              user.mailbox)
 * 'unixhier'   The value of imapd.conf's unixhierarchysep setting.
 *              Set this to 'true' if the value is true in imapd.conf
 * </pre>
 *
 * Example Usage:
 * <pre>
 * $conf['auth']['driver'] = 'composite';
 * $conf['auth']['params']['loginscreen_switch'] = '_horde_select_loginscreen';
 * $conf['auth']['params']['admin_driver'] = 'cyrus';
 * $conf['auth']['params']['drivers']['imp'] = array(
 *     'driver' => 'application',
 *     'params' => array('app' => 'imp')
 * );
 * $conf['auth']['params']['drivers']['cyrus'] = array(
 *    'driver' => 'cyrus',
 *    'params' => array(
 *        'cyradmin' => 'cyrus',
 *        'cyrpass' => 'password',
 *        'hostspec' => 'imap.example.com',
 *        'secure' => 'none'
 *        'separator' => '.'
 *    )
 * );
 * $conf['auth']['params']['drivers']['cyrus']['params']['backend'] = array(
 *     'driver' => 'sql',
 *     'params' => array(
 *         'phptype' => 'mysql',
 *         'hostspec' => 'database.example.com',
 *         'protocol' => 'tcp',
 *         'username' => 'username',
 *         'password' => 'password',
 *         'database' => 'mail',
 *         'table' => 'accountuser',
 *         'encryption' => 'md5-hex',
 *         'username_field' => 'username',
 *         'password_field' => 'password'
 *     )
 * );
 *
 * if (!function_exists('_horde_select_loginscreen')) {
 *     function _horde_select_loginscreen()
 *     {
 *         return 'imp';
 *     }
 * }
 * </pre>
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author  Ilya Krel <mail@krel.org>
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Horde_Auth
 */
class Horde_Auth_Cyrus extends Horde_Auth_Base
{
    /**
     * Horde_Imap_Client object.
     *
     * @var Horde_Imap_Client_Base
     */
    protected $_ob;

    /**
     * Pointer to another backend that Cyrus authenticates against.
     *
     * @var Horde_Auth_Base
     */
     protected $_backend;

    /**
     * An array of capabilities, so that the driver can report which
     * operations it supports and which it doesn't.
     *
     * @var array
     */
    protected $_capabilities = array(
        'add' => true,
        'remove' => true,
        'update' => true
    );

    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        parent::__construct($params);

        if (!isset($this->_params['separator'])) {
            $this->_params['separator'] = '.';
        }

        if (isset($this->_params['unixhier']) &&
            $this->_params['unixhier'] == true) {
            $this->_params['separator'] = '/';
        }

        // Create backend instance.
        $this->_backend = Horde_Auth::singleton($this->_params['backend']['driver'], $this->_params['backend']['params']);

        // Check the capabilities of the backend.
        if (!$this->_backend->hasCapability('add') ||
            !$this->_backend->hasCapability('update') ||
            !$this->_backend->hasCapability('remove')) {
            Horde::fatal('Horde_Auth_Cyrus: Backend does not have required capabilites.', __FILE__, __LINE__);
        }

        $this->_capabilities['list'] = $this->_backend->hasCapability('list');
        $this->_capabilities['groups'] = $this->_backend->hasCapability('groups');
        $this->_capabilities['transparent'] = $this->_backend->hasCapability('transparent');
    }

    /**
     * Add a set of authentication credentials.
     *
     * @param string $userId      The userId to add.
     * @param array $credentials  The credentials to add.
     *
     * @throws Horde_Auth_Exception
     */
    public function addUser($userId, $credentials)
    {
        $this->_connect();

        $this->_backend->addUser($userId, $credentials);

        $mailbox = Horde_String::convertCharset('user' . $this->_params['separator'] . $userId, Horde_Nls::getCharset(), 'utf7-imap');

        try {
            $this->_ob->createMailbox($mailbox);
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }

        if (isset($this->_params['folders']) &&
            is_array($this->_params['folders'])) {
            foreach ($this->_params['folders'] as $folder) {
                try {
                    $this->_ob->createMailbox($mailbox . Horde_String::convertCharset($this->_params['separator'] . $folder, Horde_Nls::getCharset(), 'utf7-imap'));
                } catch (Horde_Imap_Client_Exception $e) {}
            }
        }

        if (isset($this->_params['quota']) &&
            ($this->_params['quota'] >= 0)) {
            try {
                $this->_ob->setQuota($mailbox, array('storage' => $this->_params['quota']));
            } catch (Horde_Imap_Client_Exception $e) {
                throw new Horde_Auth_Exception($e);
            }
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
        $this->_connect();

        $this->_backend->removeUser($userId);

        $mailbox = Horde_String::convertCharset('user' . $this->_params['separator'] . $userId, Horde_Nls::getCharset(), 'utf7-imap');

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
     * Attempts to open connections to the IMAP servers.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _connect()
    {
        if ($this->_ob) {
            return;
        }

        $imap_config = array(
            'hostspec' => empty($this->_params['hostspec']) ? null : $this->_params['hostspec'],
            'password' => $pass,
            'port' => empty($this->_params['port']) ? null : $this->_params['port'],
            'secure' => ($this->_params['secure'] == 'none') ? null : $this->_params['secure'],
            'username' => $user
        );

        try {
            $this->_ob = Horde_Imap_Client::getInstance('Socket', $imap_config);
            $this->_ob->login();
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
    public function listUsers()
    {
        return $this->_backend->listUsers();
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
        $this->_backend->updateUser($oldID, $newID, $credentials);
    }

    /**
     * Return the URI of the login screen for this authentication method.
     *
     * @param string $app  The application to use.
     * @param string $url  The URL to redirect to after login.
     *
     * @return string  The login screen URI.
     */
    public function getLoginScreen($app = 'horde', $url = '')
    {
        return $this->_backend->getLoginScreen($app, $url);
    }

    /**
     * Checks if a userId exists in the system.
     *
     * @return boolean  Whether or not the userId already exists.
     */
    public function exists($userId)
    {
        return $this->_backend->exists($userId);
    }

    /**
     * Automatic authentication: Find out if the client matches an allowed IP
     * block.
     *
     * @return boolean  Whether or not the client is allowed.
     */
    protected function _transparent()
    {
        return $this->_backend->transparent();
    }

}
