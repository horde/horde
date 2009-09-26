<?php
/**
 * Interface for constraints.
 *
 * @author James Pepin <james@jamespepin.com>
 */
interface Horde_Constraint
{
    public function evaluate($value);
}
