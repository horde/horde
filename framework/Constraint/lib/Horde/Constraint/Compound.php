<?php
/**
 * Interface for compound constraints.
 *
 * @author James Pepin <james@jamespepin.com>
 */
abstract class Horde_Constraint_Compound implements Horde_Constraint
{
    protected $_constraints = array();

    public function __construct()
    {
        $constraints = func_get_args();
        foreach ($constraints as $c) {
            if (! $c instanceof Horde_Constraint) {
                throw new IllegalArgumentException("$c does not implement Horde_Constraint");
            }
        }
        $this->_constraints = $constraints;
    }

    public function addConstraint(Horde_Constraint $constraint)
    {
        $this->_constraints[] = $constraint;
        return $this;
    }

    abstract public function evaluate($value);
}
