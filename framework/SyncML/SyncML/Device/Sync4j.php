<?php
/**
 * @package SyncML
 */

/** Horde_Date */
require_once 'Horde/Date.php';

/** Horde_Icalendar */
require_once 'Horde/Icalendar.php';

/**
 * Sync4j (www.sync4j.org)
 *
 * The Sync4J outlook converter uses its native SIF format for data
 * exchange. Conversion to text/vcalendar etc. is done by SifConverter.php The
 * connector seems not support DevInf information, so SyncML_Device can only
 * detect it by the decice ID: so in the connector configuration the device ID
 * must be set to 'sc-pim-<type>' which should be the default anyhow.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Karsten Fourmont <karsten@horde.org>
 * @package SyncML
 */
class SyncML_Device_sync4j extends SyncML_Device {

    /**
     * Convert the content.
     */
    function convertClient2Server($content, $contentType)
    {
        list($content, $contentType) =
            parent::convertClient2Server($content, $contentType);

        switch ($contentType) {
        case 'text/x-s4j-sifn' :
        case 'text/x-sifn' :
            $content = SyncML_Device_sync4j::sif2vnote($content);
            $contentType = 'text/x-vnote';
            break;

        case 'text/x-s4j-sifc' :
        case 'text/x-sifc' :
            $content = SyncML_Device_sync4j::sif2vcard($content);
            $contentType = 'text/x-vcard';
            break;

        case 'text/x-s4j-sife' :
        case 'text/x-sife' :
            $content = SyncML_Device_sync4j::sif2vevent($content);
            $contentType = 'text/calendar';
            break;

        case 'text/x-s4j-sift' :
        case 'text/x-sift' :
            $content = SyncML_Device_sync4j::sif2vtodo($content);
            $contentType = 'text/calendar';
            break;

        case 'text/calendar':
        case 'text/x-vcalendar':
            $si = $_SESSION['SyncML.state']->sourceURI;
            if (stristr($si, 'fol-') !== false) {
                // The Funambol Outlook connector uses invalid STATUS
                // values. Actually it maps MeetingStatus values of the
                // Outlook event to the STATUS property, which is
                // completely useless. So drop the STATUS altogether.
                $content = preg_replace('/^STATUS:.*\r?\n/im', '', $content);
            }
            break;
        }

        $GLOBALS['backend']->logFile(
            SYNCML_LOGFILE_DATA,
            "\nInput converted for server ($contentType):\n$content\n");

        return array($content, $contentType);
    }

    /**
     * Converts the content from the backend to a format suitable for the
     * client device.
     *
     * Strips the uid (primary key) information as client and server might use
     * different ones.
     *
     * @param string $content      The content to convert
     * @param string $contentType  The content type of content as returned
     *                             from the backend
     * @param string $database     The server database URI.
     *
     * @return array  Three-element array with the converted content, the
     *                (possibly changed) new content type, and encoding type
     *                (like b64 as used by Funambol).
     */
    function convertServer2Client($content, $contentType, $database)
    {
        $database = $GLOBALS['backend']->_normalize($database);

        list($content, $contentType, $encodingType) =
            parent::convertServer2Client($content, $contentType, $database);

        if ($this->requestedContentType == $contentType) {
            if ($contentType == 'text/calendar' ||
                $contentType == 'text/x-vcalendar') {
                $si = $_SESSION['SyncML.state']->sourceURI;
                if (stristr($si, 'fol-') !== false) {
                    // The Funambol Outlook connector uses invalid STATUS
                    // values. Actually it maps MeetingStatus values of the
                    // Outlook event to the STATUS property, which is
                    // completely useless. So drop the STATUS altogether.
                    $content = preg_replace('/^STATUS:.*\r?\n/im', '', $content);
                }
            }
            return array($content, $contentType, $encodingType);
        }

        switch ($contentType) {
        case 'text/calendar' :
        case 'text/x-vcalendar' :
            switch($database) {
            case 'calendar':
                $content = SyncML_Device_sync4j::vevent2sif($content);
                $content = base64_encode($content);
                $contentType = 'text/x-s4j-sife';
                break 2;
            case 'tasks':
                $content = SyncML_Device_sync4j::vtodo2sif($content);
                $content = base64_encode($content);
                $contentType = 'text/x-s4j-sift';
                break 2;
            }
            break;

        case 'text/x-vcard' :
            $content = SyncML_Device_sync4j::vcard2sif($content);
            $content = base64_encode($content);
            $contentType = 'text/x-s4j-sifc';
            break;

        case 'text/x-vnote':
        case 'text/plain':
            $content = SyncML_Device_sync4j::vnote2sif($content);
            $content = base64_encode($content);
            $contentType = 'text/x-s4j-sifn';
            break;
        }

        $l = "\nOutput converted for client ($contentType):\n" . base64_decode($content) . "\n";
        $GLOBALS['backend']->logFile(SYNCML_LOGFILE_DATA, $l);

        return array($content, $contentType, 'b64');
    }

    /**
     * Decodes a sif xml string to an associative array.
     *
     * Quick hack to convert from text/vcard and text/vcalendar to
     * Sync4J's proprietery sif datatypes and vice versa.  For details
     * about the sif format see the appendix of the developer guide on
     * www.sync4j.org.
     *
     * @access private
     *
     * @param string $sif  A sif string like <k1>v1&gt;</k1><k2>v2</k2>
     *
     * @return array  Assoc array in utf8 like array ('k1' => 'v1>', 'k2' => 'v2');
     */
    function sif2array($sif)
    {
        $r = array();
        if (preg_match_all('/<([^>]*)>([^<]*)<\/\1>/si', $sif, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (isset($r[$match[1]])) {
                    if (!is_array($r[$match[1]])) {
                        $r[$match[1]] = array($r[$match[1]]);
                    }
                    $r[$match[1]][] = html_entity_decode($match[2]);
                } else {
                    $r[$match[1]] = html_entity_decode($match[2]);
                }
            }
        }
        return $r;
    }

