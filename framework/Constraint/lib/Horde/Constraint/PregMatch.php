<?php
/**
 * Matches against a PCRE regex
 *
 * Based on PHPUnit_Framework_Constraint_PCREMatch
 *
 * @author James Pepin <james@jamespepin.com>
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
