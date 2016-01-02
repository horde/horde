<?php
/**
 * Horde_Mapi_Util_Timezone::
 *
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @copyright 2009-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   Mapi_Utils
 */
/**
 * Utility functions for dealing with Microsoft MAPI Timezone format.
 *
 * Copyright 2009-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * Code dealing with searching for a timezone identifier from an AS timezone
 * blob inspired by code in the Tine20 Project (http://tine20.org).
 *
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @copyright 2009-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   Mapi_Utils
 */
class Horde_Mapi_Timezone
{
    /**
     * Date to use as start date when iterating through offsets looking for a
     * transition.
     *
     * @var Horde_Date
     */
    protected $_startDate;

    /**
     * Convert a timezone from the MAPI base64 structure to a TZ offset
     * hash.
     *
     * @param base64 encoded timezone structure defined by MS as:
     *  <pre>
     *      typedef struct TIME_ZONE_INFORMATION {
     *        LONG Bias;
     *        WCHAR StandardName[32];
     *        SYSTEMTIME StandardDate;
     *        LONG StandardBias;
     *        WCHAR DaylightName[32];
     *        SYSTEMTIME DaylightDate;
     *        LONG DaylightBias;};
     *  </pre>
     *
     *  With the SYSTEMTIME format being:
     *  <pre>
     * typedef struct _SYSTEMTIME {
     *     WORD wYear;
     *     WORD wMonth;
     *     WORD wDayOfWeek;
     *     WORD wDay;
     *     WORD wHour;
     *     WORD wMinute;
     *     WORD wSecond;
     *     WORD wMilliseconds;
     *   } SYSTEMTIME, *PSYSTEMTIME;
     *  </pre>
     *
     *  See: http://msdn.microsoft.com/en-us/library/ms724950%28VS.85%29.aspx
     *  and: http://msdn.microsoft.com/en-us/library/ms725481%28VS.85%29.aspx
     *
     * @return array  Hash of offset information
     */
    public static function getOffsetsFromSyncTZ($data)
    {
        if (version_compare(PHP_VERSION, '5.5', '>=')) {
            $format = 'lbias/Z64stdname/vstdyear/vstdmonth/vstdday/vstdweek/vstdhour/vstdminute/vstdsecond/vstdmillis/'
                . 'lstdbias/Z64dstname/vdstyear/vdstmonth/vdstday/vdstweek/vdsthour/vdstminute/vdstsecond/vdstmillis/'
                . 'ldstbias';
        } else {
            $format = 'lbias/a64stdname/vstdyear/vstdmonth/vstdday/vstdweek/vstdhour/vstdminute/vstdsecond/vstdmillis/'
                . 'lstdbias/a64dstname/vdstyear/vdstmonth/vdstday/vdstweek/vdsthour/vdstminute/vdstsecond/vdstmillis/'
                . 'ldstbias';
        }
        $tz = unpack($format, base64_decode($data));
        $tz['timezone'] = $tz['bias'];
        $tz['timezonedst'] = $tz['dstbias'];

        if (!Horde_Mapi::isLittleEndian()) {
            $tz['bias'] = Horde_Mapi::chbo($tz['bias']);
            $tz['stdbias'] = Horde_Mapi::chbo($tz['stdbias']);
            $tz['dstbias'] = Horde_Mapi::chbo($tz['dstbias']);
        }

        return $tz;
    }

    /**
     * Build an MAPI TZ blob given a TZ Offset hash.
     *
     * @param array $offsets  A TZ offset hash
     *
     * @return string  A base64_encoded MAPI Timezone structure suitable
     *                 for transmitting via wbxml.
     */
    public static function getSyncTZFromOffsets(array $offsets)
    {
        if (!Horde_Mapi::isLittleEndian()) {
            $offsets['bias'] = Horde_Mapi::chbo($offsets['bias']);
            $offsets['stdbias'] = Horde_Mapi::chbo($offsets['stdbias']);
            $offsets['dstbias'] = Horde_Mapi::chbo($offsets['dstbias']);
        }

        $packed = pack('la64vvvvvvvvla64vvvvvvvvl',
                $offsets['bias'], '', 0, $offsets['stdmonth'], $offsets['stdday'], $offsets['stdweek'], $offsets['stdhour'], $offsets['stdminute'], $offsets['stdsecond'], $offsets['stdmillis'],
                $offsets['stdbias'], '', 0, $offsets['dstmonth'], $offsets['dstday'], $offsets['dstweek'], $offsets['dsthour'], $offsets['dstminute'], $offsets['dstsecond'], $offsets['dstmillis'],
                $offsets['dstbias']);

        return base64_encode($packed);
    }

