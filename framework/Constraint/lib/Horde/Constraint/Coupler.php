<?
/**
 * class for coupling constraints.
 *
 * The primary use for this interface is to allow objects to combine two
 * constraints to form compound constraints
 *
 * @author James Pepin <james@jamespepin.com>
 */
class Horde_Constraint_Coupler
{
    private $_factory;

    public function __construct(Horde_Constraint_Compound_Factory $factory)
    {
        $this->_factory = $factory;
    }
    /**
     * couple two constraints together
     *
     * @param Horde_Constraint $husband The first constraint
     * @param Horde_Constraint $wife The second constraint
     * @return Horde_Constraint_Compound If one of the two arguments is an And constraint, 
     * then we return that constraint, otherwise, we return a new constraint
     */
    public function couple(Horde_Constraint $husband, Horde_Constraint $wife)
    {
        if($this->_coupleConstraints($husband, $wife))
        {
            return $husband;
        }
        if($this->_coupleConstraints($wife, $husband))
        {
            return $wife;
        }
        return $this->_factory->create($husband, $wife);
    }
    private function _coupleConstraints(Horde_Constraint $a, Horde_Constraint $b)
    {
        if($this->_factory->createsConstraintType($a))
        {
            $a->addConstraint($b);
            return true;
        } 
        return false;
    }
}
