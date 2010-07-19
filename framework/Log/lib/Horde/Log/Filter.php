<?php
/**
 * Horde Log package
 *
 * This package is based on Zend_Log from the Zend Framework
 * (http://framework.zend.com).  Both that package and this
 * one were written by Mike Naberezny and Chuck Hagenbuch.
 *
 * @category Horde
 * @package  Horde_Log
 * @subpackage Filters
 * @author   Mike Naberezny <mike@maintainable.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 */

/**
 * @category Horde
 * @package  Horde_Log
 * @subpackage Filters
 * @author   Mike Naberezny <mike@maintainable.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
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
     * @param  array    $event    Log event
     * @return boolean            accepted?
     */
    public function accept($event);
}
