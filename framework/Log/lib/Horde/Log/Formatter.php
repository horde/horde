<?php
/**
 * Horde Log package
 *
 * This package is based on Zend_Log from the Zend Framework
 * (http://framework.zend.com).  Both that package and this
 * one were written by Mike Naberezny and Chuck Hagenbuch.
 *
 * @author   Mike Naberezny <mike@maintainable.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @package  Log
 */

/**
 * @author   Mike Naberezny <mike@maintainable.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @package  Log
 */
interface Horde_Log_Formatter
{
    /**
     * Formats an event to be written by the handler.
     *
     * @param array $event  Log event.
     *
     * @return string  Formatted line.
     */
    public function format($event);

}
