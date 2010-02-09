<?php
/**
 * The Ingo_Script_Sieve_Test_Address class represents a test on parts or all
 * of the addresses in the given fields.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Ingo_Script_Sieve_Test_Address extends Ingo_Script_Sieve_Test
{
    /**
     * Constructor.
     *
     * @param array $vars  Any required parameters.
     */
    public function __construct($vars)
    {
        $this->_vars['headers'] = isset($vars['headers'])
            ? $vars['headers']
            : '';
        $this->_vars['comparator'] = isset($vars['comparator'])
            ? $vars['comparator']
            : 'i;ascii-casemap';
        $this->_vars['match-type'] = isset($vars['match-type'])
            ? $vars['match-type']
            : ':is';
        $this->_vars['address-part'] = isset($vars['address-part'])
            ? $vars['address-part']
            : ':all';
        $this->_vars['addresses'] = isset($vars['addresses'])
            ? $vars['addresses']
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
        return preg_split('(\r\n|\n|\r)', $this->_vars['headers']) &&
               preg_split('(\r\n|\n|\r)', $this->_vars['addresses']);
    }

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    public function toCode()
    {
        $code = 'address ' .
            $this->_vars['address-part'] . ' ' .
            ':comparator "' . $this->_vars['comparator'] . '" ' .
            $this->_vars['match-type'] . ' ';

        $headers = preg_split('(\r\n|\n|\r|,)', $this->_vars['headers']);
        $headers = array_filter($headers);
        if (count($headers) > 1) {
            $code .= "[";
            $headerstr = '';
            foreach ($headers as $header) {
                $header = trim($header);
                if (!empty($header)) {
                    $headerstr .= empty($headerstr) ? '"' : ', "';
                    $headerstr .= Ingo_Script_Sieve::escapeString($header, $this->_vars['match-type'] == ':regex') . '"';
                }
            }
            $code .= $headerstr . "] ";
        } elseif (count($headers) == 1) {
            $code .= '"' . Ingo_Script_Sieve::escapeString($headers[0], $this->_vars['match-type'] == ':regex') . '" ';
        } else {
            return "No Headers Specified";
        }

        $addresses = preg_split('(\r\n|\n|\r)', $this->_vars['addresses']);
        $addresses = array_filter($addresses);
        if (count($addresses) > 1) {
            $code .= "[";
            $addressstr = '';
            foreach ($addresses as $addr) {
                $addr = trim($addr);
                if (!empty($addr)) {
                    $addressstr .= empty($addressstr) ? '"' : ', "';
                    $addressstr .= Ingo_Script_Sieve::escapeString($addr, $this->_vars['match-type'] == ':regex') . '"';
                }
            }
            $code .= $addressstr . "] ";
        } elseif (count($addresses) == 1) {
            $code .= '"' . Ingo_Script_Sieve::escapeString($addresses[0], $this->_vars['match-type'] == ':regex') . '" ';
        } else {
            return "No Addresses Specified";
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
