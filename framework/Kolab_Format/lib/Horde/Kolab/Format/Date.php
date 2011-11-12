<?php
/**
 * Helper functions to handle format conversions.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Stuart Binge <omicron@mighty.co.za>
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Kolab date handling functions. Based upon Kolab.php from Stuart Binge.
 *
 * Copyright 2004-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Stuart Binge <omicron@mighty.co.za>
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Date
{
    /**
     * Returns a UNIX timestamp corresponding the given date string which is in
     * the format prescribed by the Kolab Format Specification.
     *
     * @param string $date The string representation of the date.
     *
     * @return integer  The unix timestamp corresponding to $date.
     */
    static public function decodeDate($date)
    {
        if (empty($date)) {
            return 0;
        }

        list($year, $month, $day) = explode('-', $date);

        return mktime(0, 0, 0, $month, $day, $year);
    }

    /**
     * Returns a UNIX timestamp corresponding the given date-time string which
     * is in the format prescribed by the Kolab Format Specification.
     *
     * @param string $datetime The string representation of the date & time.
     *
     * @return integer  The unix timestamp corresponding to $datetime.
     */
    static public function decodeDateTime($datetime)
    {
        if (empty($datetime)) {
            return 0;
        }

        list($year, $month, $day, $hour, $minute, $second) = sscanf($datetime,
                                                                    '%d-%d-%dT%d:%d:%dZ');
        return gmmktime($hour, $minute, $second, $month, $day, $year);
    }

    /**
     * Returns a UNIX timestamp corresponding the given date or date-time
     * string which is in either format prescribed by the Kolab Format
     * Specification.
     *
     * @param string $date The string representation of the date (& time).
     *
     * @return integer  The unix timestamp corresponding to $date.
     */
    static public function decodeDateOrDateTime($date)
    {
        if (empty($date)) {
            return 0;
        }

        return (strlen($date) == 10 ? self::decodeDate($date) : self::decodeDateTime($date));
    }

    /**
     * Returns a string containing the current UTC date in the format
     * prescribed by the Kolab Format Specification.
     *
     * @param int $date The integer representation of the date.
     *
     * @return string  The current UTC date in the format 'YYYY-MM-DD'.
     */
    static public function encodeDate($date = false)
    {
        if ($date === false) {
            $date = time();
        }

        return strftime('%Y-%m-%d', $date);
    }

    /**
     * Returns a string containing the current UTC date and time in the format
     * prescribed by the Kolab Format Specification.
     *
     * @param int $datetime The integer representation of the date.
     *
     * @return string    The current UTC date and time in the format
     *                   'YYYY-MM-DDThh:mm:ssZ', where the T and Z are literal
     *                   characters.
     */
    static public function encodeDateTime($datetime = false)
    {
        if ($datetime === false) {
            $datetime = time();
        }

        return gmstrftime('%Y-%m-%dT%H:%M:%SZ', $datetime);
    }

    /**
     * Parse the provided string into a PHP DateTime object.
     *
     * @param string $date_time The Kolab date-time value.
     *
     * @since Horde_Kolab_Format 1.1.0
     *
     * @return DateTime The date-time value represented as PHP DateTime object.
     */
    static public function readUtcDateTime($date_time)
    {
        if ($date = DateTime::createFromFormat(
                'Y-m-d\TH:i:s\Z', $date_time, new DateTimeZone('UTC')
            )) {
            return $date;
        }
        /**
         * No need to support fractions of a second yet. So lets just try to
         * remove a potential microseconds part and attempt parsing again.
         */
        $date_time = preg_replace(
            '/(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}).\d+Z/',
            '\1Z',
            $date_time
        );
        return DateTime::createFromFormat(
            'Y-m-d\TH:i:s\Z', $date_time, new DateTimeZone('UTC')
        );
    }

    /**
     * Parse the provided string into a PHP DateTime object.
     *
     * @param string $date     The Kolab date value.
     * @param string $timezone The associated timezone.
     *
     * @since Horde_Kolab_Format 1.1.0
     *
     * @return DateTime The date-time value represented as PHP DateTime object.
     */
    static public function readDate($date, $timezone)
    {
        return DateTime::createFromFormat(
            '!Y-m-d', $date, new DateTimeZone($timezone)
        );
    }

    /**
     * Parse the provided string into a PHP DateTime object.
     *
     * @param string $date_time The Kolab date-time value.
     * @param string $timezone  The associated timezone.
     *
     * @since Horde_Kolab_Format 1.1.0
     *
     * @return DateTime The date-time value represented as PHP DateTime object.
     */
    static public function readDateTime($date_time, $timezone)
    {
        /**
         * The trailing "Z" for UTC times holds no relevant information. The
         * authoritative timezone information is the "tz" attribute. If that one
         * is missing we will assume to have a UTC date-time in any case - with
         * or without "Z".
         */
        $date_time = preg_replace('/Z$/','', $date_time);
        if ($date = DateTime::createFromFormat(
                'Y-m-d\TH:i:s', $date_time, new DateTimeZone($timezone)
            )) {
            return $date;
        }
        /**
         * No need to support fractions of a second yet. So lets just try to
         * remove a potential microseconds part and attempt parsing again.
         */
        $date_time = preg_replace(
            '/(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}).\d+Z/',
            '\1Z',
            $date_time
        );
        return DateTime::createFromFormat(
            'Y-m-d\TH:i:s\Z', $date_time, new DateTimeZone($timezone)
        );
    }

    /**
     * Write the provided PHP DateTime object into a Kolab format UTC date-time
     * representation.
     *
     * @param DateTime $date_time The PHP DateTime object.
     *
     * @since Horde_Kolab_Format 1.1.0
     *
     * @return string The Kolab format UTC date-time string.
     */
    static public function writeUtcDateTime(DateTime $date_time)
    {
        return $date_time->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Write the provided PHP DateTime object into a Kolab format date-time
     * representation.
     *
     * @param DateTime $date_time The PHP DateTime object.
     *
     * @since Horde_Kolab_Format 1.1.0
     *
     * @return string The Kolab format date-time string.
     */
    static public function writeDateTime(DateTime $date_time)
    {
        if ($date_time->getTimezone()->getName() == 'UTC') {
            return $date_time->format('Y-m-d\TH:i:s\Z');
        } else {
            return $date_time->format('Y-m-d\TH:i:s');
        }
    }

    /**
     * Write the provided PHP DateTime object into a Kolab format date
     * representation.
     *
     * @param DateTime $date The PHP DateTime object.
     *
     * @since Horde_Kolab_Format 1.1.0
     *
     * @return string The Kolab format UTC date string.
     */
    static public function writeDate(DateTime $date)
    {
        return $date->format('Y-m-d');
    }
}
