<?php
class Horde_Core_Binder_Logger implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        global $conf;

        /* Default handler. */
        $handler = new Horde_Log_Handler_Null();

        // Try to make sure that we can log messages somehow.
        if (!empty($conf['log']['enabled'])) {
            switch ($conf['log']['type']) {
            case 'file':
                try {
                    $handler = new Horde_Log_Handler_Stream($conf['log']['name'], $conf['log']['params']['append'] ? 'a+' : 'w+');
                } catch (Horde_Log_Exception $e) {}
                break;

            case 'stream':
                try {
                    $handler = new Horde_Log_Handler_Stream($conf['log']['name']);
                } catch (Horde_Log_Exception $e) {}
                break;

            case 'syslog':
                try {
                    $handler = new Horde_Log_Handler_Syslog();
                } catch (Horde_Log_Exception $e) {}
                break;

            // case 'null':
            // default:
            //     // Use default null handler.
            //     break;
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
