<?php
/**
 * A caching factory for Horde_Rdo_Mapper descendants.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Test
 * @author   Ralf Lang <lang@b1-systems.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */


/**
 * The Horde_Rdo_Factory is a caching root object for Horde_Rdo_Mapper instances
 * This should itself be injected into applications by an injector
 *
 * @category Horde
 * @package Rdo
 */

class Horde_Rdo_Factory
{

    /**
     * The list of already loaded Horde_Rdo_Mapper classes
     * @var array
     */
    protected $_mappers = array();

    /**
     * The database connection to pass to the Horde_Rdo_Mapper classes
     * @var Horde_Db_Adapter
     */

    protected $_adapter;


    public function __construct(Horde_Db_Adapter $adapter)
    {
        $this->_adapter = $adapter;
    }


    public function count()
    {
        return count($this->_mappers);
    }

    public function create($class, Horde_Db_Adapter $adapter = null)
    {
        if (!empty($this->_mappers[$class])) {
            return $this->_mappers[$class];
        }
        if (!class_exists($class)) {
            throw new Horde_Rdo_Exception(sprintf('Class %s not found', $class));
        }
        if (!$adapter) {
            $adapter = $this->_adapter;
        }
        $this->_mappers[$class] = new $class($adapter);
        return $this->_mappers[$class]->setFactory($this);
    }
}
