<?php
/**
 * Copyright 2009-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   James Pepin <james@jamespepin.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Constraint
 */

/**
 * Represents a collection of constraints, if one is false, this collection will
 * evaluate to false
 *
 * Based on PHPUnit_Framework_Constraint_And
 *
 * @author    James Pepin <james@jamespepin.com>
 * @category  Horde
 * @copyright 2009-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Constraint
 */
class Horde_Constraint_And extends Horde_Constraint_Coupler
{
    public function evaluate($value)
    {
        foreach ($this->_constraints as $c) {
            if (!$c->evaluate($value)) {
                return false;
            }
        }
        return true;
    }
}
