<?php
/**
 * @category Horde
 * @package Core
 */
class Horde_Core_Factory_ActiveSyncState extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        return new Horde_ActiveSync_State_Sql(array(
            'db' => $injector->getInstance('Horde_Db_Adapter')
        ));
    }

}
