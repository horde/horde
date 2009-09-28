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
            $this->addConstraint($c);
        }
    }

    public function addConstraint(Horde_Constraint $constraint)
    {
        $kind = get_class($this);
        if ($constrainst instanceof $kind) {
            foreach ($constrainst->getConstraints() as $c) {
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

    abstract public function evaluate($value);
}
