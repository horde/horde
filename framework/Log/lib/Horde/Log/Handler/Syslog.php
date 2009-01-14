<?php
/**
 * Horde Log package
 *
 * @category   Horde
 * @package    Horde_Log
 * @subpackage Handlers
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 */

/**
 * @category   Horde
 * @package    Horde_Log
 * @subpackage Handlers
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 */
class Horde_Log_Handler_Syslog extends Horde_Log_Handler_Base
{
    /**
     * Options to be set by setOption().  Sets openlog and syslog options.
     * @var array
     */
    protected $_options = array(
        'ident'            => false,
        'facility'         => LOG_USER,
        'openlogOptions'   => false,
        'defaultPriority'  => LOG_ERR,
    );

    /**
     * Last ident set by a syslog-handler instance
     * @var string
     */
    protected static $_lastIdent;

    /**
     * Last facility name set by a syslog-handler instance
     * @var string
     */
    protected static $_lastFacility;

    /**
     * Map of log levels to syslog priorities
     * @var array
     */
    protected $_priorities = array(
        Horde_Log::EMERG   => LOG_EMERG,
        Horde_Log::ALERT   => LOG_ALERT,
        Horde_Log::CRIT    => LOG_CRIT,
        Horde_Log::ERR     => LOG_ERR,
        Horde_Log::WARN    => LOG_WARNING,
        Horde_Log::NOTICE  => LOG_NOTICE,
        Horde_Log::INFO    => LOG_INFO,
        Horde_Log::DEBUG   => LOG_DEBUG,
    );

    /**
     * Write a message to the log.
     *
     * @param  array    $event    Log event
     * @return bool               Always True
     */
    public function write($event)
    {
        if ($this->_options['ident'] !== self::$_lastIdent ||
            $this->_options['facility'] !== self::$_lastFacility) {
            $this->_initializeSyslog();
        }

        $priority = $this->_toSyslog($event['level']);
        if (! syslog($priority, $event['message'])) {
            throw new Horde_Log_Exception('Unable to log message');
        }

        return true;
    }

    /**
     * Translate a log level to a syslog LOG_* priority.
     *
     * @param integer $level
     *
     * @return integer A LOG_* constant
     */
    protected function _toSyslog($level)
    {
        if (isset($this->_priorities[$level])) {
            return $this->_priorities[$level];
        }
        return $this->_options['defaultPriority'];
    }

    /**
     * Initialize syslog / set ident and facility
     *
     * @param  string  $ident         ident
     * @param  string  $facility      syslog facility
     * @return void
     */
    protected function _initializeSyslog()
    {
        self::$_lastIdent = $this->_options['ident'];
        self::$_lastFacility = $this->_options['facility'];
        if (! openlog($this->_options['ident'], $this->_options['openlogOptions'], $this->_options['facility'])) {
            throw new Horde_Log_Exception('Unable to open syslog');
        }
    }

}
