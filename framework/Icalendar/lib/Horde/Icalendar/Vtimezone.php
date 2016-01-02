<?php
/**
 * Class representing vTimezones.
 *
 * Copyright 2003-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Icalendar
 */
class Horde_Icalendar_Vtimezone extends Horde_Icalendar
{
    /**
     * The component type of this class.
     *
     * @var string
     */
    public $type = 'vTimeZone';

    /**
     * TODO
     *
     * @return TODO
     */
    public function exportvCalendar()
    {
        return $this->_exportvData('VTIMEZONE');
    }

    /**
     * Parses child components of vTimezone component.
     *
     * Returns an array with the exact time of the time change as well as the
     * 'from' and 'to' offsets around the change. Time is arbitrarily based on
     * UTC for comparison.
     *
     * @param Horde_Icalendar_Standard|Horde_Icalendar_Daylight $child
     *        A timezone component.
     * @param integer $year
     *        The latest year we are interested in.
     *
     * @return array  A list of hashes with "time", "from", and "to" elements.
     */
    public function parseChild($child, $year)
    {
        $result['time'] = $result['end'] = 0;

        try {
            $t = $child->getAttribute('TZOFFSETFROM');
        } catch (Horde_Icalendar_Exception $e) {
            return array();
        }
        $result['from'] = ($t['hour'] * 60 * 60 + $t['minute'] * 60) * ($t['ahead'] ? 1 : -1);

        try {
            $t = $child->getAttribute('TZOFFSETTO');
        } catch (Horde_Icalendar_Exception $e) {
            return array();
        }
        $result['to'] = ($t['hour'] * 60 * 60 + $t['minute'] * 60) * ($t['ahead'] ? 1 : -1);

        try {
            $start = $child->getAttribute('DTSTART');
        } catch (Horde_Icalendar_Exception $e) {
            return array();
        }
        if (!is_int($start)) {
            return array();
        }
        $start = getdate($start);
        if ($start['year'] > $year) {
            return array();
        }

        $results = array();
        try {
            $rdates = $child->getAttributeValues('RDATE');
            foreach ($rdates as $rdate) {
                if ($rdate['year'] == $year || $rdate['year'] == $year - 1) {
                    $result['time'] = $result['end'] = gmmktime(
                        $start['hours'], $start['minutes'], $start['seconds'],
                        $rdate['month'], $rdate['mday'], $rdate['year']
                    );
                    $results[] = $result;
                }
            }
        } catch (Horde_Icalendar_Exception $e) {
        }

        try {
            $rrules = $child->getAttribute('RRULE');
        } catch (Horde_Icalendar_Exception $e) {
            if (!$results) {
                $result['time'] = $result['end'] = $start[0];
                $results[] = $result;
            }
            return $results;
        }

        $rrules = explode(';', $rrules);
        foreach ($rrules as $rrule) {
            $t = explode('=', $rrule);
            switch ($t[0]) {
            case 'FREQ':
                if ($t[1] != 'YEARLY') {
                    return array();
                }
                break;

            case 'INTERVAL':
                if ($t[1] != '1') {
                    return array();
                }
                break;

            case 'BYMONTH':
                $month = intval($t[1]);
                break;

            case 'BYDAY':
                $len = strspn($t[1], '1234567890-+');
                if ($len == 0) {
                    return array();
                }
                $weekday = substr($t[1], $len);
                $weekdays = array(
                    'SU' => 0,
                    'MO' => 1,
                    'TU' => 2,
                    'WE' => 3,
                    'TH' => 4,
                    'FR' => 5,
                    'SA' => 6
                );
                $weekday = $weekdays[$weekday];
                $which = intval(substr($t[1], 0, $len));
                break;

            case 'UNTIL':
                $result['end'] = intval(substr($t[1], 0, 4));
                break;
            }
        }

        if (empty($month) || !isset($weekday)) {
            return array();
        }

        // Get the timestamp for the first day of $month.
        $when = gmmktime($start['hours'], $start['minutes'], $start['seconds'],
                         $month, 1, $year);
        // Get the day of the week for the first day of $month.
        $first_of_month_weekday = intval(gmstrftime('%w', $when));

        // Go to the first $weekday before first day of $month.
        if ($weekday >= $first_of_month_weekday) {
            $weekday -= 7;
        }
        $when -= ($first_of_month_weekday - $weekday) * 60 * 60 * 24;

        // If going backwards go to the first $weekday after last day
        // of $month.
        if ($which < 0) {
            do {
                $when += 60 * 60 * 24 * 7;
            } while (intval(gmstrftime('%m', $when)) == $month);
        }

        // Calculate $weekday number $which.
        $when += $which * 60 * 60 * 24 * 7;

        $result['time'] = $when;
        $results[] = $result;

        return $results;
    }

}
