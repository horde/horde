<?php
/**
 * @category Horde
 * @package Core
 */
class Horde_Core_Factory_ActiveSyncBackend extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        global $conf;

        $params = array();

        // Logger
        if ($conf['activesync']['logging']['type'] == 'custom') {
            $params['logger'] = new Horde_Log_Logger(new Horde_Log_Handler_Stream(fopen($conf['activesync']['logging']['path'], 'a')));
        } else {
            $params['logger'] = $injector->getInstance('Horde_Log_Logger');
        }

        // Backend driver and dependencies
        $params['registry'] = $GLOBALS['registry'];
        $driver_params = array(
            'connector' => new Horde_Core_ActiveSync_Connector($params),
            'ping' => $conf['activesync']['ping'],
            'state_basic' => $injector->getInstance('Horde_ActiveSyncState'),
            'auth' => $injector->getInstance('Horde_Core_Factory_Auth')->create());
        if ($params['provisioning'] = $conf['activesync']['securitypolicies']['provisioning']) {
            $driver_params['policies'] = $conf['activesync']['securitypolicies'];
        }

        return new Horde_Core_ActiveSync_Driver($driver_params);
    }

}
