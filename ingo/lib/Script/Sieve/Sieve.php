<?php
/**
 * The Ingo_Script_Sieve_Action class represents an action in a Sieve script.
 *
 * An action is anything that has a side effect eg: discard, redirect.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
/**
 * The Sieve_Action_Redirect class represents a redirect action.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Sieve_Action_Redirect extends Sieve_Action {

    /**
     * Constructor.
     *
     * @param array $vars  Any required parameters.
     */
    function Sieve_Action_Redirect($vars = array())
    {
        $this->_vars['address'] = (isset($vars['address'])) ? $vars['address'] : '';
    }

    function toCode($depth = 0)
    {
        return str_repeat(' ', $depth * 4) . 'redirect ' .
            '"' . Ingo_Script_Sieve::escapeString($this->_vars['address']) . '";';
    }

    /**
     * Checks if the rule parameters are valid.
     *
     * @return boolean|string  True if this rule is valid, an error message
     *                         otherwise.
     */
    function check()
    {
        if (empty($this->_vars['address'])) {
            return _("Missing address to redirect message to");
        }

        return true;
    }

}

/**
 * The Sieve_Action_Reject class represents a reject action.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Sieve_Action_Reject extends Sieve_Action {

    /**
     * Constructor.
     *
     * @param array $vars  Any required parameters.
     */
    function Sieve_Action_Reject($vars = array())
    {
        $this->_vars['reason'] = (isset($vars['reason'])) ? $vars['reason'] : '';
    }

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    function toCode()
    {
        return 'reject "' . Ingo_Script_Sieve::escapeString($this->_vars['reason']) . '";';
    }

    /**
     * Checks if the rule parameters are valid.
     *
     * @return boolean|string  True if this rule is valid, an error message
     *                         otherwise.
     */
    function check()
    {
        if (empty($this->_vars['reason'])) {
            return _("Missing reason for reject");
        }

        return true;
    }

    /**
     * Returns a list of sieve extensions required for this rule and any
     * sub-rules.
     *
     * @return array  A Sieve extension list.
     */
    function requires()
    {
        return array('reject');
    }

}

/**
 * The Sieve_Action_Keep class represents a keep action.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Sieve_Action_Keep extends Sieve_Action {

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    function toCode()
    {
        return 'keep;';
    }

    /**
     * Checks if the rule parameters are valid.
     *
     * @return boolean|string  True if this rule is valid, an error message
     *                         otherwise.
     */
    function check()
    {
        return true;
    }

}

/**
 * The Sieve_Action_Discard class represents a discard action.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Sieve_Action_Discard extends Sieve_Action {

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    function toCode()
    {
        return 'discard;';
    }

    /**
     * Checks if the rule parameters are valid.
     *
     * @return boolean|string  True if this rule is valid, an error message
     *                         otherwise.
     */
    function check()
    {
        return true;
    }

}

/**
 * The Sieve_Action_Stop class represents a stop action.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Sieve_Action_Stop extends Sieve_Action {

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    function toCode()
    {
        return 'stop;';
    }

    /**
     * Checks if the rule parameters are valid.
     *
     * @return boolean|string  True if this rule is valid, an error message
     *                         otherwise.
     */
    function check()
    {
        return true;
    }

}

/**
 * The Sieve_Action_Fileinto class represents a fileinto action.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Sieve_Action_Fileinto extends Sieve_Action {

    /**
     * Constructor.
     *
     * @param array $vars  Any required parameters.
     */
    function Sieve_Action_Fileinto($vars = array())
    {
        $this->_vars['folder'] = (isset($vars['folder'])) ? $vars['folder'] : '';
        if (!empty($vars['utf8'])) {
            $this->_vars['folder'] = String::convertCharset($this->_vars['folder'], 'UTF7-IMAP', 'UTF-8');
        }
    }

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    function toCode()
    {
        return 'fileinto "' . Ingo_Script_Sieve::escapeString($this->_vars['folder']) . '";';
    }

    /**
     * Checks if the rule parameters are valid.
     *
     * @return boolean|string  True if this rule is valid, an error message
     *                         otherwise.
     */
    function check()
    {
        if (empty($this->_vars['folder'])) {
            return _("Inexistant mailbox specified for message delivery.");
        }

        return true;
    }

    /**
     * Returns a list of sieve extensions required for this rule and any
     * sub-rules.
     *
     * @return array  A Sieve extension list.
     */
    function requires()
    {
        return array('fileinto');
    }

}