    /**
     * Converts a hash to a SIF XML structure.
     *
     * @param array $array  A hash.
     * @param string $pre   A prefix string for the XML result.
     * @param string $post  A suffix string for the XML result.
     *
     * @return string  The resulting XML string.
     */
    function array2sif($array, $pre = '', $post = '')
    {
        $xml = $pre;
        foreach ($array as $key => $value) {
            if (is_a($value, 'PEAR_Error')) {
                continue;
            }
            if (is_array($value)) {
                if (is_array($value[0])) {
                    $subxml = '';
                    foreach ($value as $val) {
                        $subkey = key($val);
                        $subxml .= '<' . $subkey . '>'
                            . htmlspecialchars($val[$subkey])
                            . '</' . $subkey . '>';
                    }
                    $xml .= '<' . $key . '>' . $subxml . '</' . $key . '>';
                    continue;
                }
                $value = $value[0];
            }
            $xml .= '<' . $key . '>' . htmlspecialchars($value) . '</' . $key . '>';
        }
        return $xml . $post;
    }

    function sif2vnote($sif)
    {
        $a = SyncML_Device_sync4j::sif2array($sif);

        $iCal = new Horde_Icalendar();
        $iCal->setAttribute('VERSION', '1.1');
        $iCal->setAttribute('PRODID', '-//The Horde Project//SyncML//EN');
        $iCal->setAttribute('METHOD', 'PUBLISH');

        $vnote = Horde_Icalendar::newComponent('vnote', $iCal);
        $vnote->setAttribute('BODY', isset($a['Body']) ? $a['Body'] : '');
        if (isset($a['Subject'])) {
            $vnote->setAttribute('SUMMARY', $a['Subject']);
        }
        if (isset($a['Categories'])) {
            $vnote->setAttribute('CATEGORIES', $a['Categories']);
        }

        return $vnote->exportvCalendar();
    }

    function sif2vcard($sif)
    {
        $a = SyncML_Device_sync4j::sif2array($sif);

        $iCal = new Horde_Icalendar();
        $iCal->setAttribute('VERSION', '3.0');
        $iCal->setAttribute('PRODID', '-//The Horde Project//SyncML//EN');
        $iCal->setAttribute('METHOD', 'PUBLISH');

        $vcard = Horde_Icalendar::newComponent('vcard', $iCal);

        $map = array(
            'FileAs' => array('FN'),
            'NickName' => array('NICKNAME'),
            'HomeTelephoneNumber' => array('TEL', array('TYPE' => 'HOME')),
            'Home2TelephoneNumber' => array('TEL', array('TYPE' => 'HOME')),
            'HomeFaxNumber' => array('TEL', array('TYPE' => 'HOME')),
            'BusinessTelephoneNumber' => array('TEL', array('TYPE' => 'WORK')),
            'Business2TelephoneNumber' => array('TEL', array('TYPE' => 'WORK')),
            'BusinessFaxNumber' => array('TEL', array('TYPE' => 'FAX')),
            'PrimaryTelephoneNumber' => array('TEL', array('TYPE' => 'PREF')),
            'MobileTelephoneNumber' => array('TEL', array('TYPE' => 'CELL')),
            'CarTelephoneNumber' => array('TEL', array('TYPE' => 'CAR')),
            'PagerNumber' => array('TEL', array('TYPE' => 'PAGER')),
            'OtherTelephoneNumber' => array('TEL'),
            'OtherFaxNumber' => array('TEL'),
            'Email1Address' => array('EMAIL'),
            'Email2Address' => array('EMAIL', array('TYPE' => 'HOME')),
            'Email3Address' => array('EMAIL', array('TYPE' => 'WORK')),
            'HomeLabel' => array('LABEL', array('TYPE' => 'HOME')),
            'BusinessLabel' => array('LABEL', array('TYPE' => 'WORK')),
            'OtherLabel' => array('LABEL'),
            'Profession' => array('ROLE'),
            'JobTitle' => array('TITLE'),
            'Body' => array('NOTE'),
            'WebPage' => array('URL'),
            'Birthday' => array('BDAY'),
            'Categories' => array('CATEGORIES'),
            'Timezone' => array('TZ'),
            'Anniversary' => array('X-ANNIVERSARY'),
            'Spouse' => array('X-SPOUSE'),
            'Children' => array('X-CHILDREN'),
        );
        foreach ($map as $sif_value => $vcard_value) {
            if (isset($a[$sif_value])) {
                $vcard->setAttribute($vcard_value[0],
                                     $a[$sif_value],
                                     isset($vcard_value[1]) ? $vcard_value[1] : array());
            }
        }

        $map = array(
            array(
                'N',
                array(Horde_Icalendar_Vcard::N_FAMILY => 'LastName',
                      Horde_Icalendar_Vcard::N_GIVEN  => 'FirstName',
                      Horde_Icalendar_Vcard::N_ADDL   => 'MiddleName',
                      Horde_Icalendar_Vcard::N_PREFIX => 'Title',
                      Horde_Icalendar_Vcard::N_SUFFIX => 'Suffix'),
                array(),
                false),
            array(
                'ADR',
                array(Horde_Icalendar_Vcard::ADR_POB      => 'HomeAddressPostOfficeBox',
                      Horde_Icalendar_Vcard::ADR_EXTEND   => '',
                      Horde_Icalendar_Vcard::ADR_STREET   => 'HomeAddressStreet',
                      Horde_Icalendar_Vcard::ADR_LOCALITY => 'HomeAddressCity',
                      Horde_Icalendar_Vcard::ADR_REGION   => 'HomeAddressState',
                      Horde_Icalendar_Vcard::ADR_POSTCODE => 'HomeAddressPostalCode',
                      Horde_Icalendar_Vcard::ADR_COUNTRY  => 'HomeAddressCountry'),
                array('TYPE' => 'HOME'),
                true),
            array(
                'ADR',
                array(Horde_Icalendar_Vcard::ADR_POB      => 'BusinessAddressPostOfficeBox',
                      Horde_Icalendar_Vcard::ADR_EXTEND   => '',
                      Horde_Icalendar_Vcard::ADR_STREET   => 'BusinessAddressStreet',
                      Horde_Icalendar_Vcard::ADR_LOCALITY => 'BusinessAddressCity',
                      Horde_Icalendar_Vcard::ADR_REGION   => 'BusinessAddressState',
                      Horde_Icalendar_Vcard::ADR_POSTCODE => 'BusinessAddressPostalCode',
                      Horde_Icalendar_Vcard::ADR_COUNTRY  => 'BusinessAddressCountry'),
                array('TYPE' => 'WORK'),
                true),
            array(
                'ADR',
                array(Horde_Icalendar_Vcard::ADR_POB      => 'OtherAddressPostOfficeBox',
                      Horde_Icalendar_Vcard::ADR_EXTEND   => '',
                      Horde_Icalendar_Vcard::ADR_STREET   => 'OtherAddressStreet',
                      Horde_Icalendar_Vcard::ADR_LOCALITY => 'OtherAddressCity',
                      Horde_Icalendar_Vcard::ADR_REGION   => 'OtherAddressState',
                      Horde_Icalendar_Vcard::ADR_POSTCODE => 'OtherAddressPostalCode',
                      Horde_Icalendar_Vcard::ADR_COUNTRY  => 'OtherAddressCountry'),
                array(),
                true),
        );
        foreach ($map as $struct) {
            $values = array();
            foreach ($struct[1] as $vcard_value => $sif_value) {
                $values[$vcard_value] = isset($a[$sif_value]) ? $a[$sif_value] : '';
            }
            $check = array_flip($values);
            if (count($check) > 1 || strlen(key($check))) {
                $vcard->setAttribute($struct[0],
                                     implode(';', $values),
                                     $struct[2],
                                     $struct[3],
                                     $values);
            }
        }

        $org = array();
        if (isset($a['CompanyName'])) {
            $org[] = $a['CompanyName'];
            if (isset($a['Department'])) {
                $org[] = $a['Department'];
            }
        }
        if (count($org)) {
            $vcard->setAttribute('ORG', null, array(), true, $org);
        }

        return $vcard->exportvCalendar();
    }

