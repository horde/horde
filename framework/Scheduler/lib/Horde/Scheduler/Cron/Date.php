<?php
/**
 * @author  Ryan Flynn <ryan@ryanflynn.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Scheduler
 */
class Horde_Scheduler_Cron_Date
{
    public $legalDays = array('MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN');

    public $sec;
    public $min;
    public $hour;
    public $day;
    public $month;

    public function __construct($raw)
    {
        $this->parse(Horde_String::upper($raw));
    }

    public function nowMatches()
    {
        return $this->scheduledAt(time());
    }

    public function scheduledAt($ts = null)
    {
        if ($ts === null) {
            $ts = time();
        }
        return ($this->monthMatches($ts) &&
                $this->monthMatches($ts) &&
                $this->dayMatches($ts) &&
                $this->hourMatches($ts) &&
                $this->minMatches($ts) &&
                $this->secMatches($ts));
    }

    public function monthMatches($ts)
    {
        if ($this->month == '*') {
            return true;
        }

        $currentmonth = '-' . date('n', $ts) . '-';

        return (bool)strpos($this->month, $currentmonth);
    }

    public function dayMatches($ts)
    {
        if (!empty($this->day['value']) && $this->day['value'] == '*') {
            return true;
        }

        $currentdaynum = '-' . date('j', $ts) . '-';
        $currentdaytxt = Horde_String::upper(date('D'));

        foreach ($this->day as $day) {
            if (@strpos($day['not'], $currentdaytxt) === false) {
                $v1 = (@strpos($day['value'], $currentdaynum) !== false);
                $v2 = (@strpos($day['and'], $currentdaytxt) !== false);

                if (!empty($day['and']) && ($v1 && $v2)) {
                    return true;
                } elseif (empty($day['and']) && $v1) {
                    return true;
                }
            }
        }

        return false;
    }

    public function hourMatches($ts)
    {
        if ($this->hour == '*') {
            return true;
        }

        $currenthour = '-' . date('G', $ts) . '-';

        return (strpos($this->hour, $currenthour) !== false);
    }

    public function minMatches($ts)
    {
        if ($this->min == '*') {
            return true;
        }

        $currentmin = '-' . intval(date('i', $ts)) . '-';

        return (strpos($this->min, $currentmin) !== false);
    }

    public function secMatches($ts)
    {
        if ($this->sec == '*') {
            return true;
        }

        $currentsec = '-' . intval(date('s', $ts)) . '-';

        return (strpos($this->sec, $currentsec) !== false);
    }

