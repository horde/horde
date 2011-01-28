<?php
/**
 * An instance of this class is returned by
 * Horde_View_Helper_Benchmark::benchmark().
 *
 * Copyright 2007-2008 Maintainable Software, LLC
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage Helper
 */

/**
 * An instance of this class is returned by
 * Horde_View_Helper_Benchmark::benchmark().
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage Helper
 */
class Horde_View_Helper_Benchmark_Timer
{
    /**
     * Time that the benchmark was started
     *
     * @var float  microtime
     */
    private $_start;

    /**
     * Logger instance that will be used to record the
     * time after the benchmark has ended
     *
     * @var null|Horde_Log_Logger
     */
    private $_logger;

    /**
     * Message to log after the benchmark has ended
     *
     * @var string
     */
    private $_message;

    /**
     * Log level to log after the benchmark has ended
     *
     * @var string|integer
     */
    private $_level;

    /**
     * Start a new benchmark.
     *
     * @param string                 $message  Message to log after the benchmark has ended
     * @param string|integer         $level    Log level to log after the benchmark has ended
     * @param null|Horde_Log_Logger  $logger   Logger instance or NULL if none is available
     */
    public function __construct($message, $level = 'info', $logger = null)
    {
        $this->_message = $message;
        $this->_level   = $level;
        $this->_logger  = $logger;
        $this->_start   = microtime(true);
    }

    /**
     * End the benchmark and log the result.
     */
    public function end()
    {
        if ($this->_logger) {
            // compute elapsed time & build message
            $elapsed = microtime(true) - $this->_start;
            $message = sprintf("{$this->_message} (%.5f)", $elapsed);

            // log message (level may be specified as integer or string)
            if (is_integer($this->_level)) {
                $this->_logger->log($message, $this->_level);
            } else {
                $this->_logger->{$this->_level}($message);
            }
        }
    }

}
