<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * The Ingo_Script_String class represents a simple string.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Script_String implements Ingo_Script_Item
{
    /**
     * The string to output.
     *
     * @var string
     */
    protected $_string = '';

    /**
     * Constructor.
     *
     * @param string $string  String to be generated.
     */
    public function __construct($string)
    {
        $this->_string = $string;
    }

    /**
     * Returns the string stored by this object.
     *
     * @return string  The string stored by this object.
     */
    public function generate()
    {
        return $this->_string;
    }
}
