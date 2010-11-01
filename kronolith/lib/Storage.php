<?php
/**
 * Kronolith_Storage defines an API for storing free/busy information.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Kronolith
 */
abstract class Kronolith_Storage
{
    /**
     * String containing the current username.
     *
     * @var string
     */
    protected $_user = '';

    /**
     * Attempts to return a concrete Kronolith_Storage instance based on
     * $driver.
     *
     * @param string $driver  The type of concrete Kronolith_Storage subclass
     *                        to return.  The is based on the storage driver
     *                        ($driver).  The code is dynamically included.
     * @param string $user    The name of the user who owns the free/busy
     *                        information.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return Kronolith_Storage The newly created concrete Kronolith_Storage
     *                           instance.
     * @throws Kronolith_Exception
     */
    public static function factory($user = null, $driver = null, $params = null)
    {
        if (is_null($user)) {
            $user = $GLOBALS['registry']->getAuth();
        }

        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }

        $driver = basename($driver);

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        $class = 'Kronolith_Storage_' . $driver;
        if (class_exists($class)) {
            $driver = new $class($user, $params);
        } else {
            throw new Kronolith_Exception(sprintf(_("Unable to load the definition of %s."), $class));
        }

        try {
            $driver->initialize();
        } catch (Exception $e) {
            $driver = new Kronolith_Storage($params);
        }

        return $driver;
    }

    /**
     * Stub to initiate a driver.
     * @throws Kronolith_Exception
     */
    function initialize()
    {
        return true;
    }

    /**
     * Stub to be overridden in the child class.
     */
    abstract public function search($email, $private_only = false);

    /**
     * Stub to be overridden in the child class.
     */
    abstract public function store($email, $vfb, $public = false);
}
