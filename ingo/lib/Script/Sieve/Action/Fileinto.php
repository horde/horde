<?php
/**
 * The Ingo_Script_Sieve_Action_Fileinto class represents a fileinto action.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Ingo_Script_Sieve_Action_Fileinto extends Ingo_Script_Sieve_Action
{
    /**
     * Constructor.
     *
     * @param array $vars  Any required parameters.
     */
    public function __construct($vars = array())
    {
        $this->_vars['folder'] = isset($vars['folder'])
            ? $vars['folder']
            : '';

        if (!empty($vars['utf8'])) {
            $this->_vars['folder'] = Horde_String::convertCharset($this->_vars['folder'], 'UTF7-IMAP', 'UTF-8');
        }
    }

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    public function toCode()
    {
        return 'fileinto "' . Ingo_Script_Sieve::escapeString($this->_vars['folder']) . '";';
    }

    /**
     * Checks if the rule parameters are valid.
     *
     * @return boolean|string  True if this rule is valid, an error message
     *                         otherwise.
     */
    public function check()
    {
        return empty($this->_vars['folder'])
            ? _("Inexistant mailbox specified for message delivery.")
            : true;
    }

    /**
     * Returns a list of sieve extensions required for this rule and any
     * sub-rules.
     *
     * @return array  A Sieve extension list.
     */
    public function requires()
    {
        return array('fileinto');
    }

}
