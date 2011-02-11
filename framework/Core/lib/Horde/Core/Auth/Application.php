<?php
/**
 * The Horde_Core_Auth_Application class provides application-specific
 * authentication built on top of the horde/Auth API.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Core
 */
class Horde_Core_Auth_Application extends Horde_Auth_Base
{
    /**
     * Authentication failure reasons (additions to Horde_Auth:: reasons).
     *
     * <pre>
     * REASON_BROWSER - A browser change was detected
     * REASON_SESSIONIP - Logout due to change of IP address during session
     * </pre>
     */
    const REASON_BROWSER = 100;
    const REASON_SESSIONIP = 101;

    /**
     * Application for authentication.
     *
     * @var string
     */
    protected $_app = 'horde';

    /**
     * The list of application capabilities.
     *
     * @var array
     */
    protected $_appCapabilities;

    /**
     * The base auth driver, used for Horde authentication.
     *
     * @var Horde_Auth_Base
     */
    protected $_base;

    /**
     * Available capabilities.
     *
     * @var array
     */
    protected $_capabilities = array(
        'add',
        'authenticate',
        'exists',
        'list',
        'remove',
        'resetpassword',
        'transparent',
        'update'
    );

    /**
     * Constructor.
     *
     * @param array $params  Required parameters:
     * <pre>
     * 'app' - (string) The application which is providing authentication.
     * 'base' - (Horde_Auth_Base) The base Horde_Auth driver. Only needed if
                'app' is 'horde'.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['app'])) {
            throw new InvalidArgumentException('Missing app parameter.');
        }
        $this->_app = $params['app'];
        unset($params['app']);

        if ($this->_app == 'horde') {
            if (!isset($params['base'])) {
                throw new InvalidArgumentException('Missing base parameter.');
            }

            $this->_base = $params['base'];
            unset($params['base']);
        }

        parent::__construct($params);
    }

    /**
     * Finds out if a set of login credentials are valid, and if requested,
     * mark the user as logged in in the current session.
     *
     * @param string $userId      The user ID to check.
     * @param array $credentials  The credentials to check.
     * @param boolean $login      Whether to log the user in. If false, we'll
     *                            only test the credentials and won't modify
     *                            the current session. Defaults to true.
     *
     * @return boolean  Whether or not the credentials are valid.
     */
    public function authenticate($userId, $credentials, $login = true)
    {
        try {
            list($userId, $credentials) = $this->runHook(trim($userId), $credentials, 'preauthenticate', 'authenticate');
         } catch (Horde_Auth_Exception $e) {
            return false;
        }

        if ($this->_base) {
            if (!$this->_base->authenticate($userId, $credentials, $login)) {
                return false;
            }
        } elseif (!parent::authenticate($userId, $credentials, $login)) {
            return false;
        }

        return $this->_setAuth();
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userId      The user ID to check.
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

        $GLOBALS['registry']->callAppMethod($this->_app, 'authAuthenticate', array('args' => array($userId, $credentials), 'noperms' => true));
    }

    /**
     * Checks for triggers that may invalidate the current auth.
     * These triggers are independent of the credentials.
     *
     * @return boolean  True if the results of authenticate() are still valid.
     */
    public function validateAuth()
    {
        return $this->_base
            ? $this->_base->validateAuth()
            : parent::validateAuth();
    }

    /**
     * Add a set of authentication credentials.
     *
     * @param string $userId      The user ID to add.
     * @param array $credentials  The credentials to use.
     *
     * @throws Horde_Auth_Exception
     */
    public function addUser($userId, $credentials)
    {
        if ($this->_base) {
            $this->_base->addUser($userId, $credentials);
            return;
        }

        if ($this->hasCapability('add')) {
            $GLOBALS['registry']->callAppMethod($this->_app, 'authAddUser', array('args' => array($userId, $credentials)));
        } else {
            parent::addUser($userId, $credentials);
        }
    }

    /**
     * Update a set of authentication credentials.
     *
     * @param string $oldID       The old user ID.
     * @param string $newID       The new user ID.
     * @param array $credentials  The new credentials
     *
     * @throws Horde_Auth_Exception
     */
    public function updateUser($oldID, $newID, $credentials)
    {
        if ($this->_base) {
            $this->_base->updateUser($oldID, $newID, $credentials);
            return;
        }

        if ($this->hasCapability('update')) {
            $GLOBALS['registry']->callAppMethod($this->_app, 'authUpdateUser', array('args' => array($oldID, $newID, $credentials)));
        } else {
            parent::updateUser($userId, $credentials);
        }
    }

