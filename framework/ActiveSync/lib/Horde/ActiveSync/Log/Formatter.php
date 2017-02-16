<?php
/**
 * ActiveSync log formatter
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
class Horde_ActiveSync_Log_Formatter implements Horde_Log_Formatter
{
    /**
     * Format string.
     *
     * @var string
     * @todo  Log Device ID?
     */
    protected $_format = '[%pid%] %levelName%: %indent%';

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
        foreach ($event as $name => $value) {
            $output = str_replace("%$name%", $value, $output);
        }
        return $output;
    }

}
