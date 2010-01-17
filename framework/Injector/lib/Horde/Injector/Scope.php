<?php
/**
 * Interface for injector scopes
 *
 * Injectors implement a Chain of Responsibility pattern.  This is the required
 * interface for injectors to pass on responsibility to parent objects in the chain.
 *
 * @category Horde
 * @package Horde_Injector
 */
interface Horde_Injector_Scope
{
    /**
     * Returns the Horde_Injector_Binder object mapped to the request interface if such a
     * mapping exists
     *
     * @return Horde_Injector_Binder|null
     * @param string $interface interface name of object whose binding if being retrieved
     */
    public function getBinder($interface);

    /**
     * Returns instance of requested object if proper configuration has been provided.
     *
     * @return Object
     * @param string $interface interface name of object which is being requested
     */
    public function getInstance($interface);
}
