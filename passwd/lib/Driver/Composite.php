<?php
/**
 * The composite class chains other drivers together to change and a user's
 * password stored on various backends.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Max Kalika <max@horde.org>
 * @package Passwd
 */
class Passwd_Driver_Composite extends Passwd_Driver
{
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
     * Constructor.
     *
     * @param array $params  A hash containing chained drivers and their
     *                       parameters.
     *
     * @throws Passwd_Exception
     */
    public function __construct($params = array())
    {
        if (!isset($params['drivers']) || !is_array($params['drivers'])) {
            throw new Passwd_Exception(_("Required 'drivers' is misconfigured in Composite configuration."));
        }

        parent::__construct($params);
    }

    /**
     * Instantiate configured drivers.
     */
    protected function _loadDrivers()
    {
        if ($this->_loaded) {
            return;
        }

        foreach ($this->_params['drivers'] as $key => $settings) {
            if (isset($this->_drivers[$key])) {
                continue;
            }
            $settings['is_subdriver'] = true;
            try {
                $res = $GLOBALS['injector']
                    ->getInstance('Passwd_Factory_Driver')
                    ->create($key, $settings);
            } catch (Passwd_Exception $e) {
                throw new Passwd_Exception(
                    sprintf(_("%s: unable to load sub driver: %s"),
                            $key, $e->getMessage()));
            }

            $this->_drivers[$key] = $res;
        }

        $this->_loaded = true;
    }

    /**
     * Changes the user's password.
     *
     * @param string $username      The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @throws Passwd_Exception
     */
    public function changePassword($username,  $old_password, $new_password)
    {
        $this->_loadDrivers();

        foreach ($this->_drivers as $key => $driver) {
            if (isset($driver->_params['be_username'])) {
                $user = $driver->_params['be_username'];
            } else {
                $user = $username;
            }
            try {
                $driver->changePassword($user, $old_password,  $new_password);
            } catch (Passwd_Exception $e) {
                throw new Passwd_Exception(
                    sprintf(_("Failure in changing password for %s: %s"),
                            $this->_params['drivers'][$key]['name'],
                            $e->getMessage()));
            }
        }
    }
}
