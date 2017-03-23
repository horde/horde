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
 * Checks for an instance of a class
 *
 * Based on PHPUnit_Framework_Constraint_IsInstanceOf
 *
 * @author    James Pepin <james@jamespepin.com>
 * @category  Horde
 * @copyright 2009-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Constraint
 */
class Horde_Constraint_IsInstanceOf implements Horde_Constraint
{
    private $_type;

    public function __construct($type)
    {
        $this->_type = $type;
    }

    public function evaluate($value)
    {
        return $value instanceof $this->_type;
    }
}
