<?php
/**
 * The Horde_Auth_Base:: class provides a common abstracted interface to
 * creating various authentication backends.
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
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
        'change' => false,
        'credentials' => array(),
        'expire' => null,
        'userId' => ''
    );

    /**
     * Logger object.
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Authentication error information.
     *
     * @var array
     */
    protected $_error;

    /**
     * Constructor.
     *
     * @param array $params  Optional parameters:
     * <pre>
     * 'default_user' - (string) The default user.
     * 'logger' - (Horde_Log_Logger) A logger object.
     * </pre>
     */
    public function __construct(array $params = array())
    {
        if (isset($params['logger'])) {
            $this->_logger = $params['logger'];
            unset($params['logger']);
        }

        $params = array_merge(array(
            'default_user' => ''
        ), $params);

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
        $userId = trim($userId);

        try {
            $this->_credentials['userId'] = $userId;
            $this->_authenticate($userId, $credentials);
            $this->setCredential('userId', $this->_credentials['userId']);
            $this->setCredential('credentials', $credentials);
            return true;
        } catch (Horde_Auth_Exception $e) {
            if (($code = $e->getCode()) &&
                $code != Horde_Auth::REASON_MESSAGE) {
                $this->setError($code);
            } else {
                $this->setError(Horde_Auth::REASON_MESSAGE, $e->getMessage());
            }
            return false;
        }
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
     * Checks for triggers that may invalidate the current auth.
     * These triggers are independent of the credentials.
     *
     * @return boolean  True if the results of authenticate() are still valid.
     */
    public function validateAuth()
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
    public function transparent()
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
        if (is_null($name)) {
            return $this->_credentials;
        }

        return isset($this->_credentials[$name])
            ? $this->_credentials[$name]
            : null;
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
        switch ($type) {
        case 'change':
            $this->_credentials['change'] = (bool)$value;
            break;

        case 'credentials':
            $this->_credentials['credentials'] = array_filter(array_merge($this->_credentials['credentials'], $value));
            break;

        case 'expire':
            $this->_credentials['expire'] = intval($value);
            break;

        case 'userId':
            $this->_credentials['userId'] = strval($value);
            break;
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
        $this->_error = array(
            'msg' => $msg,
            'type' => $type
        );
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
        return isset($this->_error['type'])
            ? ($msg ? $this->_error['msg'] : $this->_error['type'])
            : false;
    }

}
