<?php
/**
 * Shout_Driver:: defines an API for implementing storage backends for Shout.
 *
 * $Id$
 *
 * Copyright 2005-2009 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @version $Revision: 76 $
 * @since   Shout 0.1
 * @package Shout
 */

class Shout_Driver {

    /**
     * Hash containing connection parameters.
     *
     * @var array $_params
     */
    var $_params = array();

    function Shout_Driver($params = array())
    {
        $this->_params = $params;
    }

    /**
    * Get a list of contexts from the instantiated driver and filter
    * the returned contexts for those which the current user can see/edit
    *
    * @param optional string $filter Filter for types of contexts to return.
    *                                One of "system" "customer" or "all"
    *
    * @param optional string $filterperms Filter contexts for given permissions
    *
    * @return array Contexts valid for this user
    *
    * @access public
    */
    function getContexts($filters = "all", $filterperms = null)
    {
        throw new Shout_Exception("This function is not implemented.");
    }

    /**
     * For the given context and type, make sure the context has the
     * appropriate properties, that it is effectively of that "type"
     *
     * @param string $context the context to check type for
     *
     * @param string $type the type to verify the context is of
     *
     * @return boolean true of the context is of type, false if not
     *
     * @access public
     */
    function checkContextType($context, $type)
    {
        throw new Shout_Exception("This function is not implemented.");
    }

    /**
     * Get a list of users valid for the current context.  Return an array
     * indexed by the extension.
     *
     * @param string $context Context for which users should be returned
     *
     * @return array User information indexed by voice mailbox number
     */
    function getUsers($context)
    {
        throw new Shout_Exception("This function is not implemented.");
    }

    /**
     * Returns the name of the user's default context
     *
     * @return string User's default context
     */
    function getHomeContext()
    {
        throw new Shout_Exception("This function is not implemented.");
    }

    /**
     * Get a context's properties
     *
     * @param string $context Context to get properties for
     *
     * @return integer Bitfield of properties valid for this context
     */
    function getContextProperties($context)
    {
        throw new Shout_Exception("This function is not implemented.");
    }

    /**
     * Get a context's extensions and return as a multi-dimensional associative
     * array
     *
     * @param string $context Context to return extensions for
     *
     * @return array Multi-dimensional associative array of extensions data
     *
     */
    function getDialplan($context)
    {
        throw new Shout_Exception("This function is not implemented.");
    }

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
            $shout = new $class($params);
            return $shout;
        } else {
            return false;
        }
    }

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
