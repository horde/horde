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
        'authenticatecallback' => 'authAuthenticateCallback',
        'exists' => 'authUserExists',
        'list' => 'authUserList',
        'loginparams' => 'authLoginParams',
        'remove' => 'authRemoveUser',
        'transparent' => 'authTransparent',
        'update' => 'authUpdateUser'
    );

    /**
     * Constructor.
     *
     * @param array $params  A hash containing connection parameters.
     * @throws Horde_Exception
     */
    public function __construct($params = array())
    {
        Horde::assertDriverConfig($params, 'auth', array('app'), 'authentication application');

        $this->_app = $params['app'];
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
            $this->_capabilities[$capability] = $registry->hasAppMethod($this->_app, $this->_apiMethods[$capability]);
            $this->_loaded[] = $capability;
        }

        return parent::hasCapability($capability);
    }

    /**
     * Finds out if a set of login credentials are valid, and if requested,
     * mark the user as logged in in the current session.
     *
     * @param string $userId      The userId to check.
     * @param array $credentials  The credentials to check.
     * @param boolean $login      Whether to log the user in. If false, we'll
     *                            only test the credentials and won't modify
     *                            the current session. Defaults to true.
     *
     * @return boolean  Whether or not the credentials are valid.
     */
    public function authenticate($userId, $credentials, $login = true)
    {
        if (!parent::authenticate($userId, $credentials, $login)) {
            return false;
        }

        $this->_authCallback();

        return true;
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userId      The userId to check.
     * @param array $credentials  The credentials to use. This object will
     *                            always be available in the 'auth_ob' key.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        if (!$this->hasCapability('authenticate')) {
            throw new Horde_Auth_Exception($this->_app . ' does not provide an authenticate() method.');
        }

        $registry = Horde_Registry::singleton();

        $credentials['auth_ob'] = $this;

        try {
            $result = $registry->callAppMethod($this->_app, $this->_apiMethods['authenticate'], array('args' => array($userId, $credentials), 'noperms' => true));
        } catch (Horde_Auth_Exception $e) {
            throw new Horde_Auth_Exception('', Horde_Auth::REASON_BADLOGIN);
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
            return $registry->callAppMethod($this->_app, $this->_apiMethods['list']);
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
            return $registry->callAppMethod($this->_app, $this->_apiMethods['exists'], array('args' => array($userId)));
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
            $registry->callAppMethod($this->_app, $this->_apiMethods['exists'], array('args' => array($userId, $credentials)));
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
            $registry->callAppMethod($this->_app, $this->_apiMethods['update'], array('args' => array($oldID, $newID, $credentials)));
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
            $registry->callAppMethod($this->_app, $this->_apiMethods['remove'], array('args' => array($userId)));
            Horde_Auth::removeUserData($userId);
        } else {
            parent::removeUser($userId);
        }
    }

    /**
     * Automatic authentication.
     *
     * @return boolean  Whether or not the client is allowed.
     * @throws Horde_Auth_Exception
     */
    public function transparent()
    {
        if (!parent::transparent()) {
            return false;
        }

        $this->_authCallback();

        return true;
    }

    /**
     * Attempt transparent authentication.
     *
     * @return boolean  Whether transparent login is supported.
     * @throws Horde_Auth_Exception
     */
    protected function _transparent()
    {
        if (!$this->hasCapability('transparent')) {
            /* If this application contains neither transparent nor
             * authenticate capabilities, it does not require any
             * authentication if already authenticated to Horde. */
            return (Horde_Auth::getAuth() &&
                    !$this->hasCapability('authenticate'));
        }

        $registry = Horde_Registry::singleton();
        return $registry->callAppMethod($this->_app, $this->_apiMethods['transparent'], array('noperms' => true));
    }

    /**
     * Returns information on what login parameters to display on the login
     * screen.
     *
     * Is defined in an application's API in the function name identified by
     * self::_apiMethods['loginparams'].
     *
     * @throws Horde_Exception
     */
    public function getLoginParams()
    {
        if (!$this->hasCapability('loginparams')) {
            return parent::getLoginParams();
        }

        $registry = Horde_Registry::singleton();
        return $registry->callAppMethod($this->_app, $this->_apiMethods['loginparams'], array('noperms' => true));
    }

    /**
     * Provide method to set internal credential values. Necessary as the
     * application API does not have direct access to the protected member
     * variables of this class.
     *
     * @param string $name  The credential name to set.
     * @param mixed $value  The credential value to set. If $name is 'userId',
     *                      this must be a text value. If $name is
     *                      'credentials' or 'params', this is an array of
     *                      values to be merged in.
     */
    public function setCredential($type, $value)
    {
        switch ($type) {
        case 'userId':
            $this->_credentials['userId'] = $value;
            break;

        case 'credentials':
        case 'params':
            $this->_credentials[$type] = array_merge($this->_credentials[$type], $value);
            break;
        }
    }

    /**
     * Provide way to finish authentication tasks in an application and ensure
     * that the full application environment is loaded.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _authCallback()
    {
        if ($this->hasCapability('authenticatecallback')) {
            $registry = Horde_Registry::singleton();
            $registry->callAppMethod($this->_app, $this->_apiMethods['authenticatecallback'], array('noperms' => true));
        }
    }

}
