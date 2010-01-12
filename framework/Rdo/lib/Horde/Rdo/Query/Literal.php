<?php
/**
 * @category Horde
 * @package  Horde_Rdo
 */

/**
 * Horde_Rdo literal query string object.
 *
 * If you need to pass a string that should not be quoted into a
 * Horde_Rdo_Query object, wrap it in a Horde_Rdo_Query_Literal object
 * and it will not be quoted or escaped. Note that of course you need
 * to be very careful about introducing user input or any other
 * untrusted input into these objects.
 *
 * Example:
 *   $literal = new Horde_Rdo_Query_Literal('MAX(column_name)');
 *
 * @category Horde
 * @package  Horde_Rdo
 */
class Horde_Rdo_Query_Literal
{
    /**
     * SQL literal string.
     *
     * @var string
     */
    protected $_string;

    /**
     * Instantiate a literal, which is just a string stored as
     * an instance member variable.
     *
     * @param string $string The string containing an SQL literal.
     */
    public function __construct($string)
    {
        $this->_string = (string)$string;
    }

    /**
     * @return string The SQL literal stored in this object.
     */
    public function __toString()
    {
        return $this->_string;
    }
}
