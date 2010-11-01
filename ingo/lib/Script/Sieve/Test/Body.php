<?php
/**
 * The Ingo_Script_Sieve_Test_Body class represents a test on the contents of
 * the body in a message.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Michael Menge <michael.menge@zdv.uni-tuebingen.de>
 * @package Ingo
 */
class Ingo_Script_Sieve_Test_Body extends Ingo_Script_Sieve_Test
{
    /**
     * Constructor.
     *
     * @param array $vars  Any required parameters.
     */
    public function __construct($vars = array())
    {
        $this->_vars['comparator'] = isset($vars['comparator'])
            ? $vars['comparator']
            : 'i;ascii-casemap';
        $this->_vars['match-type'] = isset($vars['match-type'])
            ? $vars['match-type']
            : ':is';
        $this->_vars['strings'] = isset($vars['strings'])
            ? $vars['strings']
            : '';
    }

    /**
     * Checks if the rule parameters are valid.
     *
     * @return boolean|string  True if this rule is valid, an error message
     *                         otherwise.
     */
    public function check()
    {
        return preg_split('((?<!\\\)\,|\r\n|\n|\r)', $this->_vars['strings']);
    }

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    public function toCode()
    {
        $code = 'body ' .
            ':comparator "' . $this->_vars['comparator'] . '" ' .
            $this->_vars['match-type'] . ' ';

        $strings = preg_split('(\r\n|\n|\r)', $this->_vars['strings']);
        $strings = array_filter($strings);
        if (count($strings) > 1) {
            $code .= "[";
            $stringlist = '';
            foreach ($strings as $str) {
                $stringlist .= empty($stringlist) ? '"' : ', "';
                $stringlist .= Ingo_Script_Sieve::escapeString($str, $this->_vars['match-type'] == ':regex') . '"';
            }
            $code .= $stringlist . "] ";
        } elseif (count($strings) == 1) {
            $code .= '"' . Ingo_Script_Sieve::escapeString($strings[0], $this->_vars['match-type'] == ':regex') . '" ';
        } else {
            return _("No strings specified");
        }

        return $code;
    }

    /**
     * Returns a list of sieve extensions required for this rule and any
     * sub-rules.
     *
     * @return array  A Sieve extension list.
     */
    public function requires()
    {
        return ($this->_vars['match-type'] == ':regex')
            ? array('regex', 'body')
            : array('body');
    }

}
