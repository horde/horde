<?php
/**
 * Factory for Ansel_Storage.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class Ansel_Factory_Storage extends Horde_Core_Factory_Injector
{
    /**
     * Array of already instantiated instances
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Return an Ansel_Storage instance scoped for the current Ansel scope.
     * Scope is determined by the current value of Ansel_Config::scope
     *
     * @return Ansel_Storage
     */
    public function create(Horde_Injector $injector)
    {
        $scope = $injector->getInstance('Ansel_Config')->get('scope');
        if (empty($this->_instances[$scope])) {
            $this->_instances[$scope] = new Ansel_Storage($injector->getInstance('Horde_Core_Factory_Share')->create($scope));
            $this->_instances[$scope]->setStorage($injector->getInstance('Horde_Db_Adapter'));
        }

        return $this->_instances[$scope];
    }

}
