<?php
/**
 * The Horde_Auth_Application class provides a wrapper around
 * application-provided Horde authentication which fits inside the
 * Horde Horde_Auth:: API.
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
class Horde_Auth_Application extends Horde_Auth_Driver
{
    /**
     * Cache for hasCapability().
     *
     * @var array
     */
    protected $_loaded = array();

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

        $methods = array(
            'add' => 'addUser',
            'exists' => 'userExists',
            'list' => 'userList',
            'remove' => 'removeUser',
            'update' => 'updateUser'
        );

        if (!in_array($capability, $this->_loaded) &&
            isset($methods[$capability])) {
            $this->_capabilities[$capability] = $GLOBALS['registry']->hasMethod($methods[$capability], $this->_params['app']);
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
     * @throws Horde_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        if (!$GLOBALS['registry']->hasMethod('authenticate', $this->_params['app'])) {
            throw new Horde_Exception($this->_params['app'] . ' does not provide an authenticate() method.');
        }

        if (!$GLOBALS['registry']->callByPackage($this->_params['app'], 'authenticate', array('userId' => $userId, 'credentials' => $credentials, 'params' => $this->_params))) {
            throw new Horde_Exception('', Horde_Auth::REASON_BADLOGIN);
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
     * Return the URI of the login screen for this authentication method.
     *
     * @param string $app  The application to use.
     * @param string $url  The URL to redirect to after login.
     *
     * @return string  The login screen URI.
     */
    public function getLoginScreen($app = 'horde', $url = '')
    {
        return parent::getLoginScreen($this->_params['app'], $url);
    }

    /**
     * List all users in the system.
     *
     * @return array  The array of userIds.
     * @throws Horde_Exception
     */
    public function listUsers()
    {
        return $this->hasCapability('list')
            ? $GLOBALS['registry']->callByPackage($this->_params['app'], 'userList')
            : parent::listUsers();
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
        return $this->hasCapability('exists')
            ? $GLOBALS['registry']->callByPackage($this->_params['app'], 'userExists', array($userId))
            : parent::exists($userId);
    }

    /**
     * Add a set of authentication credentials.
     *
     * @param string $userId      The userId to add.
     * @param array $credentials  The credentials to use.
     *
     * @throws Horde_Exception
     */
    public function addUser($userId, $credentials)
    {
        if ($this->hasCapability('add')) {
            $GLOBALS['registry']->callByPackage($this->_params['app'], 'addUser', array($userId, $credentials));
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
     * @throws Horde_Exception
     */
    public function updateUser($oldID, $newID, $credentials)
    {
        if ($this->hasCapability('update')) {
            $GLOBALS['registry']->callByPackage($this->_params['app'], 'updateUser', array($oldID, $newID, $credentials));
        } else {
            parent::addUser($userId, $credentials);
        }
    }

    /**
     * Delete a set of authentication credentials.
     *
     * @param string $userId  The userId to delete.
     *
     * @throws Horde_Exception
     */
    public function removeUser($userId)
    {
        if ($this->hasCapability('remove')) {
            $res = $GLOBALS['registry']->callByPackage($this->_params['app'], 'removeUser', array($userId));
            if (is_a($res, 'PEAR_Error')) {
                throw new Horde_Exception($result);
            }

            Horde_Auth::removeUserData($userId);
        } else {
            parent::removeUser($userId);
        }
    }

}
