<?php
/**
 * Checks for an instance of a class
 *
 * Based on PHPUnit_Framework_Constraint_IsInstanceOf
 *
 * @author James Pepin <james@jamespepin.com>
 */
class Horde_Constraint_IsInstanceOf implements Horde_Constraint
{
    private $_type;

    public function __construct($type)
    {
        $this->_type = $type;
    }

    public function evaluate($value)
    {
        return $value instanceof $this->_type;
    }
}
