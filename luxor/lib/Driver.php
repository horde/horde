<?php
/**
 * Luxor_Driver:: defines an API for implementing storage backends for Luxor.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @since   Luxor 0.1
 * @package Luxor
 */
class Luxor_Driver
{
    /**
     * Attempts to return a concrete Luxor_Driver instance based on $driver.
     *
     * @param string    $driver     The type of concrete Luxor_Driver subclass
     *                              to return.  The is based on the storage
     *                              driver ($driver).  The code is dynamically
     *                              included.
     *
     * @param array     $params     (optional) A hash containing any additional
     *                              configuration or connection parameters a
     *                              subclass might need.
     *
     * @return mixed    The newly created concrete Luxor_Driver instance, or
     *                  false on an error.
     */
    function factory($source, $driver = null, $params = null)
    {
        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }

        $driver = basename($driver);

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        $class = 'Luxor_Driver_' . $driver;
        if (class_exists($class)) {
            $luxor = new $class($source, $params);
        } else {
            $luxor = false;
        }

        return $luxor;
    }
}
