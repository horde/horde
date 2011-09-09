<?php
/**
 * Horde Log package
 *
 * @author     Bryan Alves <bryanalves@gmail.com>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Filters
 */

/**
 * @author     Bryan Alves <bryanalves@gmail.com>
 * @category   Horde
 * @license    http://www.horde.org/licenses/bsd BSD
 * @package    Log
 * @subpackage Filters
 */
class Horde_Log_Filter_ExactLevel implements Horde_Log_Filter
{
    /**
     * @var integer
     */
    protected $_level;

    /**
     * Filter out any log messages not equal to $level.
     *
     * @param  integer  $level  Log level to pass through the filter
     */
    public function __construct($level)
    {
        if (!is_integer($level)) {
            throw new Horde_Log_Exception('Level must be an integer');
        }

        $this->_level = $level;
    }

    /**
     * Returns TRUE to accept the message, FALSE to block it.
     *
     * @param  array    $event    Log event
     * @return boolean            accepted?
     */
    public function accept($event)
    {
        return $event['level'] == $this->_level;
    }
}
