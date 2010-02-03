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
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
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
        'resetpassword' => 'authResetPassword',
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
            $this->_capabilities[$capability] = $GLOBALS['registry']->hasAppMethod($this->_app, $this->_apiMethods[$capability]);
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

        $credentials['auth_ob'] = $this;

        try {
            $result = $GLOBALS['registry']->callAppMethod($this->_app, $this->_apiMethods['authenticate'], array('args' => array($userId, $credentials), 'noperms' => true));
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
            return $GLOBALS['registry']->callAppMethod($this->_app, $this->_apiMethods['list']);
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
            return $GLOBALS['registry']->callAppMethod($this->_app, $this->_apiMethods['exists'], array('args' => array($userId)));
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
            $GLOBALS['registry']->callAppMethod($this->_app, $this->_apiMethods['add'], array('args' => array($userId, $credentials)));
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
            $GLOBALS['registry']->callAppMethod($this->_app, $this->_apiMethods['update'], array('args' => array($oldID, $newID, $credentials)));
        } else {
            parent::updateUser($userId, $credentials);
        }
    }

    /**
     * Reset a user's password. Used for example when the user does not
     * remember the existing password.
     *
     * @param string $userId  The userId for which to reset the password.
     *
     * @return string  The new password on success.
     * @throws Horde_Auth_Exception
     */
    public function resetPassword($userId)
    {
        if ($this->hasCapability('resetpassword')) {
            return $GLOBALS['registry']->callAppMethod($this->_app, $this->_apiMethods['resetpassword'], array('args' => array($userId)));
        }

        return parent::resetPassword();
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
            $GLOBALS['registry']->callAppMethod($this->_app, $this->_apiMethods['remove'], array('args' => array($userId)));
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
     * Attempt transparent authentication. The application method is passed a
     * single parameter: the current class instance.
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

        return $GLOBALS['registry']->callAppMethod($this->_app, $this->_apiMethods['transparent'], array('args' => array($this), 'noperms' => true));
    }

    /**
     * Returns information on what login parameters to display on the login
     * screen.
     *
     * @return array  An array with the following keys:
     * <pre>
     * 'js_code' - (array) A list of javascript statements to be included via
     *             Horde::addInlineScript().
     * 'js_files' - (array) A list of javascript files to be included via
     *              Horde::addScriptFile().
     * 'nosidebar' - (boolean) If true, never load the sidebar when
     *               authenticating to this app.
     * 'params' - (array) A list of parameters to display on the login screen.
     *            Each entry is an array with the following entries:
     *            'label' - (string) The label of the entry.
     *            'type' - (string) 'select', 'text', or 'password'.
     *            'value' - (mixed) If type is 'text' or 'password', the
     *                      text to insert into the field by default. If type
     *                      is 'select', an array with they keys as the
     *                      option values and an array with the following keys:
     *                      'hidden' - (boolean) If true, the option will be
     *                                 hidden.
     *                      'name' - (string) The option label.
     *                      'selected' - (boolean) If true, will be selected
     *                                   by default.
     * </pre>
     *
     * @throws Horde_Exception
     */
    public function getLoginParams()
    {
        if (!$this->hasCapability('loginparams')) {
            return parent::getLoginParams();
        }

        return $GLOBALS['registry']->callAppMethod($this->_app, $this->_apiMethods['loginparams'], array('noperms' => true));
    }

    /**
     * Provide method to get internal credential values. Necessary as the
     * application API does not have direct access to the protected member
     * variables of this class.
     *
     * @param mixed $name  The credential name to get. If null, will return
     *                     the entire credential list.
     *
     * @return mixed  Return the credential information, or null if the.
     *                credential doesn't exist.
     */
    public function getCredential($name = null)
    {
        if (is_null($name)) {
            return $this->_credentials;
        }

        return isset($this->_credentials[$name])
            ? $this->_credentials[$name]
            : null;
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
            $GLOBALS['registry']->callAppMethod($this->_app, $this->_apiMethods['authenticatecallback'], array('noperms' => true));
        }
    }

    /**
     * Indicate whether the application requires authentication.
     *
     * @return boolean  True if application requires authentication.
     */
    public function requireAuth()
    {
        return $this->hasCapability('authenticate') || $this->hasCapability('transparent');
    }

}
