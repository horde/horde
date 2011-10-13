<?php
/**
 * This is the base Driver class for the Sesha application.
 *
 * Copyright 2004-2007 Andrew Coleman <mercury@appisolutions.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Andrew Coleman <mercury@appisolutions.net>
 * @package Sesha
 */
class Sesha_Driver
{
    /**
     * Variable holding the items in the inventory.
     *
     * @var array
     * @access private
     */
    var $_stock;

    /**
     * Hash containing connection parameters.
     *
     * @var array
     * @access private
     */
    var $_params;

    /**
     * Basic constructor for the Sesha_Driver base class.
     *
     * @param array $params  An array of parameters to pass to the driver.
     */
    function Sesha_Driver($params = array())
    {
        $this->_params = $params;
        $this->_stock = array();
    }

    /**
     * Attempts to return a concrete Sesha_Driver instance based on $driver.
     *
     * @param string $driver  The type of concrete Sesha_Driver subclass to
     *                        return.
     *
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return mixed  The newly created concrete Sesha_Driver instance, or
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
        $class = 'Sesha_Driver_' . $driver;
        if (class_exists($class)) {
            $sesha = &new $class($params);
        } else {
            $sesha = false;
        }
        return $sesha;
    }

}
