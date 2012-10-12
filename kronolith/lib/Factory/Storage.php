<?php

class Kronolith_Factory_Storage extends Horde_Core_Factory_Base
{
    /**
     * Return the driver instance.
     *
     * @return Kronolith_Storage
     * @throws Kronolith_Exception
     */
    public function create($params = array())
    {
        if (empty($params['user'])) {
            $user = $GLOBALS['registry']->getAuth();
        } else {
            $user = $params['user'];
            unset($params['user']);
        }

        if (empty($params['driver'])) {
            $driver = Horde_String::ucfirst($GLOBALS['conf']['storage']['driver']);
        } else {
            $driver = $params['driver'];
            unset($params['driver']);
        }
        $driver = basename($driver);
        $class = 'Kronolith_Storage_' . $driver;

        $driver_params =  Horde::getDriverConfig('storage', 'Sql');
        if ($driver == 'Sql') {
            if ($driver_params != 'Horde') {
                // Custom DB config
                $params['db'] = $this->_injector->getInstance('Horde_Core_Factory_Db')->create('kronolith', Horde::getDriverConfig('storage', 'Sql'));
            } else {
                // Horde default DB config
                $params['db'] = $this->_injector->getInstance('Horde_Db_Adapter');
            }
            $params['table'] = $driver_params['table'];
        }

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

}
