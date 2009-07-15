<?php
/**
 * The Horde_Auth_Composite class provides a way to combine two separate
 * drivers for admin vs. authentication purposes.
 *
 * Required parameters:
 * <pre>
 * 'admin_driver' - (string) TODO
 * 'admin_driver_config' - (array) TODO
 * 'auth_driver' - (string) TODO
 * 'auth_driver_config' - (string) TODO
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
class Horde_Auth_Composite extends Horde_Auth_Base
{
    /**
     * Hash containing any instantiated drivers.
     *
     * @var array
     */
    protected $_drivers = array();

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
        $driver = $this->_loadDriver('auth');
        return $driver->authenticate($userId, $credentials, false);
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
            $driver = $this->_loadDriver('admin');
            return $driver->hasCapability($capability);
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
    protected function _transparent()
    {
        try {
            $driver = $this->_loadDriver('auth');
            return $driver->transparent();
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
        $driver = $this->_loadDriver('admin');
        $driver->addUser($userId, $credentials);
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
        $driver = $this->_loadDriver('admin');
        $driver->updateUser($oldID, $newID, $credentials);
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
        $driver = $this->_loadDriver('admin');
        $driver->removeUser($userId);
    }

    /**
     * List all users in the system.
     *
     * @return array  The array of userIds.
     * @throws Horde_Auth_Exception
     */
    public function listUsers()
    {
        $driver = $this->_loadDriver('admin');
        return $driver->listUsers();
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
            $driver = $this->_loadDriver('admin');
            return $driver->exists($userId);
        } catch (Horde_Auth_Exception $e) {
            return false;
        }
    }

    /**
     * Loads one of the drivers in our configuration array, if it isn't already
     * loaded.
     *
     * @param string $driver  The name of the driver to load.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _loadDriver($driver)
    {
        if (empty($this->_drivers[$driver])) {
            $this->_drivers[$driver] = Horde_Auth::singleton($this->_params[$driver . '_driver'], $this->_params[$driver . '_driver_config']);
        }

        return $this->_drivers[$driver];
    }

}
