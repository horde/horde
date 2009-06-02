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

}
