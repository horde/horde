<?php
/**
 * Always returns true
 *
 * @author James Pepin <james@jamespepin.com>
 */
class Horde_Constraint_AlwaysTrue implements Horde_Constraint
{
    public function evaluate($value)
    {
        return true;
    }
}
