<?php
/**
 * Object to parse and represent iCalendar data encapsulated in a TNEF file.
 *
 * Copyright 2002-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Compress
 */
/**
 * Copyright 2002-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Compress
 */
class Horde_Compress_Tnef_ICalendar extends Horde_Compress_Tnef_Object
{
    /**
     * ICalendar METHOD
     *
     * @var string
     */
    public $method;

    /**
     *
     * @var string
     */
    public $summary;

    /**
     *
     * @var string
     */
    public $location;

    /**
     *
     * @var string
     */
    public $url;

    /**
     *
     * @var Horde_Date
     */
    public $start_utc;

    /**
     *
     * @var Horde_Date
     */
    public $end_utc;

    /**
     *
     * @var string
     */
    public $duration;

    /**
     *
     * @var boolean
     */
    public $allday;

    /**
     *
     * @var string
     */
    public $organizer;

    /**
     *
     * @var string
     */
    public $last_modifier;

    /**
     *
     * @var string
     */
    public $uid;

    /**
     * Recurrence data.
     *
     * @var array
     */
    public $recurrence = array();

    /**
     *
     * @var integer
     */
    public $type;

    /**
     *
     * @var Horde_Date
     */
    public $created;

    /**
     *
     * @var Horde_Date
     */
    public $modified;

    /**
     * Output the data for this object in an array.
     *
     * @return array
     *   - type: (string)    The MIME type of the content.
     *   - subtype: (string) The MIME subtype.
     *   - name: (string)    The filename.
     *   - stream: (string)  The file data.
     */
    public function toArray()
    {
        return $this->_generateItip();
    }

    /**
     * Allow this object to set any TNEF attributes it needs to know about,
     * ignore any it doesn't care about.
     *
     * @param integer $attribute  The attribute descriptor.
     * @param mixed $value        The value from the MAPI stream.
     * @param integer $size       The byte length of the data, as reported by
     *                            the MAPI data.
     */
    public function setTnefAttribute($attribute, $value, $size)
    {

    }

    /**
     * Allow this object to set any MAPI attributes it needs to know about,
     * ignore any it doesn't care about.
     *
     * @param integer $type  The attribute type descriptor.
     * @param integer $name  The attribute name descriptor.
     */
    public function setMapiAttribute($type, $name, $value)
    {
        switch ($name) {
        case Horde_Compress_Tnef::MAPI_CONVERSATION_TOPIC:
            $this->summary = $value;
            break;
        case Horde_Compress_Tnef::MAPI_APPOINTMENT_LOCATION:
            $this->location = $value;
            break;
        case Horde_Compress_Tnef::MAPI_APPOINTMENT_URL:
            $this->url = $value;
            break;
        case Horde_Compress_Tnef::MAPI_APPOINTMENT_START_WHOLE:
            try {
                $this->start_utc = new Horde_Date(Horde_Mapi::filetimeToUnixtime($value), 'UTC');
            } catch (Horde_Mapi_Exception $e) {
                throw new Horde_Compress_Exception($e);
            }
            break;
        case Horde_Compress_Tnef::MAPI_APPOINTMENT_END_WHOLE:
            try {
                $this->end_utc = new Horde_Date(Horde_Mapi::filetimeToUnixtime($value), 'UTC');
            } catch (Horde_Mapi_Exception $e) {
                throw new Horde_Compress_Exception($e);
            }
            break;
        case Horde_Compress_Tnef::MAPI_APPOINTMENT_DURATION:
            $this->duration = $value;
            break;
        case Horde_Compress_Tnef::MAPI_APPOINTMENT_SUBTYPE:
            $this->allday = $value;
            break;
        case Horde_Compress_Tnef::MAPI_ORGANIZER_ALIAS:
            $this->organizer = $value;
            break;
        case Horde_Compress_Tnef::MAPI_LAST_MODIFIER_NAME:
            $this->last_modifier = $value;
            break;
        case Horde_Compress_Tnef::MAPI_ENTRY_UID:
            $this->uid = Horde_Mapi::getUidFromGoid(bin2hex($value));
            break;
        case Horde_Compress_Tnef::MAPI_APPOINTMENT_RECUR:
            $this->recurrence['recur'] = $this->_parseRecurrence($value);
            break;
        case Horde_Compress_Tnef::MAPI_RECURRING:
            // ?? Reset $this->recurrence?
            break;
        case Horde_Compress_Tnef::MAPI_RECURRENCE_TYPE:
            $this->recurrence['type'] = $value;
            break;
        case Horde_Compress_Tnef::MAPI_MEETING_REQUEST_TYPE:
            $this->type = $value;
            break;
        case Horde_Compress_Tnef::MAPI_CREATION_TIME:
            try {
                $this->created = new Horde_Date(Horde_Mapi::filetimeToUnixtime($value), 'UTC');
            } catch (Horde_Mapi_Exception $e) {
                throw new Horde_Compress_Exception($e);
            }
            break;
        case Horde_Compress_Tnef::MAPI_MODIFICATION_TIME:
            try {
                $this->modified = new Horde_Date(Horde_Mapi::filetimeToUnixtime($value), 'UTC');
            } catch (Horde_Mapi_Exception $e) {
                throw new Horde_Compress_Exception($e);
            }
            break;
        }
    }