/**
 * The Sieve_Action_Vacation class represents a vacation action.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Sieve_Action_Vacation extends Sieve_Action {

    /**
     * Constructor.
     *
     * @param array $vars  Any required parameters.
     */
    function Sieve_Action_Vacation($vars = array())
    {
        $this->_vars['days'] = isset($vars['days']) ? intval($vars['days']) : '';
        $this->_vars['addresses'] = isset($vars['addresses']) ? $vars['addresses'] : '';
        $this->_vars['subject'] = isset($vars['subject']) ? $vars['subject'] : '';
        $this->_vars['reason'] = isset($vars['reason']) ? $vars['reason'] : '';
        $this->_vars['start'] = isset($vars['start']) ? $vars['start'] : '';
        $this->_vars['start_year'] = isset($vars['start_year']) ? $vars['start_year'] : '';
        $this->_vars['start_month'] = isset($vars['start_month']) ? $vars['start_month'] : '';
        $this->_vars['start_day'] = isset($vars['start_day']) ? $vars['start_day'] : '';
        $this->_vars['end'] = isset($vars['end']) ? $vars['end'] : '';
        $this->_vars['end_year'] = isset($vars['end_year']) ? $vars['end_year'] : '';
        $this->_vars['end_month'] = isset($vars['end_month']) ? $vars['end_month'] : '';
        $this->_vars['end_day'] = isset($vars['end_day']) ? $vars['end_day'] : '';
    }

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    function toCode()
    {
        $start_year = $this->_vars['start_year'];
        $start_month = $this->_vars['start_month'];
        $start_day = $this->_vars['start_day'];

        $end_year = $this->_vars['end_year'];
        $end_month = $this->_vars['end_month'];
        $end_day = $this->_vars['end_day'];

        $code = '';

        if (empty($this->_vars['start']) || empty($this->_vars['end'])) {
            return $this->_vacationCode();
        } elseif ($end_year > $start_year + 1) {
            $code .= $this->_yearCheck($start_year + 1, $end_year - 1)
                . $this->_vacationCode()
                . "\n}\n"
                . $this->_yearCheck($start_year, $start_year);
            if ($start_month < 12) {
                $code .= $this->_monthCheck($start_month + 1, 12)
                    . $this->_vacationCode()
                    . "\n}\n";
            }
            $code .= $this->_monthCheck($start_month, $start_month)
                . $this->_dayCheck($start_day, 31)
                . $this->_vacationCode()
                . "\n}\n}\n}\n"
                . $this->_yearCheck($end_year, $end_year);
            if ($end_month > 1) {
                $code .= $this->_monthCheck(1, $end_month - 1)
                    . $this->_vacationCode()
                    . "\n}\n";
            }
            $code .= $this->_monthCheck($end_month, $end_month)
                . $this->_dayCheck(1, $end_day)
                . $this->_vacationCode()
                . "\n}\n}\n}\n";
        } elseif ($end_year == $start_year + 1) {
            $code .= $this->_yearCheck($start_year, $start_year);
            if ($start_month < 12) {
                $code .= $this->_monthCheck($start_month + 1, 12)
                    . $this->_vacationCode()
                    . "\n}\n";
            }
            $code .= $this->_monthCheck($start_month, $start_month)
                . $this->_dayCheck($start_day, 31)
                . $this->_vacationCode()
                . "\n}\n}\n}\n"
                . $this->_yearCheck($end_year, $end_year);
            if ($end_month > 1) {
                $code .= $this->_monthCheck(1, $end_month - 1)
                    . $this->_vacationCode()
                    . "\n}\n";
            }
            $code .= $this->_monthCheck($end_month, $end_month)
                . $this->_dayCheck(1, $end_day)
                . $this->_vacationCode()
                . "\n}\n}\n}\n";
        } elseif ($end_year == $start_year) {
            $code .= $this->_yearCheck($start_year, $start_year);
            if ($end_month > $start_month) {
                if ($end_month > $start_month + 1) {
                    $code .= $this->_monthCheck($start_month + 1, $end_month - 1)
                        . $this->_vacationCode()
                        . "\n}\n";
                }
                $code .= $this->_monthCheck($start_month, $start_month)
                    . $this->_dayCheck($start_day, 31)
                    . $this->_vacationCode()
                    . "\n}\n}\n"
                    . $this->_monthCheck($end_month, $end_month)
                    . $this->_dayCheck(1, $end_day)
                    . $this->_vacationCode()
                    . "\n}\n}\n";
            } elseif ($end_month == $start_month) {
                $code .= $this->_monthCheck($start_month, $start_month)
                    . $this->_dayCheck($start_day, $end_day)
                    . $this->_vacationCode()
                    . "\n}\n}\n";
            }
            $code .= "}\n";
        }

        return $code;
    }

    /**
     * Checks if the rule parameters are valid.
     *
     * @return boolean|string  True if this rule is valid, an error message
     *                         otherwise.
     */
    function check()
    {
        if (empty($this->_vars['reason'])) {
            return _("Missing reason in vacation.");
        }

        return true;
    }

    /**
     * Returns a list of sieve extensions required for this rule and any
     * sub-rules.
     *
     * @return array  A Sieve extension list.
     */
    function requires()
    {
        return array('vacation', 'regex');
    }

    /**
     */
    function _vacationCode()
    {
        $code = 'vacation :days ' . $this->_vars['days'] . ' ';
        $addresses = $this->_vars['addresses'];
        $stringlist = '';
        if (count($addresses) > 1) {
            foreach ($addresses as $address) {
                $address = trim($address);
                if (!empty($address)) {
                    $stringlist .= empty($stringlist) ? '"' : ', "';
                    $stringlist .= Ingo_Script_Sieve::escapeString($address) . '"';
                }
            }
            $stringlist = "[" . $stringlist . "] ";
        } elseif (count($addresses) == 1) {
            $stringlist = '"' . Ingo_Script_Sieve::escapeString($addresses[0]) . '" ';
        }

        if (!empty($stringlist)) {
            $code .= ':addresses ' . $stringlist;
        }

        if (!empty($this->_vars['subject'])) {
            $code .= ':subject "' . Horde_Mime::encode(Ingo_Script_Sieve::escapeString($this->_vars['subject']), 'UTF-8') . '" ';
        }
        return $code
            . '"' . Ingo_Script_Sieve::escapeString($this->_vars['reason'])
            . '";';
    }

    /**
     */
    function _yearCheck($begin, $end)
    {
        $code = 'if header :regex "Received" "^.*(' . $begin;
        for ($i = $begin + 1; $i <= $end; $i++) {
            $code .= '|' . $i;
        }
        return $code
            . ') (\\\\(.*\\\\) )?..:..:.. (\\\\(.*\\\\) )?((\\\\+|\\\\-)[[:digit:]]{4}|.{1,5})( \\\\(.*\\\\))?$" {'
            . "\n    ";
    }

    /**
     */
    function _monthCheck($begin, $end)
    {
        $months = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
        $code = 'if header :regex "Received" "^.*(' . $months[$begin - 1];
        for ($i = $begin + 1; $i <= $end; $i++) {
            $code .= '|' . $months[$i - 1];
        }
        return $code
            . ') (\\\\(.*\\\\) )?.... (\\\\(.*\\\\) )?..:..:.. (\\\\(.*\\\\) )?((\\\\+|\\\\-)[[:digit:]]{4}|.{1,5})( \\\\(.*\\\\))?$" {'
            . "\n    ";
    }

    /**
     */
    function _dayCheck($begin, $end)
    {
        $code = 'if header :regex "Received" "^.*(' . str_repeat('[0 ]', 2 - strlen($begin)) . $begin;
        for ($i = $begin + 1; $i <= $end; $i++) {
            $code .= '|' . str_repeat('[0 ]', 2 - strlen($i)) . $i;
        }
        return $code
            . ') (\\\\(.*\\\\) )?... (\\\\(.*\\\\) )?.... (\\\\(.*\\\\) )?..:..:.. (\\\\(.*\\\\) )?((\\\\+|\\\\-)[[:digit:]]{4}|.{1,5})( \\\\(.*\\\\))?$" {'
            . "\n    ";
    }

}

