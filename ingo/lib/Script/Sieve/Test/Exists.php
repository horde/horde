<?php
/**
 * The Ingo_Script_Sieve_Test_Exists class represents a test for the
 * existence of one or more headers in a message.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Ingo_Script_Sieve_Test_Exists extends Ingo_Script_Sieve_Test
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
        return preg_split('(\r\n|\n|\r)', $this->_vars['headers'])
            ? true
            : _("No headers specified");
    }

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    public function toCode()
    {
        $code = 'exists ';
        $headers = preg_split('(\r\n|\n|\r)', $this->_vars['headers']);
        if (count($headers) > 1) {
            $code .= "[";
            $headerstr = '';
            foreach ($headers as $header) {
                $headerstr .= (empty($headerstr) ? '"' : ', "') .
                    Ingo_Script_Sieve::escapeString($header) . '"';
            }
            $code .= $headerstr . "] ";
        } elseif (count($headers) == 1) {
            $code .= '"' . Ingo_Script_Sieve::escapeString($headers[0]) . '" ';
        } else {
            return "**error** No Headers Specified";
        }

        return $code;
    }

}
