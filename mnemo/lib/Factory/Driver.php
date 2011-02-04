<?php
/**
 * Horde_Injector factory to create Mnemo_Driver instances.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
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
                $params = array(
                    'db' => $this->_injector->getInstance('Horde_Db_Adapter'),
                    'table' => 'mnemo_memos',
                );
                break;
            case 'Mnemo_Driver_Kolab':
                throw new Mnemo_Exception('Kolab drivers not yet refactored.');
            }
            $driver = new $class($name, $params);
            $this->_instances[$name] = $driver;
        }

        return $this->_instances[$name];
    }

}
