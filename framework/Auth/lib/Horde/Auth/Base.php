<?php
/**
 * The Horde_Auth_Base:: class provides a common abstracted interface to
 * creating various authentication backends.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Auth
 */
abstract class Horde_Auth_Base
{
    /**
     * An array of capabilities, so that the driver can report which
     * operations it supports and which it doesn't.
     *
     * @var array
     */
    protected $_capabilities = array(
        'add'           => false,
        'authenticate'  => true,
        'groups'        => false,
        'list'          => false,
        'resetpassword' => false,
        'remove'        => false,
        'transparent'   => false,
        'update'        => false
    );

    /**
     * Hash containing parameters needed for the drivers.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * The credentials currently being authenticated.
     *
     * @var array
     */
    protected $_credentials = array(
        'credentials' => array(),
        'params' => array('change' => false),
        'userId' => ''
    );

    /**
     * Current application for authentication.
     *
     * @var string
     */
    protected $_app = 'horde';

    /**
     * Logger object.
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Constructor.
     *
     * @param array $params  Optional parameters:
     * <pre>
     * 'logger' - (Horde_Log_Logger) A logger object.
     * 'notify_expire' - (callback) Callback function to output notification
     *                   when password is about to expire. Passed one
     *                   argument: UNIX timestamp of when password expires.
     * </pre>
     */
    public function __construct(array $params = array())
    {
        if (isset($params['logger'])) {
            $this->_logger = $params['logger'];
            unset($params['logger']);
        }

        $this->_params = $params;
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
        $auth = false;
        $userId = trim($userId);

        try {
            list($userId, $credentials) = Horde_Auth::runHook($userId, $credentials, $this->_app, 'preauthenticate', 'authenticate');
         } catch (Horde_Auth_Exception $e) {
            return false;
        }

        /* Store the credentials being checked so that subclasses can modify
         * them if necessary. */
        $this->_credentials['credentials'] = $credentials;
        $this->_credentials['userId'] = $userId;
        $this->_credentials['params']['app'] = $this->_app;

        try {
            $this->_authenticate($userId, $credentials);

            if ($login) {
                $auth = Horde_Auth::setAuth(
                    $this->_credentials['userId'],
                    $this->_credentials['credentials'],
                    $this->_credentials['params']
                );
            } else {
                $auth = Horde_Auth::checkExistingAuth();
            }
        } catch (Horde_Auth_Exception $e) {
            if ($e->getCode()) {
                Horde_Auth::setAuthError($e->getCode());
            } else {
                Horde_Auth::setAuthError(Horde_Auth::REASON_MESSAGE, $e->getMessage());
            }
        }

        return $auth;
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
    abstract protected function _authenticate($userId, $credentials);

    /**
     * Check existing auth for triggers that might invalidate it.
     *
     * @return boolean  Is existing auth valid?
     */
    public function checkExistingAuth()
    {
        return true;
    }

    /**
     * Adds a set of authentication credentials.
     *
     * @param string $userId      The userId to add.
     * @param array $credentials  The credentials to use.
     *
     * @throws Horde_Auth_Exception
     */
    public function addUser($userId, $credentials)
    {
        throw new Horde_Auth_Exception('Unsupported.');
    }

    /**
     * Updates a set of authentication credentials.
     *
     * @param string $oldID       The old userId.
     * @param string $newID       The new userId.
     * @param array $credentials  The new credentials
     *
     * @throws Horde_Auth_Exception
     */
    public function updateUser($oldID, $newID, $credentials)
    {
        throw new Horde_Auth_Exception('Unsupported.');
    }

    /**
     * Deletes a set of authentication credentials.
     *
     * @param string $userId  The userId to delete.
     *
     * @throws Horde_Auth_Exception
     */
    public function removeUser($userId)
    {
        throw new Horde_Auth_Exception('Unsupported.');
    }

    /**
     * Lists all users in the system.
     *
     * @return mixed  The array of userIds.
     * @throws Horde_Auth_Exception
     */
    public function listUsers()
    {
        throw new Horde_Auth_Exception('Unsupported.');
    }

    /**
     * Checks if $userId exists in the system.
     *
     * @param string $userId  User ID for which to check
     *
     * @return boolean  Whether or not $userId already exists.
     */
    public function exists($userId)
    {
        try {
            $users = $this->listUsers();
            return in_array($userId, $users);
        } catch (Horde_Auth_Exception $e) {
            return false;
        }
    }

    /**
     * Automatic authentication.
     *
     * @return boolean  Whether or not the user is authenticated automatically.
     * @throws Horde_Auth_Exception
     */
    public function transparent()
    {
        $userId = empty($this->_credentials['userId'])
            ? $GLOBALS['registry']->getAuth()
            : $this->_credentials['userId'];
        $credentials = empty($this->_credentials['credentials'])
            ? Horde_Auth::getCredential()
            : $this->_credentials['credentials'];

        list($this->_credentials['userId'], $this->_credentials['credentials']) = Horde_Auth::runHook($userId, $credentials, $this->_app, 'preauthenticate', 'transparent');
        $this->_credentials['params']['app'] = $this->_app;

        if ($this->_transparent()) {
            return Horde_Auth::setAuth(
                $this->_credentials['userId'],
                $this->_credentials['credentials'],
                $this->_credentials['params']
            );
        }

        return false;
    }

    /**
     * Transparent authentication stub.
     *
     * Transparent authentication should set 'userId', 'credentials', or
     * 'params' in $this->_credentials as needed - these values will be used
     * to set the credentials in the session.
     *
     * Transparent authentication should normally never throw an error - false
     * should be returned.
     *
     * @return boolean  Whether transparent login is supported.
     * @throws Horde_Auth_Exception
     */
    protected function _transparent()
    {
        return false;
    }

    /**
     * Reset a user's password. Used for example when the user does not
     * remember the existing password.
     *
     * @param string $userId  The user id for which to reset the password.
     *
     * @return string  The new password on success.
     * @throws Horde_Auth_Exception
     */
    public function resetPassword($userId)
    {
        throw new Horde_Auth_Exception('Unsupported.');
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
        return !empty($this->_capabilities[$capability]);
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
        return isset($this->_params[$param])
            ? $this->_params[$param]
            : null;
    }

    /**
     * Returns information on what login parameters to display on the login
     * screen. If not defined, will display the default (username, password).
     *
     * @return array  An array with the following elements:
     * <pre>
     * 'js_code' - (array) A list of javascript code to output to the login
     *              page.
     * 'js_files' - (array) A list of javascript files to include in the login
     *              page.
     * 'params' - (array) TODO
     * </pre>
     * @throws Horde_Exception
     */
    public function getLoginParams()
    {
        return array(
            'js_code' => array(),
            'js_files' => array(),
            'params' => array()
        );
    }

}
