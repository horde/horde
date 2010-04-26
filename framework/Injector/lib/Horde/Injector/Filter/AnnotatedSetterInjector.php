<?php
/**
 * Filter that finds variables marked with @inject and injects them into an
 * object.
 *
 * @author   Bob Mckee <bmckee@bywires.com>
 * @author   James Pepin <james@jamespepin.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Horde_Injector
 */
class Horde_Injector_Filter_AnnotatedSetterInjector implements Horde_Injector_Filter
{
    /**
     * @var Horde_Injector_DependencyFinder
     */
    protected $_dependencyFinder;

    /**
     * Inspect the object's class docblock for @inject annotations, and fill
     * those objects in through their setter methods.
     *
     * @param object $instance  The object to do setter injection on.
     */
    public function filter(Horde_Injector $injector, $instance)
    {
        $reflectionClass = new ReflectionClass($instance);
        $setters = $this->_findSetters($reflectionClass);
        $this->_callSetters($injector, $instance, $setters);
    }

    /**
     * Find annotated setters in the class docblock
     *
     * @param ReflectionClass $reflectionClass
     *
     * @return array
     */
    private function _findSetters(ReflectionClass $reflectionClass)
    {
        $setters = array();
        $docBlock = $reflectionClass->getDocComment();
        if ($docBlock) {
            if (preg_match_all('/@inject (\w+)/', $docBlock, $matches)) {
                foreach ($matches[1] as $setter) {
                    $setters[] = $setter;
                }
            }
        }

        return $setters;
    }

    /**
     * TODO
     */
    private function _callSetters(Horde_Injector $injector, $instance, $setters)
    {
        foreach ($setters as $setter) {
            $reflectionMethod = new ReflectionMethod($instance, $setter);
            $reflectionMethod->invokeArgs(
                $instance,
                $injector->getMethodDependencies($reflectionMethod)
            );
        }
    }
}
