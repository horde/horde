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
class Horde_Log_Handler_Syslog extends Horde_Log_Handler_Base
{
    /**
     * Options to be set by setOption().
     * Sets openlog and syslog options.
     *
     * @var array
     */
    protected $_options = array(
        'defaultPriority'  => LOG_ERR,
        'facility'         => LOG_USER,
        'ident'            => false,
        'openlogOptions'   => false
    );

    /**
     * Last ident set by a syslog-handler instance.
     *
     * @var string
     */
    protected $_lastIdent;

    /**
     * Last facility name set by a syslog-handler instance.
     *
     * @var string
     */
    protected $_lastFacility;

    /**
     * Map of log levels to syslog priorities.
     *
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
     * @param array $event  Log event.
     *
     * @return boolean  True.
     * @throws Horde_Log_Exception
     */
    public function write($event)
    {
        if (($this->_options['ident'] !== $this->_lastIdent) ||
            ($this->_options['facility'] !== $this->_lastFacility)) {
            $this->_initializeSyslog();
        }

        $priority = $this->_toSyslog($event['level']);
        if (!syslog($priority, $event['message'])) {
            throw new Horde_Log_Exception('Unable to log message');
        }

        return true;
    }

    /**
     * Translate a log level to a syslog LOG_* priority.
     *
     * @param integer $level  Log level.
     *
     * @return integer  A LOG_* constant.
     */
    protected function _toSyslog($level)
    {
        return isset($this->_priorities[$level])
            ? $this->_priorities[$level]
            : $this->_options['defaultPriority'];
    }

    /**
     * Initialize syslog / set ident and facility.
     *
     * @param string $ident     Ident.
     * @param string $facility  Syslog facility.
     *
     * @throws Horde_Log_Exception
     */
    protected function _initializeSyslog()
    {
        $this->_lastIdent = $this->_options['ident'];
        $this->_lastFacility = $this->_options['facility'];

        if (!openlog($this->_options['ident'], $this->_options['openlogOptions'], $this->_options['facility'])) {
            throw new Horde_Log_Exception('Unable to open syslog');
        }
    }

}