    /**
     * Create a offset hash suitable for use in ActiveSync transactions
     *
     * @param Horde_Date $date  A date object representing the date to base the
     *                          the tz data on.
     *
     * @return array  An offset hash.
     */
    public static function getOffsetsFromDate(Horde_Date $date)
    {
        $offsets = array(
            'bias' => 0,
            'stdname' => '',
            'stdyear' => 0,
            'stdmonth' => 0,
            'stdday' => 0,
            'stdweek' => 0,
            'stdhour' => 0,
            'stdminute' => 0,
            'stdsecond' => 0,
            'stdmillis' => 0,
            'stdbias' => 0,
            'dstname' => '',
            'dstyear' => 0,
            'dstmonth' => 0,
            'dstday' => 0,
            'dstweek' => 0,
            'dsthour' => 0,
            'dstminute' => 0,
            'dstsecond' => 0,
            'dstmillis' => 0,
            'dstbias' => 0
        );

        $timezone = $date->toDateTime()->getTimezone();
        // If transition parsing failed, we won't have a multi-element array.
        $transitions = self::_getTransitions($timezone, $date);
        if (!empty($transitions)) {
            list($std, $dst) = self::_getTransitions($timezone, $date);
        }
        if (!empty($std)) {
            $offsets['bias'] = $std['offset'] / 60 * -1;
            if ($dst) {
                $offsets = self::_generateOffsetsForTransition($offsets, $std, 'std');
                $offsets = self::_generateOffsetsForTransition($offsets, $dst, 'dst');
                $offsets['stdhour'] += $dst['offset'] / 3600;
                $offsets['dsthour'] += $std['offset'] / 3600;
                $offsets['dstbias'] = ($dst['offset'] - $std['offset']) / 60 * -1;
            }
        }

        return $offsets;
    }

    /**
     * Get the transition data for moving from DST to STD time.
     *
     * @param DateTimeZone $timezone  The timezone to get the transition for
     * @param Horde_Date $date        The date to start from. Really only the
     *                                year we are interested in is needed.
     *
     * @return array  An array containing the the STD and DST transitions
     */
    protected static function _getTransitions(DateTimeZone $timezone, Horde_Date $date)
    {

        $std = $dst = array();
        $transitions = $timezone->getTransitions(
            mktime(0, 0, 0, 12, 1, $date->year - 1),
            mktime(24, 0, 0, 12, 31, $date->year)
        );

        if ($transitions === false) {
            return array();
        }

        foreach ($transitions as $i => $transition) {
            try {
               $d = new Horde_Date($transition['time']);
               $d->setTimezone('UTC');
            } catch (Exception $e) {
                continue;
            }
            if (($d->format('Y') == $date->format('Y')) && isset($transitions[$i + 1])) {
                $next = new Horde_Date($transitions[$i + 1]['ts']);
                if ($d->format('Y') == $next->format('Y')) {
                    $dst = $transition['isdst'] ? $transition : $transitions[$i + 1];
                    $std = $transition['isdst'] ? $transitions[$i + 1] : $transition;
                } else {
                    $dst = $transition['isdst'] ? $transition: null;
                    $std = $transition['isdst'] ? null : $transition;
                }
                break;
            } elseif ($i == count($transitions) - 1) {
                $std = $transition;
            }
        }

        return array($std, $dst);
    }

    /**
     * Calculate the offsets for the specified transition
     *
     * @param array $offsets      A TZ offset hash
     * @param array $transition   A transition hash
     * @param string $type        Transition type - dst or std
     *
     * @return array  A populated offset hash
     */
    protected static function _generateOffsetsForTransition(array $offsets, array $transition, $type)
    {
        // We can't use Horde_Date directly here, since it is unable to
        // properly convert to UTC from local ON the exact hour of a std -> dst
        // transition. This is due to a conversion to DateTime in the localtime
        // zone internally before the timezone change is applied
        $transitionDate = new DateTime($transition['time']);
        $transitionDate->setTimezone(new DateTimeZone('UTC'));
        $transitionDate = new Horde_Date($transitionDate);
        $offsets[$type . 'month'] = $transitionDate->format('n');
        $offsets[$type . 'day'] = $transitionDate->format('w');
        $offsets[$type . 'minute'] = (int)$transitionDate->format('i');
        $offsets[$type . 'hour'] = (int)$transitionDate->format('H');
        for ($i = 5; $i > 0; $i--) {
            if (self::_isNthOcurrenceOfWeekdayInMonth($transition['ts'], $i)) {
                $offsets[$type . 'week'] = $i;
                break;
            }
        }

        return $offsets;
    }

    /**
     * Attempt to guess the timezone identifier from the $offsets array.
     *
     * @param array|string $offsets     The timezone to check. Either an array
     *                                  of offsets or an activesynz tz blob.
     * @param string $expectedTimezone  The expected timezone. If not empty, and
     *                                  present in the results, will return.
     *
     * @return string  The timezone identifier
     */
    public function getTimezone($offsets, $expectedTimezone = null)
    {
        $timezones = $this->getListOfTimezones($offsets, $expectedTimezone);
        if (isset($timezones[$expectedTimezone])) {
            return $expectedTimezone;
        } else {
            return current($timezones);
        }
    }

