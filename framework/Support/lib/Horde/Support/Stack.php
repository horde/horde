<?php
/**
 * @category   Horde
 * @package    Support
 * @copyright  2008-2009 Horde LLC (http://www.horde.org/)
 * @license    http://www.horde.org/licenses/bsd
 */

/**
 * Simple class for using an array as a stack.
 *
 * @category   Horde
 * @package    Support
 * @copyright  2008-2009 Horde LLC (http://www.horde.org/)
 * @license    http://www.horde.org/licenses/bsd
 */
class Horde_Support_Stack
{
    /**
     * @var array
     */
    protected $_stack = array();

    public function __construct($stack = array())
    {
        $this->_stack = $stack;
    }

    public function push($value)
    {
        $this->_stack[] = $value;
    }

    public function pop()
    {
        return array_pop($this->_stack);
    }

    public function peek($offset = 1)
    {
        if (isset($this->_stack[count($this->_stack) - $offset])) {
            return $this->_stack[count($this->_stack) - $offset];
        } else {
            return null;
        }
    }
}
