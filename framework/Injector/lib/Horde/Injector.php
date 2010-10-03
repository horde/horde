<?php
/**
 * Injector class for injecting dependencies of objects
 *
 * This class is responsible for injecting dependencies of objects.  It is
 * inspired by the bucket_Container's concept of child scopes, but written to
 * support many different types of bindings as well as allowing for setter
 * injection bindings.
 *
 * @author   Bob Mckee <bmckee@bywires.com>
 * @author   James Pepin <james@jamespepin.com>
 * @category Horde
 * @package  Injector
 */
class Horde_Injector implements Horde_Injector_Scope
{
    /**
     * @var Horde_Injector_Scope
     */
    private $_parentInjector;

    /**
     * @var array
     */
    private $_bindings = array();

    /**
     * @var array
     */
    private $_instances;

    /**
     * Create a new injector object.
     *
     * Every injector object has a parent scope.  For the very first
     * Horde_Injector, you should pass it a Horde_Injector_TopLevel object.
     *
     * @param Horde_Injector_Scope $injector  The parent scope.
     */
    public function __construct(Horde_Injector_Scope $injector)
    {
        $this->_parentInjector = $injector;
        $this->_instances = array(__CLASS__ => $this);
    }

    /**
     * Create a child injector that inherits this injector's scope.
     *
     * All child injectors inherit the parent scope.  Any objects that were
     * created using getInstance, will be available to the child container.
     * The child container can set bindings to override the parent, and none
     * of those bindings will leak to the parent.
     *
     * @return Horde_Injector  A child injector with $this as its parent.
     */
    public function createChildInjector()
    {
        return new self($this);
    }

    /**
     * Method overloader.  Handles $this->bind[BinderType] type calls.
     *
     * @return Horde_Injector_Binder  See _bind().
     * @throws BadMethodCallException
     */
    public function __call($name, $args)
    {
        if (substr($name, 0, 4) == 'bind') {
            return $this->_bind(substr($name, 4), $args);
        }

        throw new BadMethodCallException('Call to undefined method ' . __CLASS__ . '::' . $name . '()');
    }

    /**
     * Method that creates binders to send to addBinder(). This is called by
     * the magic method __call() whenever a function is called that starts
     * with bind.
     *
     * @param string $type  The type of Horde_Injector_Binder_ to be created.
     *                      Matches /^Horde_Injector_Binder_(\w+)$/.
     * @param array $args   The constructor arguments for the binder object.
     *
     * @return Horde_Injector_Binder  The binder object created. Useful for
     *                                method chaining.
     * @throws BadMethodCallException
     */
    private function _bind($type, $args)
    {
        $interface = array_shift($args);

        if (!$interface) {
            throw new BadMethodCallException('First parameter for "bind' . $type . '" must be the name of an interface or class');
        }

        $reflectionClass = new ReflectionClass('Horde_Injector_Binder_' . $type);

        $this->_addBinder(
            $interface,
            $reflectionClass->getConstructor()
                ? $reflectionClass->newInstanceArgs($args)
                : $reflectionClass->newInstance()
        );

        return $this->_getBinder($interface);
    }

    /**
     * Add a Horde_Injector_Binder to an interface
     *
     * This is the method by which we bind an interface to a concrete
     * implentation or factory.  For convenience, binders may be added by
     * $this->bind[BinderType].
     *
     * bindFactory - Creates a Horde_Injector_Binder_Factory
     * bindImplementation - Creates a Horde_Injector_Binder_Implementation
     *
     * All subsequent arguments are passed to the constructor of the
     * Horde_Injector_Binder object.
     *
     * @param string $interface              The interface to bind to.
     * @param Horde_Injector_Binder $binder  The binder to be bound to the
     *                                       specified $interface.
     *
     * @return Horde_Injector  A reference to itself for method chaining.
     */
    public function addBinder($interface, Horde_Injector_Binder $binder)
    {
        $this->_addBinder($interface, $binder);
        return $this;
    }

    /**
     * @see self::addBinder()
     */
    private function _addBinder($interface, Horde_Injector_Binder $binder)
    {
        // First we check to see if our parent already has an equal binder set.
        // if so we don't need to do anything
        if (!$binder->equals($this->_parentInjector->getBinder($interface))) {
            $this->_bindings[$interface] = $binder;
        }
    }

    /**
     * Get the Binder associated with the specified instance.
     *
     * Binders are objects responsible for binding a particular interface
     * with a class. If no binding is set for this object, the parent scope is
     * consulted.
     *
     * @param string $interface  The interface to retrieve binding information
     *                           for.
     *
     * @return Horde_Injector_Binder  The binding set for the specified
     *                                interface.
     */
    public function getBinder($interface)
    {
        return isset($this->_bindings[$interface])
            ? $this->_bindings[$interface]
            : $this->_parentInjector->getBinder($interface);
    }

    /**
     * Get the Binder associated with the specified instance.
     *
     * @param string $interface  The interface to retrieve binding information
     *                           for.
     *
     * @return Horde_Injector_Binder  The binder object created. Useful for
     *                                method chaining.
     */
    private function _getBinder($interface)
    {
        return $this->_bindings[$interface];
    }

    /**
     * Set the object instance to be retrieved by getInstance the next time
     * the specified interface is requested.
     *
     * This method allows you to set the cached object instance so that all
     * subsequent getInstance() calls return the object you have specified.
     *
     * @param string $interface  The interface to bind the instance to.
     * @param mixed $instance    The object instance to be bound to the
     *                           specified instance.
     *
     * @return Horde_Injector  A reference to itself for method chaining.
     */
    public function setInstance($interface, $instance)
    {
        $this->_instances[$interface] = $instance;
        return $this;
    }

    /**
     * Create a new instance of the specified object/interface.
     *
     * This method creates a new instance of the specified object/interface.
     * NOTE: it does not save that instance for later retrieval. If your
     * object should be re-used elsewhere, you should be using getInstance().
     *
     * @param string $interface  The interface name, or object class to be
     *                           created.
     *
     * @return mixed  A new object that implements $interface.
     */
    public function createInstance($interface)
    {
        return $this->getBinder($interface)->create($this);
    }

    /**
     * Retrieve an instance of the specified object/interface.
     *
     * This method gets you an instance, and saves a reference to that
     * instance for later requests.
     *
     * Interfaces must be bound to a concrete class to be created this way.
     * Concrete instances may be created through reflection.
     *
     * It does not gaurantee that it is a new instance of the object.  For a
     * new instance see createInstance().
     *
     * @param string $interface  The interface name, or object class to be
     *                           created.
     *
     * @return mixed  An object that implements $interface, but not
     *                necessarily a new one.
     */
    public function getInstance($interface)
    {
        // Do we have an instance?
        if (!isset($this->_instances[$interface])) {
            // Do we have a binding for this interface? If so then we don't
            // ask our parent
            if (!isset($this->_bindings[$interface])) {
                // Does our parent have an instance?
                if ($instance = $this->_parentInjector->getInstance($interface)) {
                    return $instance;
                }
            }

            // We have to make our own instance
            $this->setInstance($interface, $this->createInstance($interface));
        }

        return $this->_instances[$interface];
    }

}
