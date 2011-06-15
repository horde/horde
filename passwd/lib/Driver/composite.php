<?php
/**
 * The composite class chains other drivers together to change and a user's
 * password stored on various backends.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Max Kalika <max@horde.org>
 * @since   Passwd 3.0
 * @package Passwd
 */
class Passwd_Driver_composite extends Passwd_Driver {

    /**
     * Hash of instantiated drivers.
     *
     * @var array
     */
    protected $_drivers = array();

    /**
     * State of the loaded drivers.
     *
     * @var boolean
     */
    protected $_loaded = false;

    /**
     * Constructs a new Passwd_Driver_composite object.
     *
     * @param array $params  A hash containing chained drivers and their parameters.
     */
    function __construct($params = array())
    {
        if (!isset($params['drivers']) || !is_array($params['drivers'])) {
            throw new Passwd_Exception(_("Required 'drivers' is misconfigured in Composite configuration."));
        }

        parent::__construct($params);
    }

    /**
     * Instantiate configured drivers.
     *
     * @return  boolean   True on success or PEAR_Error on failure.
     */
    function _loadDrivers()
    {
        if ($this->_loaded) {
            return true;
        }

        foreach ($this->_params['drivers'] as $key => $settings) {
            if (!array_key_exists($key, $this->_drivers)) {
                $settings['is_subdriver'] = true;
                try {
                    $res = $GLOBALS['injector']->getInstance('Passwd_Factory_Driver')->create($key, $settings);
                }
                catch (Passwd_Error $e) {
                    $notification->push(_("Password module is not properly configured"),
                            'horde.error');
                            break;
                    throw new Passwd_Error(sprintf(_("%s: unable to load sub driver: %s"),
                                                     $key, $e->getMessage()));
                }

                $this->_drivers[$key] = $res;
            }
        }

        $this->_loaded = true;
        return true;
    }

    /**
     * Change the user's password.
     *
     * @param string $username      The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @return boolean  True or false based on success of the change.
     */
    function changePassword($username,  $old_password, $new_password)
    {
        global $notification;

        $res = $this->_loadDrivers();

        foreach ($this->_drivers as $key => $driver) {
            if (isset($driver->_params['be_username'])) {
                $user = $driver->_params['be_username'];
            } else {
                $user = $username;
            }
            try {
                return $driver->changePassword($user, $old_password,  $new_password);
            } catch (Passwd_Exception $e) {
                $notification->push($e->getMessage(), 'horde.warning');
                throw new Passwd_Exception(sprintf(_("Failure in changing password for %s: %s"),
                                                $this->_params['drivers'][$key]['name'],
                                                $e->getMessage()), 'horde.error');
            }
        }

        return true;
    }

}
