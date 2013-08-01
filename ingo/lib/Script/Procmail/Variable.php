<?php
/**
 * Copyright 2003-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * The Ingo_Script_Procmail_Variable class represents a Procmail variable.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Script_Procmail_Variable implements Ingo_Script_Item
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
        return $this->_name . '=' . $this->_value;
    }
}