    /**
     * Get the list of timezone identifiers that match the given offsets, having
     * a preference for $expectedTimezone if it's present in the results.
     *
     * @param array|string $offsets     Either an offset array, or a AS timezone
     *                                  structure.
     * @param string $expectedTimezone  The expected timezone.
     *
     * @return array  An array of timezone identifiers
     */
    public function getListOfTimezones($offsets, $expectedTimezone = null)
    {
        if (is_string($offsets)) {
            $offsets = self::getOffsetsFromSyncTZ($offsets);
        }
        $this->_setDefaultStartDate($offsets);
        $timezones = array();
        foreach (DateTimeZone::listIdentifiers() as $timezoneIdentifier) {
            $timezone = new DateTimeZone($timezoneIdentifier);
            if (false !== ($matchingTransition = $this->_checkTimezone($timezone, $offsets))) {
                if ($timezoneIdentifier == $expectedTimezone) {
                    $timezones = array($timezoneIdentifier => $matchingTransition['abbr']);
                    break;
                } else {
                    $timezones[$timezoneIdentifier] = $matchingTransition['abbr'];
                }
            }
        }

        if (empty($timezones)) {
           throw new Horde_Mapi_Exception('No timezone found for the given offsets');
        }

        return $timezones;
    }

    /**
     * Set default value for $_startDate.
     *
     * Tries to guess the correct startDate depending on object property falls
     * back to current date.
     *
     * @param array $offsets  Offsets may be avaluated for a given start year
     */
    protected function _setDefaultStartDate(array $offsets = null)
    {
        if (!empty($this->_startDate)) {
            return;
        }

        if (!empty($offsets['stdyear'])) {
            $this->_startDate = new Horde_Date($offsets['stdyear'] . '-01-01');
        } else {
            $start = new Horde_Date(time());
            $start->year--;
            $this->_startDate = $start;
        }
    }

    /**
     * Check if the given timezone matches the offsets and also evaluate the
     * daylight saving time transitions for this timezone if necessary.
     *
     * @param DateTimeZone $timezone  The timezone to check.
     * @param array $offsets          The offsets to check.
     *
     * @return array|boolean  An array of transition data or false if timezone
     *                        does not match offset.
     */
    protected function _checkTimezone(DateTimeZone $timezone, array $offsets)
    {
        list($std, $dst) = $this->_getTransitions($timezone, $this->_startDate);
        if ($this->_checkTransition($std, $dst, $offsets)) {
            return $std;
        }

        return false;
    }

    /**
     * Check if the given standardTransition and daylightTransition match to the
     * given offsets.
     *
     * @param array $std      The Standard transition date.
     * @param array $dst      The DST transition date.
     * @param array $offsets  The offsets to check.
     *
     * @return boolean
     */
    protected function _checkTransition(array $std, array $dst, array $offsets)
    {
        if (empty($std) || empty($offsets)) {
            return false;
        }

        $standardOffset = ($offsets['bias'] + $offsets['stdbias']) * 60 * -1;

        // check each condition in a single if statement and break the chain
        // when one condition is not met - for performance reasons
        if ($standardOffset == $std['offset']) {
            if ((empty($offsets['dstmonth']) && (empty($dst) || empty($dst['isdst']))) ||
                (empty($dst) && !empty($offsets['dstmonth']))) {
                // Offset contains DST, but no dst to compare
                return true;
            }
            $daylightOffset = ($offsets['bias'] + $offsets['dstbias']) * 60 * -1;
            // the milestone is sending a positive value for daylightBias while it should send a negative value
            $daylightOffsetMilestone = ($offsets['dstbias'] + ($offsets['dstbias'] * -1) ) * 60 * -1;

            if ($daylightOffset == $dst['offset'] || $daylightOffsetMilestone == $dst['offset']) {
                $standardParsed = new DateTime($std['time']);
                $daylightParsed = new DateTime($dst['time']);

                if ($standardParsed->format('n') == $offsets['stdmonth'] &&
                    $daylightParsed->format('n') == $offsets['dstmonth'] &&
                    $standardParsed->format('w') == $offsets['stdday'] &&
                    $daylightParsed->format('w') == $offsets['dstday'])
                {
                    return self::_isNthOcurrenceOfWeekdayInMonth($dst['ts'], $offsets['dstweek']) &&
                           self::_isNthOcurrenceOfWeekdayInMonth($std['ts'], $offsets['stdweek']);
                }
            }
        }

        return false;
    }

    /**
     * Test if the weekday of the given timestamp is the nth occurence of this
     * weekday within its month, where '5' indicates the last occurrence even if
     * there is less than five occurrences.
     *
     * @param integer $timestamp  The timestamp to check.
     * @param integer $occurence  1 to 5, where 5 indicates the final occurrence
     *                            during the month if that day of the week does
     *                            not occur 5 times
     * @return boolean
     */
    protected static function _isNthOcurrenceOfWeekdayInMonth($timestamp, $occurence)
    {
        $original = new Horde_Date($timestamp);
        $original->setTimezone('UTC');
        if ($occurence == 5) {
            $modified = $original->add(array('mday' => 7));
            return $modified->month > $original->month;
        } else {
            $modified = $original->sub(array('mday' => 7 * $occurence));
            $modified2 = $original->sub(array('mday' => 7 * ($occurence - 1)));

            return $modified->month < $original->month &&
                   $modified2->month == $original->month;
       }
    }

}
