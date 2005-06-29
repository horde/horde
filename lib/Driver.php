<?php
/**
 * Shout_Driver:: defines an API for implementing storage backends for Shout.
 *
 * $Horde: shout/lib/Driver.php,v 0.01 2005/06/28 11:15:03 ben Exp $
 *
 * Copyright 2005 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @version $Revision$
 * @since   Shout 0.1
 * @package Shout
 */

// {{{ Shout_Driver class
class Shout_Driver {

    // {{{ Class local variables
    /**
     * Hash containing connection parameters.
     *
     * @var array $_params
     */
    var $_params = array();
    // }}}
    
    // {{{ Shout_Driver constructor
    function Shout_Driver($params = array())
    {
        $this->_params = $params;
    }
    // }}}
    
    // {{{ getContexts method
    /**
     * Get a list of contexts from the backend and filter for which contexts
     * the current user can read/write
     *
     * @return array Contexts valid for this user
     *
     * @access public
     */
    function getContexts()
    {
        return PEAR::raiseError(_("Not implemented."));
    }
    // }}}
    
    // {{{ factory method
    /**
     * Attempts to return a concrete Shout_Driver instance based on
     * $driver.
     *
     * @param string $driver  The type of the concrete Shout_Driver subclass
     *                        to return.  The class name is based on the storage
     *                        driver ($driver).  The code is dynamically
     *                        included.
     *
     * @param array  $params  (optional) A hash containing any additional
     *                        configuration or connection parameters a
     *                        subclass might need.
     *
     * @return mixed  The newly created concrete Shout_Driver instance, or
     *                false on an error.
     */
    function &factory($driver = null, $params = null)
    {
        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }

        $driver = basename($driver);

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        require_once dirname(__FILE__) . '/Driver/' . $driver . '.php';
        $class = 'Shout_Driver_' . $driver;
        if (class_exists($class)) {
            return $shout = &new $class($params);
        } else {
            return false;
        }
    }
    // }}}

    // {{{ singleton method
    /**
     * Attempts to return a reference to a concrete Shout_Driver
     * instance based on $driver. It will only create a new instance
     * if no Shout_Driver instance with the same parameters currently
     * exists.
     *
     * This method must be invoked as: $var = &Shout_Driver::singleton()
     *
     * @param string $driver  The type of concrete Shout_Driver subclass
     *                        to return.  The is based on the storage
     *                        driver ($driver).  The code is dynamically
     *                        included.
     * @param array $params   (optional) A hash containing any additional
     *                        configuration or connection parameters a
     *                        subclass might need.
     *
     * @return mixed  The created concrete Shout_Driver instance, or false
     *                on error.
     */
    function &singleton($driver = null, $params = null)
    {
        static $instances;

        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        if (!isset($instances)) {
            $instances = array();
        }

        $signature = serialize(array($driver, $params));
        if (!isset($instances[$signature])) {
            $instances[$signature] = &Shout_Driver::factory($driver, $params);
        }

        return $instances[$signature];
    }
}
// }}}