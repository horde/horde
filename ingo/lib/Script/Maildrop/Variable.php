<?php
/**
 * The Ingo_Script_Maildrop_Variable:: class represents a Maildrop variable.
 *
 * Copyright 2005-2007 Matt Weyland <mathias@weyland.ch>
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Matt Weyland <mathias@weyland.ch>
 * @package Ingo
 */
class Ingo_Script_Maildrop_Variable
{
    /**
     */
    protected $_name;

    /**
     */
    protected $_value;

    /**
     * Constructs a new maildrop variable.
     *
     * @param array $params  Array of parameters. Expected fields are 'name'
     *                       and 'value'.
     */
    public function __construct($params = array())
    {
        $this->_name = $params['name'];
        $this->_value = $params['value'];
    }

    /**
     * Generates maildrop code to represent the variable.
     *
     * @return string  maildrop code to represent the variable.
     */
    public function generate()
    {
        return $this->_name . '=' . $this->_value . "\n";
    }

}
