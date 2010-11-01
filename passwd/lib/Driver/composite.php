<?php
/**
 * The composite class chains other drivers together to change and a user's
 * password stored on various backends.
 *
 * $Horde: passwd/lib/Driver/composite.php,v 1.7.2.7 2009/01/06 15:25:23 jan Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
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
    var $_drivers = array();

    /**
     * State of the loaded drivers.
     *
     * @var boolean
     */
    var $_loaded = false;

    /**
     * Constructs a new Passwd_Driver_composite object.
     *
     * @param array $params  A hash containing chained drivers and their parameters.
     */
    function Passwd_Driver_composite($params = array())
    {
        if (!isset($params['drivers']) || !is_array($params['drivers'])) {
            return PEAR::raiseError(_("Required 'drivers' is misconfigured in Composite configuration."));
        }

        parent::Passwd_Driver($params);
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
                $res = Passwd_Driver::factory($settings['driver'],
                                              $settings['params']);
                if (is_a($res, 'PEAR_Error')) {
                    return PEAR::raiseError(sprintf(_("%s: unable to load driver: %s"),
                                                    $key, $res->getMessage()));
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
        if (is_a($res, 'PEAR_Error')) {
            return $res;
        }

        foreach ($this->_drivers as $key => $driver) {
            if (isset($driver->_params['be_username'])) {
                $user = $driver->_params['be_username'];
            } else {
                $user = $username;
            }
            $res = $driver->changePassword($user, $old_password,
                                           $new_password);
            if (is_a($res, 'PEAR_Error')) {
                $res = PEAR::raiseError(sprintf(_("Failure in changing password for %s: %s"),
                                                $this->_params['drivers'][$key]['name'],
                                                $res->getMessage()), 'horde.error');
                if (!empty($this->_params['drivers'][$key]['required'])) {
                    return $res;
                } else {
                    $notification->push($res, 'horde.warning');
                }
            }
        }

        return true;
    }

}
