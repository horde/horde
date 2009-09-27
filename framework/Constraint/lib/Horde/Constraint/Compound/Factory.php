<?
/**
 * interface for classes that create compound constraints
 *
 * @author James Pepin <james@jamespepin.com>
 */
interface Horde_Constraint_Compound_Factory
{
    /**
     * does this factory create objects of the same type as the given object?
     * @return boolean True if this factory creates this type of object/ false otherwise
     */
    public function createsConstraintType(Horde_Constraint $constraint);

    /**
     * create a compound constraint
     *
     * @return Horde_Constraint_Compound the created constraint
     */
    public function create(Horde_Constraint $a, Horde_Constraint $b);
}
