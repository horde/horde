<?php
/**
 * @category Horde
 * @package Core
 */
class Horde_Core_Factory_ActiveSyncServer extends Horde_Core_Factory_Injector
{
    public function create(Horde_Injector $injector)
    {
        $server = new Horde_ActiveSync(
            $injector->getInstance('Horde_ActiveSyncBackend'),
            new Horde_ActiveSync_Wbxml_Decoder(fopen('php://input', 'r')),
            new Horde_ActiveSync_Wbxml_Encoder(fopen('php://output', 'w+')),
            $injector->getInstance('Horde_ActiveSyncState'),
            $injector->getInstance('Horde_Controller_Request')
        );
        $server->setSupportedVersion($GLOBALS['conf']['activesync']['version']);
        $server->setLogger(new Horde_Core_ActiveSync_Logger_Factory());
        if (!empty($GLOBALS['conf']['openssl']['cafile'])) {
            $server->setRootCertificatePath($GLOBALS['conf']['openssl']['cafile']);
        }

        return $server;
    }

}
