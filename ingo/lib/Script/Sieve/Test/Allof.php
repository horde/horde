<?php
/**
 * The Ingo_Script_Sieve_Test_Allof class represents a Allof test structure.
 *
 * Equivalent to a logical AND of all the tests it contains.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Ingo_Script_Sieve_Test_Allof extends Ingo_Script_Sieve_Test
{
    /**
     */
    protected $_tests = array();

    /**
     * Constructor.
     *
     * @param mixed $test  A Ingo_Script_Sieve_Test object or a list of
     *                     Ingo_Script_Sieve_Test objects.
     */
    public function __construct($test = null)
    {
        if (is_array($test)) {
            $this->_tests = $test;
        } elseif (!is_null($test)) {
            $this->_tests[] = $test;
        }
    }

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    public function toCode()
    {
        $code = '';
        if (count($this->_tests) > 1) {
            $testlist = '';
            foreach ($this->_tests as $test) {
                $testlist .= (empty($testlist)) ? '' : ', ';
                $testlist .= trim($test->toCode());
            }

            $code = "allof ( $testlist )";
        } elseif (count($this->_tests) == 1) {
            $code = $this->_tests[0]->toCode();
        } else {
            return 'true';
        }
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
        foreach ($this->_tests as $test) {
            $res = $test->check();
            if ($res !== true) {
                return $res;
            }
        }

        return true;
    }

    /**
     */
    public function addTest($test)
    {
        $this->_tests[] = $test;
    }

    /**
     */
    public function getTests()
    {
        return $this->_tests;
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

        foreach ($this->_tests as $test) {
            $requires = array_merge($requires, $test->requires());
        }

        return $requires;
    }

}
