<?php
/**
 * The Horde_Auth_Base:: class provides a common abstracted interface to
 * creating various authentication backends.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, http://www.horde.org/licenses/lgpl21
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license http://www.horde.org/licenses/lgpl21 LGPL-2.1
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
        'update'        => false,
        'badlogincount' => false,
        'lock'          => false,
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
     * History object.
     *
     * @var Horde_History
     */
    protected $_history_api;

    /**
     * Lock object.
     *
     * @var Horde_Lock
     */
    protected $_lock_api;

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
     *     - default_user:      (string) The default user.
     *     - logger:            (Horde_Log_Logger, optional) A logger object.
     *     - lock_api:          (Horde_Lock, optional) A locking object.
     *     - history_api:       (Horde_History, optional) A history object.
     *     - login_block_count: (integer, optional) How many failed logins
     *                          trigger autoblocking? 0 disables the feature.
     *     - login_block_time:  (integer, options) How many minutes should
     *                          autoblocking last? 0 means no expiration.
     */
    public function __construct(array $params = array())
    {
        if (isset($params['logger'])) {
            $this->_logger = $params['logger'];
            unset($params['logger']);
        }

        if (isset($params['lock_api'])) {
            $this->_lock_api = $params['lock_api'];
            $this->_capabilities['lock'] = true;
            unset($params['lock_api']);
        }

        if (isset($params['history_api'])) {
            $this->_history_api = $params['history_api'];
            $this->_capabilities['badlogincount'] = true;
            unset($params['history_api']);
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
            if (($this->hasCapability('lock')) &&
                $this->isLocked($userId)) {
                $details = $this->isLocked($userId, true);
                if ($details['lock_timeout'] == Horde_Lock::PERMANENT) {
                    $message = Horde_Auth_Translation::t("Your account has been permanently locked");
                } else {
                    $message = sprintf(Horde_Auth_Translation::t("Your account has been locked for %d minutes"), ceil(($details['lock_timeout'] - time()) / 60));
                }
                throw new Horde_Auth_Exception($message, Horde_Auth::REASON_LOCKED);
            }
            $this->_authenticate($userId, $credentials);
            $this->setCredential('userId', $this->_credentials['userId']);
            $this->setCredential('credentials', $credentials);
            if ($this->hasCapability('badlogincount')) {
                $this->_resetBadLogins($userId);
            }
            return true;
        } catch (Horde_Auth_Exception $e) {
            if (($code = $e->getCode()) &&
                $code != Horde_Auth::REASON_MESSAGE) {
                if (($code == Horde_Auth::REASON_BADLOGIN) &&
                    $this->hasCapability('badlogincount')) {
                    $this->_badLogin($userId);
                }
                $this->setError($code, $e->getMessage());
            } else {
                $this->setError(Horde_Auth::REASON_MESSAGE, $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Basic sort implementation.
     *
     * If the backend has listUsers and doesn't have a native sorting option,
     * fall back to this method.
     *
     * @param array   $users  An array of usernames.
     * @param boolean $sort   Whether to sort or not.
     *
     * @return array the users, sorted or not
     *
     */
    protected function _sort($users, $sort)
    {
        if ($sort) {
            sort($users);
        }
        return $users;
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
     * Locks a user indefinitely or for a specified time.
     *
     * @since Horde_Auth 1.2.0
     *
     * @param string $userId  The user to lock.
     * @param integer $time   The duration in minutes, 0 = permanent.
     *
     * @throws Horde_Auth_Exception
     */
    public function lockUser($userId, $time = 0)
    {
        if (!$this->_lock_api) {
            throw new Horde_Auth_Exception('Unsupported.');
        }

        if ($time == 0) {
            $time = Horde_Lock::PERMANENT;
        } else {
            $time *= 60;
        }

        try {
            if ($this->_lock_api->setLock($userId, 'horde_auth', 'login:' . $userId, $time, Horde_Lock::TYPE_EXCLUSIVE)) {
                return;
            }
        } catch (Horde_Lock_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }

        throw new Horde_Auth_Exception('User is already locked',
                                       Horde_Auth::REASON_LOCKED);
    }

    /**
     * Unlocks a user and optionally resets the bad login count.
     *
     * @since Horde_Auth 1.2.0
     *
     * @param string  $userId          The user to unlock.
     * @param boolean $resetBadLogins  Reset bad login counter?
     *
     * @throws Horde_Auth_Exception
     */
    public function unlockUser($userId, $resetBadLogins = false)
    {
        if (!$this->_lock_api) {
            throw new Horde_Auth_Exception('Unsupported.');
        }

        try {
            $locks = $this->_lock_api->getLocks(
                'horde_auth', 'login:' . $userId, Horde_Lock::TYPE_EXCLUSIVE);
            $lock_id = key($locks);
            if ($lock_id) {
                $this->_lock_api->clearLock($lock_id);
            }
            if ($resetBadLogins) {
                $this->_resetBadLogins($userId);
            }
        } catch (Horde_Lock_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
    }

    /**
     * Returns whether a user is currently locked.
     *
     * @since Horde_Auth 1.2.0
     *
     * @param string $userId         The user to check.
     * @param boolean $show_details  Return timeout too?
     *
     * @return boolean|array  If $show_details is a true, an array with
     *                        'locked' and 'lock_timeout' values. Whether the
     *                        user is locked, otherwise.
     * @throws Horde_Auth_Exception
     */
    public function isLocked($userId, $show_details = false)
    {
        if (!$this->_lock_api) {
            throw new Horde_Auth_Exception('Unsupported.');
        }

        try  {
            $locks = $this->_lock_api->getLocks(
                'horde_auth', 'login:' . $userId, Horde_Lock::TYPE_EXCLUSIVE);
        } catch (Horde_Lock_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }

        if ($show_details) {
            $lock_id = key($locks);
            return empty($lock_id)
                ? array('locked' => false, 'lock_timeout' => 0)
                : array('locked' => true, 'lock_timeout' => $locks[$lock_id]['lock_expiry_timestamp']);
        }

        return !empty($locks);
    }

    /**
     * Handles a bad login.
     *
     * @since Horde_Auth 1.2.0
     *
     * @param string $userId  The user with a bad login.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _badLogin($userId)
    {
        if (!$this->_history_api) {
            throw new Horde_Auth_Exception('Unsupported.');
        }

        $history_identifier = $userId . '@logins.failed';
        try {
            $this->_history_api->log(
                $history_identifier,
                array('action' => 'login_failed', 'who' => $userId));
            $history_log = $this->_history_api->getHistory($history_identifier);
            if ($this->_params['login_block_count'] > 0 &&
                $this->_params['login_block_count'] <= $history_log->count() &&
                $this->hasCapability('lock')) {
                $this->lockUser($userId, $this->_params['login_block_time']);
            }
        } catch (Horde_History_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
    }

    /**
     * Resets the bad login counter.
     *
     * @since Horde_Auth 1.2.0
     *
     * @param string $userId  The user to reset.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _resetBadLogins($userId)
    {
        if (!$this->_history_api) {
            throw new Horde_Auth_Exception('Unsupported.');
        }

        try {
            $this->_history_api->removeByNames(array($userId . '@logins.failed'));
        } catch (Horde_History_Exception $e) {
            throw new Horde_Auth_Exception($e);
        }
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
    public function listUsers($sort = false)
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
