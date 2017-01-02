<?php
/**
 * Copyright 2012-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * The Ingo_Script_Sieve_Action_Vacation class represents a vacation action.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
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
    public function generate()
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
            $code .= 'if ' . $this->_yearCheck($start_year + 1, $end_year - 1)
                . " {\n"
                . '    ' . $this->_vacationCode()
                . "\n    } elsif "
                . $this->_yearCheck($start_year, $start_year) . " {\n";
            $code .= '        if anyof ( ';
            if ($start_month < 12) {
                $code .= $this->_monthCheck($start_month + 1, 12)
                    . ",\n                   ";
            }
            $code .= 'allof ( '
                . $this->_monthCheck($start_month, $start_month) . ",\n"
                . '                           '
                . $this->_dayCheck($start_day, 31) . " ) ) {\n"
                . '        ' . $this->_vacationCode()
                . "\n        }\n    } elsif "
                . $this->_yearCheck($end_year, $end_year) . " {\n"
                . '        if anyof ( ';
            if ($end_month > 1) {
                $code .= $this->_monthCheck(1, $end_month - 1)
                    . ",\n                   ";
            }
            $code .= 'allof ( '
                . $this->_monthCheck($end_month, $end_month) . ",\n"
                . '                           '
                . $this->_dayCheck(1, $end_day) . " ) ) {\n"
                . '        ' . $this->_vacationCode()
                . "\n        }\n    }";
        } elseif ($end_year == $start_year + 1) {
            $code .= 'if allof ( '
                . $this->_yearCheck($start_year, $start_year) . ",\n"
                . '           anyof ( ';
            if ($start_month < 12) {
                $code .= $this->_monthCheck($start_month + 1, 12) . ",\n"
                    . '                   ';
            }
            $code .= 'allof ( '
                . $this->_monthCheck($start_month, $start_month) . ",\n"
                    . '                           '
                . $this->_dayCheck($start_day, 31) . " ) ) ) {\n"
                . '    ' . $this->_vacationCode()
                . "\n    } elsif allof ( "
                . $this->_yearCheck($end_year, $end_year) . ",\n"
                . '                    anyof ( ';
            if ($end_month > 1) {
                $code .= $this->_monthCheck(1, $end_month - 1) . ",\n"
                    . '                            ';
            }
            $code .= 'allof ( '
                . $this->_monthCheck($end_month, $end_month) . ",\n"
                . '                                    '
                . $this->_dayCheck(1, $end_day) . " ) ) ) {\n"
                . '    ' . $this->_vacationCode()
                . "\n    }";
        } elseif ($end_year == $start_year) {
            $code .= 'if ' . $this->_yearCheck($start_year, $start_year) . " {\n";
            if ($end_month > $start_month) {
                $code .= '        if anyof ( ';
                if ($end_month > $start_month + 1) {
                    $code .= $this->_monthCheck($start_month + 1, $end_month - 1)
                        . ",\n                   ";
                }
                $code .= 'allof ( '
                    . $this->_monthCheck($start_month, $start_month) . ",\n"
                    . '                           '
                    . $this->_dayCheck($start_day, 31) . " ),\n"
                    . '                   allof ( '
                    . $this->_monthCheck($end_month, $end_month) . ",\n"
                    . '                           '
                    . $this->_dayCheck(1, $end_day) . " ) ) {\n"
                    . '        '
                    . $this->_vacationCode()
                    . "\n        }\n";
            } elseif ($end_month == $start_month) {
                $code .= '        if allof ( '
                    . $this->_monthCheck($start_month, $start_month) . ",\n"
                    . '                   '
                    . $this->_dayCheck($start_day, $end_day) . " ) {\n"
                    . '        '
                    . $this->_vacationCode()
                    . "\n        }\n";
            }
            $code .= "    }";
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
        $code = '    vacation ';
        if (!empty($this->_vars['days'])) {
            $code .= ':days ' . $this->_vars['days'] . ' ';
        }
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
            $code .= ':subject "' . Horde_Mime::encode(Ingo_Script_Sieve::escapeString($this->_vars['subject'])) . '" ';
        }
        return $code
            . '"'
            . Ingo_Script_Sieve::escapeString(
                  Ingo_Script_Util::vacationReason(
                      $this->_vars['reason'],
                      $this->_vars['start'],
                      $this->_vars['end']
                  )
              )
            . '";';
    }

    /**
     */
    protected function _yearCheck($begin, $end)
    {
        $code = 'header :regex "Received" "^.*(' . $begin;
        for ($i = $begin + 1; $i <= $end; $i++) {
            $code .= '|' . $i;
        }
        return $code
            . ') (\\\\(.*\\\\) )?..:..:.. (\\\\(.*\\\\) )?((\\\\+|\\\\-)[[:digit:]]{4}|.{1,5})( \\\\(.*\\\\))?$"';
    }

    /**
     */
    protected function _monthCheck($begin, $end)
    {
        $months = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
        $code = 'header :regex "Received" "^.*(' . $months[$begin - 1];
        for ($i = $begin + 1; $i <= $end; $i++) {
            $code .= '|' . $months[$i - 1];
        }
        return $code
            . ') (\\\\(.*\\\\) )?.... (\\\\(.*\\\\) )?..:..:.. (\\\\(.*\\\\) )?((\\\\+|\\\\-)[[:digit:]]{4}|.{1,5})( \\\\(.*\\\\))?$"';
    }

    /**
     */
    protected function _dayCheck($begin, $end)
    {
        $code = 'header :regex "Received" "^.*(' . str_repeat('[0 ]', 2 - strlen($begin)) . $begin;
        for ($i = $begin + 1; $i <= $end; $i++) {
            $code .= '|' . str_repeat('[0 ]', 2 - strlen($i)) . $i;
        }
        return $code
            . ') (\\\\(.*\\\\) )?... (\\\\(.*\\\\) )?.... (\\\\(.*\\\\) )?..:..:.. (\\\\(.*\\\\) )?((\\\\+|\\\\-)[[:digit:]]{4}|.{1,5})( \\\\(.*\\\\))?$"';
    }
}
