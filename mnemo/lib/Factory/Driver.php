<?php
/**
 * Horde_Injector factory to create Mnemo_Driver instances.
 *
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Mnemo
 */
class Mnemo_Factory_Driver
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector  The injector to use.
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Return the Mnemo_Driver:: instance.
     *
     * @param mixed $name  The notepad to open
     *
     * @return Mnemo_Driver
     * @throws Mnemo_Exception
     */
    public function create($name = '')
    {
        if (!isset($this->_instances[$name])) {
            $driver = $GLOBALS['conf']['storage']['driver'];
            $params = Horde::getDriverConfig('storage', $driver);
            $class = 'Mnemo_Driver_' . ucfirst(basename($driver));
            if (!class_exists($class)) {
                throw new Mnemo_Exception(sprintf('Unable to load the definition of %s.', $class));
            }

            switch ($class) {
            case 'Mnemo_Driver_Sql':
                if ($params['driverconfig'] != 'Horde') {
                    $customParams = $params;
                    unset($customParams['driverconfig'], $customParams['table']);
                    $params['db'] = $this->_injector->getInstance('Horde_Core_Factory_Db')->create('mnemo', $customParams);
                } else {
                    $params['db'] = $this->_injector->getInstance('Horde_Db_Adapter');
                }
                break;

            case 'Mnemo_Driver_Kolab':
                $params = array(
                    'storage' => $this->_injector->getInstance('Horde_Kolab_Storage')
                );
            }
            $driver = new $class($name, $params);
            $this->_instances[$name] = $driver;
        }

        return $this->_instances[$name];
    }
}
