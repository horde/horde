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
     * Log queue.
     *
     * @var array
     */
    protected static $_queue;

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
            try {
                $handler->setOption('ident', $conf['log']['ident']);
            } catch (Horde_Log_Exception $e) {
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

        switch ($conf['log']['priority']) {
        case 'WARNING':
            // Bug #12109
            $priority = 'WARN';
            break;

        default:
            $priority = defined('Horde_Log::' . $conf['log']['priority'])
                ? $conf['log']['priority']
                : 'NOTICE';
            break;
        }
        $handler->addFilter(constant('Horde_Log::' . $priority));

        try {
            /* Horde_Core_Log_Logger contains code to format the log
             * message. */
            $ob = new Horde_Core_Log_Logger($handler);
            self::processQueue($ob);
            return $ob;
        } catch (Horde_Log_Exception $e) {
            $this->error = $e;
            return new Horde_Core_Log_Logger(new Horde_Log_Handler_Null());
        }
    }

    /**
     * Is the logger available?
     *
     * @return boolean  True if logging is available.
     */
    public static function available()
    {
        return (isset($GLOBALS['registry']) && $GLOBALS['registry']->hordeInit);
    }

    /**
     * Queue log entries to output once the framework is initialized.
     */
    public static function queue(Horde_Core_Log_Object $ob)
    {
        if (!isset(self::$_queue)) {
            self::$_queue = array();
            register_shutdown_function(array(__CLASS__, 'processQueue'));
        }

        self::$_queue[] = $ob;
    }

    /**
     * Process the log queue.
     */
    public static function processQueue($logger = null)
    {
        if (empty(self::$_queue) || !self::available()) {
            return;
        }

        if (is_null($logger)) {
            $logger = $GLOBALS['injector']->getInstance('Horde_Log_Logger');
        }

        foreach (self::$_queue as $val) {
            $logger->logObject($val);
        }

        self::$_queue = array();
    }

}
