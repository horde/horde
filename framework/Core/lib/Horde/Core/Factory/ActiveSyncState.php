<?php
/**
 * @category Horde
 * @package Core
 */
class Horde_Core_Factory_ActiveSyncState
{
    public function create(Horde_Injector $injector)
    {
        global $conf;

        // Backend driver and dependencies
        $state_params = $conf['activesync']['state']['params'];
        $state_params['db'] = $injector->getInstance('Horde_Db_Adapter');

        return new Horde_ActiveSync_State_History($state_params);
    }

}