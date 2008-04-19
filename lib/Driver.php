<?php
/**
 * Operator_Driver:: defines an API for implementing storage backends for
 * Operator.
 *
 * $Horde: incubator/operator/lib/Driver.php,v 1.1 2008/04/19 01:26:06 bklang Exp $
 *
 * Copyright 2007-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Your Name <you@example.com>
 * @package Operator
 */
class Operator_Driver {

    /**
     * Array holding the current foo list. Each array entry is a hash
     * describing a foo. The array is indexed by the IDs.
     *
     * @var array
     */
    var $_foos = array();

    /**
     * Lists all foos.
     *
     * @return array  Returns a list of all foos.
     */
    function listFoos()
    {
        return $this->_foos;
    }

    /**
     * Attempts to return a concrete Operator_Driver instance based on $driver.
     *
     * @param string $driver  The type of the concrete Operator_Driver subclass
     *                        to return.  The class name is based on the
     *                        storage driver ($driver).  The code is
     *                        dynamically included.
     *
     * @param array $params   A hash containing any additional configuration
     *                        or connection parameters a subclass might need.
     *
     * @return Operator_Driver  The newly created concrete Operator_Driver
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

        $class = 'Operator_Driver_' . $driver;
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
