<?php
/**
 * A factory for Kolab_Filter objects.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Filter
 */

/**
 * A factory for Kolab_Filter objects.
 *
 * Copyright 2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Filter
 */
class Horde_Kolab_Filter_Factory
{
    /**
     * Creates the logger.
     *
     * @param Horde_Injector $injector The injector provides the required
     *                                 configuration.
     *
     * @return Horde_Log_Logger The logger.
     */
    public function getLogger(Horde_Injector $injector)
    {
        $configuration = $injector->getInstance('Horde_Kolab_Filter_Configuration');

        $conf = $configuration->getConf();
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
                return new Horde_Log_Logger(new Horde_Log_Handler_Null());
            }
            try {
                $handler->setOption('ident', $conf['log']['ident']);
            } catch (Horde_Log_Exception $e) {
            }
            break;
        case 'syslog':
            try {
                $handler = new Horde_Log_Handler_Syslog();
            } catch (Horde_Log_Exception $e) {
                return new Horde_Log_Logger(new Horde_Log_Handler_Null());
            }
            break;
        case 'mock':
            $handler = new Horde_Log_Handler_Mock();
            break;
        case 'null':
        default:
            // Use default null handler.
            return new Horde_Log_Logger(new Horde_Log_Handler_Null());
            break;
        }
        if (!defined('Horde_Log::' . $conf['log']['priority'])) {
            $conf['log']['priority'] = 'NOTICE';
        }
        $handler->addFilter(constant('Horde_Log::' . $conf['log']['priority']));
        return new Horde_Log_Logger($handler);
    }

    /**
     * Creates the connection to the user database.
     *
     * @param Horde_Injector $injector The injector provides the required
     *                                 configuration.
     *
     * @return Horde_Kolab_Server_Composite The connection to the user DB.
     */
    public function getUserDb(Horde_Injector $injector)
    {
        $configuration = $injector->getInstance('Horde_Kolab_Filter_Configuration');

        $conf = $configuration->getConf();

        $factory = new Horde_Kolab_Server_Factory();

        return new Horde_Kolab_Server_Composite(
            $factory->getServer($conf['server']),
            new Horde_Kolab_Server_Objects_Base(),
            new Horde_Kolab_Server_Structure_Kolab(),
            new Horde_Kolab_Server_Search_Base(),
            new Horde_Kolab_Server_Schema_Base()
        );
    }
}
