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
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @category   Horde
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @package    Log
 * @subpackage Filters
 */
class Horde_Log_Filter_Suppress implements Horde_Log_Filter
{
    /**
     * Accept all events?
     *
     * @var boolean
     */
    protected $_accept = Horde_Log_Filter::ACCEPT;

    /**
     * This is a simple boolean filter.
     *
     * @param boolean $suppress  Should all log events be suppressed?
     */
    public function suppress($suppress)
    {
        $this->_accept = !$suppress;
    }

    /**
     * Returns Horde_Log_Filter::ACCEPT to accept the message,
     * Horde_Log_Filter::IGNORE to ignore it.
     *
     * @param array $event  Event data.
     *
     * @return boolean  Accepted?
     */
    public function accept($event)
    {
        return $this->_accept;
    }

}
