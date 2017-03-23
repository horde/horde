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
 * Negates another constraint
 *
 * Based on PHPUnit_Framework_Constraint_Not
 *
 * @author    James Pepin <james@jamespepin.com>
 * @category  Horde
 * @copyright 2009-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Constraint
 */
class Horde_Constraint_Not implements Horde_Constraint
{
    private $_constraint;

    public function __construct(Horde_Constraint $constraint)
    {
        $this->_constraint = $constraint;
    }

    public function evaluate($value)
    {
        return !$this->_constraint->evaluate($value);
    }
}
