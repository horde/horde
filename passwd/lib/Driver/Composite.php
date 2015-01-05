<?php
/**
 * Copyright 2003-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2003-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */

/**
 * The composite class chains other drivers together to change and a user's
 * password stored on various backends.
 *
 * @author    Max Kalika <max@horde.org>
 * @category  Horde
 * @copyright 2003-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */
class Passwd_Driver_Composite extends Passwd_Driver
{
    /**
     * Hash of instantiated drivers.
     *
     * @var array
     */
    protected $_drivers = null;

    /**
     * @param array $params  Driver parameters:
     *   - drivers: (array) Array of Passwd_Driver objects.
     *
     * @throws Passwd_Exception
     */
    public function __construct(array $params = array())
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
        if (!is_null($this->_drivers)) {
            return;
        }

        $driver = $GLOBALS['injector']->getInstance('Passwd_Factory_Driver');

        foreach ($this->_params['drivers'] as $key => $val) {
            if (!isset($this->_drivers[$key])) {
                try {
                    $res = $driver->create($key, array_merge($val, array(
                        'is_subdriver' => true
                    )));
                } catch (Passwd_Exception $e) {
                    throw new Passwd_Exception(sprintf(_("%s: unable to load sub driver: %s"), $key, $e->getMessage()));
                }

                $this->_drivers[$key] = $res;
            }
        }
    }

    /**
     */
    protected function _changePassword($user, $oldpass, $newpass)
    {
        $this->_loadDrivers();

        foreach ($this->_drivers as $key => $driver) {
            try {
                $driver->changePassword($user, $oldpass,  $newpass);
            } catch (Passwd_Exception $e) {
                throw new Passwd_Exception(sprintf(_("Failure in changing password for %s: %s"), $this->_params['drivers'][$key]['name'], $e->getMessage()));
            }
        }
    }

}
