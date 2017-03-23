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
 * Matches against a PCRE regex
 *
 * Based on PHPUnit_Framework_Constraint_PCREMatch
 *
 * @author    James Pepin <james@jamespepin.com>
 * @category  Horde
 * @copyright 2009-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Constraint
 */
class Horde_Constraint_PregMatch implements Horde_Constraint
{
    private $_regex;

    public function __construct($regex)
    {
        $this->_regex = $regex;
    }

    public function evaluate($value)
    {
        return preg_match($this->_regex, $value) > 0;
    }
}
