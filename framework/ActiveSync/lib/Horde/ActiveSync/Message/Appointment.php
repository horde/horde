<?php
/**
 * Horde_ActiveSync_Message_Appointment class represents a single ActiveSync
 * Appointment object. Responsible for mapping all fields to and from wbxml.
 *
 * @copyright 2010-2011 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
class Horde_ActiveSync_Message_Appointment extends Horde_ActiveSync_Message_Base
{
    /* POOMCAL Tag Constants */
    const POOMCAL_TIMEZONE = 'POOMCAL:Timezone';
    const POOMCAL_ALLDAYEVENT = 'POOMCAL:AllDayEvent';
    const POOMCAL_ATTENDEES = 'POOMCAL:Attendees';
    const POOMCAL_ATTENDEE = 'POOMCAL:Attendee';
    const POOMCAL_ATTENDEESTATUS =  'POOMCAL:AttendeeStatus';
    const POOMCAL_ATTENDEETYPE =  'POOMCAL:AttendeeType';
    const POOMCAL_EMAIL = 'POOMCAL:Email';
    const POOMCAL_NAME = 'POOMCAL:Name';
    const POOMCAL_BODY = 'POOMCAL:Body';
    const POOMCAL_BODYTRUNCATED = 'POOMCAL:BodyTruncated';
    const POOMCAL_BUSYSTATUS = 'POOMCAL:BusyStatus';
    const POOMCAL_CATEGORIES = 'POOMCAL:Categories';
    const POOMCAL_CATEGORY = 'POOMCAL:Category';
    const POOMCAL_RTF = 'POOMCAL:Rtf';
    const POOMCAL_DTSTAMP = 'POOMCAL:DtStamp';
    const POOMCAL_ENDTIME = 'POOMCAL:EndTime';
    const POOMCAL_EXCEPTION = 'POOMCAL:Exception';
    const POOMCAL_EXCEPTIONS = 'POOMCAL:Exceptions';
    const POOMCAL_DELETED = 'POOMCAL:Deleted';
    const POOMCAL_EXCEPTIONSTARTTIME = 'POOMCAL:ExceptionStartTime';
    const POOMCAL_LOCATION = 'POOMCAL:Location';
    const POOMCAL_MEETINGSTATUS = 'POOMCAL:MeetingStatus';
    const POOMCAL_ORGANIZEREMAIL = 'POOMCAL:OrganizerEmail';
    const POOMCAL_ORGANIZERNAME = 'POOMCAL:OrganizerName';
    const POOMCAL_RECURRENCE = 'POOMCAL:Recurrence';
    const POOMCAL_TYPE = 'POOMCAL:Type';
    const POOMCAL_UNTIL = 'POOMCAL:Until';
    const POOMCAL_OCCURRENCES = 'POOMCAL:Occurrences';
    const POOMCAL_INTERVAL = 'POOMCAL:Interval';
    const POOMCAL_DAYOFWEEK = 'POOMCAL:DayOfWeek';
    const POOMCAL_DAYOFMONTH = 'POOMCAL:DayOfMonth';
    const POOMCAL_WEEKOFMONTH = 'POOMCAL:WeekOfMonth';
    const POOMCAL_MONTHOFYEAR = 'POOMCAL:MonthOfYear';
    const POOMCAL_REMINDER = 'POOMCAL:Reminder';
    const POOMCAL_SENSITIVITY = 'POOMCAL:Sensitivity';
    const POOMCAL_SUBJECT = 'POOMCAL:Subject';
    const POOMCAL_STARTTIME = 'POOMCAL:StartTime';
    const POOMCAL_UID = 'POOMCAL:UID';
    const POOMCAL_RESPONSETYPE =  'POOMCAL:ResponseType';

    /* Sensitivity */
    const SENSITIVITY_NORMAL = 0;
    const SENSITIVITY_PERSONAL = 1;
    const SENSITIVITY_PRIVATE = 2;
    const SENSITIVITY_CONFIDENTIAL = 3;

    /* Busy status */
    const BUSYSTATUS_FREE = 0;
    const BUSYSTATUS_TENTATIVE = 1;
    const BUSYSTATUS_BUSY = 2;
    const BUSYSTATUS_OUT = 3;

    /* All day meeting */
    const IS_ALL_DAY = 1;

    /* Meeting status */
    const MEETING_NOT_MEETING = 0;
    const MEETING_IS_MEETING = 1;
    const MEETING_RECEIVED = 3;
    const MEETING_CANCELLED = 5;
    const MEETING_CANCELLED_RECEIVED = 7;

    /* Response status */
    const RESPONSE_NONE = 0;
    const RESPONSE_ORGANIZER = 1;
    const RESPONSE_TENTATIVE = 2;
    const RESPONSE_ACCEPTED =3;
    const RESPONSE_DECLINED = 4;
    const RESPONSE_NORESPONSE = 5; // Not sure what difference this is to NONE?

    /**
     * Workarounds for PHP < 5.2.6 not being able to return an array by reference
     * from a __get() property.
     */
    public $exceptions = array();
    public $attendees = array();
    public $categories = array();
    public $bodytruncated = 0;

    /**
     * Constructor
     *
     * @param array $params
     *
     * @return Horde_ActiveSync_Message_Appointment
     */
    public function __construct($params = array()) {
        $this->_mapping = array(
            self::POOMCAL_TIMEZONE => array (self::KEY_ATTRIBUTE => 'timezone'),
            self::POOMCAL_DTSTAMP => array (self::KEY_ATTRIBUTE => 'dtstamp', self::KEY_TYPE => self::TYPE_DATE),
            self::POOMCAL_STARTTIME => array (self::KEY_ATTRIBUTE => 'starttime', self::KEY_TYPE => self::TYPE_DATE),
            self::POOMCAL_SUBJECT => array (self::KEY_ATTRIBUTE => 'subject'),
            self::POOMCAL_UID => array (self::KEY_ATTRIBUTE => 'uid', self::KEY_TYPE => self::TYPE_HEX),
            self::POOMCAL_ORGANIZERNAME => array (self::KEY_ATTRIBUTE => 'organizername'),
            self::POOMCAL_ORGANIZEREMAIL => array (self::KEY_ATTRIBUTE => 'organizeremail'),
            self::POOMCAL_LOCATION => array (self::KEY_ATTRIBUTE => 'location'),
            self::POOMCAL_ENDTIME => array (self::KEY_ATTRIBUTE => 'endtime', self::KEY_TYPE => self::TYPE_DATE),
            self::POOMCAL_RECURRENCE => array (self::KEY_ATTRIBUTE => 'recurrence', self::KEY_TYPE => 'Horde_ActiveSync_Message_Recurrence'),
            self::POOMCAL_SENSITIVITY => array (self::KEY_ATTRIBUTE => 'sensitivity'),
            self::POOMCAL_BUSYSTATUS => array (self::KEY_ATTRIBUTE => 'busystatus'),
            self::POOMCAL_ALLDAYEVENT => array (self::KEY_ATTRIBUTE => 'alldayevent'),
            self::POOMCAL_REMINDER => array (self::KEY_ATTRIBUTE => 'reminder'),
            self::POOMCAL_RTF => array (self::KEY_ATTRIBUTE => 'rtf'),
            self::POOMCAL_MEETINGSTATUS => array (self::KEY_ATTRIBUTE => 'meetingstatus'),
            self::POOMCAL_ATTENDEES => array (self::KEY_ATTRIBUTE => 'attendees', self::KEY_TYPE => 'Horde_ActiveSync_Message_Attendee', self::KEY_VALUES => self::POOMCAL_ATTENDEE),
            self::POOMCAL_BODY => array (self::KEY_ATTRIBUTE => 'body'),
            self::POOMCAL_BODYTRUNCATED => array (self::KEY_ATTRIBUTE => 'bodytruncated'),
            self::POOMCAL_EXCEPTIONS => array (self::KEY_ATTRIBUTE => 'exceptions', self::KEY_TYPE => 'Horde_ActiveSync_Message_Exception', self::KEY_VALUES => self::POOMCAL_EXCEPTION),
            self::POOMCAL_CATEGORIES => array (self::KEY_ATTRIBUTE => 'categories', self::KEY_VALUES => self::POOMCAL_CATEGORY),
            //self::POOMCAL_RESPONSETYPE => array(self::KEY_ATTRIBUTE => 'responsetype'),
        );

        $this->_properties = array(
            'timezone' => false,
            'dtstamp' => false,
            'starttime' => false,
            'subject' => false,
            'uid' => false,
            'organizername' => false,
            'organizeremail' => false,
            'location' => false,
            'endtime' => false,
            'recurrence' => false,
            'sensitivity' => false,
            'busystatus' => false,
            'alldayevent' => false,
            'reminder' => false,
            'rtf' => false,
            'meetingstatus' => false,
            'body' => false,
            'bodytruncated' => false,
        );

        parent::__construct($params);
    }

    /**
     * Set the timezone
     *
     * @param mixed $date     Either a Horde_Date or timezone descriptor such as
     *                        America/New_York etc...
     *
     */
    public function setTimezone($date)
    {
        if (!($date instanceof Horde_Date)) {
            $timezone = new Horde_Date(time(), $date);
        }
        $offsets = Horde_ActiveSync_Timezone::getOffsetsFromDate($date);
        $tz = Horde_ActiveSync_Timezone::getSyncTZFromOffsets($offsets);
        $this->_properties['timezone'] = $tz;
    }

    /**
     * Set the appointment's modify timestamp
     *
     * @param mixed $timestamp  Horde_Date or a unix timestamp
     */
    public function setDTStamp($date)
    {
        if (!($date instanceof Horde_Date)) {
            $date = new Horde_Date($date);
        }
        $this->_properties['dtstamp'] = $date;
    }

    /**
     * Get the appointment's dtimestamp
     *
     */
    public function getDTStamp()
    {
        return $this->_getAttribute('dtstamp');
    }

    /**
     * Set the appointment time/duration.
     *
     * @param array $timestamp 'start', 'end' or 'duration' (in seconds) or 'allday'
     */
    public function setDatetime($datetime = array())
    {
        /* Start date is always required */
        if (empty($datetime['start'])) {
            throw new InvalidArgumentException('Missing the required start parameter');
        }

        /* Get or calculate start and end time in local tz */
        $start = clone($datetime['start']);
        if (!empty($datetime['end'])) {
            $end = clone($datetime['end']);
        } elseif (!empty($datetime['duration'])) {
            $end = clone($start);
            $end->sec += $datetime['duration'];
        } else {
            $end = clone($start);
        }

        /*Is this an all day event? */
        if ($start->hour == 0 &&
            $start->min == 0 &&
            $start->sec == 0 &&
            $end->hour == 23 &&
            $end->min == 59) {

            $end = new Horde_Date(
                array('year'  => (int)$end->year,
                      'month' => (int)$end->month,
                      'mday'  => (int)$end->mday + 1));

            $this->_properties['alldayevent'] = self::IS_ALL_DAY;
        } elseif (!empty($datetime['allday'])) {
            $this->_properties['alldayevent'] = self::IS_ALL_DAY;
            $end = new Horde_Date(
                array('year'  => (int)$end->year,
                      'month' => (int)$end->month,
                      'mday'  => (int)$end->mday));
        }
        $this->_properties['starttime'] = $start;
        $this->_properties['endtime'] = $end;
    }

    /**
     * Get the appointment's time data
     *
     * @return array containing 'start', 'end', 'allday'
     */
    public function getDatetime()
    {
        return array(
            'start' => $this->_properties['starttime'],
            'end' => $this->_properties['endtime'],
            'allday' => !empty($this->_properties['alldayevent']) ? true : false
        );
    }

    /**
     * Set the appointment subject field.
     *
     * @param string $subject   UTF-8 string
     */
    public function setSubject($subject)
    {
        $this->_properties['subject'] = $subject;
    }

    public function getSubject()
    {
        return $this->_getAttribute('subject');
    }

    /**
     * Set the appointment uid. Note that this is the PIM's UID value, and not
     * the value that the server uses for the UID. ActiveSync messages do not
     * include any server uid value as part of the message natively.
     *
     * @param string $uid  The server's uid for this appointment
     */
    public function setUid($uid)
    {
        $this->_properties['uid'] = $uid;
    }

    /**
     * Get the PIM's UID. See not above regarding server UIDs.
     *
     * @return string
     */
    public function getUid()
    {
        return $this->_getAttribute('uid');
    }

    /**
     * Because the PIM doesn't pass the server uid as part of the message,
     * we need to add it manually so the backend can have access to it
     * when changing this object.
     *
     * @param string $uid  The server UID
     */
    public function setServerUID($uid)
    {
        $this->_getAttribute('serveruid');
    }

    /**
     * Obtain the server UID. See note above.
     *
     * @return string
     */
    public function getServerUID()
    {
        return $this->_getAttribute('serveruid');
    }

    /**
     * Set the organizer name and/or email
     *
     * @param array  'name' and 'email' for this appointment organizer.
     */
    public function setOrganizer($organizer)
    {
        $this->_properties['organizername'] = !empty($organizer['name'])
                                                ? $organizer['name']
                                                : '';

        $this->_properties['organizeremail'] = !empty($organizer['email'])
                                                ? $organizer['email']
                                                : '';
    }

    /**
     * Get the details for the appointment organizer
     *
     * @return array with 'name' and 'email' values
     */
    public function getOrganizer()
    {
        return array('name' => $this->_getAttribute('organizername'),
                     'email' => $this->_getAttribute('organizeremail'));
    }

    /**
     * Set appointment location field.
     *
     * @param string $location
     */
    public function setLocation($location)
    {
        $this->_properties['location'] = $location;
    }

    /**
     * Get the location field
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->_getAttribute('location');
    }

    /**
     * Set recurrence information for this appointment
     *
     * @param Horde_Date_Recurrence $recurrence
     */
    public function setRecurrence(Horde_Date_Recurrence $recurrence)
    {
        $r = new Horde_ActiveSync_Message_Recurrence();

        /* Map the type fields */
        switch ($recurrence->recurType) {
        case Horde_Date_Recurrence::RECUR_DAILY:
            $r->type = Horde_ActiveSync_Message_Recurrence::TYPE_DAILY;
            $r->dayofweek = Horde_Date::MASK_ALLDAYS;
            break;
        case Horde_Date_Recurrence::RECUR_WEEKLY;
            $r->type = Horde_ActiveSync_Message_Recurrence::TYPE_WEEKLY;
            $r->dayofweek = $recurrence->getRecurOnDays();
            break;
        case Horde_Date_Recurrence::RECUR_MONTHLY_DATE:
            $r->type = Horde_ActiveSync_Message_Recurrence::TYPE_MONTHLY;
            break;
        case Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY;
            $r->type = Horde_ActiveSync_Message_Recurrence::TYPE_MONTHLY_NTH;
            $r->dayofweek = $recurrence->getRecurOnDays();
            break;
        case Horde_Date_Recurrence::RECUR_YEARLY_DATE:
            $r->type = Horde_ActiveSync_Message_Recurrence::TYPE_YEARLY;
            break;
        case Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY:
            $r->type = Horde_ActiveSync_Message_Recurrence::TYPE_YEARLYNTH;
            $r->dayofweek = $recurrence->getRecurOnDays();
            break;
        }
        if (!empty($recurrence->recurInterval)) {
            $r->interval = $recurrence->recurInterval;
        }

        /* AS messages can only have one or the other (or none), not both */
        if ($recurrence->hasRecurCount()) {
            $r->occurrences = $recurrence->getRecurCount();
        } elseif ($recurrence->hasRecurEnd()) {
            $r->until = $recurrence->getRecurEnd();
        }

        $this->_properties['recurrence'] = $r;
    }

    /**
     * Obtain a recurrence object. Note this returns a Horde_Date_Recurrence
     * object, not Horde_ActiveSync_Message_Recurrence.
     *
     * @return Horde_Date_Recurrence
     */
    public function getRecurrence()
    {
        if (!$recurrence = $this->_getAttribute('recurrence')) {
            return false;
        }

        $rrule = new Horde_Date_Recurrence(new Horde_Date($this->_getAttribute('startdate')));

        /* Map MS AS type field to Horde_Date_Recurrence types */
        switch ($recurrence->type) {
        case Horde_ActiveSync_Message_Recurrence::TYPE_DAILY:
            $rrule->setRecurType(Horde_Date_Recurrence::RECUR_DAILY);
             break;
        case Horde_ActiveSync_Message_Recurrence::TYPE_WEEKLY:
            $rrule->setRecurType(Horde_Date_Recurrence::RECUR_WEEKLY);
            $rrule->setRecurOnDay($recurrence->dayofweek);
            break;
        case Horde_ActiveSync_Message_Recurrence::TYPE_MONTHLY:
            $rrule->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_DATE);
            break;
        case Horde_ActiveSync_Message_Recurrence::TYPE_MONTHLY_NTH:
            $rrule->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY);
            $rrule->setRecurOnDay($recurrence->dayofweek);
            break;
        case Horde_ActiveSync_Message_Recurrence::TYPE_YEARLY:
            $rrule->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_DATE);
            break;
        case Horde_ActiveSync_Message_Recurrence::TYPE_YEARLYNTH:
            $rrule->setRecurType(Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY);
            $rrule->setRecurOnDay($recurrence->dayofweek);
            break;
        }

        if ($rcnt = $recurrence->occurrences) {
            $rrule->setRecurCount($rcnt);
        }
        if ($runtil = $recurrence->until) {
            $rrule->setRecurEnd(new Horde_Date($runtil));
        }
        if ($interval = $recurrence->interval) {
            $rrule->setRecurInterval($interval);
        }

        return $rrule;
    }

    /**
     * Add a recurrence exception
     *
     * @param Horde_ActiveSync_Message_Exception $exception
     */
    public function addException(Horde_ActiveSync_Message_Exception $exception)
    {
        $this->exceptions[] = $exception;
    }

    /**
     *
     * @return array  An array of Horde_ActiveSync_Message_Exception objects
     */
    public function getExceptions()
    {
        return $this->exceptions;
    }

    /**
     * Set the sensitivity level for this appointment.
     *
     * Should be one of:
     *   normal, personal, private, confidential
     *
     * @param integer $sensitivity  The SENSITIVITY constant
     */
    public function setSensitivity($sensitivity)
    {
        $this->_properties['sensitivity'] = $sensitivity;
    }

    /**
     * Return the sensitivity setting for this appointment
     *
     * @return integer  The SENSITIVITY constant
     */
    public function getSensitivity()
    {
        return $this->_getAttribute('sensitivity');
    }

    /**
     * Sets the busy status for this appointment
     *
     * @param integer  $busy  The BUSYSTATUS constant
     */
    public function setBusyStatus($busy)
    {
        $this->_properties['busystatus'] = $busy;
    }

    /**
     * Return the busy status for this appointment.
     *
     * @return integer The BUSYSTATUS constant
     */
    public function getBusyStatus()
    {
        return $this->_getAttribute('busystatus');
    }

    /**
     * Set user response type. Should be one of:
     *   none, organizer, tentative, accepted, declined
     *
     * @param integer $response  The response type constant
     */
    public function setResponseType($response)
    {
        $this->_properties['responsetype'] = $response;
    }

    /**
     * Get response type
     *
     * @return integer  The responsetype constant
     */
    public function getResponseType()
    {
        return $this->_getAttribute('responsetype');
    }

    /**
     * Set reminder for this appointment.
     *
     * @param integer $minutes  The number of minutes before appintment to
     *                          trigger a reminder.
     */
    public function setReminder($minutes)
    {
        $this->_properties['reminder'] = (int)$minutes;
    }

    /**
     *
     * @return integer  Number of minutes before appointment for notifications.
     */
    public function getReminder()
    {
        return $this->_getAttribute('reminder');
    }

    /**
     * Set the status for this appointment. Should be one of:
     *   none, meeting, received, canceled, canceledreceived.
     *
     * @param integer $status  A MEETING_* constant
     */
    public function setMeetingStatus($status)
    {

        $this->_properties['meetingstatus'] = $status;
    }

    /**
     *
     * @return integer A MEETING_* constant
     */
    public function getMeetingStatus()
    {
        return $this->_getAttribute('meetingstatus', self::MEETING_NOT_MEETING);
    }

    /**
     * Add an attendee to this appointment
     *
     * @param array $attendee   'name', 'email' for each attendee
     */
    public function addAttendee($attendee)
    {
        /* Both email and name are REQUIRED if setting an attendee */
        $this->attendees[] = $attendee;
    }

    /**
     * Get a list of this event's attendees
     *
     * @return array  An array of 'name' and 'email' hashes
     */
    public function getAttendees()
    {
        return $this->attendees;
    }

    /**
     * Set the appointment's body
     *
     * @param string $body  UTF-8 encoded string
     */
    public function setBody($body)
    {
        $this->_properties['body'] = $body;
    }

    /**
     * Get the appointment's body
     *
     * @return string  UTF-8 encoded string
     */
    public function getBody()
    {
        return $this->_getAttribute('body');
    }

    /**
     * Add a category to the appointment
     *
     * @param string $category
     */
    public function addCategory($category)
    {
        $this->categories[] = $category;
    }

    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Return the collection class name the object is for.
     *
     * @return string
     */
    public function getClass()
    {
        return 'Calendar';
    }

}
