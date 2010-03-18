<?php
class Horde_Core_Binder_Logger implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        global $conf;

        // Try to make sure that we can log messages somehow.
        if (empty($conf['log']['enabled'])) {
            $handler = new Horde_Log_Handler_Null();
        } else {
            switch ($conf['log']['type']) {
            case 'file':
                $handler = new Horde_Log_Handler_Stream($conf['log']['name'], $conf['log']['params']['append'] ? 'a+' : 'w+');
                break;

            case 'stream':
                $handler = new Horde_Log_Handler_Stream($conf['log']['name']);
                break;

            case 'syslog':
                $handler = new Horde_Log_Handler_Syslog();
                break;

            case 'null':
            default:
                $handler = new Horde_Log_Handler_Null();
                break;
            }

            if (!is_string($conf['log']['priority'])) {
                $conf['log']['priority'] = 'NOTICE';
            }
            $handler->addFilter(constant('Horde_Log::' . $conf['log']['priority']));
        }

        return new Horde_Core_Log_Logger($handler);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
