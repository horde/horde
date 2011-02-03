<?php
/**
 * @category Horde
 * @package  Core
 */
class Horde_Core_Factory_Logger extends Horde_Core_Factory_Injector
{
    /**
     * Stores the exception if the logger could not be started.
     *
     * @var Horde_Log_Exception
     */
    public $error;

    /**
     */
    public function create(Horde_Injector $injector)
    {
        global $conf;

        $this->error = null;

        /* Default handler. */
        if (empty($conf['log']['enabled'])) {
            return new Horde_Core_Log_Logger(new Horde_Log_Handler_Null());
        }

        switch ($conf['log']['type']) {
        case 'file':
        case 'stream':
            $append = ($conf['log']['type'] == 'file')
                ? ($conf['log']['params']['append'] ? 'a+' : 'w+')
                : null;
            $format = isset($conf['log']['params']['format'])
                ? $conf['log']['params']['format']
                : 'default';

            switch ($format) {
            case 'custom':
                $formatter = new Horde_Log_Formatter_Xml(array('format' => $conf['log']['params']['template']));
                break;

            case 'default':
            default:
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
                $this->error = $e;
                return new Horde_Core_Log_Logger(new Horde_Log_Handler_Null());
            }
            break;

        case 'syslog':
            try {
                $handler = new Horde_Log_Handler_Syslog();
                if (!empty($conf['log']['name'])) {
                    $handler->setOption('facility', $conf['log']['name']);
                }
                if (!empty($conf['log']['ident'])) {
                    $handler->setOption('ident', $conf['log']['ident']);
                }
            } catch (Horde_Log_Exception $e) {
                $this->error = $e;
                return new Horde_Core_Log_Logger(new Horde_Log_Handler_Null());
            }
            break;

        case 'null':
        default:
            // Use default null handler.
            return new Horde_Core_Log_Logger(new Horde_Log_Handler_Null());
        }

        if (!defined('Horde_Log::' . $conf['log']['priority'])) {
            $conf['log']['priority'] = 'NOTICE';
        }
        $handler->addFilter(constant('Horde_Log::' . $conf['log']['priority']));

        try {
            /* Horde_Core_Log_Logger contains code to format the log
             * message. */
            return new Horde_Core_Log_Logger($handler);
        } catch (Horde_Log_Exception $e) {
            $this->error = $e;
            return new Horde_Core_Log_Logger(new Horde_Log_Handler_Null());
        }
    }

}
