<?php
/**
 * The Horde_Auth_Composite class provides a way to combine two separate
 * drivers for admin vs. authentication purposes.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Auth
 */
class Horde_Auth_Composite extends Horde_Auth_Base
{
    /**
     * Constructor.
     *
     * @param array $params  Required parameters:
     * <pre>
     * 'admin_driver' - (Horde_Auth_Base) The admin driver.
     * 'auth_driver' - (Horde_Auth_Base) The auth driver.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        foreach (array('admin_driver', 'auth_driver') as $val) {
            if (!isset($params[$val])) {
                throw new InvalidArgumentException('Missing ' . $val . ' parameter.');
            }
        }

        parent::__construct($params);
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
        return $this->_params['auth_driver']->authenticate($userId, $credentials);
    }

    /**
     * Query the current Auth object to find out if it supports the given
     * capability.
     *
     * @param string $capability  The capability to test for.
     *
     * @return boolean  Whether or not the capability is supported.
     */
    public function hasCapability($capability)
    {
        try {
            return $this->_params['admin_driver']->hasCapability($capability);
        } catch (Horde_Auth_Exception $e) {
            return false;
        }
    }

    /**
     * Automatic authentication: Find out if the client matches an allowed IP
     * block.
     *
     * @return boolean  Whether or not the client is allowed.
     */
    public function transparent()
    {
        try {
            return $this->_params['auth_driver']->transparent();
        } catch (Horde_Auth_Exception $e) {
            return false;
        }
    }

    /**
     * Add a set of authentication credentials.
     *
     * @param string $userId       The userId to add.
     * @param array  $credentials  The credentials to use.
     *
     * @throws Horde_Auth_Exception
     */
    public function addUser($userId, $credentials)
    {
        $this->_params['admin_driver']->addUser($userId, $credentials);
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
        $this->_params['admin_driver']->updateUser($oldID, $newID, $credentials);
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
        return $this->_params['admin_driver']->resetPassword($userId);
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
        $this->_params['admin_driver']->removeUser($userId);
    }

    /**
     * List all users in the system.
     *
     * @return array  The array of userIds.
     * @throws Horde_Auth_Exception
     */
    public function listUsers()
    {
        return $this->_params['admin_driver']->listUsers();
    }

    /**
     * Checks if a userId exists in the system.
     *
     * @param string $userId  User ID to check
     *
     * @return boolean  Whether or not the userId already exists.
     */
    public function exists($userId)
    {
        try {
            return $this->_params['admin_driver']->exists($userId);
        } catch (Horde_Auth_Exception $e) {
            return false;
        }
    }

}
