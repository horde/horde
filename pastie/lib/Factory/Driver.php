<?php
/**
 * Pastie_Factory_Driver:: defines a factory for Pastie storage backends.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author  Ralf Lang <lang@b1-systems.de>
 * @package Pastie
 */

class Pastie_Factory_Driver extends Horde_Core_Factory_Base
{
    private $_instances = array();

    /**
     * A Factory for the Pastie_Driver.
     * @param string name  An arbitrary name string to identify the driver instance
     * @param array params  a hash of driver parameters. For the Rdo driver, these are the parameters for creating a Horde_Db_Adapter
     * @return Horde_Rdo_Driver  A concrete instance of Horde_Rdo_Driver with all necessary dependencies injected
     */
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
            $class = 'Pastie_Driver_' . ucfirst(basename($driver));

            if (!class_exists($class)) {
                throw new Pastie_Exception(sprintf('Unable to load the definition of %s.', $class));
            }

            switch ($class) {
            case 'Pastie_Driver_Rdo':
                if (empty($params['db'])) {
                    $params['db'] = $this->_injector->getInstance('Horde_Core_Factory_Db')->create('pastie', $params);
                }
                break;
            }
            $this->_instances[$name] = new $class($params);
        }

        return $this->_instances[$name];
    }

}
