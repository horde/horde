<?php
/**
 * Checks for equality
 *
 * Based on PHPUnit_Framework_Constraint_IsEqual
 *
 * @author James Pepin <james@jamespepin.com>
 */
class Horde_Constraint_IsEqual implements Horde_Constraint
{
    private $_value;

    public function __construct($value)
    {
        $this->_value = $value;
    }

    public function evaluate($value)
    {
        return $this->_value == $value;
    }
}
