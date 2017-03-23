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
 * Checks for equality
 *
 * Based on PHPUnit_Framework_Constraint_IsEqual
 *
 * @author    James Pepin <james@jamespepin.com>
 * @category  Horde
 * @copyright 2009-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Constraint
 */
class Horde_Constraint_IsEqual implements Horde_Constraint
{
    private $_value;

    public function __construct($value)
    {
        $this->_value = $value;
    }

    public function evaluate($value)
    {
        return $this->_value == $value;
    }
}
