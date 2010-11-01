<?php
/**
 * Pastie_Driver:: defines an API for implementing storage backends for
 * Pastie.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Pastie
 */
class Pastie_Driver
{
    /**
     * Attempts to return a concrete instance based on $driver.
     *
     * @param string $driver  The type of the concrete subclass to return.
     *                        The class name is based on the storage driver
     *                        ($driver).  The code is dynamically included.
     *
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     *
     * @return Pastie_Driver  The newly created concrete instance.
     * @throws Horde_Exception
     */
    static public function factory($driver = null, $params = null)
    {
        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['params']['driver'];
        }

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        $driver = ucfirst(basename($driver));
        $class = 'Pastie_Driver_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Exception('Could not find driver ' . $class);
    }

    /**
     * Attempts to return a reference to a concrete Pastie_Driver instance based
     * on $driver.
     *
     * It will only create a new instance if no Pastie_Driver instance with the
     * same parameters currently exists.
     *
     * This should be used if multiple storage sources are required.
     *
     * This method must be invoked as: $var = &Pastie_Driver::singleton()
     *
     * @param string    $notepad    The name of the current notepad.
     *
     * @param string    $driver     The type of concrete Pastie_Driver subclass
     *                              to return.  The is based on the storage
     *                              driver ($driver).  The code is dynamically
     *                              included.
     *
     * @param array     $params     (optional) A hash containing any additional
     *                              configuration or connection parameters a
     *                              subclass might need.
     *
     * @return mixed    The created concrete Pastie_Driver instance, or false
     *                  on error.
     */
    function &singleton($bin = '', $driver = null, $params = null)
    {
        static $instances = array();
        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        $signature = serialize(array($notepad, $driver, $params));
        if (!isset($instances[$signature])) {
            $instances[$signature] = &Pastie_Driver::factory($notepad, $driver, $params);
        }

        return $instances[$signature];
    }

}
