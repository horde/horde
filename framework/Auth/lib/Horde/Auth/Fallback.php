<?php
/**
 * Copyright 2016-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, http://www.horde.org/licenses/lgpl21
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package  Auth
 */

/**
 * The Horde_Auth_Fallback class provides a way to combine two separate
 * drivers for failover and legacy support cases, for example
 * using ldap, but falling back to a local database
 * To the user, this driver presents the combined, unique users of both backends
 * Only the primary driver allows adding, editing and removing users.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package  Auth
 */
class Horde_Auth_Fallback extends Horde_Auth_Base
{
    /**
     * Constructor.
     *
     * @param array $params  Required parameters:
     * <pre>
     * 'primary_driver' - (Horde_Auth_Base) The primary driver.
     * 'fallback_driver' - (Horde_Auth_Base) The auth driver.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        foreach (array('primary_driver', 'fallback_driver') as $val) {
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
        if (!$this->_params['primary_driver']->authenticate($userId, $credentials) && !$this->_params['fallback_driver']->authenticate($userId, $credentials)) {
            throw new Horde_Auth_Exception($this->_params['primary_driver']->getError(true), $this->_params['primary_driver']->getError());
        }
    }

    /**
     * Query the primary Auth object to find out if it supports the given
     * capability.
     *
     * @param string $capability  The capability to test for.
     *
     * @return boolean  Whether or not the capability is supported.
     */
    public function hasCapability($capability)
    {
        try {
            return $this->_params['primary_driver']->hasCapability($capability);
        } catch (Horde_Auth_Exception $e) {
            return false;
        }
    }

    /**
     * Automatic authentication.
     *
     * @return boolean  Whether or not the client is allowed.
     */
    public function transparent()
    {
        try {
            return $this->_params['primary_driver']->transparent() || $this->_params['fallback_driver']->transparent();
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
        $this->_params['primary_driver']->addUser($userId, $credentials);
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
        $this->_params['primary_driver']->updateUser($oldID, $newID, $credentials);
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
        return $this->_params['primary_driver']->resetPassword($userId);
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
        $this->_params['primary_driver']->removeUser($userId);
    }

    /**
     * Lists all users in the system.
     *
     * @param boolean $sort  Sort the users?
     *
     * @return array  The array of userIds.
     * @throws Horde_Auth_Exception
     */
    public function listUsers($sort = false)
    {
        $res = array_unique(array_merge($this->_params['primary_driver']->listUsers($sort), $this->_params['fallback_driver']->listUsers($sort)));
        return ($sort) ? sort($res) : $res;
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
            return $this->_params['primary_driver']->exists($userId) || $this->_params['fallback_driver']->exists($userId);
        } catch (Horde_Auth_Exception $e) {
            return false;
        }
    }

}
