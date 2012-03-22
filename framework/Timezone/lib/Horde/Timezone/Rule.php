<?php
/**
 * Class representing a set of "Rule" timezone database entries of the
 * same name.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Timezone
 */
class Horde_Timezone_Rule
{
    /**
     * A ruleset name.
     *
     * @var string
     */
    protected $_name;

    /**
     * All Rule lines for this ruleset.
     *
     * @var array
     */
    protected $_rules = array();

    /**
     * List to map weekday descriptions used in the timezone database.
     *
     * @var array
     */
    protected $_weekdays = array('Mon' => Horde_Date::DATE_MONDAY,
                                 'Tue' => Horde_Date::DATE_TUESDAY,
                                 'Wed' => Horde_Date::DATE_WEDNESDAY,
                                 'Thu' => Horde_Date::DATE_THURSDAY,
                                 'Fri' => Horde_Date::DATE_FRIDAY,
                                 'Sat' => Horde_Date::DATE_SATURDAY,
                                 'Sun' => Horde_Date::DATE_SUNDAY);

    /**
     * Constructor.
     *
     * @param string $name  A ruleset name.
     */
    public function __construct($name)
    {
        $this->_name = $name;
    }

    /**
     * Adds a Rule line to this ruleset.
     *
     * @param array $rule  A parsed Rule line.
     */
    public function add($rule)
    {
        $this->_rules[] = $rule;
    }

