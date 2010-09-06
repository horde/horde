<?php
/**
 * Horde Log package
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @package    Log
 * @subpackage Handlers
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @package    Log
 * @subpackage Handlers
 */
class Horde_Log_Handler_Firebug extends Horde_Log_Handler_Base
{
    /**
     * Formats the log message before writing.
     *
     * @var Horde_Log_Formatter
     */
    protected $_formatter;

    /**
     * Options to be set by setOption().
     *
     * @var array
     */
    protected $_options = array(
        'buffering' => false
    );

    /**
     * Array of buffered output.
     *
     * @var string
     */
    protected $_buffer = array();

    /**
     * Mapping of log priorities to Firebug methods.
     *
     * @var array
     */
    protected static $_methods = array(
        Horde_Log::EMERG   => 'error',
        Horde_Log::ALERT   => 'error',
        Horde_Log::CRIT    => 'error',
        Horde_Log::ERR     => 'error',
        Horde_Log::WARN    => 'warn',
        Horde_Log::NOTICE  => 'info',
        Horde_Log::INFO    => 'info',
        Horde_Log::DEBUG   => 'debug',
    );

    /**
     * Class Constructor
     *
     * @param Horde_Log_Formatter $formatter  Log formatter.
     */
    public function __construct(Horde_Log_Formatter $formatter = null)
    {
        $this->_formatter = is_null($formatter)
            ? new Horde_Log_Formatter_Simple()
            : $formatter;
    }

    /**
     * Write a message to the firebug console.  This function really just
     * writes the message to the buffer.  If buffering is enabled, the
     * message won't be output until the buffer is flushed. If
     * buffering is not enabled, the buffer will be flushed
     * immediately.
     *
     * @param array $event  Log event.
     *
     * @return boolean  True.
     */
    public function write($event)
    {
        $this->_buffer[] = $event;

        if (empty($this->_options['buffering'])) {
            $this->flush();
        }

        return true;
    }

    /**
     * Flush the buffer.
     */
    public function flush()
    {
        if (!count($this->_buffer)) {
            return true;
        }

        $output = array();
        foreach ($this->_buffer as $event) {
            $line = trim($this->_formatter->format($event));

            // Normalize line breaks.
            $line = str_replace("\r\n", "\n", $line);

            // Escape line breaks
            $line = str_replace("\n", "\\n\\\n", $line);

            // Escape quotes.
            $line = str_replace('"', '\\"', $line);

            // Firebug call.
            $method = isset(self::$_methods[$event['level']])
                ? self::$_methods[$event['level']]
                : 'log';
            $output[] = 'console.' . $method . '("' . $line . '");';
        }

        echo '<script type="text/javascript">'
            . "\nif (('console' in window) || ('firebug' in console)) {\n"
            . implode("\n", $output) . "\n"
            . "}\n"
            . "</script>\n";

        $this->_buffer = array();
    }

}
