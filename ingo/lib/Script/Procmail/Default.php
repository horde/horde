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
 * The Ingo_Script_Procmail_Default class represents a final rule to deliver to
 * $DEFAULT.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Script_Procmail_Default implements Ingo_Script_Item
{
    /**
     */
    protected $_params;

    /**
     * Constructor.
     *
     * @param array $params  Array of parameters. Expected fields are
     *                       'delivery_agent' and optionally
     *                       'delivery_mailbox_prefix'.
     */
    public function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Generates procmail code to represent the default delivery rule.
     *
     * @return string  Procmail code to represent the default rule.
     */
    public function generate()
    {
        $code = ":0 w\n| " . $this->_params['delivery_agent'] . ' ';
        if (isset($this->_params['delivery_mailbox_prefix'])) {
            $code .= $this->_params['delivery_mailbox_prefix'];
        }
        return $code . '$DEFAULT';
    }
}
