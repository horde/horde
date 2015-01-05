<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Paul Wolstenholme <wolstena@sfu.ca>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * The Ingo_Script_Sieve_Action_Notify class represents a notify action.
 *
 * It supports both enotify (RFC 5435) and the older, deprecated notify
 * (draft-martin-sieve-notify-01) capabilities.
 *
 * @author   Paul Wolstenholme <wolstena@sfu.ca>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Script_Sieve_Action_Notify extends Ingo_Script_Sieve_Action
{
    /**
     * Constructor.
     *
     * @param array $vars  Required parameters:
     *   - address: (string) Address.
     *   - name: (string) Name.
     *   - notify: (boolean) If set, use notify instead of enotify.
     *
     */
    public function __construct($vars = array())
    {
        $this->_vars['address'] = isset($vars['address'])
            ? $vars['address']
            : '';
        $this->_vars['name'] = isset($vars['name'])
            ? $vars['name']
            : '';
        $this->_vars['notify'] = !empty($vars['notify']);
    }

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    public function generate()
    {
        $addr = Ingo_Script_Sieve::escapeString($this->_vars['address']);

        if ($this->_vars['notify']) {
            return 'notify :method "mailto" :options "' . $addr .
                '" :message "' ._("You have received a new message") . "\n" .
                    _("From:") . " \$from\$ \n" .
                    _("Subject:") . " \$subject\$ \n" .
                    _("Rule:") . ' ' . $this->_vars['name'] . '";';
        }

        // RFC 5436 defines mailto: behavior. Use the default
        // server-defined notification message.
        return 'notify "mailto:' . $addr . '";';
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
        return $this->_vars['notify']
            ? array('notify')
            : array('enotify');
    }
}
