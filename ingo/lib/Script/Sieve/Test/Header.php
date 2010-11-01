<?php
/**
 * The Ingo_Script_Sieve_Test_Header class represents a test on the contents
 * of one or more headers in a message.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Ingo_Script_Sieve_Test_Header extends Ingo_Script_Sieve_Test
{
    /**
     * Constructor.
     *
     * @param array $vars  Any required parameters.
     */
    public function __construct($vars = array())
    {
        $this->_vars['headers'] = isset($vars['headers'])
            ? $vars['headers']
            : 'Subject';
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
        return preg_split('((?<!\\\)\,|\r\n|\n|\r)', $this->_vars['headers']) &&
               preg_split('((?<!\\\)\,|\r\n|\n|\r)', $this->_vars['strings']);
    }

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    public function toCode()
    {
        $code = 'header ' .
            ':comparator "' . $this->_vars['comparator'] . '" ' .
            $this->_vars['match-type'] . ' ';

        $headers = preg_split('(\r\n|\n|\r)', $this->_vars['headers']);
        $headers = array_filter($headers);
        if (count($headers) > 1) {
            $code .= "[";
            $headerstr = '';
            foreach ($headers as $header) {
                $headerstr .= empty($headerstr) ? '"' : ', "';
                $headerstr .= Ingo_Script_Sieve::escapeString($header, $this->_vars['match-type'] == ':regex') . '"';
            }
            $code .= $headerstr . "] ";
        } elseif (count($headers) == 1) {
            $code .= '"' . $headers[0] . '" ';
        } else {
            return _("No headers specified");
        }

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
            $code .= '"' . Ingo_Script_Sieve::escapeString(reset($strings), $this->_vars['match-type'] == ':regex') . '" ';
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
            ? array('regex')
            : array();
    }

}
