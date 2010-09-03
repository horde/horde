<?php
/**
 * TODO
 *
 * @author   Bob Mckee <bmckee@bywires.com>
 * @author   James Pepin <james@jamespepin.com>
 * @category Horde
 * @package  Injector
 */
class Horde_Injector_Binder_Implementation implements Horde_Injector_Binder
{
    /**
     * TODO
     */
    private $_implementation;

    /**
     * @var Horde_Injector_DependencyFinder
     */
    private $_dependencyFinder;

    /**
     * TODO
     */
    public function __construct($implementation,
                                Horde_Injector_DependencyFinder $finder = null)
    {
        $this->_implementation = $implementation;
        $this->_dependencyFinder = is_null($finder)
            ? new Horde_Injector_DependencyFinder()
            : $finder;
    }

    /**
     * TODO
     *
     * @return TODO
     */
    public function getImplementation()
    {
        return $this->_implementation;
    }

    /**
     * TODO
     *
     * @return boolean  Equality.
     */
    public function equals(Horde_Injector_Binder $otherBinder)
    {
        return (($otherBinder instanceof Horde_Injector_Binder_Implementation) &&
                ($otherBinder->getImplementation() == $this->_implementation));
    }

    /**
     * TODO
     */
    public function create(Horde_Injector $injector)
    {
        $reflectionClass = new ReflectionClass($this->_implementation);
        $this->_validateImplementation($reflectionClass);
        return $this->_getInstance($injector, $reflectionClass);
    }

    /**
     * TODO
     */
    protected function _validateImplementation(ReflectionClass $reflectionClass)
    {
        if ($reflectionClass->isAbstract() || $reflectionClass->isInterface()) {
            throw new Horde_Injector_Exception('Cannot bind interfaces or abstract classes "' . $this->_implementation . '" to an interface.');
        }
    }

    /**
     * TODO
     */
    protected function _getInstance(Horde_Injector $injector,
                                    ReflectionClass $class)
    {
        return $class->getConstructor()
            ? $class->newInstanceArgs($this->_dependencyFinder->getMethodDependencies($injector, $class->getConstructor()))
            : $class->newInstance();
    }

}
