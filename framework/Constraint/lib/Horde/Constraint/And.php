<?php
/**
 * Represents a collection of constraints, if one is false, this collection will
 * evaluate to false
 *
 * Based on PHPUnit_Framework_Constraint_And
 *
 * @author James Pepin <james@jamespepin.com>
 */
class Horde_Constraint_And extends Horde_Constraint_Coupler
{
    public function evaluate($value)
    {
        foreach ($this->_constraints as $c) {
            if (!$c->evaluate($value)) {
                return false;
            }
        }
        return true;
    }
}
