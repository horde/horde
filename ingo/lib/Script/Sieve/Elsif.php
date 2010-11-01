<?php
/**
 * The Ingo_Script_Sieve_Elsif:: class represents a Sieve Elsif Statement.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Ingo_Script_Sieve_Elsif
{
    /**
     * The Ingo_Script_Sieve_Test object for the if test.
     *
     * @var Ingo_Script_Sieve_Test
     */
    protected $_test;

    /**
     * A list of Ingo_Script_Sieve_Action objects that go into the if clause.
     *
     * @var array
     */
    protected $_actions = array();

    /**
     * Constructor.
     *
     * @param Ingo_Script_Sieve_Test $test  A Ingo_Script_Sieve_Test object.
     */
    public function __construct($test = null)
    {
        $this->_test = is_null($test)
            ? new Ingo_Script_Sieve_Test_False()
            : $test;
        $this->_actions[] = new Ingo_Script_Sieve_Action_Keep();
    }

    /**
     */
    public function getTest()
    {
        return $this->_test;
    }

    /**
     */
    public function setTest($test)
    {
        $this->_test = $test;
    }

    /**
     */
    public function getActions()
    {
        return $this->_actions;
    }

    /**
     */
    public function setActions($actions)
    {
        $this->_actions = $actions;
    }

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    public function toCode()
    {
        $code = 'elsif ' . $this->_test->toCode() . " { \n";
        foreach ($this->_actions as $action) {
            $code .= '    ' . $action->toCode() . "\n";
        }
        $code .= "} ";

        return $code;
    }

    /**
     * Checks if all sub-rules are valid.
     *
     * @return boolean|string  True if all rules are valid, an error message
     *                         otherwise.
     */
    public function check()
    {
        $res = $this->_test->check();
        if ($res !== true) {
            return $res;
        }

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

        return array_merge($requires, $this->_test->requires());
    }

}
