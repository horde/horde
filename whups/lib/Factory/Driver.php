<?php
/**
 * Horde_Injector based factory for Kronolith_Driver
 */
class Whups_Factory_Driver extends Horde_Core_Factory_Base
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Return the driver instance.
     *
     * @param string $driver  The storage backend to use
     * @param array $params   Driver params
     *
     * @return Kronolith_Driver
     * @throws Kronolith_Exception
     */
    public function create($driver = null, $params = array())
    {
        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['tickets']['driver'];
        }
        if (!empty($this->_instances[$driver])) {
            return $this->_instances[$driver];
        }
        $driver = basename($driver);
        $class = 'Whups_Driver_' . $driver;
        if (class_exists($class)) {
            if (is_null($params)) {
                $params = Horde::getDriverConfig('tickets', $driver);
            }
            $this->_instances[$driver] = new $class($params);
            switch ($driver) {
            case 'Sql':
                $this->_instances[$driver]
                    ->setStorage($GLOBALS['injector']->getInstance('Horde_Core_Factory_Db')->create('whups', 'tickets'));
            }

            return $this->_instances[$driver];
        } else {
            throw new Whups_Exception(sprintf('No such backend "%s" found', $driver));
        }
    }

}
