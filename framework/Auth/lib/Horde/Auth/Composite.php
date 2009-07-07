<?php
/**
 * The Auth_composite class provides a wrapper around
 * application-provided Horde authentication which fits inside the
 * Horde Horde_Auth:: API.
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Auth
 */
class Horde_Auth_Composite extends Horde_Auth_Driver
{
    /**
     * Hash containing any instantiated drivers.
     *
     * @var array
     */
    protected $_drivers = array();

    /**
     * Return the named parameter for the current auth driver.
     *
     * @param string $param  The parameter to fetch.
     *
     * @return string  The parameter's value.
     */
    public function getParam($param)
    {
        if (($login_driver = Horde_Auth::getDriverByParam('loginscreen_switch', $this->_params)) &&
            $this->_loadDriver($login_driver)) {
            return $this->_drivers[$login_driver]->getParam($param);
        }

        return null;
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
        if (($auth_driver = Horde_Auth::getDriverByParam('loginscreen_switch', $this->_params)) &&
            $this->_loadDriver($auth_driver)) {
            $this->_drivers[$auth_driver]->authenticate($userId, $credentials);
            return;
        }

        if (($auth_driver = Horde_Auth::getDriverByParam('username_switch', $this->_params, array($userId))) &&
            $this->_loadDriver($auth_driver)) {
            $this->_drivers[$auth_driver]->hasCapability('transparent');
            return;
        }

        throw new Horde_Exception('', Horde_Auth::REASON_FAILED);
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
        switch ($capability) {
        case 'add':
        case 'update':
        case 'remove':
        case 'list':
            if (!empty($this->_params['admin_driver']) &&
                $this->_loadDriver($this->_params['admin_driver'])) {
                return $this->_drivers[$this->_params['admin_driver']]->hasCapability($capability);
            } else {
                return false;
            }
            break;

        case 'transparent':
            if (($login_driver = Horde_Auth::getDriverByParam('loginscreen_switch', $this->_params)) &&
                $this->_loadDriver($login_driver)) {
                return $this->_drivers[$login_driver]->hasCapability('transparent');
            }
            return false;
            break;

        default:
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
        if (($login_driver = Horde_Auth::getDriverByParam('loginscreen_switch', $this->_params)) &&
            $this->_loadDriver($login_driver)) {
            return $this->_drivers[$login_driver]->transparent();
        }

        return false;
    }

    /**
     * Return the URI of the login screen for this authentication object.
     *
     * @param string $app  The application to use.
     * @param string $url  The URL to redirect to after login.
     *
     * @return string  The login screen URI.
     */
    public function getLoginScreen($app = 'horde', $url = '')
    {
        if (($login_driver = Horde_Auth::getDriverByParam('loginscreen_switch', $this->_params)) &&
            $this->_loadDriver($login_driver)) {
            return $this->_drivers[$login_driver]->getLoginScreen($app, $url);
        }

        return parent::getLoginScreen($app, $url);
    }

    /**
     * Add a set of authentication credentials.
     *
     * @param string $userId       The userId to add.
     * @param array  $credentials  The credentials to use.
     *
     * @throws Horde_Exception
     */
    public function addUser($userId, $credentials)
    {
        if (!empty($this->_params['admin_driver']) &&
            $this->_loadDriver($this->_params['admin_driver'])) {
            $this->_drivers[$this->_params['admin_driver']]->addUser($userId, $credentials);
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
        if (!empty($this->_params['admin_driver']) &&
            $this->_loadDriver($this->_params['admin_driver'])) {
            $this->_drivers[$this->_params['admin_driver']]->updateUser($oldID, $newID, $credentials);
        } else {
            parent::updateUser($oldID, $newID, $credentials);
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
        if (!empty($this->_params['admin_driver']) &&
            $this->_loadDriver($this->_params['admin_driver'])) {
            $this->_drivers[$this->_params['admin_driver']]->removeUser($userId);
        } else {
            parent::removeUser($userId);
        }
    }

    /**
     * List all users in the system.
     *
     * @return array  The array of userIds.
     * @throws Horde_Exception
     */
    public function listUsers()
    {
        if (!empty($this->_params['admin_driver']) &&
            $this->_loadDriver($this->_params['admin_driver'])) {
            return $this->_drivers[$this->_params['admin_driver']]->listUsers();
        }

        return parent::listUsers();
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
        if (!empty($this->_params['admin_driver']) &&
            $this->_loadDriver($this->_params['admin_driver'])) {
            return $this->_drivers[$this->_params['admin_driver']]->exists($userId);
        }

        return parent::exists($userId);
    }

    /**
     * Loads one of the drivers in our configuration array, if it isn't already
     * loaded.
     *
     * @param string $driver  The name of the driver to load.
     *
     * @return boolean  True if driver successfully initializes.
     */
    protected function _loadDriver($driver)
    {
        if (empty($this->_drivers[$driver])) {
            // This is a bit specialized for Horde::getDriverConfig(),
            // so localize it here:
            global $conf;
            if (!empty($this->_params['drivers'][$driver]['params'])) {
                $params = $this->_params['drivers'][$driver]['params'];
                if (isset($conf[$this->_params['drivers'][$driver]['driver']])) {
                    $params = array_merge($conf[$this->_params['drivers'][$driver]['driver']], $params);
                }
            } elseif (!empty($conf[$driver])) {
                $params = $conf[$driver];
            } else {
                $params = null;
            }

            $this->_drivers[$driver] = Horde_Auth::singleton($this->_params['drivers'][$driver]['driver'], $params);
        }

        return isset($this->_drivers[$driver]);
    }

}
