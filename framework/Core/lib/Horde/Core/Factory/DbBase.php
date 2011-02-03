<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_DbBase extends Horde_Core_Factory_Injector
{
    /**
     * Returns the Horde_Db_Adapter object for the default Horde DB/SQL
     * configuration.
     *
     * @return Horde_Db_Adapter
     * @throws Horde_Exception
     */
    public function create(Horde_Injector $injector)
    {
        return $injector->getInstance('Horde_Core_Factory_Db')->create('horde');
    }
}
