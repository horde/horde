<?php
/**
 * Horde Log package
 *
 * This package is based on Zend_Log from the Zend Framework
 * (http://framework.zend.com).  Both that package and this
 * one were written by Mike Naberezny and Chuck Hagenbuch.
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @package    Log
 * @subpackage Formatters
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @package    Log
 * @subpackage Formatters
 */
class Horde_Log_Formatter_Simple implements Horde_Log_Formatter
{
    /**
     * Format string.
     *
     * @var string
     */
    protected $_format;

    /**
     * Constructor.
     *
     * @param array $options  Configuration options:
     * <pre>
     * 'format' - (string) The log template.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct($options = null)
    {
        $format = (is_array($options) && isset($options['format']))
            ? $options['format']
            : $options;

        if (is_null($format)) {
            $format = '%timestamp% %levelName%: %message%' . PHP_EOL;
        }

        if (!is_string($format)) {
            throw new InvalidArgumentException('Format must be a string');
        }

        $this->_format = $format;
    }

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
        foreach ($event as $name => $value) {
            $output = str_replace("%$name%", $value, $output);
        }
        return $output;
    }

}
