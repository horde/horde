<?php
/**
 * Horde Date wrapper/logic class, including some calculation
 * functions.
 *
 * @category Horde
 * @package  Horde_Date
 */


/**
 * @category Horde
 * @package  Horde_Date
 */
class Horde_Date_Utils
{
    /**
     * Returns whether a year is a leap year.
     *
     * @param integer $year  The year.
     *
     * @return boolean  True if the year is a leap year.
     */
    public static function isLeapYear($year)
    {
        if (strlen($year) != 4 || preg_match('/\D/', $year)) {
            return false;
        }

        return (($year % 4 == 0 && $year % 100 != 0) || $year % 400 == 0);
    }

    /**
     * Returns the date of the year that corresponds to the first day of the
     * given week.
     *
     * @param integer $week  The week of the year to find the first day of.
     * @param integer $year  The year to calculate for.
     *
     * @return Horde_Date  The date of the first day of the given week.
     */
    public static function firstDayOfWeek($week, $year)
    {
        return new Horde_Date(sprintf('%04dW%02d', $year, $week));
    }

    /**
     * Returns the number of days in the specified month.
     *
     * @param integer $month  The month
     * @param integer $year   The year.
     *
     * @return integer  The number of days in the month.
     */
    public static function daysInMonth($month, $year)
    {
        static $cache = array();
        if (!isset($cache[$year][$month])) {
            $date = new DateTime(sprintf('%04d-%02d-01', $year, $month));
            $cache[$year][$month] = $date->format('t');
        }
        return $cache[$year][$month];
    }

    /**
     * Returns a relative, natural language representation of a timestamp
     * TODO: Wider range of values ... maybe future time as well?
     *
     * @param integer $timestamp   The timestamp.
     * @param string $date_format  Format to display date if timestamp is more
     *                             then 1 day old.
     * @param string $time_format  Format to display time if timestamp is 1 day
     *                             old.
     *
     * @return string  The relative time (i.e. 2 minutes ago)
     */
    public static function relativeDateTime($timestamp, $date_format = '%x', $time_format = '%X')
    {
        $delta = time() - $timestamp;
        if ($delta < 60) {
            return sprintf(ngettext("%d second ago", "%d seconds ago", $delta), $delta);
        }
        $delta = round($delta / 60);
        if ($delta < 60) {
            return sprintf(ngettext("%d minute ago", "%d minutes ago", $delta), $delta);
        }

        $delta = round($delta / 60);
        if ($delta < 24) {
            return sprintf(ngettext("%d hour ago", "%d hours ago", $delta), $delta);
        }

        if ($delta > 24 && $delta < 48) {
            $date = new Horde_Date($timestamp);
            return sprintf(_("yesterday at %s"), $date->strftime($time_format));
        }

        // Default to the user specified date format.
        $date = new Horde_Date($timestamp);
        return $date->strftime($date_format);
    }

    /**
     * Tries to convert strftime() formatters to date() formatters.
     *
     * Unsupported formatters will be removed.
     *
     * @param string $format  A strftime() formatting string.
     *
     * @return string  A date() formatting string.
     */
    public static function strftime2date($format)
    {
        return preg_replace(array('/%a/',
                                  '/%A/',
                                  '/%d/',
                                  '/%e/',
                                  '/%j/',
                                  '/%u/',
                                  '/%w/',
                                  '/%U/',
                                  '/%V/',
                                  '/%W/',
                                  '/%b/',
                                  '/%B/',
                                  '/%h/',
                                  '/%m/',
                                  '/%C/',
                                  '/%g/',
                                  '/%G/',
                                  '/%y/',
                                  '/%Y/',
                                  '/%H/',
                                  '/%I/',
                                  '/%i/',
                                  '/%M/',
                                  '/%p/',
                                  '/%P/',
                                  '/%r/',
                                  '/%R/',
                                  '/%S/',
                                  '/%T/',
                                  '/%X/e',
                                  '/%z/',
                                  '/%Z/',
                                  '/%c/',
                                  '/%D/',
                                  '/%F/',
                                  '/%s/',
                                  '/%x/e',
                                  '/%n/',
                                  '/%t/',
                                  '/%%/'),
                           array('D',
                                 'l',
                                 'd',
                                 'j',
                                 'z',
                                 'N',
                                 'w',
                                 '',
                                 'W',
                                 '',
                                 'M',
                                 'F',
                                 'M',
                                 'm',
                                 '',
                                 '',
                                 'o',
                                 'y',
                                 'Y',
                                 'H',
                                 'h',
                                 'g',
                                 'i',
                                 'A',
                                 'a',
                                 'h:i:s A',
                                 'H:i',
                                 's',
                                 'H:i:s',
                                 'Horde_Date_Utils::strftime2date(Horde_Nls::getLangInfo(T_FMT))',
                                 'O',
                                 '',
                                 '',
                                 'm/d/y',
                                 'Y-m-d',
                                 'U',
                                 'Horde_Date_Utils::strftime2date(Horde_Nls::getLangInfo(D_FMT))',
                                 "\n",
                                 "\t",
                                 '%'),
                           $format);
    }

}
