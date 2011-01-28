<?php
/**
 * Skeleton_Driver:: defines an API for implementing storage backends for
 * Skeleton.
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Your Name <you@example.com>
 * @package Skeleton
 */
class Skeleton_Driver
{
    /**
     * Array holding the current foo list. Each array entry is a hash
     * describing a foo. The array is indexed by the IDs.
     *
     * @var array
     */
    protected $_foos = array();

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
     * @return Skeleton_Driver  The newly created concrete instance.
     * @throws Skeleton_Exception
     */
    static public function factory($driver = null, $params = null)
    {
        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        $driver = ucfirst(basename($driver));
        $class = 'Skeleton_Driver_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Skeleton_Exception('Could not find driver ' . $class);
    }

    /**
     * Lists all foos.
     *
     * @return array  Returns a list of all foos.
     */
    public function listFoos()
    {
        return $this->_foos;
    }

}
