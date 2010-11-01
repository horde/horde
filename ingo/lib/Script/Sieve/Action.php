<?php
/**
 * The Ingo_Script_Sieve_Action class represents an action in a Sieve script.
 *
 * An action is anything that has a side effect eg: discard, redirect.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Ingo_Script_Sieve_Action
{
    /**
     * Any necessary action parameters.
     *
     * @var array
     */
    protected $_vars = array();

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    public function toCode()
    {
        return 'toCode() Function Not Implemented in class ' . get_class($this) ;
    }

    public function toString()
    {
        return $this->toCode();
    }

    /**
     * Checks if the rule parameters are valid.
     *
     * @return boolean|string  True if this rule is valid, an error message
     *                         otherwise.
     */
    public function check()
    {
        return 'check() Function Not Implemented in class ' . get_class($this) ;
    }

    /**
     * Returns a list of sieve extensions required for this rule and any
     * sub-rules.
     *
     * @return array  A Sieve extension list.
     */
    public function requires()
    {
        return array();
    }

}
