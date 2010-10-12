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
     * @param integer $timestamp       The timestamp.
     * @param string $date_format      Format to display date if timestamp is
     *                                 more then 1 day old.
     * @param string $time_format      Format to display time if timestamp is 1
     *                                 day old.
     * @param Horde_Translation $dict  A translation handler implementing
     *                                 Horde_Translation.
     *
     * @return string  The relative time (i.e. 2 minutes ago)
     */
    public static function relativeDateTime($timestamp, $date_format = '%x',
                                            $time_format = '%X', $dict = null)
    {
        if (!$dict) {
            $dict = new Horde_Translation_Gettext('Horde_Date', dirname(__FILE__) . '/../../../locale');
        }

        $delta = time() - $timestamp;
        if ($delta < 60) {
            return sprintf($dict->n("%d second ago", "%d seconds ago", $delta), $delta);
        }
        $delta = round($delta / 60);
        if ($delta < 60) {
            return sprintf($dict->n("%d minute ago", "%d minutes ago", $delta), $delta);
        }

        $delta = round($delta / 60);
        if ($delta < 24) {
            return sprintf($dict->n("%d hour ago", "%d hours ago", $delta), $delta);
        }

        if ($delta > 24 && $delta < 48) {
            $date = new Horde_Date($timestamp);
            return sprintf($dict->t("yesterday at %s"), $date->strftime($time_format));
        }

        $delta = round($delta / 24);
        if ($delta < 7) {
            return sprintf($dict->t("%d days ago"), $delta);
        }

        if (round($delta / 7) < 5) {
            $delta = round($delta / 7);
            return sprintf($dict->n("%d week ago", "%d weeks ago", $delta), $delta);
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
        $replace = array(
            '/%a/'  => 'D',
            '/%A/'  => 'l',
            '/%d/'  => 'd',
            '/%e/'  => 'j',
            '/%j/'  => 'z',
            '/%u/'  => 'N',
            '/%w/'  => 'w',
            '/%U/'  => '',
            '/%V/'  => 'W',
            '/%W/'  => '',
            '/%b/'  => 'M',
            '/%B/'  => 'F',
            '/%h/'  => 'M',
            '/%m/'  => 'm',
            '/%C/'  => '',
            '/%g/'  => '',
            '/%G/'  => 'o',
            '/%y/'  => 'y',
            '/%Y/'  => 'Y',
            '/%H/'  => 'H',
            '/%I/'  => 'h',
            '/%i/'  => 'g',
            '/%M/'  => 'i',
            '/%p/'  => 'A',
            '/%P/'  => 'a',
            '/%r/'  => 'h:i:s A',
            '/%R/'  => 'H:i',
            '/%S/'  => 's',
            '/%T/'  => 'H:i:s',
            '/%X/e' => 'Horde_Date_Utils::strftime2date(Horde_Nls::getLangInfo(T_FMT))',
            '/%z/'  => 'O',
            '/%Z/'  => '',
            '/%c/'  => '',
            '/%D/'  => 'm/d/y',
            '/%F/'  => 'Y-m-d',
            '/%s/'  => 'U',
            '/%x/e' => 'Horde_Date_Utils::strftime2date(Horde_Nls::getLangInfo(D_FMT))',
            '/%n/'  => "\n",
            '/%t/'  => "\t",
            '/%%/'  => '%'
        );

        return preg_replace(array_keys($replace), array_values($replace), $format);
    }

}
