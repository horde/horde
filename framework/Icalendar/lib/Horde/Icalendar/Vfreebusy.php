<?php
/**
 * Class representing vFreebusy components.
 *
 * Copyright 2003-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @todo Don't use timestamps
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Icalendar
 */
class Horde_Icalendar_Vfreebusy extends Horde_Icalendar
{
    /**
     * The component type of this class.
     *
     * @var string
     */
    public $type = 'vFreebusy';

    /**
     * TODO
     *
     * @var array
     */
    protected $_busyPeriods = array();

    /**
     * TODO
     *
     * @var array
     */
    protected $_extraParams = array();

    /**
     * Parses a string containing vFreebusy data.
     *
     * @param string $data     The data to parse.
     * @param $type TODO
     * @param $charset TODO
     */
    public function parsevCalendar($data, $type = null, $charset = null)
    {
        parent::parsevCalendar($data, 'VFREEBUSY', $charset);

        // Do something with all the busy periods.
        foreach ($this->_attributes as $key => $attribute) {
            if ($attribute['name'] != 'FREEBUSY') {
                continue;
            }
            foreach ($attribute['values'] as $value) {
                $params = isset($attribute['params'])
                    ? $attribute['params']
                    : array();
                if (isset($value['duration'])) {
                    $this->addBusyPeriod('BUSY', $value['start'], null,
                                         $value['duration'], $params);
                } else {
                    $this->addBusyPeriod('BUSY', $value['start'],
                                         $value['end'], null, $params);
                }
            }
            unset($this->_attributes[$key]);
        }
    }

    /**
     * Returns the component exported as string.
     *
     * @return string  The exported vFreeBusy information according to the
     *                 iCalendar format specification.
     */
    public function exportvCalendar()
    {
        foreach ($this->_busyPeriods as $start => $end) {
            $periods = array(array('start' => $start, 'end' => $end));
            $this->setAttribute('FREEBUSY', $periods,
                                isset($this->_extraParams[$start])
                                ? $this->_extraParams[$start] : array());
        }

        $res = $this->_exportvData('VFREEBUSY');

        foreach ($this->_attributes as $key => $attribute) {
            if ($attribute['name'] == 'FREEBUSY') {
                unset($this->_attributes[$key]);
            }
        }

        return $res;
    }

    /**
     * Returns a display name for this object.
     *
     * @return string  A clear text name for displaying this object.
     */
    public function getName()
    {
        $name = '';

        try {
            $method = !empty($this->_container)
                ? $this->_container->getAttribute('METHOD')
                : 'PUBLISH';
            if ($method == 'PUBLISH') {
                $attr = 'ORGANIZER';
            } elseif ($method == 'REPLY') {
                $attr = 'ATTENDEE';
            }
        } catch (Horde_Icalendar_Exception $e) {
            $attr = 'ORGANIZER';
        }

        try {
            $name = $this->getAttribute($attr, true);
            if (isset($name[0]['CN'])) {
                return $name[0]['CN'];
            }
        } catch (Horde_Icalendar_Exception $e) {}

        try {
            $name = parse_url($this->getAttribute($attr));
            return $name['path'];
        } catch (Horde_Icalendar_Exception $e) {
            return '';
        }
    }

    /**
     * Returns the email address for this object.
     *
     * @return string  The email address of this object's owner.
     */
    public function getEmail()
    {
        $name = '';

        try {
            $method = !empty($this->_container)
                ? $this->_container->getAttribute('METHOD')
                : 'PUBLISH';
            if ($method == 'PUBLISH') {
                $attr = 'ORGANIZER';
            } elseif ($method == 'REPLY') {
                $attr = 'ATTENDEE';
            }
        } catch (Horde_Icalendar_Exception $e) {
            $attr = 'ORGANIZER';
        }

        try {
            $name = parse_url($this->getAttribute($attr));
            return $name['path'];
        } catch (Horde_Icalendar_Exception $e) {
            return '';
        }
    }

    /**
     * Returns the busy periods.
     *
     * @return array  All busy periods.
     */
    public function getBusyPeriods()
    {
        return $this->_busyPeriods;
    }

    /**
     * Returns any additional freebusy parameters.
     *
     * @return array  Additional parameters of the freebusy periods.
     */
    public function getExtraParams()
    {
        return $this->_extraParams;
    }

