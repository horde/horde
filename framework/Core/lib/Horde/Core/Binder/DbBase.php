<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Binder_DbBase implements Horde_Injector_Binder
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
        $db = new Horde_Core_Factory_Db($injector);
        return $db->getDb('horde');
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
