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
class Horde_Log_Filter_Level implements Horde_Log_Filter
{
    /**
     * Filter level.
     *
     * @var integer
     */
    protected $_level;

    /**
     * Filter out any log messages greater than $level.
     *
     * @param integer $level  Maximum log level to pass through the filter.
     *
     * @throws InvalidArgumentException
     */
    public function __construct($level)
    {
        if (!is_integer($level)) {
            throw new InvalidArgumentException('Level must be an integer');
        }

        $this->_level = $level;
    }

    /**
     * Returns Horde_Log_Filter::ACCEPT to accept the message,
     * Horde_Log_Filter::IGNORE to ignore it.
     *
     * @param array $event  Log event.
     *
     * @return boolean  Accepted?
     */
    public function accept($event)
    {
        return ($event['level'] <= $this->_level);
    }

}
