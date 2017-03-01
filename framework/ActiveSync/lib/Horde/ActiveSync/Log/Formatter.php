<?php
/**
 * ActiveSync log formatter
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
class Horde_ActiveSync_Log_Formatter implements Horde_Log_Formatter
{
    /**
     * Format string.
     *
     * @var string
     * @todo  Log Device ID?
     */
    protected $_format = '[%pid%] %levelName%: %indent%';

    protected $_levelMap = array(
        'CLIENT' => 'I',
        'SERVER' => 'O',
        'META' => '>>>'
    );

    /**
     * Formats an event to be written by the handler.
     *
     * @param array $event  Log event.
     *
     * @return string  Formatted line.
     */
    public function format($event)
    {
        $output = $this->_format;
        $event['pid'] = getmypid();
        if (empty($event['indent'])) {
            $event['indent'] = '';
        } else {
            $event['indent'] = str_repeat(' ', $event['indent']);
        }
        $event['levelName'] = !empty($this->_levelMap[$event['levelName']])
            ? $this->_levelMap[$event['levelName']]
            : $event['levelName'];
        foreach ($event as $name => $value) {
            $output = str_replace("%$name%", $value, $output);
        }
        return $output;
    }

}
