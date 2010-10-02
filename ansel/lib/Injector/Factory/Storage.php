<?php
/**
 * Ansel_Storage:: factory
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Ansel
 */
class Ansel_Injector_Factory_Storage
{
    /**
     * Depends on the Ansel_Scope value being available to the injector.
     * @var array
     */
    private $_instances = array();

    /**
     *
     * @var Horde_Injector
     */
    private $_injector;

    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Return an Ansel_Storage instance scoped for the current Ansel scope
     *
     * @param string $scope  The application scope
     *
     * @return Ansel_Storage
     */
    public function getScope()
    {
        $scope = $this->_injector->getInstance('Ansel_Config')->get('scope');
        if (empty($this->_instances[$scope])) {
            $this->_instances[$scope] = new Ansel_Storage($this->_injector->getInstance('Horde_Share_Factory')->getScope($scope, 'Sql_Hierarchical'));
        }

       return $this->_instances[$scope];
    }

}