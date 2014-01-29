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
 * Copyright 2004-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
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
     * Parse the provided string into a PHP DateTime object.
     *
     * @todo Drop in version 3.0.0.
     *
     * @param string $date_time The Kolab date-time value.
     *
     * @return DateTime The date-time value represented as PHP DateTime object.
     */
    static public function readUtcDateTime($date_time)
    {
        return self::readDateTime($date_time);
    }

    /**
     * Parse the provided string into a PHP DateTime object.
     *
     * @todo Drop $timezone parameter in version 3.0.0.
     *
     * @param string $date     The Kolab date value.
     * @param string $timezone The associated timezone. Deprecated.
     *
     * @return DateTime The date-time value represented as PHP DateTime object.
     */
    static public function readDate($date, $timezone = null)
    {
        if (empty($date)) {
            return false;
        }

        return DateTime::createFromFormat(
            '!Y-m-d', $date
        );
    }

    /**
     * Parse the provided string into a PHP DateTime object.
     *
     * @todo Drop $timezone parameter in version 3.0.0.
     *
     * @param string $date_time The Kolab date-time value.
     * @param string $timezone  The associated timezone. Deprecated.
     *
     * @return DateTime The date-time value represented as PHP DateTime object.
     */
    static public function readDateTime($date_time, $timezone = null)
    {
        if (empty($date_time)) {
            return false;
        }

        try {
            $date = new DateTime($date_time);
            $date->setTimezone(new DateTimeZone('UTC'));
            return $date;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Parse the provided string into a PHP DateTime object.
     *
     * @todo Drop $timezone parameter in version 3.0.0.
     *
     * @param string $date      The string representation of the date (& time).
     * @param string $timezone  The associated timezone. Deprecated.
     *
     * @return DateTime The date-time value represented as PHP DateTime object.
     */
    static public function readDateOrDateTime($date, $timezone = null)
    {
        if (empty($date)) {
            return null;
        }

        return strlen($date) == 10
            ? self::readDate($date, $timezone)
            : self::readDateTime($date, $timezone);
    }

    /**
     * Write the provided PHP DateTime object into a Kolab format UTC date-time
     * representation.
     *
     * @todo Drop in version 3.0.0.
     *
     * @param DateTime $date_time The PHP DateTime object.
     *
     * @return string The Kolab format UTC date-time string.
     */
    static public function writeUtcDateTime(DateTime $date_time)
    {
        return self::writeDateTime($date_time);
    }

    /**
     * Write the provided PHP DateTime object into a Kolab format date-time
     * representation.
     *
     * @param DateTime $date_time The PHP DateTime object.
     *
     * @return string The Kolab format date-time string.
     */
    static public function writeDateTime(DateTime $date_time)
    {
        $date_time->setTimezone(new DateTimeZone('UTC'));
        return $date_time->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Write the provided PHP DateTime object into a Kolab format date
     * representation.
     *
     * @param DateTime $date The PHP DateTime object.
     *
     * @return string The Kolab format UTC date string.
     */
    static public function writeDate(DateTime $date)
    {
        return $date->format('Y-m-d');
    }
}
