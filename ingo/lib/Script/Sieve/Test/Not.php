<?php
/**
 * The Ingo_Script_Sieve_Test_Not class represents the inverse of a given
 * test.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Ingo_Script_Sieve_Test_Not extends Ingo_Script_Sieve_Test
{
    /**
     */
    protected $_test = array();

    /**
     * Constructor.
     *
     * @param Ingo_Script_Sieve_Test $test  An Ingo_Script_Sieve_Test object.
     */
    public function __construct($test)
    {
        $this->_test = $test;
    }

    /**
     * Checks if the sub-rule is valid.
     *
     * @return boolean|string  True if this rule is valid, an error message
     *                         otherwise.
     */
    public function check()
    {
        return $this->_test->check();
    }

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    public function toCode()
    {
        return 'not ' . $this->_test->toCode();
    }

    /**
     * Returns a list of sieve extensions required for this rule and any
     * sub-rules.
     *
     * @return array  A Sieve extension list.
     */
    public function requires()
    {
        return $this->_test->requires();
    }

}
