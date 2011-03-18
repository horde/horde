<?php
/**
 * Factory class for creating Horde_Imsp_Auth objects.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 * @package Horde_Imsp
 */
class Horde_Imsp_Auth_Factory
{
    static protected $_instances = array();

    /**
     * Attempts to return a concrete Horde_Imsp_Auth instance based on $driver
     * Must be called as &Horde_Imsp_Auth::factory()
     *
     * @param  string $driver Type of Horde_Imsp_Auth subclass to return.
     *
     * @return mixed  The created Horde_Imsp_Auth subclass.
     * @throws Horde_Exception
     */
    static protected function _factory($driver)
    {
        $driver = basename($driver);

        if (empty($driver) || (strcmp($driver, 'none') == 0)) {
            return new Horde_Imsp_Auth();
        }
        $class = 'Horde_Imsp_Auth_' . $driver;
        if (class_exists($class)) {
            return new $class();
        }

        throw new Horde_Exception(sprintf(Horde_Imsp_Translation::t("Unable to load the definition of %s."), $class));
    }

    /**
     * Attempts to return a concrete Horde_Imsp_Auth instance based on $driver.
     * Will only create a new object if one with the same parameters already
     * does not exist.
     * Must be called like: $var = &Horde_Imsp_Auth::singleton('driver_type');
     *
     * @param  string $driver Type of IMSP_Auth subclass to return.
     *
     * @return object Reference to IMSP_Auth subclass.
     */
    static public function create($driver)
    {
        /* Check for any imtest driver instances and kill them.
           Otherwise, the socket will hang between requests from
           seperate drivers (an Auth request and an Options request).*/
        if (is_array(self::$_instances)) {
            foreach (self::$_instances as $obj) {
                if ($obj->getDriverType() == 'imtest') {
                    $obj->logout();
                }
            }
        }
        $signature = serialize(array($driver));
        if (!isset(self::$_instances[$signature])) {
            self::$_instances[$signature] = self::_factory($driver);
        }

        return self::$_instances[$signature];
    }

}