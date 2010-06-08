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
 * @author  Your Name <you@example.com>
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
            $driver = $GLOBALS['conf']['storage']['driver'];
        }
        $driver = ucfirst(basename($driver));

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        $class = 'Pastie_Driver_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Exception('Could not find driver ' . $class);
    }

}