    function sif2vevent($sif)
    {
        $a = SyncML_Device_sync4j::sif2array($sif);

        $iCal = new Horde_Icalendar();
        $iCal->setAttribute('PRODID', '-//The Horde Project//SyncML//EN');
        $iCal->setAttribute('METHOD', 'PUBLISH');

        $vEvent = Horde_Icalendar::newComponent('vevent', $iCal);
        $vEvent->setAttribute('DTSTAMP', time());

        $map = array('Subject' => 'SUMMARY',
                     'Body' => 'DESCRIPTION',
                     'Categories' => 'CATEGORIES',
                     'Location' => 'LOCATION');
        foreach ($map as $source => $target) {
            if (!empty($a[$source])) {
                $vEvent->setAttribute($target, $a[$source]);
            }
        }

        if ($a['AllDayEvent'] == 1) {
            // Not exactly correct, we ignore the start and end time of
            // all-day events and simple assume that the client had set them
            // correctly to 0:00.
            $startTime = $iCal->_parseDateTime($a['Start']);
            $vEvent->setAttribute('DTSTART',
                                  array('year' => date('Y', $startTime),
                                        'month' => date('m', $startTime),
                                        'mday' => date('d', $startTime)),
                                  array('VALUE' => 'DATE'));
            $t = $iCal->_parseDateTime($a['End']);
            $d = new Horde_Date(array('year' => date('Y', $t),
                                      'month' => date('m', $t),
                                      'mday' => date('d', $t) + 1));
            $d->correct();
            $vEvent->setAttribute('DTEND',$d, array('VALUE' => 'DATE'));
        } else {
            $startTime = $iCal->_parseDateTime($a['Start']);
            $vEvent->setAttribute('DTSTART', $startTime);
            $vEvent->setAttribute('DTEND',
                                  $iCal->_parseDateTime($a['End']));
        }

        if (isset($a['IsRecurring']) && $a['IsRecurring'] == 1) {
            $interval = '';
            switch ($a['RecurrenceType']) {
            case 0:
                /* olDaily */
                if (!empty($a['DayOfWeekMask'])) {
                    $freq = 'WEEKLY';
                    $interval = ';INTERVAL=1';
                } else {
                    $freq = 'DAILY';
                    $interval = ';INTERVAL=' . $a['Interval'];
                }
                break;
            case 1:
                /* olWeekly */
                $freq = 'WEEKLY';
                $interval = ';INTERVAL=' . $a['Interval'];
                break;
            case 2:
                /* olMonthly */
                $freq = 'MONTHLY';
                $interval = ';INTERVAL=' . $a['Interval'];
                break;
            case 3:
                /* olMonthNth */
                $freq = 'MONTHLY';
                $interval = ';INTERVAL=' . $a['Interval'];
                break;
            case 5:
                /* olYearly */
                $freq = 'YEARLY';
                $interval = ';INTERVAL=' . $a['Interval'];
                break;
            case 6:
                /* olYearNth */
                $freq = 'YEARLY';
                $interval = ';INTERVAL=' . $a['Interval'];
                break;
            }
            $rrule = 'FREQ=' . $freq;
            if (isset($a['Occurrences'])) {
                $rrule .= ';COUNT=' . $a['Occurrences'];
            } elseif (!isset($a['NoEndDate']) || $a['NoEndDate'] != 1) {
                $rrule .= ';UNTIL=' . $a['PatternEndDate'];
            }
            $rrule .= $interval;
            if (!empty($a['DayOfMonth'])) {
                $rrule .= ';BYMONTHDAY=' . $a['DayOfMonth'];
            }
            if (!empty($a['MonthOfYear'])) {
                $rrule .= ';BYMONTH=' . $a['MonthOfYear'];
            }
            if (!empty($a['DayOfWeekMask'])) {
                $rrule .= ';BYDAY=';
                $icaldays = array('SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA');
                for ($i = $flag = 0; $i <= 7 ; ++$i) {
                    if (pow(2, $i) & $a['DayOfWeekMask']) {
                        if ($flag) {
                            $rrule .= ',';
                        }
                        $rrule .= $icaldays[$i];
                        $flag = true;
                    }
                }
            }
            $vEvent->setAttribute('RRULE', $rrule);
        }

        if (isset($a['ExcludeDate'])) {
            $dates = array();
            if (!is_array($a['ExcludeDate'])) {
                $dates[] = $a['AllDayEvent'] == 1
                    ? $iCal->_parseDate($a['ExcludeDate'])
                    : $iCal->_parseDateTime($a['ExcludeDate']);
            } else {
                foreach ($a['ExcludeDate'] as $date) {
                    $dates[] = $a['AllDayEvent'] == 1
                        ? $iCal->_parseDate($date)
                        : $iCal->_parseDateTime($date);
                }
            }
            if ($a['AllDayEvent'] == 1) {
                $vEvent->setAttribute('EXDATE', $dates, array('VALUE' => 'DATE'));
            } else {
                $vEvent->setAttribute('EXDATE', $dates);
            }
        }

        if (isset($a['ReminderSet']) && $a['ReminderSet'] == 1) {
            $vEvent->setAttribute('AALARM', $startTime - $a['ReminderMinutesBeforeStart'] * 60);
        }

        if (isset($a['BusyStatus'])) {
            switch ($a['BusyStatus']) {
            case 0:
                /* olFree - FREE is not a iCalendar standard value. */
                $vEvent->setAttribute('STATUS', 'FREE');
                break;
            case 1:
                /* olTentative */
                $vEvent->setAttribute('STATUS', 'TENTATIVE');
                break;
            case 2:
                /* olBusy */
            case 3:
                /* olOutOfOffice */
                $vEvent->setAttribute('STATUS', 'CONFIRMED');
                break;
            }
        }

        if (isset($a['Sensitivity'])) {
            switch ($a['Sensitivity']) {
            case 0:
                /* olNormal - FREE is not a iCalendar standard value. */
                $vEvent->setAttribute('CLASS', 'PUBLIC');
                break;
            case 1:
                /* olPersonal */
            case 2:
                /* olPrivate */
                $vEvent->setAttribute('CLASS', 'PRIVATE');
                break;
            case 3:
                /* olConfidential */
                $vEvent->setAttribute('CLASS', 'CONFIDENTIAL');
                break;
            }
        }

        return $vEvent->exportvCalendar();
    }

