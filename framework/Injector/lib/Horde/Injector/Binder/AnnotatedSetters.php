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
 * @package  Injector
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

    /**
     * Constructor.
     *
     * @param Horde_Injector_Binder $binder            TODO
     * @param Horde_Injector_DependencyFinder $finder  TODO
     *
     */
    public function __construct(Horde_Injector_Binder $binder,
                                Horde_Injector_DependencyFinder $finder = null)
    {
        $this->_binder = $binder;
        $this->_dependencyFinder = is_null($finder)
            ? new Horde_Injector_DependencyFinder()
            : $finder;
    }

    /**
     * TODO
     *
     * @param Horde_Injector_Binder $binder  TODO
     *
     * @return boolean  Equality.
     */
    public function equals(Horde_Injector_Binder $otherBinder)
    {
        return ($otherBinder instanceof Horde_Injector_Binder_AnnotatedSetters) &&
            $this->getBinder()->equals($otherBinder->getBinder());
    }

    /**
     * TODO
     *
     * @return Horde_Injector_Binder  TODO
     */
    public function getBinder()
    {
        return $this->_binder;
    }

    /**
     * TODO
     */
    public function create(Horde_Injector $injector)
    {
        $instance = $this->_binder->create($injector);

        $reflectionClass = new ReflectionClass(get_class($instance));
        $setters = $this->_findAnnotatedSetters($reflectionClass);
        $this->_callSetters($setters, $injector, $instance);

        return $instance;
    }

    /**
     * Find all public methods in $reflectionClass that are annotated with
     * @inject.
     *
     * @param ReflectionClass $reflectionClass  TODO
     *
     * @return array  TODO
     */
    private function _findAnnotatedSetters(ReflectionClass $reflectionClass)
    {
        $setters = array();
        foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            if ($this->_isSetterMethod($reflectionMethod)) {
                $setters[] = $reflectionMethod;
            }
        }

        return $setters;
    }

    /**
     * Is a method a setter method, by the criteria we define (has a doc
     * comment that includes @inject).
     *
     * @param ReflectionMethod $reflectionMethod  TODO
     */
    private function _isSetterMethod(ReflectionMethod $reflectionMethod)
    {
        $docBlock = $reflectionMethod->getDocComment();
        if ($docBlock) {
            if (strpos($docBlock, '@inject') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Call each ReflectionMethod in the $setters array, filling in its
     * dependencies with the $injector.
     *
     * @param array $setters            Array of ReflectionMethods to call.
     * @param Horde_Injector $injector  The injector to get dependencies from.
     * @param object $instance          The object to call setters on.
     */
    private function _callSetters(array $setters, Horde_Injector $injector,
                                  $instance)
    {
        foreach ($setters as $setterMethod) {
            $setterMethod->invokeArgs(
                $instance,
                $this->_dependencyFinder->getMethodDependencies($injector, $setterMethod)
            );
        }
    }

}
