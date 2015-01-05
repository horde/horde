<?php
/**
 * Horde_Core_ActiveSync_Logger::
 *
 * PHP Version 5
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2010-2015 Horde LLC (http://www.horde.org/)
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
 * @copyright 2010-2015 Horde LLC (http://www.horde.org/)
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
        if ($conf['activesync']['logging']['type'] == 'onefile') {
            if (!empty($properties['DeviceId'])) {
                $device_id = $properties['DeviceId'];
                $format = "%timestamp% $device_id %levelName%: %message%" . PHP_EOL;
                $formatter = new Horde_Log_Formatter_Simple(array('format' => $format));
                $stream = fopen($conf['activesync']['logging']['path'], 'a');
                if ($stream) {
                    $logger = new Horde_Log_Logger(new Horde_Log_Handler_Stream($stream, false, $formatter));
                }
            }
        } elseif ($conf['activesync']['logging']['type'] == 'perdevice') {
            if (!empty($properties['DeviceId'])) {
                $stream = fopen($conf['activesync']['logging']['path'] . '/' . strtoupper($properties['DeviceId']) . '.txt', 'a');
                if ($stream) {
                    $logger = new Horde_Log_Logger(new Horde_Log_Handler_Stream($stream));
                }
            }
        }

        if (!$logger) {
            $logger = new Horde_Log_Logger(new Horde_Log_Handler_Null());
        }

        return $logger;
    }

}
