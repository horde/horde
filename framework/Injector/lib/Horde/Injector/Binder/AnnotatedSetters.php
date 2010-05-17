<?php
/**
 * This is a binder that finds methods marked with @inject and calls them with
 * their dependencies. It must be stacked on another binder that actually
 * creates the instance.
 *
 * @author   Bob Mckee <bmckee@bywires.com>
 * @author   James Pepin <james@jamespepin.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Horde_Injector
 */
class Horde_Injector_Binder_AnnotatedSetters implements Horde_Injector_Binder
{
    /**
     * @var Horde_Injector_Binder
     */
    private $_binder;

    /**
     * @var Horde_Injector_DependencyFinder
     */
    private $_dependencyFinder;

    private $_setters = array();

    /**
     *
     */
    public function __construct(Horde_Injector_Binder $binder, Horde_Injector_DependencyFinder $dependencyFinder)
    {
        $this->_binder = $binder;
        $this->_dependencyFinder = $dependencyFinder;
    }

    /**
     * TODO
     */
    public function equals(Horde_Injector_Binder $otherBinder)
    {
        return false;
    }

    /**
     * TODO
     */
    public function create(Horde_Injector $injector)
    {
        $instance = $this->_binder->create($injector);

        $reflectionClass = new ReflectionClass(get_class($instance));
        $this->_bindAnnotatedSetters($reflectionClass);
        $this->_callSetters($injector, $instance);

        return $instance;
    }

    /**
     */
    private function _bindAnnotatedSetters(ReflectionClass $reflectionClass)
    {
        foreach ($this->_findAnnotatedSetters($reflectionClass) as $setter) {
            $this->_setters[] = $setter;
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
    private function _findAnnotatedSetters(ReflectionClass $reflectionClass)
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

    /**
     * TODO
     */
    protected function _callSetters(Horde_Injector $injector, $instance)
    {
        foreach ($this->_setters as $setter) {
            $reflectionMethod = new ReflectionMethod($instance, $setter);
            $reflectionMethod->invokeArgs(
                $instance,
                $this->_dependencyFinder->getMethodDependencies($injector, $reflectionMethod)
            );
        }
    }
}
