<?php
/**
 * A binder object for binding an interface to a closure.
 *
 * An interface may be bound to a closure.  That closure must accept a
 * Horde_Injector and return an object that satisfies the instance
 * requirement. For example:
 *
 * <pre>
 * $injector->bindClosure('database', function($injector) { return new my_mysql(); });
 * </pre>
 *
 * @author   Bob Mckee <bmckee@bywires.com>
 * @author   James Pepin <james@jamespepin.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Injector
 */
class Horde_Injector_Binder_Closure implements Horde_Injector_Binder
{
    /**
     * TODO
     *
     * @var Horde_Injector_Binder_Closure
     */
    private $_closure;

    /**
     * Create a new Horde_Injector_Binder_Closure instance.
     *
     * @param string $closure  The closure to use for creating objects.
     */
    public function __construct($closure)
    {
        $this->_closure = $closure;
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
        return (($otherBinder instanceof Horde_Injector_Binder_Closure) &&
                ($otherBinder->getClosure() == $this->_closure));
    }

    /**
     * Get the closure that this binder was bound to.
     *
     * @return callable  The closure this binder is bound to.
     */
    public function getClosure()
    {
        return $this->_closure;
    }

    /**
     * Create instance using a closure
     *
     * If the closure depends on a Horde_Injector we want to limit its scope
     * so it cannot change anything that effects any higher-level scope.  A
     * closure should not have the responsibility of making a higher-level
     * scope change.
     * To enforce this we create a new child Horde_Injector.  When a
     * Horde_Injector is requested from a Horde_Injector it will return
     * itself. This means that the closure will only ever be able to work on
     * the child Horde_Injector we give it now.
     *
     * @param Horde_Injector $injector  Injector object.
     *
     * @return TODO
     */
    public function create(Horde_Injector $injector)
    {
        $childInjector = $injector->createChildInjector();
        $closure = $this->_closure;

        return $closure($childInjector);
    }

}
