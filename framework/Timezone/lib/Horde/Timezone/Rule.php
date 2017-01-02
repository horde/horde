<?php
/**
 * Class representing a set of "Rule" timezone database entries of the
 * same name.
 *
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
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
        foreach ($this->_rules as $ruleNo => $rule) {
            // The rule items are:
            // 0: "Rule"
            // 1: The rule name
            // 2: The start year or "minimum"
            // 3: The end year or "only" if only at the start year or "maximum"
            // 4: Type, whatever that means (unused)
            // 5: The start month
            // 6: The start day, either specific or as a rule
            // 7: The start time
            // 8: The offset to the base time
            // 9: The time name abbreviation
            $year = $rule[3];
            if ($year[0] == 'o') {
                // TO is "only"
                $rule[3] = $rule[2];
            }
            if ($rule[3][0] != 'm' && $rule[3] < $start->year) {
                // TO is not "maximum" and is before the searched period
                continue;
            }
            if ($end &&
                $rule[2][0] != 'm' && $rule[2] > $end->year) {
                // FROM is not "minimum" and is after the searched period
                break;
            }
            if ($rule[2][0] != 'm' && $rule[2] < $start->year) {
                $rule[2] = $start->year;
            }

            // The month of rule start.
            $month = Horde_Timezone::getMonth($rule[5]);

            // The time of rule start.
            preg_match('/(\d+)(?::(\d+))?(?::(\d+))?([wsguz])?/', $rule[7], $match);
            $hour = $match[1];
            $minute = isset($match[2]) ? $match[2] : 0;
            if (!isset($match[4])) {
                $modifier = 'w';
            } elseif ($match[4] == 'g' || $match[4] == 'z') {
                $modifier = 'u';
            } else {
                $modifier = $match[4];
            }

            // Find the start date.
            $first = $this->_getFirstMatch($rule, $rule[2]);
            $first->hour = $hour;
            $first->min = $minute;

            $previousOffset = $this->_findPreviousOffset(
                $first, $ruleNo, $startOffset
            );
            if ($rule[8] == 0) {
                $component = new Horde_Icalendar_Standard();
                $component->setAttribute('TZOFFSETFROM', $previousOffset);
                $component->setAttribute('TZOFFSETTO', $startOffset);
            } else {
                $component = new Horde_Icalendar_Daylight();
                $component->setAttribute('TZOFFSETFROM', $previousOffset);
                $component->setAttribute(
                    'TZOFFSETTO', $this->_getOffset($startOffset, $rule[8])
                );
            }
            switch ($modifier) {
            case 's':
                $first->hour += ($previousOffset['ahead'] ? 1 : -1) * $previousOffset['hour']
                    - ($startOffset['ahead'] ? 1 : -1) * $startOffset['hour'];
                $first->min += ($previousOffset['ahead'] ? 1 : -1) * $previousOffset['minute']
                    - ($startOffset['ahead'] ? 1 : -1) * $startOffset['minute'];
                break;
            case 'u':
                $first->hour += ($previousOffset['ahead'] ? 1 : -1) * $previousOffset['hour'];
                $first->min += ($previousOffset['ahead'] ? 1 : -1) * $previousOffset['minute'];
                break;
            }
            $component->setAttribute('DTSTART', $first);

            // Find the end date.
            if ($rule[3][0] == 'm') {
                $until = '';
            } else {
                $last = $this->_getFirstMatch($rule, $rule[3]);
                $last->hour = $hour;
                $last->min = $minute;
                switch ($modifier) {
                case 's':
                    $last->hour -= ($startOffset['ahead'] ? 1 : -1) * $startOffset['hour'];
                    $last->min -= ($startOffset['ahead'] ? 1 : -1) * $startOffset['minute'];
                    break;
                case 'w':
                    $last->hour -= ($previousOffset['ahead'] ? 1 : -1) * $previousOffset['hour'];
                    $last->min -= ($previousOffset['ahead'] ? 1 : -1) * $previousOffset['minute'];
                    break;
                }
                $until = ';UNTIL=' . $last->format('Ymd\THis') . 'Z';
            }

            if ($rule[2] != $rule[3]) {
                if (preg_match('/^\d+$/', $rule[6])) {
                    // Rule starts on a specific date.
                    $component->setAttribute(
                        'RRULE',
                        'FREQ=YEARLY;BYMONTH=' . $month
                        . ';BYMONTHDAY=' . $rule[6]
                        . $until);
                } elseif (substr($rule[6], 0, 4) == 'last') {
                    // Rule starts on the last of a certain weekday of the month.
                    $component->setAttribute(
                        'RRULE',
                        'FREQ=YEARLY;BYDAY=-1'
                        . Horde_String::upper(substr($rule[6], 4, 2))
                        . ';BYMONTH=' . $month . $until);
                } elseif (strpos($rule[6], '>=')) {
                    // Rule starts on a certain weekday after a certain day of
                    // month.
                    list($weekday, $day) = explode('>=', $rule[6]);
                    for ($days = array(), $i = $day, $lastDay = min(Horde_Date_Utils::daysInMonth($month, $rule[2]), $i + 6);
                         $day > 1 && $i <= $lastDay;
                         $i++) {
                        $days[] = $i;
                    }
                    $component->setAttribute(
                        'RRULE',
                        'FREQ=YEARLY;BYMONTH=' . $month
                        . ($days ? (';BYMONTHDAY=' . implode(',', $days)) : '')
                        . ';BYDAY=1' . Horde_String::upper(substr($weekday, 0, 2))
                        . $until);
                } elseif (strpos($rule[6], '<=')) {
                    // Rule starts on a certain weekday before a certain day of
                    // month.
                    for ($days = array(), $i = 1; $i <= $day; $i++) {
                        $days[] = $i;
                    }
                    $component->setAttribute(
                        'RRULE',
                        'FREQ=YEARLY;BYMONTH=' . $month
                        . ';BYMONTHDAY=' . implode(',', $days)
                        . ';BYDAY=-1' . Horde_String::upper(substr($weekday, 0, 2))
                        . $until);
                } else {
                    continue;
                }
            }
            $component->setAttribute('TZNAME', sprintf($name, $rule[9]));
            $tz->addComponent($component);
        }
    }

    /**
     * Finds a date matching a rule definition.
     *
     * @param array $rule      A rule definition hash from addRules().
     * @param integer $year    A year when the rule should be applied.
     *
     * @return Horde_Date  The first matching date.
     */
    protected function _getFirstMatch($rule, $year)
    {
        $month = Horde_Timezone::getMonth($rule[5]);

        if (preg_match('/^\d+$/', $rule[6])) {
            // Rule starts on a specific date.
            $date = new Horde_Date(array(
                'year'  => $year,
                'month' => $month,
                'mday'  => $rule[6],
            ));
        } elseif (substr($rule[6], 0, 4) == 'last') {
            // Rule starts on the last of a certain weekday of the month.
            $weekday = $this->_weekdays[substr($rule[6], 4, 3)];
            $date = new Horde_Date(array(
                'year'  => $year,
                'month' => $month,
                'mday'  => Horde_Date_Utils::daysInMonth($month, $rule[2]),
            ));
            while ($date->dayOfWeek() != $weekday) {
                $date->mday--;
            }
        } elseif (strpos($rule[6], '>=')) {
            // Rule starts on a certain weekday after a certain day of month.
            list($weekday, $day) = explode('>=', $rule[6]);
            $weekdayInt = $this->_weekdays[substr($weekday, 0, 3)];
            $date = new Horde_Date(array(
                'year'  => $year,
                'month' => $month,
                'mday'  => $day,
            ));
            while ($date->dayOfWeek() != $weekdayInt) {
                $date->mday++;
            }
        } elseif (strpos($rule[6], '<=')) {
            // Rule starts on a certain weekday before a certain day of month.
            list($weekday, $day) = explode('>=', $rule[6]);
            $weekdayInt = $this->_weekdays[substr($weekday, 0, 3)];
            $date = new Horde_Date(array(
                'year'  => $year,
                'month' => $month,
                'mday'  => $day,
            ));
            while ($date->dayOfWeek() != $weekdayInt) {
                $date->mday--;
            }
        } else {
            throw new Horde_Timezone_Exception('Cannot parse rule ' . $rule[6]);
        }

        return $date;
    }

    /**
     * Finds the offset of a previous rule.
     *
     * There may be different potential rules that are "before" the current
     * one, and there may even be two sequential daylight rules with different
     * offsets. Thus we go through all earlier rules (as in "before the current
     * rule in the rule definition", because the rules are ordered by start
     * date), and find the one that starts the closest to the start date of the
     * current rule.
     *
     * @param Horde_Date $date      The start date of the current rule.
     * @param integer $ruleNo       The rule number of the current rule.
     * @param integer $startOffset  The offset to use for the first rule, and
     *                              also the default.
     *
     * @return integer  The offset of the last rule before the current.
     */
    protected function _findPreviousOffset($date, $ruleNo, $startOffset)
    {
        // This is the default.
        $offset = $startOffset;

        if ($ruleNo == 0) {
            return $offset;
        }

        // Remember the closest found day and year. The year is faster to
        // calculate, only fall back to days if necessary. For years we compare
        // the end dates, to quickly rule out any rules that even ended earlier
        // than the currently closest match. For rules with end dates in the
        // future we have to go down to the level of days and calculate the
        // distance of the occurrence in the current year.
        $diff = $diffYear = null;
        for ($i = $ruleNo - 1; $i >= 0; $i--) {
            $end = $this->_rules[$i][3][0] == 'o'
                ? $this->_rules[$i][2]
                : $this->_rules[$i][3];
            if ($end[0] != 'm') {
                if (!is_null($diffYear) &&
                    ($date->year - $end) > $diffYear) {
                    // We already found a rule that ends closer (by year)
                    continue;
                }
                if (is_null($diffYear) ||
                    ($date->year - $end) < $diffYear) {
                    // This rule ends closer.
                    $diffYear = $date->year - $end;
                    $diff = $this->_getDiff($i, $date, min($date->year, $end));
                    $offset = $this->_rules[$i][8]
                        ? $this->_getOffset($startOffset, $this->_rules[$i][8])
                        : $startOffset;
                    continue;
                }
            }
            // On the same year or with an end in the future, now check days.
            $year = $date->year;
            if ($end[0] != 'm') {
                $year = min($year, $end);
            }
            $diffDays = $this->_getDiff($i, $date, $year);
            if (!is_null($diff) && $diffDays > $diff) {
                // We already found a rule that ends closer.
                continue;
            }
            if (is_null($diff) || $diffDays < $diff) {
                // This rule ends closer.
                $diffYear = $date->year - $year;
                $diff = $diffDays;
                $offset = $this->_rules[$i][8]
                    ? $this->_getOffset($startOffset, $this->_rules[$i][8])
                    : $startOffset;
            }
        }

        return $offset;
    }

    protected function _setTime($date, $hour, $minute, $modifier, $utc = false)
    {
    }

    /**
     * Helper method to calculate the difference in days between a date and the
     * occurence of rule.
     *
     * @param integer $ruleNo   A rule number.
     * @param Horde_Date $date  A date.
     * @param integer $year     A year.
     *
     * @return integer  The days between the date and the rule occurrence in
     *                  the year.
     */
    protected function _getDiff($ruleNo, $date, $year)
    {
        $ruleDate = $this->_getFirstMatch($this->_rules[$ruleNo], $year);
        if ($ruleDate->after($date)) {
            $ruleDate = $this->_getFirstMatch($this->_rules[$ruleNo], $year - 1);
        }
        return $date->diff($ruleDate);
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
