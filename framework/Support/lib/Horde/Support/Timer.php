<?php
/**
 * @category   Horde
 * @package    Support
 * @copyright  1999-2009 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */

/**
 * Simple interface for timing operations.
 *
 * <code>
 *  $t = new Horde_Support_Timer;
 *  $t->push();
 *  $elapsed = $t->pop();
 * </code>
 *
 * @category   Horde
 * @package    Support
 * @copyright  1999-2009 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Horde_Support_Timer
{
    /**
     * @var array
     */
    protected $_start = array();

    /**
     * @var integer
     */
    protected $_idx = 0;

    /**
     * Push a new timer start on the stack.
     */
    public function push()
    {
        $start = $this->_start[$this->_idx++] = microtime(true);
        return $start;
    }

    /**
     * Pop the latest timer start and return the difference with the current
     * time.
     */
    public function pop()
    {
        $etime = microtime(true);

        if (! ($this->_idx > 0)) {
            throw new Exception('No timers have been started');
        }

        return $etime - $this->_start[--$this->_idx];
    }

}