    public function parse($str)
    {
        $s = array();

        list($s['sec'], $s['min'], $s['hour'], $s['day'], $s['month']) = preg_split('|[\n\t ]+|', $str);

        foreach ($s as $k => $v) {
            if (strpos($v, '*') !== false) {
                $s[$k] = array('*');
            } elseif (!$this->generallyDecentSyntax($v)) {
                die("Illegal syntax in '$v'\n");
            } else {
                $s[$k] = explode(',', $s[$k]);
            }
        }

        if ($s['sec'][0] == '*') {
            $this->sec = '*';
        } else {
            for ($i = 0; $i < sizeof($s['sec']); $i++) {
                if ($this->isRange($s['sec'][$i])) {
                    $s['sec'][$i] = $this->expandRange($this->rangeVals($s['sec'][$i]));
                }
            }
            $this->sec = '-' . join('-', $s['sec']) . '-';
        }

        if ($s['min'][0] == '*') {
            $this->min = '*';
        } else {
            for ($i = 0; $i < sizeof($s['min']); $i++) {
                if ($this->isRange($s['min'][$i])) {
                    $s['min'][$i] = $this->expandRange($this->rangeVals($s['min'][$i]));
                }
            }
            $this->min = '-' . join('-', $s['min']) . '-';
        }

        if ($s['hour'][0] == '*') {
            $this->hour = '*';
        } else {
            for ($i = 0; $i < sizeof($s['hour']); $i++) {
                if ($this->isRange($s['hour'][$i])) {
                    $s['hour'][$i] = $this->expandRange($this->rangeVals($s['hour'][$i]));
                }
            }
            $this->hour = '-' . join('-', $s['hour']) . '-';
        }

        if ($s['day'][0] == '*') {
            $this->day = '*';
        } else {
            for ($i = 0; $i < sizeof($s['day']); $i++) {
                $tmp = array();
                if (($char = $this->isCond($s['day'][$i])) !== false) {
                    if ($char == '&') {
                        list($tmp['value'], $tmp['and']) = explode($char, $s['day'][$i]);
                        if ($this->isRange($tmp['and'])) {
                            $tmp['and'] = $this->expandRange($this->rangeVals($tmp['and']));
                        }
                    } else {
                        list($tmp['value'], $tmp['not']) = explode($char, $s['day'][$i]);
                        if ($this->isRange($tmp['not'])) {
                            $tmp['not'] = $this->expandRange($this->rangeVals($tmp['not']));
                        }
                    }
                } else {
                    $tmp = array('value' => $s['day'][$i]);
                }

                $s['day'][$i] = $tmp;

                if ($this->isRange($s['day'][$i]['value'])) {
                    $s['day'][$i]['value'] = $this->expandRange($this->rangeVals($s['day'][$i]['value']));
                }
            }

            $this->day = $s['day'];
        }

        if ($s['month'][0] == '*') {
            $this->month = '*';
        } else {
            for ($i = 0; $i < sizeof($s['month']); $i++) {
                if ($this->isRange($s['month'][$i])) {
                    $s['month'][$i] = $this->expandRange($this->rangeVals($s['month'][$i]));
                }
            }
            $this->month = '-' . join('-', $s['month']) . '-';
        }
    }

    public function isCond($s)
    {
        if (strpos($s, '&') !== false) {
            return '&';
        } elseif (strpos($s, '!') !== false) {
            return '!';
        } else {
            return false;
        }
    }

    public function isRange($s)
    {
        return preg_match('/^\w+\-\w+/', $s);
    }

    public function isCondRange($s)
    {
        return ($this->isCond($s) && $this->isRange($s));
    }

    public function isCondVal($s)
    {
        return ($this->isCond($s) && !$this->isRange($s));
    }

    public function rangeVals($s)
    {
        return explode('-', $s);
    }

    public function expandRange($l, $h = '')
    {
        // Expand range from 1-5 -> '-1-2-3-4-5-'.
        if (is_array($l)) {
            $h = $l[1];
            $l = $l[0];
        }

        if ($this->isDigit($l)) {
            if (!$this->isDigit($h)) {
                die("Invalid value '$h' in range '$l-$h'");
            }

            // Currently there is no possible reason to need to do a
            // range beyond 0-59 for anything.
            if ($l < 0) {
                $l = 0;
            } elseif ($l > 59) {
                $l = 59;
            }

            if ($h < 0) {
                $h = 0;
            } elseif ($h > 59) {
                $h = 59;
            }

            if ($l > $h) {
                $tmp = $l;
                $l = $h;
                $h = $tmp;
                unset($tmp);
            }

            // For some reason range() doesn't work w/o the explicit
            // intval() calls.
            return '-' . join('-', range(intval($l), intval($h))) . '-';
        } else {
            // Invalid.
            die("Invalid value '$l' in range '$l-$h'");
        }
    }

    public function dayValue($s)
    {
        for ($i = 0; $i < count($this->legalDays); $i++) {
            if ($this->legalDays[$i] == $s) {
                return $i;
            }
        }

        return -1;
    }

    public function isDigit($s)
    {
        return preg_match('/^\d+$/', $s);
    }

    public function isAlpha($s)
    {
        return $this->isLegalDay($s);
    }

    public function isLegalDay($s)
    {
        return in_array($s, $this->legalDays);
    }

    public function generallyDecentSyntax($s)
    {
        return ($s == '*' ||
                preg_match('/^\d+(-\d+)?([!&][A-Z\*]+(-[A-Z\*]+)?)?(,\d+(-\d+)?([!&][A-Z\*]+(-[A-Z\*]+)?)?)*$/', $s));
    }

}
