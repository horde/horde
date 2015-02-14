<?php
/**
 * Object to parse and represent iCalendar data encapsulated in a TNEF file.
 *
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
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
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
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
    const PART_ACTION    = 'NEEDS-ACTION';
    const PART_TENTATIVE = 'TENTATIVE';
    const PART_DECLINE   = 'DECLINE';
    const PART_ACCEPTED  = 'ACCEPTED';

    /**
     * ICalendar METHOD
     *
     * @var string
     */
    protected $_method;

    /**
     *
     * @var string
     */
    protected $_summary;

    /**
     *
     * @var string
     */
    protected $_location;

    /**
     *
     * @var string
     */
    protected $_url;

    /**
     *
     * @var Horde_Date
     */
    protected $_startUtc;

    /**
     *
     * @var Horde_Date
     */
    protected $_endUtc;

    /**
     *
     * @var string
     */
    protected $_duration;

    /**
     *
     * @var boolean
     */
    protected $_allday;

    /**
     *
     * @var string
     */
    protected $_organizer;

    /**
     *
     * @var string
     */
    protected $_lastModifier;

    /**
     *
     * @var string
     */
    protected $_uid;

    /**
     * Recurrence data.
     *
     * @var array
     */
    protected $_recurrence = array();

    /**
     *
     * @var integer
     */
    protected $_type;

    /**
     *
     * @var Horde_Date
     */
    protected $_created;

    /**
     *
     * @var Horde_Date
     */
    protected $_modified;

    /**
     * Cache of the iCalendar text.
     *
     * @var string
     */
    protected $_content;

    /**
     * List of required attendees parsed from the MAPI object.
     *
     * @var string
     */
    protected $_requiredAttendees;

    /**
     * The current PARTSTAT property for this meeting request.
     *
     * @var string  A self::PART_* constant.
     */
    protected $_partStat;

    /**
     * The description/body of the meeting request.
     *
     * @var string
     */
    protected $_description;

    /**
     * RSVP property
     *
     * @var boolean
     */
    protected $_rsvp = false;
    /**
     * MIME type.
     *
     * @var string
     */
    public $type = 'text/calendar';

    /**
     * Accessor
     *
     * @param  string $property
     * @return mixed
     */
    public function __get($property)
    {
        if ($property == 'content') {
            if (empty($this->_content)) {
                $this->_toItip();
                return $this->_content;
            }
        }

        throw new InvalidArgumentException('Invalid property access.');
    }

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
        return $this->_toItip();
    }

    /**
     * Set the METHOD parameter, used to help generate the PART-STAT attribute.
     *
     * @param string $method  The METHOD parameter.
     * @param string $class   The message class.
     */
    public function setMethod($method, $class = null)
    {
        $this->_method = $method;
        switch ($class) {
        case Horde_Compress_Tnef::IPM_MEETING_RESPONSE_TENT:
            $this->_partStat = self::PART_TENTATIVE;
            $this->_rsvp = false;
            break;
        case Horde_Compress_Tnef::IPM_MEETING_RESPONSE_NEG:
            $this->_partStat = self::PART_DECLINE;
            $this->_rsvp = false;
            break;
        case Horde_Compress_Tnef::IPM_MEETING_RESPONSE_POS:
            $this->_partStat = self::PART_ACCEPTED;
            $this->_rsvp = false;
            break;
        case Horde_Compress_Tnef::IPM_MEETING_REQUEST:
            $this->_partStat =self::PART_ACTION;
            $this->_rsvp = true;
            break;
        }
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
        switch ($attribute) {
        case Horde_Compress_Tnef::ABODY;
            $this->_description = $value;
            break;
        }
    }

    /**
     * Allow this object to set any MAPI attributes it needs to know about,
     * ignore any it doesn't care about.
     *
     * @param integer $type  The attribute type descriptor.
     * @param integer $name  The attribute name descriptor.
     * @param mixed $value   The value to set.
     *
     * @throws  Horde_Compress_Exception
     */
    public function setMapiAttribute($type, $name, $value, $ns = null)
    {
        // First check for pidTag* properties - these will have to namespace.
        // @todo look at just cocantenating the GUID with the pidLid in the
        // constants in H6?
        if (empty($ns)) {
            switch ($name) {
            case Horde_Compress_Tnef::MAPI_CONVERSATION_TOPIC:
                $this->_summary = $value;
                break;
            case Horde_Compress_Tnef::MAPI_SENDER_SMTP: // pidTag
            case Horde_Compress_Tnef::MAPI_LAST_MODIFIER_NAME:
                // Sender SMTP is more appropriate, but not present in all
                // meeting request MAPI objects (it's normally taken form the
                // parent MAPI mail message object) Since this class doesn't
                // (currently) have access to the parent MIME
                // part (since this isn't necessarily from an email), this is the
                // only hope of obtaining an ORGANIZER.
                $this->_lastModifier = $value;
                break;
            case Horde_Compress_Tnef::MAPI_CREATION_TIME:
                try {
                    $this->_created = new Horde_Date(Horde_Mapi::filetimeToUnixtime($value), 'UTC');
                } catch (Horde_Mapi_Exception $e) {
                    throw new Horde_Compress_Exception($e);
                }
                break;
            case Horde_Compress_Tnef::MAPI_MODIFICATION_TIME:
                try {
                    $this->_modified = new Horde_Date(Horde_Mapi::filetimeToUnixtime($value), 'UTC');
                } catch (Horde_Mapi_Exception $e) {
                    throw new Horde_Compress_Exception($e);
                }
                break;
            case Horde_Compress_Tnef::MAPI_RESPONSE_REQUESTED:
                $this->_rsvp = $value;
                break;
            case Horde_Compress_Tnef::MAPI_TAG_RTF_COMPRESSED:
                // We may already have a description from the TNEF attBODY attribute
                if (empty($this->_description)) {
                    $this->_description = $value;
                }
                break;
            }
        } elseif ($ns == Horde_Compress_Tnef::PSETID_APPOINTMENT) {
            switch ($name) {
            case Horde_Compress_Tnef::MAPI_APPOINTMENT_LOCATION:
                $this->_location = $value;
                break;
            case Horde_Compress_Tnef::MAPI_APPOINTMENT_URL:
                $this->_url = $value;
                break;
            case Horde_Compress_Tnef::MAPI_APPOINTMENT_START_WHOLE:
                try {
                    $this->_startUtc = new Horde_Date(Horde_Mapi::filetimeToUnixtime($value), 'UTC');
                } catch (Horde_Mapi_Exception $e) {
                    throw new Horde_Compress_Exception($e);
                }
                break;
            case Horde_Compress_Tnef::MAPI_APPOINTMENT_END_WHOLE:
                try {
                    $this->_endUtc = new Horde_Date(Horde_Mapi::filetimeToUnixtime($value), 'UTC');
                } catch (Horde_Mapi_Exception $e) {
                    throw new Horde_Compress_Exception($e);
                }
                break;
            case Horde_Compress_Tnef::MAPI_APPOINTMENT_DURATION:
                $this->_duration = $value;
                break;
            case Horde_Compress_Tnef::MAPI_APPOINTMENT_SUBTYPE:
                $this->_allday = $value;
                break;
            case Horde_Compress_Tnef::MAPI_ORGANIZER_ALIAS:
                $this->_organizer = $value;
                break;
            case Horde_Compress_Tnef::MAPI_TO_ATTENDEES:
                // Don't even ask. Why, Microsoft, why??
                $value = str_replace(array('(', ')'), array('<', '>'), $value);
                $this->_requiredAttendees = $value;
                break;
            case Horde_Compress_Tnef::MAPI_APPOINTMENT_RECUR:
                $this->_recurrence['recur'] = $this->_parseRecurrence($value);
                break;
            case Horde_Compress_Tnef::MAPI_RECURRING:
                // ?? Reset $this->_recurrence?
                break;
            case Horde_Compress_Tnef::MAPI_RECURRENCE_TYPE:
                $this->_recurrence['type'] = $value;
                break;
            case Horde_Compress_Tnef::MAPI_RESPONSE_STATUS:
                // Don't think we need this, it seems more geared towards writing
                // a TNEF. Indicates the response status of an ATTENDEE. Putting
                // this here for reference, see MS-OXOCAL 2.2.1.11
                break;
            }
        } elseif ($ns == Horde_Compress_Tnef::PSETID_MEETING) {
            switch ($name) {
            case Horde_Compress_Tnef::MAPI_ENTRY_CLEANID:
            case Horde_Compress_Tnef::MAPI_ENTRY_UID:
                // Still not 100% sure about where a suitable UID comes from;
                // These attributes are all said to contain it, at various times.
                // The "Clean" UID is supposed to only be in appointments that
                // are exceptions to a recurring series, though I have a number
                // of examples where that is not the case. Also, in some cases
                // some of these attributes seem to be set here multiple times,
                // sometimes with non-empty and then empty values, so never set
                // self::$_uid if it is already set, or if $value is empty.
                if (empty($this->_uid) && !empty($value)) {
                    $this->_uid = Horde_Mapi::getUidFromGoid(bin2hex($value));
                }
                break;
            case Horde_Compress_Tnef::MAPI_MEETING_REQUEST_TYPE: //pset
                $this->_type = $value;
                break;
            }
        } else {
            $this->_logger->notice(sprintf('Unknown namespace GUID: %s', $ns));
        }
    }

    /**
     * Parse recurrence properties.
     *
     * @param string  $value MAPI stream
     *
     * @return Horde_Date_Recurrence
     * @throws  Horde_Compress_Exception
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
    protected function _toItip()
    {
        $iCal = new Horde_Icalendar();

        // METHOD
        if ($this->_type) {
            switch ($this->_type) {
            case Horde_Compress_Tnef::MAPI_MEETING_INITIAL:
            case Horde_Compress_Tnef::MAPI_MEETING_FULL_UPDATE:
                $this->_method = 'REQUEST';
                break;
            case Horde_Compress_Tnef::MAPI_MEETING_INFO:
                $this->_method = 'PUBLISH';
                break;
            }
        }
        $iCal->setAttribute('METHOD', $this->_method);

        // VEVENT
        $vEvent = Horde_Icalendar::newComponent('vevent', $iCal);
        if (empty($this->_endUtc)) {
            return;
        }
        $end = clone $this->_endUtc;
        $end->sec++;
        if ($this->_allday) {
            $vEvent->setAttribute('DTSTART', $this->_startUtc, array('VALUE' => 'DATE'));
            $vEvent->setAttribute('DTEND', $end, array('VALUE' => 'DATE'));
        } else {
            $vEvent->setAttribute('DTSTART', $this->_startUtc);
            $vEvent->setAttribute('DTEND', $end);
        }
        $vEvent->setAttribute('DTSTAMP', $_SERVER['REQUEST_TIME']);
        $vEvent->setAttribute('UID', $this->_uid);
        if ($this->_created) {
            $vEvent->setAttribute('CREATED', $this->_created);
        }
        if ($this->_modified) {
            $vEvent->setAttribute('LAST-MODIFIED', $this->_modified);
        }

        // SUMMARY and DESCRIPTION
        $vEvent->setAttribute('SUMMARY', $this->_summary);
        if ($this->_description) {
            $vEvent->setAttribute('DESCRIPTION', trim($this->_description));
        }

        // ORGANIZER
        if (!$this->_organizer && $this->_lastModifier) {
            $email = $this->_lastModifier;
        } else if ($this->_organizer) {
            $email = $this->_organizer;
        }
        if (!empty($email)) {
            $vEvent->setAttribute('ORGANIZER', 'mailto:' . $email);
        }

        // ATTENDEE
        // @todo RSVP??
        if (!empty($this->_requiredAttendees)) {
            $list = new Horde_Mail_Rfc822_List($this->_requiredAttendees);
            foreach ($list as $email) {
                $params = array('ROLE' => 'REQ-PARTICIPANT');
                if (!empty($this->_partStat)) {
                    $params['PARTSTAT'] = $this->_partStat;
                }
                if ($this->_rsvp) {
                    $params['RSVP'] = 'TRUE';
                }
                $vEvent->setAttribute('ATTENDEE', $email->bare_address, $params);
            }
        }

        // LOCATION
        if ($this->_location) {
            $vEvent->setAttribute('LOCATION', $this->_location);
        }

        // URL
        if ($this->_url) {
            $vEvent->setAttribute('URL', $this->_url);
        }

        // RECUR
        if (!empty($this->_recurrence['recur'])) {
            $rrule = $this->_recurrence['recur']->toRRule20($iCal);
            $vEvent->setAttribute('RRULE', $rrule);
        }
        $iCal->addComponent($vEvent);
        $this->_content = $iCal->exportvCalendar();

        return array(
            'type'    => 'text',
            'subtype' => 'calendar',
            'name'    => $this->_summary,
            'stream'  => $this->_content
        );
    }

}