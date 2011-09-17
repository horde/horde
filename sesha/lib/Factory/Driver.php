<?php

class Sesha_Factory_Driver extends Horde_Core_Factory_Base
{
    private $_instances = array();

    public function create($name = '', $params = array())
    {
        if (!isset($this->_instances[$name])) {
            if (!empty($params['driver'])) {
                $driver = $params['driver'];
                unset($params['driver']);
            } else {
                $driver = $GLOBALS['conf']['storage']['driver'];
                $params = Horde::getDriverConfig('storage', $driver);
            }
            $class = 'Sesha_Driver_' . ucfirst(basename($driver));

            if (!class_exists($class)) {
                throw new Sesha_Exception(sprintf('Unable to load the definition of %s.', $class));
            }

            switch ($class) {
            case 'Sesha_Driver_Sql':
                if (empty($params['db'])) {
                    $params['db'] = $this->_injector->getInstance('Horde_Core_Factory_Db')->create('sesha', $params);
                }
                break;
            }
            $this->_instances[$name] = new $class($name, $params);
        }

        return $this->_instances[$name];
    }

}
