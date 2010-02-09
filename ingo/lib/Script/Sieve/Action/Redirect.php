<?php
/**
 * The Ingo_Script_Sieve_Action_Redirect class represents a redirect action.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Ingo_Script_Sieve_Action_Redirect extends Ingo_Script_Sieve_Action
{
    /**
     * Constructor.
     *
     * @param array $vars  Any required parameters.
     */
    public function __construct($vars = array())
    {
        $this->_vars['address'] = isset($vars['address'])
            ? $vars['address']
            : '';
    }

    /**
     */
    public function toCode($depth = 0)
    {
        return str_repeat(' ', $depth * 4) . 'redirect ' .
            '"' . Ingo_Script_Sieve::escapeString($this->_vars['address']) . '";';
    }

    /**
     * Checks if the rule parameters are valid.
     *
     * @return boolean|string  True if this rule is valid, an error message
     *                         otherwise.
     */
    public function check()
    {
        return empty($this->_vars['address'])
            ? _("Missing address to redirect message to")
            : true;
    }

}