    /**
     * Adds rules from this ruleset to a VTIMEZONE component.
     *
     * @param Horde_Icalendar_Vtimezone $tz  A VTIMEZONE component.
     * @param string $tzid                   The timezone ID of the component.
     * @param string $name                   A timezone name abbreviation.
     *                                       May contain a placeholder that is
     *                                       replaced the Rules' "Letter(s)"
     *                                       entry.
     * @param array $startOffset             An offset hash describing the
     *                                       base offset of a timezone.
     * @param Horde_Date $start              Start of the period to add rules
     *                                       for.
     * @param Horde_Date $end                End of the period to add rules
     *                                       for.
     */
    public function addRules(Horde_Icalendar_Vtimezone $tz, $tzid, $name,
                             $startOffset,
                             Horde_Date $start, Horde_Date $end = null)
    {
        $offset = $startOffset;
        foreach ($this->_rules as $rule) {
            $year = $rule[3];
            if ($year[0] == 'o') {
                // TO is "only"
                $rule[3] = $rule[2];
            }
            if ($rule[3][0] != 'm' && $rule[3] < $start->year) {
                // TO is not maximum and is before the searched period
                continue;
            }
            if ($end &&
                $rule[2][0] != 'm' && $rule[2] > $end->year) {
                // FROM is not "minimum" and is after the searched period
                break;
            }
            if ($rule[8] == 0) {
                $component = new Horde_Icalendar_Standard();
                $component->setAttribute('TZOFFSETFROM', $offset);
                $component->setAttribute('TZOFFSETTO', $startOffset);
                $offset = $startOffset;
            } else {
                $component = new Horde_Icalendar_Daylight();
                $component->setAttribute('TZOFFSETFROM', $offset);
                $offset = $this->_getOffset($startOffset, $rule[8]);
                $component->setAttribute('TZOFFSETTO', $offset);
            }
            $month = Horde_Timezone::getMonth($rule[5]);
            preg_match('/(\d+)(?::(\d+))?(?::(\d+))?(w|s|u)?/', $rule[7], $match);
            if ($rule[2] == $rule[3] && preg_match('/^\d+$/', $rule[6])) {
                if (!isset($match[2])) {
                    $match[2] = 0;
                }
                $rdate = new Horde_Date(
                    array('year'  => $rule[2],
                          'month' => Horde_Timezone::getMonth($rule[5]),
                          'mday'  => $rule[6],
                          'hour'  => $match[1],
                          'min'   => $match[2],
                          'sec'   => 0));
                $component->setAttribute('DTSTART', $rdate);
            } elseif (substr($rule[6], 0, 4) == 'last') {
                $weekday = $this->_weekdays[substr($rule[6], 4, 3)];
                $last = new Horde_Date(
                    $rule[2],
                    $month,
                    Horde_Date_Utils::daysInMonth($month, $rule[2]));
                while ($last->dayOfWeek() != $weekday) {
                    $last->mday--;
                }
                $component->setAttribute('DTSTART', $last);
                if ($rule[3][0] == 'm') {
                    $until = '';
                } else {
                    $last = new Horde_Date(
                        array('year'  => $rule[3],
                              'month' => $month,
                              'mday'  => Horde_Date_Utils::daysInMonth($month, $rule[2]),
                              'hour'  => $match[1],
                              'min'   => $match[2],
                              'sec'   => 0),
                        $tzid);
                    while ($last->dayOfWeek() != $weekday) {
                        $last->mday--;
                    }
                    $last->setTimezone('UTC');
                    $until = ';UNTIL=' . $last->format('Ymd\THIs') . 'Z';
                }
                $component->setAttribute(
                    'RRULE',
                    'FREQ=YEARLY;BYDAY=-1'
                    . Horde_String::upper(substr($rule[6], 4, 2))
                    . ';BYMONTH=' . $month . $until);
            } elseif (strpos($rule[6], '>=')) {
                list($weekday, $day) = explode('>=', $rule[6]);
                $weekdayInt = $this->_weekdays[substr($weekday, 0, 3)];
                $first = new Horde_Date(
                    array('year'  => $rule[2],
                          'month' => $month,
                          'mday'  => $day));
                while ($first->dayOfWeek() != $weekdayInt) {
                    $first->mday++;
                }
                $component->setAttribute('DTSTART', $first);
                if ($rule[3][0] == 'm') {
                    $until = '';
                } else {
                    $last = new Horde_Date(
                        array('year'  => $rule[3],
                              'month' => $month,
                              'mday'  => $day,
                              'hour'  => $match[1],
                              'min'   => $match[2],
                              'sec'   => 0),
                        $tzid);
                    while ($last->dayOfWeek() != $weekday) {
                        $last->mday++;
                    }
                    $last->setTimezone('UTC');
                    $until = ';UNTIL=' . $last->format('Ymd\THIs') . 'Z';
                }
                for ($days = array(), $i = $day, $last = Horde_Date_Utils::daysInMonth($month, $rule[2]);
                     $i <= $last;
                     $i++) {
                    $days[] = $i;
                }
                $component->setAttribute(
                    'RRULE',
                    'FREQ=YEARLY;BYMONTH=' . $month
                    . ';BYMONTHDAY=' . implode(',', $days)
                    . ';BYDAY=1' . Horde_String::upper(substr($weekday, 0, 2))
                    . $until);
            } elseif (strpos($rule[6], '<=')) {
                list($weekday, $day) = explode('>=', $rule[6]);
                $weekdayInt = $this->_weekdays[substr($weekday, 0, 3)];
                $last = new Horde_Date(
                    array('year'  => $rule[2],
                          'month' => $month,
                          'mday'  => $day));
                while ($last->dayOfWeek() != $weekdayInt) {
                    $last->mday--;
                }
                $component->setAttribute('DTSTART', $last);
                if ($rule[3][0] == 'm') {
                    $until = '';
                } else {
                    $last = new Horde_Date(
                        array('year'  => $rule[3],
                              'month' => $month,
                              'mday'  => $day,
                              'hour'  => $match[1],
                              'min'   => $match[2],
                              'sec'   => 0),
                        $tzid);
                    while ($last->dayOfWeek() != $weekday) {
                        $last->mday--;
                    }
                    $last->setTimezone('UTC');
                    $until = ';UNTIL=' . $last->format('Ymd\THIs') . 'Z';
                }
                for ($days = array(), $i = 1; $i <= $day; $i++) {
                    $days[] = $i;
                }
                $component->setAttribute(
                    'RRULE',
                    'FREQ=YEARLY;BYMONTH=' . $month
                    . ';BYMONTHDAY=' . implode(',', $days)
                    . ';BYDAY=-1' . Horde_String::upper(substr($weekday, 0, 2))
                    . $until);
            }
            $component->setAttribute('TZNAME', sprintf($name, $rule[9]));
            $tz->addComponent($component);
        }
    }

    /**
     * Calculates the new offset of a timezone.
     *
     * @param array $start  A hash describing the original timezone offset.
     * @param string $new   A string describing the offset to be added to (or
     *                      subtracted from) the original offset.
     *
     * @return array  A hash describing the new timezone offset.
     */
    protected function _getOffset($start, $new)
    {
        $start = ($start['ahead'] ? 1 : -1) * (60 * $start['hour'] + $start['minute']);
        preg_match('/(-)?(\d+):(\d+)/', $new, $match);
        $start += ($match[1] == '-' ? -1 : 1) * (60 * $match[2] + $match[3]);
        $result = array('ahead' => $start > 0);
        $start = abs($start);
        $result['hour'] = floor($start / 60);
        $result['minute'] = $start % 60;
        return $result;
    }
}
