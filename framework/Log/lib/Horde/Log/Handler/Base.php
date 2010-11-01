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
abstract class Horde_Log_Handler_Base
{
    /**
     * Options.
     *
     * @var array
     */
    protected $_options = array();

    /**
     * List of filter objects.
     *
     * @var array
     */
    protected $_filters = array();

    /**
     * Add a filter specific to this handler.
     *
     * @param Horde_Log_Filter $filter  Filter to add.
     */
    public function addFilter($filter)
    {
        $this->_filters[] = is_integer($filter)
            ? new Horde_Log_Filter_Level($filter)
            : $filter;
    }

    /**
     * Log a message to this handler.
     *
     * @param array $event  Log event.
     */
    public function log($event)
    {
        // If any local filter rejects the message, don't log it.
        foreach ($this->_filters as $filter) {
            if (!$filter->accept($event)) {
                return;
            }
        }

        $this->write($event);
    }

    /**
     * Sets an option specific to the implementation of the log handler.
     *
     * @param string $optionKey   Key name for the option to be changed.  Keys
     *                            are handler-specific.
     * @param mixed $optionValue  New value to assign to the option
     *
     * @return boolean  True.
     * @throws Horde_Log_Exception
     */
    public function setOption($optionKey, $optionValue)
    {
        if (!isset($this->_options[$optionKey])) {
            throw new Horde_Log_Exception('Unknown option "' . $optionKey . '".');
        }
        $this->_options[$optionKey] = $optionValue;

        return true;
    }

    /**
     * Buffer a message to be stored in the storage.
     *
     * @param array $event  Log event.
     */
    abstract public function write($event);

}
