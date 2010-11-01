<?php
/**
 * Luxor_Files:: defines an API to access source file repositories.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @since   Luxor 0.1
 * @package Luxor
 */
class Luxor_Files
{
    /**
     * Attempts to return a concrete Luxor_Files instance based on $driver.
     *
     * @param string    $driver     The type of concrete Luxor_Files subclass
     *                              to return.  The is based on the repository
     *                              driver ($driver).  The code is dynamically
     *                              included.
     * @param array     $params     (optional) A hash containing any additional
     *                              configuration or connection parameters a
     *                              subclass might need.
     *
     * @return mixed    The newly created concrete Luxor_Files instance, or
     *                  false on an error.
     */
    function factory($driver, $params = array())
    {
        $driver = basename($driver);
        $class = 'Luxor_Files_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        } else {
            return false;
        }
    }
}
