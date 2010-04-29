<?php
/**
 * TODO
 *
 * @author   Bob Mckee <bmckee@bywires.com>
 * @author   James Pepin <james@jamespepin.com>
 * @category Horde
 * @package  Horde_Injector
 */
class Horde_Injector_Binder_ImplementationWithSetters extends Horde_Injector_Binder_Implementation
{
    /**
     * TODO
     */
    public function create(Horde_Injector $injector)
    {
        $reflectionClass = new ReflectionClass($this->_implementation);
        $this->_validateImplementation($reflectionClass);
        $instance = $this->_getInstance($injector, $reflectionClass);
        $this->_bindAnnotatedSetters($reflectionClass);
        $this->_callSetters($injector, $instance);
        return $instance;
    }

    /**
     */
    private function _bindAnnotatedSetters(ReflectionClass $reflectionClass)
    {
        foreach ($this->_findSetters($reflectionClass) as $setter) {
            $this->bindSetter($setter);
        }
    }

    /**
     * Find all public methods in $reflectionClass that are annotated with
     * @inject.
     *
     * @param ReflectionClass $reflectionClass
     *
     * @return array
     */
    private function _findSetters(ReflectionClass $reflectionClass)
    {
        $setters = array();
        foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            $docBlock = $reflectionMethod->getDocComment();
            if ($docBlock) {
                if (strpos($docBlock, '@inject') !== false) {
                    $setters[] = $reflectionMethod->name;
                }
            }
        }

        return $setters;
    }
}