    /**
     * Delete a set of authentication credentials.
     *
     * @param string $userId  The user ID to delete.
     *
     * @throws Horde_Auth_Exception
     */
    public function removeUser($userId)
    {
        if ($this->_base) {
            $this->_base->removeUser($userId);
        } else {
            if ($this->hasCapability('remove')) {
                $GLOBALS['registry']->callAppMethod($this->_app, 'authRemoveUser', array('args' => array($userId)));
            } else {
                parent::removeUser($userId);
            }
        }
    }

    /**
     * List all users in the system.
     *
     * @return array  The array of user IDs.
     * @throws Horde_Auth_Exception
     */
    public function listUsers()
    {
        if ($this->_base) {
            return $this->_base->listUsers();
        }

        return $this->hasCapability('list')
            ? $GLOBALS['registry']->callAppMethod($this->_app, 'authUserList')
            : parent::listUsers();
    }

    /**
     * Checks if a user ID exists in the system.
     *
     * @param string $userId  User ID to check.
     *
     * @return boolean  Whether or not the user ID already exists.
     */
    public function exists($userId)
    {
        if ($this->_base) {
            return $this->_base->exists($userId);
        }

        return $this->hasCapability('exists')
            ? $GLOBALS['registry']->callAppMethod($this->_app, 'authUserExists', array('args' => array($userId)))
            : parent::exists($userId);
    }

    /**
     * Automatic authentication.
     *
     * @return boolean  Whether or not the client is allowed.
     * @throws Horde_Auth_Exception
     */
    public function transparent()
    {
        global $registry;

        $is_auth = $registry->getAuth();

        if (!($userId = $this->getCredential('userId'))) {
            $userId = $is_auth;
        }
        if (!($credentials = $this->getCredential('credentials'))) {
            $credentials = $registry->getAuthCredential();
        }

        list($userId, $credentials) = $this->runHook($userId, $credentials, 'preauthenticate', 'transparent');

        $this->setCredential('userId', $userId);
        $this->setCredential('credentials', $credentials);

        if ($this->_base) {
            $result = $this->_base->transparent();
        } elseif ($this->hasCapability('transparent')) {
            $result = $registry->callAppMethod($this->_app, 'authTransparent', array('args' => array($this), 'noperms' => true));
        } else {
            /* If this application contains neither transparent nor
             * authenticate capabilities, it does not require any
             * authentication if already authenticated to Horde. */
            $result = ($registry->getAuth() && !$this->hasCapability('authenticate'));
        }

        return $result && $this->_setAuth();
    }

    /**
     * Reset a user's password. Used for example when the user does not
     * remember the existing password.
     *
     * @param string $userId  The user ID for which to reset the password.
     *
     * @return string  The new password on success.
     * @throws Horde_Auth_Exception
     */
    public function resetPassword($userId)
    {
        if ($this->_base) {
            return $this->_base->resetPassword($userId);
        }

        return $this->hasCapability('resetpassword')
            ? $GLOBALS['registry']->callAppMethod($this->_app, 'authResetPassword', array('args' => array($userId)))
            : parent::resetPassword();
    }

    /**
     * Queries the current driver to find out if it supports the given
     * capability.
     *
     * @param string $capability  The capability to test for.
     *
     * @return boolean  Whether or not the capability is supported.
     */
    public function hasCapability($capability)
    {
        if ($this->_base) {
            return $this->_base->hasCapability($capability);
        }

        if (!isset($this->_appCapabilities)) {
            $this->_appCapabilities = $GLOBALS['registry']->getApiInstance($this->_app, 'application')->auth;
        }

        return in_array(strtolower($capability), $this->_appCapabilities);
    }

    /**
     * Returns the named parameter for the current auth driver.
     *
     * @param string $param  The parameter to fetch.
     *
     * @return string  The parameter's value, or null if it doesn't exist.
     */
    public function getParam($param)
    {
        return $this->_base
            ? $this->_base->getParam($param)
            : parent::getParam($param);
    }

    /**
     * Retrieve internal credential value(s).
     *
     * @param mixed $name  The credential value to get. If null, will return
     *                     the entire credential list. Valid names:
     * <pre>
     * 'change' - (boolean) Do credentials need to be changed?
     * 'credentials' - (array) The credentials needed to authenticate.
     * 'expire' - (integer) UNIX timestamp of the credential expiration date.
     * 'userId' - (string) The user ID.
     * </pre>
     *
     * @return mixed  Return the credential information, or null if the
     *                credential doesn't exist.
     */
    public function getCredential($name = null)
    {
        return $this->_base
            ? $this->_base->getCredential($name)
            : parent::getCredential($name);
    }

