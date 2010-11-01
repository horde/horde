<?php
/**
 * @category Horde
 * @package Core
 */
class Horde_Core_Factory_ActiveSyncServer
{
    public function create(Horde_Injector $injector)
    {
        global $conf;

        // Logger
        if ($conf['activesync']['logging']['type'] == 'custom') {
            $logger = new Horde_Log_Logger(new Horde_Log_Handler_Stream(fopen($conf['activesync']['logging']['path'], 'a')));
        } else {
            $logger = $injector->getInstance('Horde_Log_Logger');
        }

        $request = $injector->getInstance('Horde_Controller_Request');
        $backend = $injector->getInstance('Horde_ActiveSyncBackend');
        $server = new Horde_ActiveSync(
            $backend,
            new Horde_ActiveSync_Wbxml_Decoder(fopen('php://input', 'r')),
            new Horde_ActiveSync_Wbxml_Encoder(fopen('php://output', 'w+')),
            $request);

        $server->setLogger($logger);

        return $server;
    }

}