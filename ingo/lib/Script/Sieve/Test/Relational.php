<?php
/**
 * The Ingo_Script_Sieve_Test_Relational class represents a relational test.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Todd Merritt <tmerritt@email.arizona.edu>
 * @package Ingo
 */
class Ingo_Script_Sieve_Test_Relational extends Ingo_Script_Sieve_Test
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
        $this->_vars['headers'] = isset($vars['headers'])
            ? $vars['headers']
            : '';
        $this->_vars['value'] = isset($vars['value'])
            ? $vars['value']
            : 0;
    }

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    public function toCode()
    {
        $code = 'header :value "' .
            $this->_vars['comparison'] . '" ' .
            ':comparator "i;ascii-numeric" ';

        $headers = preg_split('(\r\n|\n|\r)', $this->_vars['headers']);
        $header_count = count($headers);

        if ($header_count > 1) {
            $code .= "[";
            $headerstr = '';

            foreach ($headers as $val) {
                $headerstr .= (empty($headerstr) ? '"' : ', "') .
                    Ingo_Script_Sieve::escapeString($val) . '"';
            }

            $code .= $headerstr . '] ';
            $headerstr = '[' . $headerstr . ']';
        } elseif ($header_count == 1) {
            $code .= '"' . Ingo_Script_Sieve::escapeString($headers[0]) . '" ';
            $headerstr = Ingo_Script_Sieve::escapeString($headers[0]);
        }

        $code .= '["' . $this->_vars['value'] . '"]';

        // Add workarounds for negative numbers - works only if the comparison
        // value is positive. Sieve doesn't support comparisons of negative
        // numbers at all so this is the best we can do.
        switch ($this->_vars['comparison']) {
        case 'gt':
        case 'ge':
        case 'eq':
            // Greater than, greater or equal, equal: number must be
            // non-negative.
            return 'allof ( not header :comparator "i;ascii-casemap" :contains "'
                . $headerstr . '" "-", ' . $code . ' )';

        case 'lt':
        case 'le':
        case 'ne':
            // Less than, less or equal, nonequal: also match negative numbers
            return 'anyof ( header :comparator "i;ascii-casemap" :contains "'
                . $headerstr . '" "-", ' . $code . ' )';
        }
    }

    /**
     * Checks if the rule parameters are valid.
     *
     * @return boolean|string  True if this rule is valid, an error message
     *                         otherwise.
     */
    public function check()
    {
        $headers = preg_split('(\r\n|\n|\r)', $this->_vars['headers']);
        return $headers ? true : _("No headers specified");
    }

    /**
     * Returns a list of sieve extensions required for this rule and any
     * sub-rules.
     *
     * @return array  A Sieve extension list.
     */
    public function requires()
    {
        return array('relational', 'comparator-i;ascii-numeric');
    }

}