    /**
     * Set internal credential value.
     *
     * @param string $name  The credential name to set.
     * @param mixed $value  The credential value to set. See getCredential()
     *                      for the list of valid credentials/types.
     */
    public function setCredential($type, $value)
    {
        if ($this->_base) {
            $this->_base->setCredential($type, $value);
        } else {
            parent::setCredential($type, $value);
        }
    }

    /**
     * Sets the error message for an invalid authentication.
     *
     * @param string $type  The type of error (Horde_Auth::REASON_* constant).
     * @param string $msg   The error message/reason for invalid
     *                      authentication.
     */
    public function setError($type, $msg = null)
    {
        if ($this->_base) {
            $this->_base->setError($type, $msg);
        } else {
            parent::setError($type, $msg);
        }
    }

    /**
     * Returns the error type or message for an invalid authentication.
     *
     * @param boolean $msg  If true, returns the message string (if set).
     *
     * @return mixed  Error type, error message (if $msg is true) or false
     *                if entry doesn't exist.
     */
    public function getError($msg = false)
    {
        return $this->_base
            ? $this->_base->getError($msg)
            : parent::getError($msg);
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
        return ($this->_base && method_exists($this->_base, 'getLoginParams'))
            ? $this->_base->getLoginParams()
            : $GLOBALS['registry']->callAppMethod($this->_app, 'authLoginParams', array('noperms' => true));
    }

    /**
     * Indicate whether the application requires authentication.
     *
     * @return boolean  True if application requires authentication.
     */
    public function requireAuth()
    {
        return !$this->_base &&
               ($this->hasCapability('authenticate') ||
                $this->hasCapability('transparent'));
    }

    /**
     * Runs the pre/post-authenticate hook and parses the result.
     *
     * @param string $userId      The userId who has been authorized.
     * @param array $credentials  The credentials of the user.
     * @param string $type        Either 'preauthenticate' or
     *                            'postauthenticate'.
     * @param string $method      The triggering method (preauthenticate only).
     *                            Either 'authenticate' or 'transparent'.
     *
     * @return array  Two element array, $userId and $credentials.
     * @throws Horde_Auth_Exception
     */
    public function runHook($userId, $credentials, $type, $method = null)
    {
        if (!is_array($credentials)) {
            $credentials = empty($credentials)
                ? array()
                : array($credentials);
        }

        $ret_array = array($userId, $credentials);

        if ($type == 'preauthenticate') {
            $credentials['authMethod'] = $method;
        }

        try {
            $result = Horde::callHook($type, array($userId, $credentials), $this->_app);
        } catch (Horde_Exception $e) {
            throw new Horde_Auth_Exception($e);
        } catch (Horde_Exception_HookNotSet $e) {
            return $ret_array;
        }

        unset($credentials['authMethod']);

        if ($result === false) {
            if ($this->getError() != Horde_Auth::REASON_MESSAGE) {
                $this->setError(Horde_Auth::REASON_FAILED);
            }
            throw new Horde_Auth_Exception($type . ' hook failed');
        }

        if (is_array($result)) {
            if ($type == 'postauthenticate') {
                $ret_array[1] = $result;
            } else {
                if (isset($result['userId'])) {
                    $ret_array[0] = $result['userId'];
                }

                if (isset($result['credentials'])) {
                    $ret_array[1] = $result['credentials'];
                }
            }
        }

        return $ret_array;
    }

    /**
     * Set authentication credentials in the Horde session.
     *
     * @return boolean  True on success, false on failure.
     */
    protected function _setAuth()
    {
        global $registry;

        if ($registry->isAuthenticated(array('app' => $this->_app, 'notransparent' => true))) {
            return true;
        }

        /* Grab the current language before we destroy the session. */
        $language = $registry->preferredLang();

        /* Destroy any existing session on login and make sure to use a
         * new session ID, to avoid session fixation issues. */
        if (!$registry->getAuth()) {
            $registry->getCleanSession();
        }

        $userId = $this->getCredential('userId');
        $credentials = $this->getCredential('credentials');

        try {
            list(,$credentials) = $this->runHook($userId, $credentials, 'postauthenticate');
        } catch (Horde_Auth_Exception $e) {
            return false;
        }

        $registry->setAuth($userId, $credentials, array(
            'app' => $this->_app,
            'change' => $this->getCredential('change'),
            'language' => $language
        ));

        if ($this->_base &&
            isset($GLOBALS['notification']) &&
            ($expire = $this->_base->getCredential('expire'))) {
            $toexpire = ($expire - time()) / 86400;
            $GLOBALS['notification']->push(sprintf(Horde_Core_Translation::ngettext("%d day until your password expires.", "%d days until your password expires.", $toexpire), $toexpire), 'horde.warning');
        }

        $registry->callAppMethod($this->_app, 'authAuthenticateCallback', array('noperms' => true));

        return true;
    }

}
