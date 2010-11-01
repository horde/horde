<?php
/**
 * Utility functions for dealing with Microsoft ActiveSync's Timezone format.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 *
 * @category Horde
 * @package  Horde_ActiveSync
 */
class Horde_ActiveSync_Timezone
{

    /**
     * Convert a timezone from the ActiveSync base64 structure to a TZ offset
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
    static public function getOffsetsFromSyncTZ($data)
    {
        $tz = unpack('lbias/a64stdname/vstdyear/vstdmonth/vstdday/vstdweek/vstdhour/vstdminute/vstdsecond/vstdmillis/' .
                     'lstdbias/a64dstname/vdstyear/vdstmonth/vdstday/vdstweek/vdsthour/vdstminute/vdstsecond/vdstmillis/' .
                     'ldstbias', base64_decode($data));
        $tz['timezone'] = $tz['bias'];
        $tz['timezonedst'] = $tz['dstbias'];

        return $tz;
    }


    /**
     * Build an ActiveSync TZ blob given a TZ Offset hash.
     *
     * @param array $offsets  A TZ offset hash
     *
     * @return string  A base64_encoded ActiveSync Timezone structure suitable
     *                 for transmitting via wbxml.
     */
    static public function getSyncTZFromOffsets($offsets)
    {
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
     */
    static public function getOffsetsFromDate($date)
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
        list($std, $dst) = self::_getTransitions($timezone, $date);
        if ($std) {
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
     * @return array containing the the STD and DST transitions
     */
    static protected function _getTransitions($timezone, $date)
    {
        $std = $dst = null;
        // @TODO PHP 5.3 lets you specify a start and end timestamp, probably
        // should version sniff here for the improved performance. Just need
        // to remember that the first transition structure will then be for
        // the start date, so we should go back one year from $date, then ignore
        // the first entry.
        $transitions = $timezone->getTransitions();
        foreach ($transitions as $i => $transition) {
            $d = new Horde_Date($transition['time'], 'UTC');
            if ($d->format('Y') == $date->format('Y')) {
                if (isset($transitions[$i + 1])) {
                    $next = new Horde_Date($transitions[$i + 1]['ts']);
                    if ($d->format('Y') == $next->format('Y')) {
                        $dst = $transition['isdst'] ? $transition : $transitions[$i + 1];
                        $std = $transition['isdst'] ? $transitions[$i + 1] : $transition;
                    } else {
                        $dst = $transition['isdst'] ? $transition: null;
                        $std = $transition['isdst'] ? null : $transition;
                    }
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
	static protected function _generateOffsetsForTransition($offsets, $transition, $type)
	{
	    $transitionDate = new Horde_Date($transition['time'], 'UTC');
        $offsets[$type . 'month'] = $transitionDate->format('n');
        $offsets[$type . 'day'] = $transitionDate->format('w');
        $offsets[$type . 'minute'] = (int)$transitionDate->format('i');
        $offsets[$type . 'hour'] = $transitionDate->format('H');
        for ($i = 5; $i > 0; $i--) {
            $nth = clone($transitionDate);
            $nth->setNthWeekday($transitionDate->format('w'), $i);
            if ($transitionDate->compareDate($nth) == 0) {
                $offsets[$type . 'week'] = $i;
                break;
            };
        }

        return $offsets;
	}

}
