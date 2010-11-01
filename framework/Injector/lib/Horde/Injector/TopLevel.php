<?php
/**
 * Top level injector class for returning the default binding for an object
 *
 * This class returns a Horde_Injector_Binder_Implementation with the requested
 * $interface mapped to itself.  This is the default case, and for conrete
 * classes should work all the time so long as you constructor parameters are
 * typed.
 *
 * @author   Bob Mckee <bmckee@bywires.com>
 * @author   James Pepin <james@jamespepin.com>
 * @category Horde
 * @package  Injector
 */
class Horde_Injector_TopLevel implements Horde_Injector_Scope
{
    /**
     * Get an Implementation Binder that maps the $interface to itself
     *
     * @param string $interface  The interface to retrieve binding information
     *                           for.
     *
     * @return Horde_Injector_Binder_ImplementationWithSetters
     *          A new binding object that maps the interface to itself, with
     *          setter injection.
     */
    public function getBinder($interface)
    {
        $dependencyFinder = new Horde_Injector_DependencyFinder();
        $implementationBinder = new Horde_Injector_Binder_Implementation($interface, $dependencyFinder);

        return new Horde_Injector_Binder_AnnotatedSetters($implementationBinder, $dependencyFinder);
    }

    /**
     * Always return null.  Object doesn't keep instance references
     *
     * Method is necessary because this object is the default parent Injector.
     * The child of this injector will ask it for instances in the case where
     * no bindings are set on the child.  This should always return null.
     *
     * @param string $interface The interface in question
     * @return null
     */
    public function getInstance($interface)
    {
        return null;
    }

}
