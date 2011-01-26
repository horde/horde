<?php
/**
 * Factory for Ansel_Storage.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org)
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Ansel
 */
class Ansel_Factory_Storage
{
    /**
     * Array of already instantiated instances
     *
     * @var array
     */
    private $_instances = array();

    /**
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Constructor
     *
     * @param Horde_Injector $injector
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Return an Ansel_Storage instance scoped for the current Ansel scope.
     * Scope is determined by the current value of Ansel_Config::scope
     *
     * @return Ansel_Storage
     */
    public function create()
    {
        $scope = $this->_injector->getInstance('Ansel_Config')->get('scope');
        if (empty($this->_instances[$scope])) {
            $this->_instances[$scope] = new Ansel_Storage($this->_injector->getInstance('Horde_Core_Factory_Share')->create($scope, 'Sql'));
        }

        return $this->_instances[$scope];
    }

}
