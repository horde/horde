<?php
/**
 * @category Horde
 * @package Core
 */
class Horde_Core_Factory_ActiveSyncBackend extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        global $conf, $registry;

        // Backend driver and dependencies
        $params = array('registry' => $registry);
        $adapter_params = array('factory' => new Horde_Core_ActiveSync_Imap_Factory());
        $driver_params = array(
            'connector' => new Horde_Core_ActiveSync_Connector($params),
            'imap' => !empty($conf['activesync']['emailsync'])
                ? new Horde_ActiveSync_Imap_Adapter($adapter_params)
                : null,
            'ping' => $conf['activesync']['ping'],
            'state' => $injector->getInstance('Horde_ActiveSyncState'),
            'auth' => $injector->getInstance('Horde_Core_Factory_Auth')->create());

        return new Horde_Core_ActiveSync_Driver($driver_params);
    }

}
