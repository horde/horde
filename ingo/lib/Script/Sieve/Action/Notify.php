<?php
/**
 * The Ingo_Script_Sieve_Action_Notify class represents a notify action.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Paul Wolstenholme <wolstena@sfu.ca>
 * @package Ingo
 */
class Ingo_Script_Sieve_Action_Notify extends Ingo_Script_Sieve_Action
{
    /**
     * Constructor.
     *
     * @param array $vars  Any required parameters.
     */
    public function __construct($vars = array())
    {
        $this->_vars['address'] = isset($vars['address'])
            ? $vars['address']
            : '';
        $this->_vars['name'] = isset($vars['name'])
            ? $vars['name']
            : '';
    }

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    public function toCode()
    {
        return 'notify :method "mailto" :options "' .
            Ingo_Script_Sieve::escapeString($this->_vars['address']) .
            '" :message "' .
            _("You have received a new message") . "\n" .
            _("From:") . " \$from\$ \n" .
            _("Subject:") . " \$subject\$ \n" .
            _("Rule:") . ' ' . $this->_vars['name'] . '";';
    }

    /**
     * Checks if the rule parameters are valid.
     *
     * @return boolean|string  True if this rule is valid, an error message
     *                         otherwise.
     */
    public function check()
    {
        return empty($this->_vars['address'])
            ? _("Missing address to notify")
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
        return array('notify');
    }

}
