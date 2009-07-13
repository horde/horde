<?php
/**
 * The Horde_Auth_Driver:: class provides a common abstracted interface to
 * creating various authentication backends.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@curecanti.org>
 * @package Horde_Auth
 */
class Horde_Auth_Driver
{
    /**
     * An array of capabilities, so that the driver can report which
     * operations it supports and which it doesn't.
     *
     * @var array
     */
    protected $_capabilities = array(
        'add'           => false,
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
    protected $_authCredentials = array();

    /**
     * Constructor.
     *
     * @param array $params  A hash containing parameters.
     */
    public function __construct($params = array())
    {
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
     * @param string $realm       The authentication realm to check.
     *
     * @return boolean  Whether or not the credentials are valid.
     */
    public function authenticate($userId, $credentials, $login = true,
                                 $realm = null)
    {
        $auth = false;
        $userId = trim($userId);

        if (!empty($GLOBALS['conf']['hooks']['preauthenticate'])) {
            try {
                if (!Horde::callHook('_horde_hook_preauthenticate', array($userId, $credentials, $realm), 'horde')) {
                    if (Horde_Auth::getAuthError() != Horde_Auth::REASON_MESSAGE) {
                        Horde_Auth::setAuthError(Horde_Auth::REASON_FAILED);
                    }
                    return false;
                }
            } catch (Horde_Exception $e) {}
        }

        /* Store the credentials being checked so that subclasses can modify
         * them if necessary (like transparent auth does). */
        $this->_authCredentials = array(
            'changeRequested' => false,
            'credentials' => $credentials,
            'realm' => $realm,
            'userId' => $userId
        );

        try {
            $this->_authenticate($userId, $credentials);

            if ($login) {
                $auth = Horde_Auth::setAuth(
                    $this->_authCredentials['userId'],
                    $this->_authCredentials['credentials'],
                    $this->_authCredentials['realm'],
                    $this->_authCredentials['changeRequested']
                );
            } else {
                if (!Horde_Auth::checkSessionIP()) {
                    Horde_Auth::setAuthError(self::REASON_SESSIONIP);
                } elseif (!Horde_Auth::checkBrowserString()) {
                    Horde_Auth::setAuthError(self::REASON_BROWSER);
                } else {
                    $auth = true;
                }
            }
        } catch (Horde_Exception $e) {
            Horde::logMessage($e, __FILE__, __LINE__, PEAR_LOG_DEBUG);
            Horde_Auth::setAuthError($e->getCode() || Horde_Auth::REASON_MESSAGE, $e->getMessage());
        }

        return $auth;
    }

    /**
     * Authentication stub.
     *
     * Horde_Exception should pass a message string (if any) in the message
     * field, and the REASON_* constant in the code field (defaults to
     * REASON_MESSAGE).
     *
     * @throws Horde_Exception
     */
    protected function _authenticate()
    {
    }

    /**
     * Adds a set of authentication credentials.
     *
     * @param string $userId      The userId to add.
     * @param array $credentials  The credentials to use.
     *
     * @throws Horde_Exception
     */
    public function addUser($userId, $credentials)
    {
        throw new Horde_Exception('unsupported');
    }

    /**
     * Updates a set of authentication credentials.
     *
     * @param string $oldID       The old userId.
     * @param string $newID       The new userId.
     * @param array $credentials  The new credentials
     *
     * @throws Horde_Exception
     */
    public function updateUser($oldID, $newID, $credentials)
    {
        throw new Horde_Exception('unsupported');
    }

    /**
     * Deletes a set of authentication credentials.
     *
     * @param string $userId  The userId to delete.
     *
     * @throws Horde_Exception
     */
    public function removeUser($userId)
    {
        throw new Horde_Exception('unsupported');
    }

    /**
     * Lists all users in the system.
     *
     * @return mixed  The array of userIds.
     * @throws Horde_Exception
     */
    public function listUsers()
    {
        throw new Horde_Exception('unsupported');
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
        } catch (Horde_Exception $e) {
            return false;
        }
    }

    /**
     * Automatic authentication: Finds out if the client matches an allowed IP
     * block.
     *
     * @return boolean  Whether or not the client is allowed.
     */
    public function transparent()
    {
        try {
            return $this->_transparent();
        } catch (Horde_Exception $e) {
            Horde_Auth::setAuthError($e->getCode() || Horde_Auth::REASON_MESSAGE, $e->getMessage());
            return false;
        }
    }

    /**
     * Transparent authentication stub.
     *
     * If the auth error message is desired to be set, Horde_Exception should
     * thrown instead of returning false.
     * The Horde_Exception object should have a message string (if any) in the
     * message field, and the REASON_* constant in the code field (defaults to
     * REASON_MESSAGE).
     *
     * @return boolean  Whether transparent login is supported.
     * @throws Horde_Exception
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
     * @throws Horde_Exception
     */
    public function resetPassword($userId)
    {
        throw new Horde_Exception('unsupported');
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
     * Returns the URI of the login screen for the current authentication
     * method.
     *
     * @param string $app  The application to use.
     * @param string $url  The URL to redirect to after login.
     *
     * @return string  The login screen URI.
     */
    public function getLoginScreen($app = 'horde', $url = '')
    {
        $login = Horde::url($GLOBALS['registry']->get('webroot', $app) . '/login.php', true);
        if (!empty($url)) {
            $login = Horde_Util::addParameter($login, 'url', $url);
        }
        return $login;
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
     * Driver-level admin check stub.
     *
     * @todo
     *
     * @return boolean  False.
     */
    public function isAdmin($permission = null, $permlevel = null, $user = null)
    {
        return false;
    }

}
