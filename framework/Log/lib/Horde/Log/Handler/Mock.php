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
class Horde_Log_Handler_Mock extends Horde_Log_Handler_Base
{
    /**
     * Log events.
     *
     * @var array
     */
    public $events = array();

    /**
     * Was shutdown called?
     *
     * @var boolean
     */
    public $shutdown = false;

    /**
     * Write a message to the log.
     *
     * @param array $event  Event data.
     */
    public function write($event)
    {
        $this->events[] = $event;
    }

    /**
     * Record shutdown
     */
    public function shutdown()
    {
        $this->shutdown = true;
    }

}
