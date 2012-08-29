<?php
/**
 * Horde_Core_ActiveSync_Logger::
 *
 * PHP Version 5
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2010-2012 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=Core
 * @package   Core
 */
 /**
 * Horde_Core_ActiveSync_Logger_Factory:: Implements a factory/builder for
 * providing a Horde_Log_Logger object correctly configured for the
 * debug settings and the device properties.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2010-2012 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=Core
 * @package   Core
 */
 class Horde_Core_ActiveSync_Logger_Factory implements Horde_ActiveSync_Interface_LoggerFactory
 {

    /**
     * Factory for a log object. Attempts to create a device specific file if
     * custom logging is requested.
     *
     * @param array $properties  The property array.
     *
     * @return Horde_Log_Logger  The logger object, correctly configured.
     */
    public function create($properties = array())
    {
        global $conf;
        $logger = false;
        if ($conf['activesync']['logging']['type'] == 'custom') {
            if (!empty($properties['DeviceId'])) {
                // if (@is_dir($conf['activesync']['logging']['path'] . '/' . $properties['DeviceId']) === false) {
                //     mkdir($conf['activesync']['logging']['path'] . '/' . $properties['DeviceId']);
                // }

                // if (!empty($properties['Cmd'])) {
                //     $base = $conf['activesync']['logging']['path'] . '/' . $properties['DeviceId'] . '/' . $properties['Cmd'];
                //     $cnt = 0;
                //     do {
                //         $cnt++;
                //         $logfile = $base . $cnt;
                //     } while (file_exists($logfile));
                //     $stream = @fopen($logfile, 'a');
                //     if ($stream) {
                //         $logger = new Horde_Log_Logger(new Horde_Log_Handler_Stream($stream));
                //     }
                // }
                $stream = fopen($conf['activesync']['logging']['path'] . '/' . $properties['DeviceId'] . '.txt', 'a');
                if ($stream) {
                    $logger = new Horde_Log_Logger(new Horde_Log_Handler_Stream($stream));
                }
            }
        }

        if (!$logger) {
            $logger = $GLOBALS['injector']->getInstance('Horde_Log_Logger');
        }

        return $logger;
    }

 }