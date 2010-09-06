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
class Horde_Log_Filter_Message implements Horde_Log_Filter
{
    /**
     * Filter regex.
     *
     * @var string
     */
    protected $_regexp;

    /**
     * Filter out any log messages not matching $regexp.
     *
     * @param string $regexp  Regular expression to test the log message.
     *
     * @throws InvalidArgumentException  Invalid regular expression.
     */
    public function __construct($regexp)
    {
        if (@preg_match($regexp, '') === false) {
            throw new InvalidArgumentException('Invalid regular expression ' . $regexp);
        }

        $this->_regexp = $regexp;
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
        return (preg_match($this->_regexp, $event['message']) > 0);
    }

}
