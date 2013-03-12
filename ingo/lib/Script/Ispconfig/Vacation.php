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
 * The Ingo_Script_Ispconfig_Vacation class represents a ISPConfig vacation
 * message.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Script_Ispconfig_Vacation implements Ingo_Script_Item
{
    /**
     * @var boolean
     */
    public $disable;

    /**
     * @var Ingo_Storage_Vacation
     */
    public $vacation;

    /**
     * Constructor.
     *
     * @param array $params  Array of parameters. Expected fields are 'vacation'
     *                       and 'disable'.
     */
    public function __construct($params = array())
    {
        $this->vacation = $params['vacation'];
        $this->disable = $params['disable'];
    }

    /**
     * Generates  code to represent the vacation message.
     *
     * @return string  Code to represent the vacation message.
     */
    public function generate()
    {
        return '';
    }
}