    /**
     * Parse recurrence properties.
     *
     * @param string  $value MAPI stream
     *
     * @return Horde_Date_Recurrence
     */
    protected function _parseRecurrence($value)
    {
        $deleted = $modified = array();

        // both are 0x3004 (version strings);
        $this->_geti($value, 16);
        $this->_geti($value, 16);

        $freq = $this->_geti($value, 16);
        $pattern = $this->_geti($value, 16);
        $calendarType = $this->_geti($value, 16);
        $firstDt = $this->_geti($value, 32);
        $period = $this->_geti($value, 32);

        // Only used for tasks, otherwise value must be zero.
        $flag = $this->_geti($value, 32);

        // TypeSpecific field
        switch ($pattern) {
        case Horde_Compress_Tnef::PATTERN_DAY:
            // Nothing here to see, move along.
            break;
        case Horde_Compress_Tnef::PATTERN_WEEK:
            // Bits: 0/unused, 1/Saturday, 2/Friday, 3/Thursday, 4/Wednesday,
            // 5/Tuesday, 6/Monday, 7/Sunday.
            $day = $this->_geti($value, 8);
            // ??
            $this->_geti($value, 24);
            break;
        case Horde_Compress_Tnef::PATTERN_MONTH:
        case Horde_Compress_Tnef::PATTERN_MONTH_END:
            // Day of month on which the recurrence falls.
            $day = $this->_geti($value, 32);
            break;
        case Horde_Compress_Tnef::PATTERN_MONTH_NTH:
            // Bits: 0/unused, 1/Saturday, 2/Friday, 3/Thursday, 4/Wednesday,
            // 5/Tuesday, 6/Monday, 7/Sunday.
            // For Nth Weekday of month
            $day = $this->_geti($value, 8);
            $this->_geti($value, 24);
            $n = $this->_geti($value, 32);
            break;
        }
        $end = $this->_geti($value, 32);
        $count = $this->_geti($value, 32);
        $fdow = $this->_geti($value, 32);
        $deletedCount = $this->_geti($value, 32);
        for ($i = 0; $i < $deletedCount; $i++) {
            $deleted[] = $this->_geti($value, 32);
        }
        $modifiedCount = $this->_geti($value, 32);
        for ($i = 0; $i < $modifiedCount; $i++) {
            $modified[] = $this->_geti($value, 32);
        }

        // What Timezone are these in?
        try {
            $startDate = new Horde_Date(Horde_Mapi::filetimeToUnixtime($this->_geti($value, 32)));
            $endDate = new Horde_Date(Horde_Mapi::filetimeToUnixtime($this->_geti($value, 32)));
        } catch (Horde_Mapi_Exception $e) {
            throw new Horde_Compress_Exception($e);
        }

        $rrule = new Horde_Date_Recurrence($startDate);
        switch ($pattern) {
        case Horde_Compress_Tnef::PATTERN_DAY:
            $rrule->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
            break;
        case Horde_Compress_Tnef::PATTERN_WEEK:
            $rrule->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
            break;
        case Horde_Compress_Tnef::PATTERN_MONTH:
        case Horde_Compress_Tnef::PATTERN_MONTH_END:
            $rrule->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_DATE);
            break;
        case Horde_Compress_Tnef::PATTERN_MONTH_NTH:
            $rrule->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY);
            break;
        default:
            if ($freq == Horde_Compress_Tnef::RECUR_YEARLY) {
                $rrule->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY);
            }
        }

        switch ($end) {
        case Horde_Compress_Tnef::RECUR_END_N:
            $rrule->setRecurCount($count);
            break;
        case Horde_Compress_Tnef::RECUR_END_DATE:
            $rrule->setRecurEnd($endDate);
            break;
        }

        return $rrule;
    }

    /**
     * Generate an iTip from embedded TNEF MEETING data.
     *
     * @return array  see @self::toArray().
     */
    protected function _generateItip()
    {
        // Meeting requests will have 'type' set to a non-empty value.
        // if (!empty($this->_iTip[0]) && !empty($this->_iTip[0]['method']) &&
        //     $this->_iTip[0]['method'] != 'VTODO') {
            $iCal = new Horde_Icalendar();

            // METHOD
            if ($this->type) {
                switch ($this->type) {
                case self::MAPI_MEETING_INITIAL:
                case self::MAPI_MEETING_FULL_UPDATE:
                    $this->method = 'REQUEST';
                    break;
                case self::MAPI_MEETING_INFO:
                    $this->method = 'PUBLISH';
                    break;
                }
            }
            $iCal->setAttribute('METHOD', $this->method);

            // VEVENT
            $vEvent = Horde_Icalendar::newComponent('vevent', $iCal);
            if (empty($this->end_utc)) {
                return;
            }
            $end = clone $this->end_utc;
            $end->sec++;
            if ($this->allday) {
                $vEvent->setAttribute('DTSTART', $this->start_utc, array('VALUE' => 'DATE'));
                $vEvent->setAttribute('DTEND', $end, array('VALUE' => 'DATE'));
            } else {
                $vEvent->setAttribute('DTSTART', $this->start_utc);
                $vEvent->setAttribute('DTEND', $end);
            }
            $vEvent->setAttribute('DTSTAMP', $_SERVER['REQUEST_TIME']);
            $vEvent->setAttribute('UID', $this->uid);
            if ($this->created) {
                $vEvent->setAttribute('CREATED', $this->created);
            }
            if ($this->modified) {
                $vEvent->setAttribute('LAST-MODIFIED', $this->modified);
            }
            $vEvent->setAttribute('SUMMARY', $this->summary);

            if (!$this->organizer && $this->last_modifier) {
                $email = $this->last_modifier;
            } else if ($this->organizer) {
                $email = $this->organizer;
            }
            if (!empty($email)) {
                $vEvent->setAttribute('ORGANIZER', 'mailto:' . $email);
            }
            if ($this->url) {
                $vEvent->setAttribute('URL', $this->_url);
            }
            if (!empty($this->recurrence['recur'])) {
                $rrule = $this->recurrence['recur']->toRRule20($iCal);
                $vEvent->setAttribute('RRULE', $rrule);
            }
            $iCal->addComponent($vEvent);

            return array(
                'type'    => 'text',
                'subtype' => 'calendar',
                'name'    => $this->summary,
                'stream'  => $iCal->exportvCalendar()
            );
    }

}