    function sif2vtodo($sif)
    {
        $a = SyncML_Device_sync4j::sif2array($sif);

        $iCal = new Horde_Icalendar();
        $iCal->setAttribute('PRODID', '-//The Horde Project//SyncML//EN');
        $iCal->setAttribute('METHOD', 'PUBLISH');

        $vtodo = Horde_Icalendar::newComponent('vtodo', $iCal);

        $vtodo->setAttribute('SUMMARY', $a['Subject']);
        $vtodo->setAttribute('DESCRIPTION', $a['Body']);
        if ($a['Importance'] == 0) {
            $vtodo->setAttribute('PRIORITY', 5);
        } elseif ($a['Importance'] == 2) {
            $vtodo->setAttribute('PRIORITY', 1);
        } else {
            $vtodo->setAttribute('PRIORITY', 3);
        }
        if (!empty($a['StartDate']) && $a['StartDate'] != '45001231T230000Z') {
            $vtodo->setAttribute('DTSTART', $iCal->_parseDateTime($a['StartDate']));
        }
        $dueSet = false;
        if (!empty($a['DueDate']) && $a['DueDate'] != '45001231T230000Z') {
            $vtodo->setAttribute('DUE', $iCal->_parseDateTime($a['DueDate']));
            $dueSet = true;
        }
        if (!empty($a['ReminderSet'])) {
            if (!$dueSet) {
                $vtodo->setAttribute('DUE', $iCal->_parseDateTime($a['ReminderTime']));
            }
            $vtodo->setAttribute('AALARM', $iCal->_parseDateTime($a['ReminderTime']));
        }
        if (!empty($a['Complete'])) {
            $vtodo->setAttribute('STATUS', 'COMPLETED');
        }
        $vtodo->setAttribute('CATEGORIES', isset($a['Categories']) ? $a['Categories'] : '');
        if (isset($a['Sensitivity'])) {
            switch ($a['Sensitivity']) {
            case 0:
                /* olNormal */
                $vtodo->setAttribute('CLASS', 'PUBLIC');
                break;
            case 1:
                /* olPersonal */
            case 2:
                /* olPrivate */
                $vtodo->setAttribute('CLASS', 'PRIVATE');
                break;
            case 3:
                /* olConfidential */
                $vtodo->setAttribute('CLASS', 'CONFIDENTIAL');
                break;
            }
        }

        return $vtodo->exportvCalendar();
    }

