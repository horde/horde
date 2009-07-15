<?php
/**
 * The Horde_Auth_Application class provides a wrapper around
 * application-provided Horde authentication which fits inside the
 * Horde_Auth:: API.
 *
 * Required parameters:
 * <pre>
 * 'app' - (string) The application which is providing authentication.
 * </pre>
 *
 * Optional parameters:
 * <pre>
 * 'params' - (array) Parameters to pass to the application's authenticate
 *            method.
 * </pre>
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Auth
 */
class Horde_Auth_Application extends Horde_Auth_Base
{
    /**
     * Cache for hasCapability().
     *
     * @var array
     */
    protected $_loaded = array();

    /**
     * Equivalent methods in application's API.
     *
     * @var array
     */
    protected $_apiMethods = array(
        'add' => 'authAddUser',
        'authenticate' => 'authAuthenticate',
        'exists' => 'authUserExists',
        'list' => 'authUserList',
        'remove' => 'authRemoveUser',
        'update' => 'authUpdateUser'
    );

    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        Horde::assertDriverConfig($params, 'auth', array('app'), 'authentication application');

        parent::__construct($params);
    }

    /**
     * Queries the current Auth object to find out if it supports the given
     * capability.
     *
     * @param string $capability  The capability to test for.
     *
     * @return boolean  Whether or not the capability is supported.
     */
    public function hasCapability($capability)
    {
        $capability = strtolower($capability);

        if (!in_array($capability, $this->_loaded) &&
            isset($this->_apiMethods[$capability])) {
            $registry = Horde_Registry::singleton();
            $this->_capabilities[$capability] = $registry->hasMethod($this->_apiMethods[$capability], $this->_params['app']);
            $this->_loaded[] = $capability;
        }

        return parent::hasCapability($capability);
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
        if (!$this->hasCapability('authenticate')) {
            throw new Horde_Auth_Exception($this->_params['app'] . ' does not provide an authenticate() method.');
        }

        $registry = Horde_Registry::singleton();

        try {
            $result = $registry->callByPackage($this->_params['app'], $this->_apiMethods['authenticate'], array($userId, $credentials));
        } catch (Horde_Auth_Exception $e) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
        }

        // Horrific hack.  Avert your eyes.  Since an application may already
        // set the authentication information necessary, we don't want to
        // overwrite that info.  Coming into this function, we know that
        // the authentication has not yet been set in this session.  So after
        // calling the app-specific auth handler, if authentication
        // information has suddenly appeared, it follows that the information
        // has been stored already in the session and we shouldn't overwrite.
        // So grab the authentication ID set and stick it in $_authCredentials
        // this will eventually cause setAuth() in authenticate() to exit
        // before re-setting the auth info values.
        if ($ret && ($authid = Horde_Auth::getAuth())) {
            $this->_authCredentials['userId'] = $authid;
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
        if ($this->hasCapability('list')) {
            $registry = Horde_Registry::singleton();
            return $registry->callByPackage($this->_params['app'], $this->_apiMethods['list']);
        } else {
            return parent::listUsers();
        }
    }

    /**
     * Checks if $userId exists in the system.
     *
     * @param string $userId  User ID to check.
     *
     * @return boolean  Whether or not $userId already exists.
     */
    public function exists($userId)
    {
        if ($this->hasCapability('exists')) {
            $registry = Horde_Registry::singleton();
            return $registry->callByPackage($this->_params['app'], $this->_apiMethods['exists'], array($userId));
        } else {
            return parent::exists($userId);
        }
    }

    /**
     * Add a set of authentication credentials.
     *
     * @param string $userId      The userId to add.
     * @param array $credentials  The credentials to use.
     *
     * @throws Horde_Auth_Exception
     */
    public function addUser($userId, $credentials)
    {
        if ($this->hasCapability('add')) {
            $registry = Horde_Registry::singleton();
            $registry->callByPackage($this->_params['app'], $this->_apiMethods['exists'], array($userId, $credentials));
        } else {
            parent::addUser($userId, $credentials);
        }
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
        if ($this->hasCapability('update')) {
            $registry = Horde_Registry::singleton();
            $registry->callByPackage($this->_params['app'], $this->_apiMethods['update'], array($oldID, $newID, $credentials));
        } else {
            parent::updateUser($userId, $credentials);
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
        if ($this->hasCapability('remove')) {
            $registry = Horde_Registry::singleton();
            $registry->callByPackage($this->_params['app'], $this->_apiMethods['remove'], array($userId));
            Horde_Auth::removeUserData($userId);
        } else {
            parent::removeUser($userId);
        }
    }

}
