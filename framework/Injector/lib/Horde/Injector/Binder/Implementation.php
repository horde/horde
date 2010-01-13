<?php
/**
 *
 * @author Bob Mckee <bmckee@bywires.com>
 * @author James Pepin <james@jamespepin.com>
 * @category Horde
 * @package Horde_Injector
 */
class Horde_Injector_Binder_Implementation implements Horde_Injector_Binder
{
    private $_implementation;
    private $_setters;

    public function __construct($implementation)
    {
        $this->_implementation = $implementation;
        $this->_setters = array();
    }

    public function getImplementation()
    {
        return $this->_implementation;
    }

    public function bindSetter($method)
    {
        $this->_setters[] = $method;
        return $this;
    }

    public function equals(Horde_Injector_Binder $otherBinder)
    {
        if (!$otherBinder instanceof Horde_Injector_Binder_Implementation) {
            return false;
        }

        if ($otherBinder->getImplementation() != $this->_implementation) {
            return false;
        }

        return true;
    }

    public function create(Horde_Injector $injector)
    {
        $reflectionClass = new ReflectionClass($this->_implementation);
        $this->_validateImplementation($reflectionClass);
        $instance = $this->_getInstance($injector, $reflectionClass);
        $this->_callSetters($injector, $instance);
        return $instance;
    }

    private function _validateImplementation(ReflectionClass $reflectionClass)
    {
        if ($reflectionClass->isAbstract() || $reflectionClass->isInterface()) {
            throw new Horde_Injector_Exception('Cannot bind interfaces or abstract classes "' .
                $this->_implementation . '" to an interface.');
        }
    }

    private function _getInstance(Horde_Injector $injector, ReflectionClass $class)
    {
        if ($class->getConstructor()) {
            return $class->newInstanceArgs(
                $this->_getMethodDependencies($injector, $class->getConstructor())
            );
        }
        return $class->newInstance();
    }

    private function _getMethodDependencies(Horde_Injector $injector, ReflectionMethod $method)
    {
        $dependencies = array();
        foreach ($method->getParameters() as $parameter) {
            $dependencies[] = $this->_getParameterDependency($injector, $parameter);
        }
        return $dependencies;
    }

    private function _getParameterDependency(Horde_Injector $injector, ReflectionParameter $parameter)
    {
        if ($parameter->getClass()) {
            $dependency = $injector->getInstance($parameter->getClass()->getName());
        } elseif ($parameter->isOptional()) {
            $dependency = $parameter->getDefaultValue();
        } else {
            throw new Horde_Injector_Exception('Unable to instantiate class "' . $this->_implementation .
                '" because a value could not be determined untyped parameter "$' .
                $parameter->getName() . '"');
        }
        return $dependency;
    }

    private function _callSetters(Horde_Injector $injector, $instance)
    {
        foreach ($this->_setters as $setter) {
            $reflectionMethod = new ReflectionMethod($instance, $setter);
            $reflectionMethod->invokeArgs(
                $instance,
                $this->_getMethodDependencies($injector, $reflectionMethod)
            );
        }
    }
}
