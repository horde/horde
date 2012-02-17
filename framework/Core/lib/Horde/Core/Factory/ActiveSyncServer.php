<?php
/**
 * @category Horde
 * @package Core
 */
class Horde_Core_Factory_ActiveSyncServer extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        global $conf;
        $logger = false;

        // Logger
        if ($conf['activesync']['logging']['type'] == 'custom') {
            // See if we can get a device id
            $request = $GLOBALS['injector']->getInstance('Horde_Controller_Request');
            $get = $request->getGetVars();
            if (!empty($get['DeviceId'])) {
                $stream = @fopen(dirname($conf['activesync']['logging']['path']) . '/' . $get['DeviceId'] . '.txt', 'a');
            }
            if (!$stream) {
                $stream = @fopen($conf['activesync']['logging']['path'], 'a');
            }
            if ($stream) {
                $logger = new Horde_Log_Logger(new Horde_Log_Handler_Stream($stream));
            }
        }
        if (!$logger) {
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