    function vnote2sif($vnote)
    {
        $iCal = new Horde_Icalendar();
        if (!$iCal->parsevCalendar($vnote)) {
            // handle plain text:
            $a = array('Body' => $vnote);
        } else {
            $components = $iCal->getComponents();
            if (!is_array($components) || count($components) == 0) {
                $a = array(
                    'Body' => $GLOBALS['backend']->t("Error converting notes."));
            } else {
                $a = array(
                    'Body' => $components[0]->getAttribute('BODY'),
                    'Categories' => $components[0]->getAttribute('CATEGORIES'));
                $sum = $components[0]->getAttribute('SUMMARY');
                if (!is_a($sum, 'PEAR_Error')) {
                    $a['Subject'] = $sum;
                }
            }
        }

        return SyncML_Device_sync4j::array2sif($a, '<note>', '</note>');
    }

    function vcard2sif($vcard)
    {
        $iCal = new Horde_Icalendar();
        if (!$iCal->parsevCalendar($vcard)) {
            // @TODO: NEVER use die() in a library.
            die("There was an error importing the data.");
        }

        $components = $iCal->getComponents();

        switch (count($components)) {
        case 0:
            // @TODO: NEVER use die() in a library.
            die("No data was found.");

        case 1:
            $content = $components[0];
            break;

        default:
            // @TODO: NEVER use die() in a library.
            die("Multiple components found; only one is supported.");
        }

        // from here on, the code is taken from
        // Turba_Driver::toHash, v 1.65 2005/03/12
        // and modified for the Sync4J attribute names.
        $attr = $content->getAllAttributes();
        foreach ($attr as $item) {
            switch ($item['name']) {
            case 'FN':
                $hash['FileAs'] = $item['value'];
                break;

            case 'N':
                $name = $item['values'];
                $hash['LastName'] = $name[Horde_Icalendar_Vcard::N_FAMILY];
                $hash['FirstName'] = $name[Horde_Icalendar_Vcard::N_GIVEN];
                $hash['MiddleName'] = $name[Horde_Icalendar_Vcard::N_ADDL];
                $hash['Title'] = $name[Horde_Icalendar_Vcard::N_PREFIX];
                $hash['Suffix'] = $name[Horde_Icalendar_Vcard::N_SUFFIX];
                break;

            case 'NICKNAME':
                $hash['NickName'] = $item['value'];
                break;

            // For vCard 3.0.
            case 'ADR':
                if (isset($item['params']['TYPE'])) {
                    if (!is_array($item['params']['TYPE'])) {
                        $item['params']['TYPE'] = array($item['params']['TYPE']);
                    }
                } else {
                    $item['params']['TYPE'] = array();
                    if (isset($item['params']['WORK'])) {
                        $item['params']['TYPE'][] = 'WORK';
                    }
                    if (isset($item['params']['HOME'])) {
                        $item['params']['TYPE'][] = 'HOME';
                    }
                }

                $address = $item['values'];
                foreach ($item['params']['TYPE'] as $adr) {
                    switch (Horde_String::upper($adr)) {
                    case 'HOME':
                        $prefix = 'HomeAddress';
                        break;

                    case 'WORK':
                        $prefix = 'BusinessAddress';
                        break;

                    default:
                        $prefix = 'HomeAddress';
                    }

                    if ($prefix) {
                        $hash[$prefix . 'Street'] =
                            isset($address[Horde_Icalendar_Vcard::ADR_STREET])
                            ? $address[Horde_Icalendar_Vcard::ADR_STREET]
                            : null;
                        $hash[$prefix . 'City'] =
                            isset($address[Horde_Icalendar_Vcard::ADR_LOCALITY])
                            ? $address[Horde_Icalendar_Vcard::ADR_LOCALITY]
                            : null;
                        $hash[$prefix . 'State'] =
                            isset($address[Horde_Icalendar_Vcard::ADR_REGION])
                            ? $address[Horde_Icalendar_Vcard::ADR_REGION]
                            : null;
                        $hash[$prefix . 'PostalCode'] =
                            isset($address[Horde_Icalendar_Vcard::ADR_POSTCODE])
                            ? $address[Horde_Icalendar_Vcard::ADR_POSTCODE]
                            : null;
                        $hash[$prefix . 'Country'] =
                            isset($address[Horde_Icalendar_Vcard::ADR_COUNTRY])
                            ? $address[Horde_Icalendar_Vcard::ADR_COUNTRY]
                            : null;
                        $hash[$prefix . 'PostOfficeBox'] =
                            isset($address[Horde_Icalendar_Vcard::ADR_POB])
                            ? $address[Horde_Icalendar_Vcard::ADR_POB]
                            : null;
                    }
                }
                break;

            case 'TEL':
                if (isset($item['params']['FAX'])) {
                    if (isset($item['params']['WORK'])) {
                        $hash['BusinessFaxNumber'] = $item['value'];
                    } elseif (isset($item['params']['HOME'])) {
                        $hash['HomeFaxNumber'] = $item['value'];
                    } else {
                        $hash['OtherFaxNumber'] = $item['value'];
                    }
                } elseif (isset($item['params']['TYPE'])) {
                    if (!is_array($item['params']['TYPE'])) {
                        $item['params']['TYPE'] = array($item['params']['TYPE']);
                    }
                    // For vCard 3.0.
                    foreach ($item['params']['TYPE'] as $tel) {
                        if (Horde_String::upper($tel) == 'WORK') {
                            $hash['BusinessTelephoneNumber'] = $item['value'];
                        } elseif (Horde_String::upper($tel) == 'HOME') {
                            $hash['HomeTelephoneNumber'] = $item['value'];
                        } elseif (Horde_String::upper($tel) == 'CELL') {
                            $hash['MobileTelephoneNumber'] = $item['value'];
                        } elseif (Horde_String::upper($tel) == 'FAX') {
                            $hash['BusinessFaxNumber'] = $item['value'];
                        }
                    }
                } else {
                    if (isset($item['params']['HOME'])) {
                        $hash['HomeTelephoneNumber'] = $item['value'];
                    } elseif (isset($item['params']['WORK'])) {
                        $hash['BusinessTelephoneNumber'] = $item['value'];
                    } elseif (isset($item['params']['CELL'])) {
                        $hash['MobileTelephoneNumber'] = $item['value'];
                    } elseif (!isset($hash['HomeTelephoneNumber'])) {
                        $hash['HomeTelephoneNumber'] = $item['value'];
                    }
                }
                break;

            case 'EMAIL':
                $email_set = false;
                if (isset($item['params']['HOME']) && (!isset($hash['Email2Address']) ||
                    isset($item['params']['PREF']))) {
                   $hash['Email2Address'] = Horde_Icalendar_Vcard::getBareEmail($item['value']);
                   $email_set = true;
                } elseif (isset($item['params']['WORK']) && (!isset($hash['Email3Address']) ||
                          isset($item['params']['PREF']))) {
                   $hash['Email3Address'] = Horde_Icalendar_Vcard::getBareEmail($item['value']);
                   $email_set = true;
                } elseif (isset($item['params']['TYPE'])) {
                   if (!is_array($item['params']['TYPE'])) {
                      $item['params']['TYPE'] = array($item['params']['TYPE']);
                   }
                   if (in_array('HOME', $item['params']['TYPE']) &&
                       (!isset($hash['Email2Address']) || in_array('PREF', $item['params']['TYPE']))) {
                      $hash['Email2Address'] = Horde_Icalendar_Vcard::getBareEmail($item['value']);
                      $email_set = true;
                   } elseif (in_array('WORK', $item['params']['TYPE']) &&
                             (!isset($hash['Email3Address']) || in_array('PREF', $item['params']['TYPE']))) {
                      $hash['Email3Address'] = Horde_Icalendar_Vcard::getBareEmail($item['value']);
                      $email_set = true;
                   }
                }
                if (!$email_set && (!isset($hash['Email1Address']) || isset($item['params']['PREF']))) {
                   $hash['Email1Address'] = Horde_Icalendar_Vcard::getBareEmail($item['value']);
                }
                break;

            case 'TITLE':
                $hash['JobTitle'] = $item['value'];
                break;

            case 'ORG':
                $values = preg_split('/(?<!\\\\);/', trim($item['value']));
                $hash['CompanyName'] = $values[0];
                $hash['Department'] = isset($values[1]) ? $values[1] : '';
                break;

            case 'NOTE':
                $hash['Body'] = $item['value'];
                break;

            case 'URL':
                $hash['WebPage'] = $item['value'];
                break;

            case 'BDAY':
                if (is_array($item['value'])) {
                    $hash['Birthday'] = sprintf('%04d-%02d-%02d',
                                                $item['value']['year'],
                                                $item['value']['month'],
                                                $item['value']['mday']);
                }
                break;

            case 'X-ANNIVERSARY':
                if (is_array($item['value'])) {
                    $hash['Anniversary'] = sprintf('%04d-%02d-%02d',
                                                $item['value']['year'],
                                                $item['value']['month'],
                                                $item['value']['mday']);
                }
                break;

            case 'X-SPOUSE':
                $hash['Spouse'] = $item['value'];
                break;

            case 'X-CHILDREN':
                $hash['Children'] = $item['value'];
                break;

            case 'CATEGORIES':
                $hash['Categories'] = $item['value'];
                break;
            }
        }

        return SyncML_Device_sync4j::array2sif(
            $hash,
            '<?xml version="1.0"?><contact>',
            '</contact>');
    }

