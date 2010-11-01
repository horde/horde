<?php
/**
 * The Ingo_Script_Sieve_Else:: class represents a Sieve Else Statement.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Ingo_Script_Sieve_Else
{
    /**
     * A list of Ingo_Script_Sieve_Action objects that go into the else clause.
     *
     * @var array
     */
    protected $_actions = array();

    /**
     * Constructor.
     *
     * @param mixed $actions  An Ingo_Script_Sieve_Action object or a list of
     *                        Ingo_Script_Sieve_Action objects.
     */
    public function __construct($actions = null)
    {
        if (is_array($actions)) {
            $this->_actions = $actions;
        } elseif (!is_null($actions)) {
            $this->_actions[] = $actions;
        }
    }

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
     public function toCode()
     {
        if (count($this->_actions) == 0) {
            return '';
        }

        $code = 'else' . " { \n";
        foreach ($this->_actions as $action) {
            $code .= '    ' . $action->toCode() . "\n";
        }
        $code .= "} ";

        return $code;
    }

    /**
     */
    public function setActions($actions)
    {
        $this->_actions = $actions;
    }

    /**
     */
    public function getActions()
    {
        return $this->_actions;
    }

    /**
     * Checks if all sub-rules are valid.
     *
     * @return boolean|string  True if all rules are valid, an error message
     *                         otherwise.
     */
    public function check()
    {
        foreach ($this->_actions as $action) {
            $res = $action->check();
            if ($res !== true) {
                return $res;
            }
        }

        return true;
    }

    /**
     * Returns a list of sieve extensions required for this rule and any
     * sub-rules.
     *
     * @return array  A Sieve extension list.
     */
    public function requires()
    {
        $requires = array();

        foreach ($this->_actions as $action) {
            $requires = array_merge($requires, $action->requires());
        }

        return $requires;
    }

}
