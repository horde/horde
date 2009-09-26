<?php
/**
 * Negates another constraint
 *
 * Based on PHPUnit_Framework_Constraint_Not
 *
 * @author James Pepin <james@jamespepin.com>
 */
class Horde_Constraint_Not implements Horde_Constraint
{
    private $_constraint;

    public function __construct(Horde_Constraint $constraint)
    {
        $this->_constraint = $constraint;
    }

    public function evaluate($value)
    {
        return !$this->_constraint->evaluate($value);
    }
}