    function vevent2sif($vcard)
    {
        /* Some special handling for all-day vEvents that are not passed
         * as TYPE=DATE (TYPE=DATE does not exist for vCalendar 1.0) */
        if (preg_match('/(\r\n|\r|\n)DTSTART:.*T000000(\r\n|\r|\n)/', $vcard)) {
            if (preg_match('/(\r\n|\r|\n)DTEND:(\d\d\d\d)(\d\d)(\d\d)T235959(\r\n|\r|\n)/', $vcard, $m)) {
                $vcard = preg_replace('/(\r\n|\r|\n)DTSTART:(.*)T000000(\r\n|\r|\n)/',
                                      "$1DTSTART;VALUE=DATE:$2$3", $vcard);
                $vcard = preg_replace('/(\r\n|\r|\n)DTEND:(.*)T235959(\r\n|\r|\n)/',
                                      "$1DTEND;VALUE=DATE:$2$3", $vcard);
            }
            // @TODO: else: handle case with DTEND= T240000
        }
        $iCal = new Horde_Icalendar();
        if (!$iCal->parsevCalendar($vcard)) {
            // @TODO: NEVER use die() in a library.
            die("There was an error importing the data.");
        }

        $components = $iCal->getComponents();

        switch (count($components)) {
        case 0:
            // @TODO: NEVER use die() in a library.
            die("No data was found.");

        case 1:
            $content = $components[0];
            break;

        default:
            // @TODO: NEVER use die() in a library.
            die("Multiple components found; only one is supported.");
        }

        $hash = array('ReminderSet' => 0,
                      'IsRecurring' => 0,
                      'BusyStatus' => 2);
        $alarm = $end = null;
        $start = $content->getAttribute('DTSTART');
        $start_params = $content->getAttribute('DTSTART', true);
        if (!empty($start_params[0]['VALUE']) &&
            $start_params[0]['VALUE'] == 'DATE') {
            $hash['AllDayEvent'] = 1;
            $hash['Start'] = sprintf('%04d-%02d-%02d',
                                     $start['year'],
                                     $start['month'],
                                     $start['mday']);
            $start = mktime(0, 0, 0,
                            $start['month'],
                            $start['mday'],
                            $start['year']);
        } else {
            $hash['AllDayEvent'] = 0;
            $hash['Start'] = Horde_Icalendar::_exportDateTime($start);
        }

        foreach ($content->getAllAttributes() as $item) {
            $GLOBALS['backend']->logMessage(
                sprintf('Sync4j for name %s, value %s',
                        $item['name'],
                        is_string($item['value'])
                        ? $item['value'] : var_export($item['value'], true)), 'DEBUG');

            switch (Horde_String::upper($item['name'])) {
            case 'DTSTART':
                break;

            case 'DTEND':
                if (!empty($item['params']['VALUE']) &&
                    $item['params']['VALUE'] == 'DATE') {
                    $hash['AllDayEvent'] = 1;
                    $date = new Horde_Date($item['value']['year'],
                                           $item['value']['month'],
                                           $item['value']['mday']);
                    $date->mday--;
                    $hash['End'] = $date->format('Y-m-d');
                    $end = $date->datestamp();
                } else {
                    $hash['AllDayEvent'] = 0;
                    $hash['End'] = Horde_Icalendar::_exportDateTime($item['value']);
                    $end = $item['value'];
                }
                break;

            case 'SUMMARY':
                $hash['Subject'] = $item['value'];
                break;

            case 'DESCRIPTION':
                $hash['Body'] = $item['value'];
                break;

            case 'LOCATION':
                $hash['Location'] = $item['value'];
                break;

            case 'CATEGORIES':
                $hash['Categories'] = $item['value'];
                break;

            case 'AALARM':
                $hash['ReminderSet'] = 1;
                $alarm = $item['value'];
                break;

            case 'STATUS':
                switch (Horde_String::upper($item['value'])) {
                case 'FREE':
                case 'CANCELLED':
                    $hash['BusyStatus'] = 0;
                    break;

                case 'TENTATIVE':
                    $hash['BusyStatus'] = 1;
                    break;

                case 'CONFIRMED':
                    $hash['BusyStatus'] = 2;
                    break;
                }
                break;

            case 'CLASS':
                switch (Horde_String::upper($item['value'])) {
                case 'PUBLIC':
                    $hash['Sensitivity'] = 0;
                    break;

                case 'PRIVATE':
                    $hash['Sensitivity'] = 2;
                    break;

                case 'CONFIDENTIAL':
                    $hash['Sensitivity'] = 3;
                    break;
                }
                break;

            case 'RRULE':
                // Parse the recurrence rule into keys and values.
                $rdata = array();
                $parts = explode(';', $item['value']);
                foreach ($parts as $part) {
                    list($key, $value) = explode('=', $part, 2);
                    $rdata[Horde_String::upper($key)] = $value;
                }

                if (!isset($rdata['FREQ'])) {
                    break;
                }

                $hash['IsRecurring'] = 1;

                if (isset($rdata['BYDAY'])) {
                    $maskdays = array('SU' => Horde_Date::MASK_SUNDAY,
                                      'MO' => Horde_Date::MASK_MONDAY,
                                      'TU' => Horde_Date::MASK_TUESDAY,
                                      'WE' => Horde_Date::MASK_WEDNESDAY,
                                      'TH' => Horde_Date::MASK_THURSDAY,
                                      'FR' => Horde_Date::MASK_FRIDAY,
                                      'SA' => Horde_Date::MASK_SATURDAY);
                    $days = explode(',', $rdata['BYDAY']);
                    $mask = 0;
                    foreach ($days as $day) {
                        $instance = (int)$day;
                        $mask |= $maskdays[str_replace($instance, '', $day)];
                    }
                }

                $hash['Interval'] = isset($rdata['INTERVAL'])
                    ? $rdata['INTERVAL']
                    : 1;

                switch (Horde_String::upper($rdata['FREQ'])) {
                case 'DAILY':
                    $hash['RecurrenceType'] = 0;
                    break;

                case 'WEEKLY':
                    $hash['RecurrenceType'] = 1;
                    if (isset($rdata['BYDAY'])) {
                        $hash['DayOfWeekMask'] = $mask;
                    }
                    break;

                case 'MONTHLY':
                    if (isset($rdata['BYDAY'])) {
                        $hash['RecurrenceType'] = 3;
                        $hash['Instance'] = $instance;
                        $hash['DayOfWeekMask'] = $mask;
                    } else {
                        $hash['RecurrenceType'] = 2;
                        $hash['DayOfMonth'] = date('j', $start);
                    }
                    break;

                case 'YEARLY':
                    if (isset($rdata['BYDAY'])) {
                        $hash['RecurrenceType'] = 6;
                        $hash['Instance'] = $instance;
                        $hash['DayOfWeekMask'] = $mask;
                    } else {
                        $hash['RecurrenceType'] = 5;
                        $hash['DayOfMonth'] = date('j', $start);
                    }
                    $hash['MonthOfYear'] = date('n', $start);
                    unset($hash['Interval']);
                    break;
                }

                if (isset($rdata['UNTIL'])) {
                    $hash['NoEndDate'] = 0;
                    $hash['PatternEndDate'] = $rdata['UNTIL'];
                } elseif (isset($rdata['COUNT'])) {
                    $hash['NoEndDate'] = 0;
                    $hash['Occurrences'] = $rdata['COUNT'];
                } else {
                    $hash['NoEndDate'] = 1;
                }
                break;

            case 'EXDATE':
                if (empty($hash['Exceptions'])) {
                    $hash['Exceptions'] = array();
                }
                foreach ($item['values'] as $date) {
                    if ($hash['AllDayEvent'] == 1) {
                        $d = new Horde_Date(array('year' => $date['year'],
                                                  'month' => $date['month'],
                                                  'mday' => $date['mday'] + 1));
                        $d->correct();
                        $hash['Exceptions'][] = array('ExcludeDate' => $d->format('Y-m-d'));
                    } else {
                        $hash['Exceptions'][] = array('ExcludeDate' => Horde_Icalendar::_exportDate($date));
                    }
                }
                break;
            }
        }

        if (!empty($start)) {
            if ($hash['ReminderSet'] && !empty($alarm) && $start != $alarm) {
                $hash['ReminderMinutesBeforeStart'] = ($start - $alarm) / 60;
            } else {
                // Parse VALARM components.
                foreach ($content->getComponents() as $component) {
                    if ($component->getType() != 'vAlarm' ||
                        is_a($trigger = $component->getAttribute('TRIGGER'), 'PEAR_Error') ||
                        is_array($trigger) ||
                        empty($trigger)) {
                        continue;
                    }
                    $hash['ReminderSet'] = 1;
                    $hash['ReminderMinutesBeforeStart'] = (-$trigger) / 60;
                }
            }
        }

        if (empty($hash['AllDayEvent']) && !empty($start) &&
            !empty($end) && $start != $end) {
            $hash['Duration'] = ($end - $start) / 60;
            $GLOBALS['backend']->logMessage(
                'Duration set to ' . $hash['Duration'], 'DEBUG');

        }

        return SyncML_Device_sync4j::array2sif(
            $hash,
            '<?xml version="1.0"?><appointment>',
            '</appointment>');
    }

