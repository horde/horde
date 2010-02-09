<?php
/**
 * The Ingo_Script_Sieve_Action_Vacation class represents a vacation action.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Ingo
 */
class Ingo_Script_Sieve_Action_Vacation extends Ingo_Script_Sieve_Action
{
    /**
     * Constructor.
     *
     * @param array $vars  Any required parameters.
     */
    public function __construct($vars = array())
    {
        $this->_vars = array_merge(array(
            'days' => '',
            'addresses' => '',
            'subject' => '',
            'reason' => '',
            'start' => '',
            'start_year' => '',
            'start_month' => '',
            'start_day' => '',
            'end' => '',
            'end_year' => '',
            'end_month' => '',
            'end_day' => ''
        ), $vars);
    }

    /**
     * Returns a script snippet representing this rule and any sub-rules.
     *
     * @return string  A Sieve script snippet.
     */
    public function toCode()
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
    public function check()
    {
        return empty($this->_vars['reason'])
            ? _("Missing reason in vacation.")
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
        return array('vacation', 'regex');
    }

    /**
     */
    protected function _vacationCode()
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
    protected function _yearCheck($begin, $end)
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
    protected function _monthCheck($begin, $end)
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
    protected function _dayCheck($begin, $end)
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