    /**
     * Returns all the free periods of time in a given period.
     *
     * @param integer $startStamp  The start timestamp.
     * @param integer $endStamp    The end timestamp.
     *
     * @return array  A hash with free time periods, the start times as the
     *                keys and the end times as the values.
     */
    public function getFreePeriods($startStamp, $endStamp)
    {
        $this->simplify();
        $periods = array();

        // Check that we have data for some part of this period.
        if ($this->getEnd() < $startStamp || $this->getStart() > $endStamp) {
            return $periods;
        }

        // Locate the first time in the requested period we have data for.
        $nextstart = max($startStamp, $this->getStart());

        // Check each busy period and add free periods in between.
        foreach ($this->_busyPeriods as $start => $end) {
            if ($start <= $endStamp && $end >= $nextstart) {
                if ($nextstart <= $start) {
                    $periods[$nextstart] = min($start, $endStamp);
                }
                $nextstart = min($end, $endStamp);
            }
        }

        // If we didn't read the end of the requested period but still have
        // data then mark as free to the end of the period or available data.
        if ($nextstart < $endStamp && $nextstart < $this->getEnd()) {
            $periods[$nextstart] = min($this->getEnd(), $endStamp);
        }

        return $periods;
    }

    /**
     * Adds a busy period to the info.
     *
     * This function may throw away data in case you add a period with a start
     * date that already exists. The longer of the two periods will be chosen
     * (and all information associated with the shorter one will be removed).
     *
     * @param string $type       The type of the period. Either 'FREE' or
     *                           'BUSY'; only 'BUSY' supported at the moment.
     * @param integer $start     The start timestamp of the period.
     * @param integer $end       The end timestamp of the period.
     * @param integer $duration  The duration of the period. If specified, the
     *                           $end parameter will be ignored.
     * @param array   $extra     Additional parameters for this busy period.
     */
    public function addBusyPeriod($type, $start, $end = null, $duration = null,
                                  $extra = array())
    {
        if ($type == 'FREE') {
            // Make sure this period is not marked as busy.
            return false;
        }

        // Calculate the end time if duration was specified.
        $tempEnd = is_null($duration) ? $end : $start + $duration;

        // Make sure the period length is always positive.
        $end = max($start, $tempEnd);
        $start = min($start, $tempEnd);

        if (isset($this->_busyPeriods[$start])) {
            // Already a period starting at this time. Change the current
            // period only if the new one is longer. This might be a problem
            // if the callee assumes that there is no simplification going
            // on. But since the periods are stored using the start time of
            // the busy periods we have to throw away data here.
            if ($end > $this->_busyPeriods[$start]) {
                $this->_busyPeriods[$start] = $end;
                $this->_extraParams[$start] = $extra;
            }
        } else {
            // Add a new busy period.
            $this->_busyPeriods[$start] = $end;
            $this->_extraParams[$start] = $extra;
        }

        return true;
    }

    /**
     * Returns the timestamp of the start of the time period this free busy
     * information covers.
     *
     * @return integer  A timestamp.
     */
    public function getStart()
    {
        try {
            return $this->getAttribute('DTSTART');
        } catch (Horde_Icalendar_Exception $e) {
            return count($this->_busyPeriods)
                ? min(array_keys($this->_busyPeriods))
                : false;
        }
    }

    /**
     * Returns the timestamp of the end of the time period this free busy
     * information covers.
     *
     * @return integer  A timestamp.
     */
    public function getEnd()
    {
        try {
            return $this->getAttribute('DTEND');
        } catch (Horde_Icalendar_Exception $e) {
            return count($this->_busyPeriods)
                ? max(array_values($this->_busyPeriods))
                : false;
        }
    }

    /**
     * Merges the busy periods of another Horde_Icalendar_Vfreebusy object
     * into this one.
     *
     * This might lead to simplification no matter what you specify for the
     * "simplify" flag since periods with the same start date will lead to the
     * shorter period being removed (see addBusyPeriod).
     *
     * @param Horde_Icalendar_Vfreebusy $freebusy  A freebusy object.
     * @param boolean $simplify                    If true, simplify() will
     *                                             called after the merge.
     */
    public function merge(Horde_Icalendar_Vfreebusy $freebusy,
                          $simplify = true)
    {
        $extra = $freebusy->getExtraParams();
        foreach ($freebusy->getBusyPeriods() as $start => $end) {
            // This might simplify the busy periods without taking the
            // "simplify" flag into account.
            $this->addBusyPeriod('BUSY', $start, $end, null,
                                 isset($extra[$start])
                                 ? $extra[$start] : array());
        }

        foreach (array('DTSTART', 'DTEND') as $val) {
            try {
                $thisattr = $this->getAttribute($val);
            } catch (Horde_Icalendar_Exception $e) {
                $thisattr = null;
            }

            try {
                $thatattr = $freebusy->getAttribute($val);
            } catch (Horde_Icalendar_Exception $e) {
                $thatattr = null;
            }

            if (is_null($thisattr) && !is_null($thatattr)) {
                $this->setAttribute($val, $thatattr, array(), false);
            } elseif (!is_null($thatattr)) {
                switch ($val) {
                case 'DTSTART':
                    $set = ($thatattr < $thisattr);
                    break;

                case 'DTEND':
                    $set = ($thatattr > $thisattr);
                    break;
                }

                if ($set) {
                    $this->setAttribute($val, $thatattr, array(), false);
                }
            }
        }

        if ($simplify) {
            $this->simplify();
        }

        return true;
    }

