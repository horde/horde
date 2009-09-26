<?php
/**
 * Always returns false
 *
 * @author James Pepin <james@jamespepin.com>
 */
class Horde_Constraint_AlwaysFalse implements Horde_Constraint
{
    public function evaluate($value)
    {
        return false;
    }
}