    function vtodo2sif($vcard)
    {
        $iCal = new Horde_Icalendar();
        if (!$iCal->parsevCalendar($vcard)) {
            return PEAR::raiseError('There was an error importing the data.');
        }

        $components = $iCal->getComponents();

        switch (count($components)) {
        case 0:
            return PEAR::raiseError('No data was found');

        case 1:
            $content = $components[0];
            break;

        default:
            return PEAR::raiseError('Multiple components found; only one is supported.');
        }

        $hash['Complete'] = 0;
        $due = false;

        $attr = $content->getAllAttributes();
        foreach ($attr as $item) {
            switch ($item['name']) {
            case 'SUMMARY':
                $hash['Subject'] = $item['value'];
                break;

            case 'DESCRIPTION':
                $hash['Body'] = $item['value'];
                break;

            case 'PRIORITY':
                if ($item['value'] == 1) {
                    $hash['Importance'] = 2;
                } elseif ($item['value'] == 5) {
                    $hash['Importance'] = 0;
                } else {
                    $hash['Importance'] = 1;
                }
                break;

            case 'DTSTART':
                $hash['StartDate'] = Horde_Icalendar::_exportDateTime($item['value']);
                break;

            case 'DUE':
                $hash['DueDate'] = Horde_Icalendar::_exportDateTime($item['value']);
                $due = $item['value'];
                break;

            case 'AALARM':
                $hash['ReminderTime'] = $item['value'];
                $hash['ReminderSet'] = 1;
                break;

            case 'STATUS':
                $hash['Complete'] = $item['value'] == 'COMPLETED' ? 1 : 0;
                break;

            case 'CATEGORIES':
                $hash['Categories'] = $item['value'];
                break;

            case 'CLASS':
                switch (Horde_String::upper($item['value'])) {
                case 'PUBLIC':
                    $hash['Sensitivity'] = 0;
                    break;

                case 'PRIVATE':
                    $hash['Sensitivity'] = 2;
                    break;

                case 'CONFIDENTIAL':
                    $hash['Sensitivity'] = 3;
                    break;
                }
                break;
            }
        }

        if ($due && !isset($hash['ReminderSet'])) {
            // Parse VALARM components.
            foreach ($content->getComponents() as $component) {
                if ($component->getType() != 'vAlarm' ||
                    is_a($trigger = $component->getAttribute('TRIGGER'), 'PEAR_Error') ||
                    is_array($trigger) ||
                    empty($trigger)) {
                    continue;
                }
                $hash['ReminderSet'] = 1;
                $hash['ReminderTime'] = Horde_Icalendar::_exportDateTime($due - $trigger);
            }
        }

        return SyncML_Device_sync4j::array2sif(
            $hash,
            '<?xml version="1.0"?><task>',
            '</task>');
    }

    /**
     * Sync4j as of Funambol Outlook connector 3.0.15 can't deal
     * with <![CDATA[ so omit it.
     * The Funambol Sync4j client chokes on the cdata
     * so for this device it has to be set to false. Syn4j uses base64
     * encoding and so the problems with escaping does not occur.
     */
    function useCdataTag()
    {
        return false;
    }

}
