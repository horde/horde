<?php
/**
 * The Ingo_Script_Sieve_Test_Size class represents a message size test.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Ingo_Script_Sieve_Test_Size extends Ingo_Script_Sieve_Test
{
    /**
     * Constructor.
     *
     * @param array $vars  Any required parameters.
     */
    public function __construct($vars = array())
    {
        $this->_vars['comparison'] = isset($vars['comparison'])
            ? $vars['comparison']
            : '';
        $this->_vars['size'] = isset($vars['size'])
            ? $vars['size']
            : '';
    }

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    public function toCode()
    {
        return 'size ' . $this->_vars['comparison'] . ' ' . $this->_vars['size'];
    }

    /**
     * Checks if the rule parameters are valid.
     *
     * @return boolean|string  True if this rule is valid, an error message
     *                         otherwise.
     */
    public function check()
    {
        if (!(isset($this->_vars['comparison']) &&
              isset($this->_vars['size']))) {
            return false;
        }

        return true;
    }

}
