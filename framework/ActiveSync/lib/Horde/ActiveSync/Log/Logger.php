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
 */
class Horde_ActiveSync_Log_Logger extends Horde_Log_Logger
{
    /**
     * Constructor.
     *
     * @param Horde_Log_Handler_Base|null $handler  Default handler.
     */
    public function __construct($handler = null)
    {
        parent::__construct($handler);
        $this->addLevel('SERVER', 10);
        $this->addLevel('CLIENT', 11);
        $this->addLevel('META', 12);
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
