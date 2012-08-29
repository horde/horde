<?php
/**
 * Abstract class to handle different kinds of Data formats and to
 * help data exchange between Horde applications and external sources.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
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

}
