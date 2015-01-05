<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * The Ingo_Script_Sieve_Action_Flag class is the base class for flag actions.
 * It supports both imap4flags (RFC 5232) and the older, deprecated imapflags
 * (draft-melnikov-sieve-imapflags-03) capabilities.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
abstract class Ingo_Script_Sieve_Action_Flag extends Ingo_Script_Sieve_Action
{
    /**
     * Constructor.
     *
     * @params array $vars  Required parameters:
     *   - flags: (integer) The mask of flags to set.
     *   - imapflags: (boolean) If set, use imapflags instead of imap4flags.
     */
    public function __construct($vars = array())
    {
        $this->_vars['flags'] = array();

        if (isset($vars['flags'])) {
            $flag_map = array(
                Ingo_Storage::FLAG_ANSWERED => '\Answered',
                Ingo_Storage::FLAG_DELETED => '\Deleted',
                Ingo_Storage::FLAG_FLAGGED => '\Flagged',
                Ingo_Storage::FLAG_SEEN => '\Seen'
            );

            foreach ($flag_map as $key => $val) {
                if ($vars['flags'] & $key) {
                    $this->_vars['flags'][] = $val;
                }
            }
        }

        $this->_vars['imapflags'] = !empty($vars['imapflags']);
    }

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @param string $mode  The sieve flag command to use. Either:
     *  - addflag
     *  - removeflag
     *
     * @return string  A Sieve script snippet.
     */
    protected function _generate($mode)
    {
        if (empty($this->_vars['flags'])) {
            return '';
        }

        $flist = array();
        foreach ($this->_vars['flags'] as $flag) {
            $flist[] = '"' . Ingo_Script_Sieve::escapeString($flag) . '"';
        }

        /* Use string list since it is supported by both imap4flags and
         * imapflags. */
        return $mode . ' [' . implode(', ', $flist) . '];';
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
        return $this->_vars['imapflags']
            ? array('imapflags')
            : array('imap4flags');
    }

}
