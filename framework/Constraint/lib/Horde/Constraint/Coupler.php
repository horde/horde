<?php
/**
 * Interface for grouped (compound, coupled) constraints.
 *
 * @author James Pepin <james@jamespepin.com>
 */
abstract class Horde_Constraint_Coupler implements Horde_Constraint
{
    protected $_constraints = array();

    public function __construct()
    {
        $constraints = func_get_args();
        foreach ($constraints as $c) {
            if (! $c instanceof Horde_Constraint) {
                throw new IllegalArgumentException("$c does not implement Horde_Constraint");
            }
            $this->addConstraint($c);
        }
    }

    public function addConstraint(Horde_Constraint $constraint)
    {
        $kind = get_class($this);
        if ($constraint instanceof $kind) {
            foreach ($constraint->getConstraints() as $c) {
                $this->addConstraint($c);
            }
        } else {
            $this->_constraints[] = $constraint;
        }
        return $this;
    }

    public function getConstraints()
    {
        return $this->_constraints;
    }
}
