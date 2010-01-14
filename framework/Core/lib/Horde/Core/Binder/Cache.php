<?php
class Horde_Core_Binder_Cache implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        $driver = $GLOBALS['conf']['cache']['driver'];
        $params = Horde::getDriverConfig('cache', $driver);

        if (is_array($driver)) {
            list($app, $driver_name) = $driver;
            $driver = basename($driver_name);
        } else {
            $driver = basename($driver);
        }

        if (empty($driver) || $driver == 'none') {
            return new Horde_Cache_Null($params);
        }

        $class = (empty($app) ? 'Horde' : $app) . '_Cache_' . ucfirst($driver);
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Exception('Class definition of ' . $class . ' not found.');
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
