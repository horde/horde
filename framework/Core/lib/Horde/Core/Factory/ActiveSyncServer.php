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

        if (empty($conf['activesync']['logging']['level'])) {
            $level = Horde_ActiveSync_Wbxml::LOG_PROTOCOL;
        } else {
            $level = $conf['activesync']['logging']['level'];
        }

        $server = new Horde_ActiveSync(
            $injector->getInstance('Horde_ActiveSyncBackend'),
            new Horde_ActiveSync_Wbxml_Decoder(fopen('php://input', 'r'), $level),
            new Horde_ActiveSync_Wbxml_Encoder(fopen('php://output', 'w+'), $level),
            $injector->getInstance('Horde_ActiveSyncState'),
            $injector->getInstance('Horde_Controller_Request')
        );
        $server->setSupportedVersion($conf['activesync']['version']);

        // @todo Remove this BC level mapping for H6.
        if (!empty($conf['activesync']['logging']['level'])) {
            $level = ($conf['activesync']['logging']['level'] == Horde_ActiveSync_Wbxml::LOG_PROTOCOL)
                ? Horde_ActiveSync_Logger::META
                : Horde_ActiveSync_Logger::CLIENT;
        } else {
            $level = null;
        }

        $server->setLogger(new Horde_ActiveSync_Log_Factory(array(
            'type' => $conf['activesync']['logging']['type'],
            'path' => $conf['activesync']['logging']['path'],
            'level' => $level))
        );
        if (!empty($conf['openssl']['cafile'])) {
            $server->setRootCertificatePath($conf['openssl']['cafile']);
        }

        return $server;
    }

}
