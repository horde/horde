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
class Horde_Log_Handler_Stream extends Horde_Log_Handler_Base
{
    /**
     * Formats the log message before writing.
     *
     * @var Horde_Log_Formatter
     */
    protected $_formatter;

    /**
     * Holds the PHP stream to log to.
     *
     * @var null|stream
     */
    protected $_stream = null;

    /**
     * The open mode.
     *
     * @var string
     */
    protected $_mode;

    /**
     * The stream to open.
     *
     * @var string
     */
    protected $_streamOrUrl;

    /**
     * Class Constructor
     *
     * @param mixed $streamOrUrl              Stream or URL to open as a
     *                                        stream.
     * @param string $mode                    Mode, only applicable if a URL
     *                                        is given.
     * @param Horde_Log_Formatter $formatter  Log formatter.
     *
     * @throws Horde_Log_Exception
     */
    public function __construct($streamOrUrl, $mode = 'a+',
                                Horde_Log_Formatter $formatter = null)
    {
        $this->_formatter = is_null($formatter)
            ? new Horde_Log_Formatter_Simple()
            : $formatter;
        $this->_mode = $mode;
        $this->_streamOrUrl = $streamOrUrl;

        if (is_resource($streamOrUrl)) {
            if (get_resource_type($streamOrUrl) != 'stream') {
                throw new Horde_Log_Exception(__CLASS__ . ': Resource is not a stream');
            }

            if ($mode != 'a+') {
                throw new Horde_Log_Exception(__CLASS__ . ': Mode cannot be changed on existing streams');
            }

            $this->_stream = $streamOrUrl;
        } else {
            $this->__wakeup();
        }
    }

    /**
     * Wakup function - reattaches stream.
     *
     * @throws Horde_Log_Exception
     */
    public function __wakeup()
    {
        if (!($this->_stream = @fopen($this->_streamOrUrl, $this->_mode, false))) {
            throw new Horde_Log_Exception(__CLASS__ . ': "' . $this->_streamOrUrl . '" cannot be opened with mode "' . $this->_mode . '"');
        }
    }

    /**
     * Write a message to the log.
     *
     * @param array $event  Log event.
     *
     * @return boolean  True.
     * @throws Horde_Log_Exception
     */
    public function write($event)
    {
        $line = $this->_formatter->format($event);

        if (!@fwrite($this->_stream, $line)) {
            throw new Horde_Log_Exception(__CLASS__ . ': Unable to write to stream');
        }

        return true;
    }

}
