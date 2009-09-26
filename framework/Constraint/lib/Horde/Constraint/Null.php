<?php
/**
 * Checks if the value is null
 *
 * Based on PHPUnit_Framework_Constraint_Null
 *
 * @author James Pepin <james@jamespepin.com>
 */
class Horde_Constraint_Null implements Horde_Constraint
{
    public function evaluate($value)
    {
        return is_null($value);
    }
}
