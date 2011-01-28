<?php
/**
 * The Ingo_Script_Procmail_Variable:: class represents a Procmail variable.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Ingo
 */
class Ingo_Script_Procmail_Variable
{
    /**
     */
    protected $_name;

    /**
     */
    protected $_value;

    /**
     * Constructs a new procmail variable.
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
     * Generates procmail code to represent the variable.
     *
     * @return string  Procmail code to represent the variable.
     */
    public function generate()
    {
        return $this->_name . '=' . $this->_value . "\n";
    }

}
