<?php
/**
 * The Horde_Auth_Cyrus class provides Horde with the ability of
 * administrating a Cyrus mail server authentications against another backend
 * that Horde can update (eg SQL or LDAP).
 *
 * Optional values:
 * <pre>
 *
 * </pre>
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Ilya Krel <mail@krel.org>
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Auth
 */
class Horde_Auth_Cyrus extends Horde_Auth_Base
{
    /**
     * Horde_Imap_Client object.
     *
     * @var Horde_Imap_Client_Base
     */
    protected $_imap;

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
     * @param array $params  Parameters:
     * <pre>
     * 'backend' - (Horde_Auth_Base) [REQUIRED] The backend object.
     * 'charset' - (string) Default charset.
     *             DEFAULT: NONE
     * 'folders' - (array) An array of folders to create under username.
     *             DEFAULT: NONE
     * 'imap' - (Horde_Imap_Client_Base) [REQUIRED] An IMAP client object.
     * 'quota' - (integer) The quota (in kilobytes) to grant on the mailbox.
     *           DEFAULT: NONE
     * 'separator' - (string) Hierarchy separator to use (e.g., is it
     *               user/mailbox or user.mailbox)
     *               DEFAULT: '.'
     * 'unixhier' - (boolean) The value of imapd.conf's unixhierarchysep
     *              setting. Set this to true if the value is true in
     *              imapd.conf.
     *              DEFAULT: false
     * </pre>
     *
     * @throws InvalidArgumentException
     * @throws Horde_Auth_Exception
     */
    public function __construct(array $params = array())
    {
        foreach (array('backend', 'imap') as $val) {
            if (!isset($params[$val])) {
                throw new InvalidArgumentException('Missing ' . $val . ' parameter.');
            }
        }

        $this->_backend = $params['backend'];
        $this->_ob = $params['imap'];
        unset($params['backend']);

        $params = array_merge(array(
            'charset' => null,
            'separator' => '.',
        ), $params);

        parent::__construct($params);

        if (isset($this->_params['unixhier']) &&
            $this->_params['unixhier'] == true) {
            $this->_params['separator'] = '/';
        }

        // Check the capabilities of the backend.
        if (!$this->_backend->hasCapability('add') ||
            !$this->_backend->hasCapability('update') ||
            !$this->_backend->hasCapability('remove')) {
            throw new Horde_Auth_Exception(__CLASS__ . ': Backend does not have required capabilites.');
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
        $this->_backend->addUser($userId, $credentials);

        $mailbox = Horde_String::convertCharset('user' . $this->_params['separator'] . $userId, $this->_params['charset'], 'utf7-imap');

        try {
            $this->_imap->createMailbox($mailbox);
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }

        if (isset($this->_params['folders']) &&
            is_array($this->_params['folders'])) {
            foreach ($this->_params['folders'] as $folder) {
                try {
                    $this->_imap->createMailbox($mailbox . Horde_String::convertCharset($this->_params['separator'] . $folder, $this->_params['charset'], 'utf7-imap'));
                } catch (Horde_Imap_Client_Exception $e) {}
            }
        }

        if (isset($this->_params['quota']) &&
            ($this->_params['quota'] >= 0)) {
            try {
                $this->_imap->setQuota($mailbox, array('storage' => $this->_params['quota']));
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
        $this->_backend->removeUser($userId);

        $mailbox = Horde_String::convertCharset('user' . $this->_params['separator'] . $userId, $this->_params['charset'], 'utf7-imap');

        /* Set ACL for mailbox deletion. */
        list($admin) = explode('@', $this->_params['cyradmin']);

        try {
            $this->_imap->setACL($mailbox, $admin, array('rights' => 'lrswipcda'));
            $this->_imap->deleteMailbox($mailbox);
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
     * Checks if a userId exists in the system.
     *
     * @return boolean  Whether or not the userId already exists.
     */
    public function exists($userId)
    {
        return $this->_backend->exists($userId);
    }

    /**
     * Authentication stub.
     *
     * On failure, Horde_Auth_Exception should pass a message string (if any)
     * in the message field, and the Horde_Auth::REASON_* constant in the code
     * field (defaults to Horde_Auth::REASON_MESSAGE).
     *
     * @param string $userID      The userID to check.
     * @param array $credentials  An array of login credentials.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        throw new Horde_Auth_Exception('Unsupported.');
    }

    /**
     * Automatic authentication: Find out if the client matches an allowed IP
     * block.
     *
     * @return boolean  Whether or not the client is allowed.
     */
    public function transparent()
    {
        return $this->_backend->transparent();
    }

}
