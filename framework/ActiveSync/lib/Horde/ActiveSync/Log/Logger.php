<?php
/**
 * ActiveSync logger.
 *
 * @copyright  2017 Horde LLC (http://www.horde.org/)
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    ActiveSync
 */
/**
 * @copyright  2017 Horde LLC (http://www.horde.org/)
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    ActiveSync
 *
 * @method void emerg() emerg($event) Log an event at the EMERG log level
 * @method void alert() alert($event) Log an event at the ALERT log level
 * @method void crit() crit($event) Log an event at the CRIT log level
 * @method void err() err($event) Log an event at the ERR log level
 * @method void warn() warn($event) Log an event at the WARN log level
 * @method void notice() notice($event) Log an event at the NOTICE log level
 * @method void info() info($event) Log an event at the INFO log level
 * @method void debug() debug($event) Log an event at the DEBUG log level
 * @method void meta($event) Log an event at the META log level.
 * @method void client(string $event, integer $indent) Log an event as a CLIENT
 *         message, indented by specified number of spaces.
 * @method void server(string $event, integer $indent) Log an event as a SERVER
 *         message, indented by specified number of spaces.
 */
class Horde_ActiveSync_Log_Logger extends Horde_Log_Logger
{
    const SERVER = 10;
    const CLIENT = 11;
    const META   = 12;

    /**
     * Constructor.
     *
     * @param Horde_Log_Handler_Base|null $handler  Default handler.
     */
    public function __construct($handler = null)
    {
        parent::__construct($handler);
        $this->addLevel('SERVER', self::SERVER);
        $this->addLevel('CLIENT', self::CLIENT);
        $this->addLevel('META', self::META);
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
        if (in_array($method, array('client', 'server'))) {
            $event = array(
                'message' => $params[0],
                'indent' => $params[1],
                'level' => $this->_levels[$levelName]
            );
        } else {
            $event = array(
                'message' => array_shift($params),
                'level' =>  $this->_levels[$levelName],
                'indent' => 0
            );
        }

        $this->log($event);
    }

}
