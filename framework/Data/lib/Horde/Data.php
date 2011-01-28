<?php
/**
 * Abstract class to handle different kinds of Data formats and to
 * help data exchange between Horde applications and external sources.
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Data
 */
class Horde_Data
{
    /* Import already mapped csv data. */
    const IMPORT_MAPPED = 1;
    /* Map date and time entries of csv data. */
    const IMPORT_DATETIME =  2;
    /* Import generic CSV data. */
    const IMPORT_CSV = 3;
    /* Import MS Outlook data. */
    const IMPORT_OUTLOOK = 4;
    /* Import vCalendar/iCalendar data. */
    const IMPORT_ICALENDAR = 5;
    /* Import vCards. */
    const IMPORT_VCARD = 6;
    /* Import generic tsv data. */
    const IMPORT_TSV = 7;
    /* Import Mulberry address book data. */
    const IMPORT_MULBERRY = 8;
    /* Import Pine address book data. */
    const IMPORT_PINE = 9;
    /* Import file. */
    const IMPORT_FILE = 11;
    /* Import data. */
    const IMPORT_DATA = 12;

    /* Export generic CSV data. */
    const EXPORT_CSV = 100;
    /* Export iCalendar data. */
    const EXPORT_ICALENDAR = 101;
    /* Export vCards. */
    const EXPORT_VCARD = 102;
    /* Export TSV data. */
    const EXPORT_TSV = 103;
    /* Export Outlook CSV data. */
    const EXPORT_OUTLOOKCSV = 104;

    /**
     * Attempts to return a concrete instance based on $format.
     *
     * @param string $format  The type of concrete subclass to return.
     * @param array $params   Parameters to pass to the format driver.
     *
     * @return Horde_Data_Driver  The newly created concrete instance.
     * @throws Horde_Data_Exception
     */
    static public function factory($format, array $params = array())
    {
        $format = ucfirst(strtolower(basename($format)));
        $class = __CLASS__ . '_' . $format;

        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Horde_Data_Exception('Driver not found: ' . $class);
    }

}
