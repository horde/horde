<?php
/**
 * A binder object for binding an interface to a factory class and method.
 *
 * An interface may be bound to a factory class.  That factory class must
 * provide a method or methods that accept a Horde_Injector, and return an
 * object that satisfies the instance requirement. For example:
 *
 * <pre>
 * class MyFactory {
 *   ...
 *   public function create(Horde_Injector $injector)
 *   {
 *     return new MyClass($injector->getInstance('Collaborator'), new MyOtherClass(17));
 *   }
 *   ...
 * }
 * </pre>
 *
 * @author   Bob Mckee <bmckee@bywires.com>
 * @author   James Pepin <james@jamespepin.com>
 * @category Horde
 * @package  Injector
 */
class Horde_Injector_Binder_Factory implements Horde_Injector_Binder
{
    /**
     * TODO
     *
     * @var string
     */
    private $_factory;

    /**
     * TODO
     *
     * @var string
     */
    private $_method;

    /**
     * Create a new Horde_Injector_Binder_Factory instance.
     *
     * @param string $factory  The factory class to use for creating objects.
     * @param string $method   The method on that factory to use for creating
     *                         objects.
     */
    public function __construct($factory, $method)
    {
        $this->_factory = $factory;
        $this->_method = $method;
    }

    /**
     * TODO
     *
     * @param Horde_Injector_Binder $otherBinder  TODO
     *
     * @return boolean  Equality.
     */
    public function equals(Horde_Injector_Binder $otherBinder)
    {
        return (($otherBinder instanceof Horde_Injector_Binder_Factory) &&
                ($otherBinder->getFactory() == $this->_factory) &&
                ($otherBinder->getMethod() == $this->_method));
    }

    /**
     * Get the factory classname that this binder was bound to.
     *
     * @return string  The factory classname this binder is bound to.
     */
    public function getFactory()
    {
        return $this->_factory;
    }

    /**
     * Get the method that this binder was bound to.
     *
     * @return string  The method this binder is bound to.
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * Create instance using a factory method
     *
     * If the factory depends on a Horde_Injector we want to limit its scope
     * so it cannot change anything that effects any higher-level scope.  A
     * factory should not have the responsibility of making a higher-level
     * scope change.
     * To enforce this we create a new child Horde_Injector.  When a
     * Horde_Injector is requested from a Horde_Injector it will return
     * itself. This means that the factory will only ever be able to work on
     * the child Horde_Injector we give it now.
     *
     * @param Horde_Injector $injector  Injector object.
     *
     * @return TODO
     */
    public function create(Horde_Injector $injector)
    {
        $childInjector = $injector->createChildInjector();

        /* We use getInstance() here because we don't want to have to create
         * this factory more than one time to create more objects of this
         * type. */
        return $childInjector->getInstance($this->_factory)->{$this->_method}($childInjector);
    }

}
