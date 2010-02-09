<?php
/**
 * The Ingo_Script_Sieve_Action_Flag class is the base class for flag actions.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Ingo
 */
class Ingo_Script_Sieve_Action_Flag extends Ingo_Script_Sieve_Action
{
    /**
     * Constructor.
     *
     * @params array $vars  Any required parameters.
     */
    public function __construct($vars = array())
    {
        if (isset($vars['flags'])) {
            if ($vars['flags'] & Ingo_Storage::FLAG_ANSWERED) {
                $this->_vars['flags'][] = '\Answered';
            }
            if ($vars['flags'] & Ingo_Storage::FLAG_DELETED) {
                $this->_vars['flags'][] = '\Deleted';
            }
            if ($vars['flags'] & Ingo_Storage::FLAG_FLAGGED) {
                $this->_vars['flags'][] = '\Flagged';
            }
            if ($vars['flags'] & Ingo_Storage::FLAG_SEEN) {
                $this->_vars['flags'][] = '\Seen';
            }
        } else {
            $this->_vars['flags'] = '';
        }
    }

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @param string $mode  The sieve flag command to use. Either 'removeflag'
     *                      or 'addflag'.
     *
     * @return string  A Sieve script snippet.
     */
    public function _toCode($mode)
    {
        $code  = '';

        if (is_array($this->_vars['flags']) && !empty($this->_vars['flags'])) {
            $code .= $mode . ' ';
            if (count($this->_vars['flags']) > 1) {
                $stringlist = '';
                foreach ($this->_vars['flags'] as $flag) {
                    $flag = trim($flag);
                    if (!empty($flag)) {
                        $stringlist .= empty($stringlist) ? '"' : ', "';
                        $stringlist .= Ingo_Script_Sieve::escapeString($flag) . '"';
                    }
                }
                $stringlist = '[' . $stringlist . ']';
                $code .= $stringlist . ';';
            } else {
                $code .= '"' . Ingo_Script_Sieve::escapeString($this->_vars['flags'][0]) . '";';
            }
        }
        return $code;
    }

    /**
     * Checks if the rule parameters are valid.
     *
     * @return boolean|string  True if this rule is valid, an error message
     *                         otherwise.
     */
    public function check()
    {
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
        return array('imapflags');
    }

}
