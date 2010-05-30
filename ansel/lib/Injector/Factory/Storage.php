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
     *
     * @var array
     */
    private $_instances = array();

   /**
    * @var Horde_Injector
    */
    private $_injector;

    /**
     *
     * @param Horde_Injector $injector
     *
     * @return Horde_Injector_Factory_Storage
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Obtain a Ansel_Storage object for the requested scope.
     *
     * @param string $scope  The application scope
     *
     * @return Ansel_Storage
     */
    public function getScope($scope = 'ansel')
    {
        if (!isset($this->_instances[$scope])) {
            $this->_instances[$scope] = new Ansel_Storage($this->_injector->getInstance('Horde_Share')->getScope($scope, 'Sql_Hierarchical'));
        }
       
       return $this->_instances[$scope];
    }

}