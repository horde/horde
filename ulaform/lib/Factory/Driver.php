<?php
/**
 * Horde_Injector factory to create Ulaform_Driver instances.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @author  Vilius Šumskas <vilius@lnk.lt>
 * @package Ulaform
 */
class Ulaform_Factory_Driver
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
     * Return the Ulaform_Driver:: instance.
     *
     * @param mixed $name  Instance name
     *
     * @return Ulaform_Driver
     * @throws Ulaform_Exception
     */
    public function create($name = '')
    {
        if (!isset($this->_instances[$name])) {
            $driver = 'Sql';
            $params = Horde::getDriverConfig('sql', $driver);

            $class = 'Ulaform_Driver_' . $driver;
            if (!class_exists($class)) {
                throw new Ulaform_Exception(sprintf('Unable to load the definition of %s.', $class));
            }

            $params = array(
                'db' => $this->_injector->getInstance('Horde_Db_Adapter'),
                'charset' => $params['charset'],
            );

            $driver = new $class($params);
            $this->_instances[$name] = $driver;
        }

        return $this->_instances[$name];
    }
}
