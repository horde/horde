<?php
/**
 * Skoli_Driver:: defines an API for implementing storage backends for
 * Skoli.
 *
 * Copyright 2007-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Martin Blumenthal <tinu@humbapa.ch>
 * @package Skoli
 */
class Skoli_Driver {

    /**
     * String containing the current class name.
     *
     * @var string
     */
    var $_class = '';

    /**
     * An error message to throw when something is wrong.
     *
     * @var string
     */
    var $_errormsg;

    /**
     * Constructor - All real work is done by initialize().
     */
    function Skoli_Driver($errormsg = null)
    {
        if (is_null($errormsg)) {
            $this->_errormsg = _("The School backend is not currently available.");
        } else {
            $this->_errormsg = $errormsg;
        }
    }

    /**
     * Attempts to return a concrete Skoli_Driver instance based on $driver.
     *
     * @param string $class   The name of the class to load.
     *
     * @param string $driver  The type of the concrete Skoli_Driver subclass
     *                        to return.  The class name is based on the
     *                        storage driver ($driver).  The code is
     *                        dynamically included.
     *
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     *
     * @return Skoli_Driver  The newly created concrete Skoli_Driver
     *                          instance, or false on an error.
     */
    function &factory($class = '', $driver = null, $params = null)
    {
        /* Check if we have access to the given class */
        static $classes;
        if (!is_array($classes)) {
            $classes = Skoli::listClasses();
        }
        if ($class != '' && !isset($classes[$class])) {
            $class = new Skoli_Driver(sprintf(_("Access for class \"%s\" is denied"), $class));
            return $class;
        }

        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }
        $driver = basename($driver);

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        require_once dirname(__FILE__) . '/Driver/' . $driver . '.php';
        $objclass = 'Skoli_Driver_' . $driver;
        if (class_exists($objclass)) {
            $class = new $objclass($class, $params);
            $result = $class->initialize();
            if (is_a($result, 'PEAR_Error')) {
                $class = new Skoli_Driver(sprintf(_("The School backend is not currently available: %s"), $result->getMessage()));
            }
        } else {
            $class = new Skoli_Driver(sprintf(_("Unable to load the definition of %s."), $objclass));
        }

        return $class;
    }

    /**
     * Attempts to return a reference to a concrete Skoli_Driver
     * instance based on $driver. It will only create a new instance
     * if no Skoli_Driver instance with the same parameters currently
     * exists.
     *
     * This should be used if multiple storage sources are required.
     *
     * This method must be invoked as: $var = &Skoli_Driver::singleton()
     *
     * @param string    $class      The name of the class to load.
     *
     * @param string    $driver     The type of concrete Skoli_Driver subclass
     *                              to return.  The is based on the storage
     *                              driver ($driver).  The code is dynamically
     *                              included.
     *
     * @param array     $params     (optional) A hash containing any additional
     *                              configuration or connection parameters a
     *                              subclass might need.
     *
     * @return mixed    The created concrete Skoli_Driver instance, or false
     *                  on error.
     */
    function &singleton($class = '', $driver = null, $params = null)
    {
        static $instances = array();

        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        $signature = serialize(array($class, $driver, $params));
        if (!isset($instances[$signature])) {
            $instances[$signature] = &Skoli_Driver::factory($class, $driver, $params);
        }

        return $instances[$signature];
    }

}
