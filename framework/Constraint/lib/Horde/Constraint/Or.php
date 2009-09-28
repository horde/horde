<?php
/**
 * Represents a collection of constraints, if any are true, the collection will evaluate to true.
 *
 * @author James Pepin <james@jamespepin.com>
 * @author Chuck Hagenbuch <chuck@horde.org>
 */
class Horde_Constraint_Or extends Horde_Constraint_Coupler
{
    public function evaluate($value)
    {
        foreach ($this->_constraints as $c) {
            if ($c->evaluate($value)) {
                return true;
            }
        }
        return false;
    }
}
