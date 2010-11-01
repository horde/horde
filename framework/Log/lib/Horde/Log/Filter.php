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
 * @subpackage Filters
 */

/**
 * @category   Horde
 * @subpackage Filters
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @package    Log
 * @subpackage Filters
 */
interface Horde_Log_Filter
{
    /**
     * Accept a message
     */
    const ACCEPT = true;

    /**
     * Filter out a message
     */
    const IGNORE = false;

    /**
     * Returns Horde_Log_Filter::ACCEPT to accept the message,
     * Horde_Log_Filter::IGNORE to ignore it.
     *
     * @param array $event  Log event.
     *
     * @return boolean  Accepted?
     */
    public function accept($event);

}