    /**
     * Removes all overlaps and simplifies the busy periods array as much as
     * possible.
     */
    public function simplify()
    {
        $clean = false;
        $busy  = array($this->_busyPeriods, $this->_extraParams);
        while (!$clean) {
            $result = $this->_simplify($busy[0], $busy[1]);
            $clean = $result === $busy;
            $busy = $result;
        }

        ksort($result[1], SORT_NUMERIC);
        $this->_extraParams = $result[1];

        ksort($result[0], SORT_NUMERIC);
        $this->_busyPeriods = $result[0];
    }

    /**
     * TODO
     *
     * @param $busyPeriods TODO
     * @param array $extraParams TODO
     *
     * @return array TODO
     */
    protected function _simplify($busyPeriods, $extraParams = array())
    {
        $checked = $checkedExtra = array();
        $checkedEmpty = true;

        foreach ($busyPeriods as $start => $end) {
            if ($checkedEmpty) {
                $checked[$start] = $end;
                $checkedExtra[$start] = isset($extraParams[$start])
                    ? $extraParams[$start]
                    : array();
                $checkedEmpty = false;
            } else {
                $added = false;
                foreach ($checked as $testStart => $testEnd) {
                    // Replace old period if the new period lies around the
                    // old period.
                    if ($start <= $testStart && $end >= $testEnd) {
                        // Remove old period entry.
                        unset($checked[$testStart]);
                        unset($checkedExtra[$testStart]);
                        // Add replacing entry.
                        $checked[$start] = $end;
                        $checkedExtra[$start] = isset($extraParams[$start])
                            ? $extraParams[$start]
                            : array();
                        $added = true;
                    } elseif ($start >= $testStart && $end <= $testEnd) {
                        // The new period lies fully within the old
                        // period. Just forget about it.
                        $added = true;
                    } elseif (($end <= $testEnd && $end >= $testStart) ||
                              ($start >= $testStart && $start <= $testEnd)) {
                        // Now we are in trouble: Overlapping time periods. If
                        // we allow for additional parameters we cannot simply
                        // choose one of the two parameter sets. It's better
                        // to leave two separated time periods.
                        $extra = isset($extraParams[$start])
                            ? $extraParams[$start]
                            : array();
                        $testExtra = isset($checkedExtra[$testStart])
                            ? $checkedExtra[$testStart]
                            : array();
                        // Remove old period entry.
                        unset($checked[$testStart]);
                        unset($checkedExtra[$testStart]);
                        // We have two periods overlapping. Are their
                        // additional parameters the same or different?
                        $newStart = min($start, $testStart);
                        $newEnd = max($end, $testEnd);
                        if ($extra === $testExtra) {
                            // Both periods have the same information. So we
                            // can just merge.
                            $checked[$newStart] = $newEnd;
                            $checkedExtra[$newStart] = $extra;
                        } else {
                            // Extra parameters are different. Create one
                            // period at the beginning with the params of the
                            // first period and create a trailing period with
                            // the params of the second period. The break
                            // point will be the end of the first period.
                            $break = min($end, $testEnd);
                            $checked[$newStart] = $break;
                            $checkedExtra[$newStart] =
                                isset($extraParams[$newStart])
                                ? $extraParams[$newStart]
                                : array();
                            $checked[$break] = $newEnd;
                            $highStart = max($start, $testStart);
                            $checkedExtra[$break] =
                                isset($extraParams[$highStart])
                                ? $extraParams[$highStart]
                                : array();

                            // Ensure we also have the extra data in the
                            // extraParams.
                            $extraParams[$break] =
                                isset($extraParams[$highStart])
                                ? $extraParams[$highStart]
                                : array();
                        }
                        $added = true;
                    }

                    if ($added) {
                        break;
                    }
                }

                if (!$added) {
                    $checked[$start] = $end;
                    $checkedExtra[$start] = isset($extraParams[$start])
                        ? $extraParams[$start]
                        : array();
                }
            }
        }

        return array($checked, $checkedExtra);
    }

}
