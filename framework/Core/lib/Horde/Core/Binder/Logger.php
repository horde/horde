<?php
class Horde_Core_Binder_Logger implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        global $conf;

        /* Default handler. */
        if (empty($conf['log']['enabled'])) {
            return new Horde_Log_Logger(new Horde_Log_Handler_Null());
        }

        switch ($conf['log']['type']) {
        case 'file':
        case 'stream':
            $append = ($conf['log']['type'] == 'file')
                ? ($conf['log']['params']['append'] ? 'a+' : 'w+')
                : null;

            switch ($conf['log']['params']['format']) {
            case 'custom':
                $formatter = new Horde_Log_Formatter_Xml(array('format' => $conf['log']['params']['template']));
                break;

            case 'default':
                // Use Horde_Log defaults.
                $formatter = null;
                break;

            case 'xml':
                $formatter = new Horde_Log_Formatter_Xml();
                break;
            }

            try {
                $handler = new Horde_Log_Handler_Stream($conf['log']['name'], $append, $formatter);
            } catch (Horde_Log_Exception $e) {
                return new Horde_Log_Logger(new Horde_Log_Handler_Null());
            }
            break;

        case 'syslog':
            try {
                $handler = new Horde_Log_Handler_Syslog();
            } catch (Horde_Log_Exception $e) {
                return new Horde_Log_Logger(new Horde_Log_Handler_Null());
            }
            break;

        case 'null':
        default:
            // Use default null handler.
            return new Horde_Log_Logger(new Horde_Log_Handler_Null());
            break;
        }

        if (!is_string($conf['log']['priority'])) {
            $conf['log']['priority'] = 'NOTICE';
        }
        $handler->addFilter(constant('Horde_Log::' . $conf['log']['priority']));

        /* Horde_Core_Log_Logger contains code to format the log message. */
        return new Horde_Core_Log_Logger($handler);
    }

    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}