/**
 * The Sieve_Action_Flag class is the base class for flag actions.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Ingo
 */
class Sieve_Action_Flag extends Sieve_Action {

    /**
     * Constructor.
     *
     * @params array $vars  Any required parameters.
     */
    function Sieve_Action_Flag($vars = array())
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
    function _toCode($mode)
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
    function check()
    {
        return true;
    }

    /**
     * Returns a list of sieve extensions required for this rule and any
     * sub-rules.
     *
     * @return array  A Sieve extension list.
     */
    function requires()
    {
        return array('imapflags');
    }

}

/**
 * The Sieve_Action_Addflag class represents an add flag action.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Sieve_Action_Addflag extends Sieve_Action_Flag {

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    function toCode()
    {
        return $this->_toCode('addflag');
    }

}

/**
 * The Sieve_Action_Removeflag class represents a remove flag action.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Sieve_Action_Removeflag extends Sieve_Action_Flag {

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    function toCode()
    {
        return $this->_toCode('removeflag');
    }

}

/**
 * The Sieve_Action_Notify class represents a notify action.
 *
 * @author  Paul Wolstenholme <wolstena@sfu.ca>
 * @package Ingo
 */
class Sieve_Action_Notify extends Sieve_Action {

    /**
     * Constructor.
     *
     * @param array $vars  Any required parameters.
     */
    function Sieve_Action_Notify($vars = array())
    {
        $this->_vars['address'] = isset($vars['address']) ? $vars['address'] : '';
        $this->_vars['name'] = isset($vars['name']) ? $vars['name'] : '';
    }

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    function toCode()
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
    function check()
    {
        if (empty($this->_vars['address'])) {
            return _("Missing address to notify");
        }

        return true;
    }

    /**
     * Returns a list of sieve extensions required for this rule and any
     * sub-rules.
     *
     * @return array  A Sieve extension list.
     */
    function requires()
    {
        return array('notify');
    }

}
