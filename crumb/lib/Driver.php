<?php
/**
 * Crumb_Driver:: defines an API for implementing storage backends for
 * Crumb.
 *
 * $Horde$
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Crumb
 */
class Crumb_Driver {


    /**
     * Lists all clients.
     *
     * @return array  Returns a list of all foos.
     */
    function listClients()
    {
        return $this->_listClients();
    }

    /**
     * Attempts to return a concrete Crumb_Driver instance based on $driver.
     *
     * @param string $driver  The type of the concrete Crumb_Driver subclass
     *                        to return.  The class name is based on the
     *                        storage driver ($driver).  The code is
     *                        dynamically included.
     *
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     *
     * @return Crumb_Driver  The newly created concrete Crumb_Driver
     *                          instance, or false on an error.
     */
    function factory($driver = null, $params = null)
    {
        if ($driver === null) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }
        $driver = basename($driver);

        if ($params === null) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        $class = 'Crumb_Driver_' . $driver;
        if (!class_exists($class)) {
            include dirname(__FILE__) . '/Driver/' . $driver . '.php';
        }
        if (class_exists($class)) {
            return new $class($params);
        } else {
            return false;
        }
    }

}
