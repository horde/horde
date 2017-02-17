<?php
/**
 * Compatibility wrapper used to maintain BC with old style of log handling.
 * Will be removed in version 3.0.0 for Horde 6.
 *
 * @copyright  2017 Horde LLC (http://www.horde.org/)
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    ActiveSync
 * @since      2.38.0
 */
/**
 * @copyright  2017 Horde LLC (http://www.horde.org/)
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    ActiveSync
 * @since      2.38.0
 */
class Horde_ActiveSync_Log_Logger_Deprecated extends Horde_Log_Logger
{
    /**
     * Constructor.
     *
     * @param Horde_Log_Handler_Base|null $handler  Default handler.
     */
    public function __construct($handler = null, Horde_Log_Logger $logger = null)
    {
        parent::__construct($handler);
        $this->addLevel('SERVER', '10');
        $this->addLevel('CLIENT', '11');
        $this->addLevel('META', '12');
        $this->_logger = $logger;
    }

    /**
     * Undefined method handler allows a shortcut:
     * <pre>
     * $log->levelName('message');
     *   instead of
     * $log->log('message', Horde_Log_LEVELNAME);
     * </pre>
     *
     * @param string $method  Log level name.
     * @param string $params  Message to log.
     */
    public function __call($method, $params)
    {
        $levelName = Horde_String::upper($method);
        if (!isset($this->_levels[$levelName])) {
            throw new Horde_Log_Exception('Bad log level ' . $levelName);
        }
        if (in_array($method, array('client', 'server', 'meta'))) {
            switch ($method) {
            case 'client':
                $pre = 'I ';
                break;
            case 'server':
                $pre = 'O ';
                break;
            default:
                $pre = '';
            }
            $message = sprintf('[%s] %s%s ', getmypid(), $pre, str_repeat(' ' , $params[1]));
            if (is_resource($params[0])) {
                rewind($params[0]);
                $message .= stream_get_contents($params[0]);
                rewind($params[0]);
            } else {
                $message .= $params[0];
            }
            $event = array(
                'message' => $message,
                'indent' => $params[1],
                'level' => $this->_levels['DEBUG']
            );
        } else {
            $event = array(
                'message' => array_shift($params),
                'level' =>  $this->_levels[$levelName],
                'indent' => 0
            );
        }

        $this->_logger->log($event);
    }

}
