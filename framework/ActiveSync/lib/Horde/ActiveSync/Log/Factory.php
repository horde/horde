<?php
/**
 * ActiveSync log factory.
 *
 * @copyright  2010-2017 Horde LLC (http://www.horde.org/)
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    ActiveSync
 * @since      2.38.0
 */
/**
 * @copyright  2010-2017 Horde LLC (http://www.horde.org/)
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    ActiveSync
 * @since      2.38.0
 */
class Horde_ActiveSync_Log_Factory implements Horde_ActiveSync_Interface_LoggerFactory
{
    /**
     * Parameter array.
     *
     * @var array
     */
    protected $_params;

    /**
     * Const'r
     *
     * @param array $params  Factory parameters. Must contain:
     *   - type: The type of log. ['onefile' | 'perdevice' | 'perrequest']
     *   - path: The path to either the log file (for 'onefile' type) or
     *           the logging directory (for 'perdevice' or 'perrequest' types).
     *   - level: The logging level. Defaults to META. MUST be one of
     *            [CLIENT | META]. CLIENT logs all WBXML sent/received plus
     *            minimal information messages. META will log CLIENT plus all
     *            additional debug messaging.
     *
     * @throws  InvalidArgumentException
     */
    public function __construct(array $params)
    {
        if (empty($params['level'])) {
            $params['level'] = 'META';
        }
        $this->_params = $params;
    }

    /**
     * Creates and configures the logger object.
     *
     * @param array $properties  The property array. Sould contain:
     *   - DeviceId: The device id of the current client.
     *   - Cmd:      The current command being handled, if known.
     *
     * @return Horde_Log_Logger  The logger object, correctly configured.
     */
    public function create($properties = array())
    {
        $stream = $logger = false;
        $formatter = new Horde_ActiveSync_Log_Formatter();

        if (empty($this->_params['path'])) {
             new Horde_ActiveSync_Log_Logger(new Horde_Log_Handler_Null());
        }

        switch ($this->_params['type']) {
        case 'onefile':
            if (!empty($properties['DeviceId'])) {
                $device_id = Horde_String::upper($properties['DeviceId']);
                $stream = @fopen($this->_params['path'], 'a');
            }
            break;
        case 'perdevice':
            if (!empty($properties['DeviceId'])) {
                $stream = @fopen(
                    $this->_params['path'] . '/' . Horde_String::upper($properties['DeviceId']) . '.txt',
                    'a'
                );
            }
            break;
        case 'perrequest':
            if (!empty($properties['DeviceId'])) {
                $dir = sprintf('%s/%s',
                    $this->_params['path'],
                    Horde_String::upper($properties['DeviceId'])
                );
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $path = sprintf('%s/%s-%s-%s.txt',
                    $dir,
                    time(),
                    getmypid(),
                    (!empty($properties['Cmd']) ? $properties['Cmd'] : 'UnknownCmd')
                );
                $stream = fopen($path, 'a');
            }
        }

        if ($stream) {
            $handler = new Horde_ActiveSync_Log_Handler($stream, false, $formatter);
            $handler->addFilter(constant('Horde_ActiveSync_Log_Logger::' . $this->_params['level']));
            $logger = new Horde_ActiveSync_Log_Logger($handler);
        }

        if (!$logger) {
            $logger = new Horde_ActiveSync_Log_Logger(new Horde_Log_Handler_Null());
        }

        return $logger;
    }

}
