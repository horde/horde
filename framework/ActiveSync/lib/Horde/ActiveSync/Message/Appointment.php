<?php
/**
 * Horde_ActiveSync_Message_Appointment class represents a single ActiveSync
 * Appointment object. Responsible for mapping all fields to and from wbxml.
 *
 * @copyright 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
class Horde_ActiveSync_Message_Appointment extends Horde_ActiveSync_Message_Base
{
    /* Sensitivity */
    const SENSITIVITY_NORMAL = 0;
    const SENSITIVITY_PERSONAL = 1;
    const SENSITIVITY_PRIVATE = 2;
    const SENSITIVITY_CONFIDENTIAL = 3;

    /* Busy status */
    const BUSYSTATUS_FREE = 0;
    const BUSYSTATUS_TENATIVE = 1;
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
    const RESPONSE_TENATIVE = 2;
    const RESPONSE_ACCEPTED =3;
    const RESPONSE_DECLINED = 4;
    const RESPONSE_NORESPONSE = 5; // Not sure what difference this is to NONE?

    /**
     * Workarounds for PHP < 5.2.6 not being able to return an array by reference
     * from a __get() property.
     */
    public $exceptions = array();
    public $attendees;
    public $categories;

    /**
     * Constructor
     *
     * @param array $params
     *
     * @return Horde_ActiveSync_Message_Appointment
     */
    public function __construct($params = array()) {
        $mapping = array(
            SYNC_POOMCAL_TIMEZONE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'timezone'),
            SYNC_POOMCAL_DTSTAMP => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'dtstamp', Horde_ActiveSync_Message_Base::KEY_TYPE => Horde_ActiveSync_Message_Base::TYPE_DATE),
            SYNC_POOMCAL_STARTTIME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'starttime', Horde_ActiveSync_Message_Base::KEY_TYPE => Horde_ActiveSync_Message_Base::TYPE_DATE),
            SYNC_POOMCAL_SUBJECT => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'subject'),
            SYNC_POOMCAL_UID => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'uid', Horde_ActiveSync_Message_Base::KEY_TYPE => Horde_ActiveSync_Message_Base::TYPE_HEX),
            SYNC_POOMCAL_ORGANIZERNAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'organizername'),
            SYNC_POOMCAL_ORGANIZEREMAIL => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'organizeremail'),
            SYNC_POOMCAL_LOCATION => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'location'),
            SYNC_POOMCAL_ENDTIME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'endtime', Horde_ActiveSync_Message_Base::KEY_TYPE => Horde_ActiveSync_Message_Base::TYPE_DATE),
            SYNC_POOMCAL_RECURRENCE => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'recurrence', Horde_ActiveSync_Message_Base::KEY_TYPE => 'Horde_ActiveSync_Message_Recurrence'),
            SYNC_POOMCAL_SENSITIVITY => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'sensitivity'),
            SYNC_POOMCAL_BUSYSTATUS => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'busystatus'),
            SYNC_POOMCAL_ALLDAYEVENT => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'alldayevent'),
            SYNC_POOMCAL_REMINDER => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'reminder'),
            SYNC_POOMCAL_RTF => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'rtf'),
            SYNC_POOMCAL_MEETINGSTATUS => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'meetingstatus'),
            SYNC_POOMCAL_ATTENDEES => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'attendees', Horde_ActiveSync_Message_Base::KEY_TYPE => 'Horde_ActiveSync_Message_Attendee', Horde_ActiveSync_Message_Base::KEY_VALUES => SYNC_POOMCAL_ATTENDEE),
            SYNC_POOMCAL_BODY => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'body'),
            SYNC_POOMCAL_BODYTRUNCATED => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'bodytruncated'),
            SYNC_POOMCAL_EXCEPTIONS => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'exceptions', Horde_ActiveSync_Message_Base::KEY_TYPE => 'Horde_ActiveSync_Message_Exception', Horde_ActiveSync_Message_Base::KEY_VALUES => SYNC_POOMCAL_EXCEPTION),
            SYNC_POOMCAL_CATEGORIES => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'categories', Horde_ActiveSync_Message_Base::KEY_VALUES => SYNC_POOMCAL_CATEGORY),
            SYNC_POOMCAL_RESPONSETYPE => array(Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'responsetype'),
        );

        parent::__construct($mapping, $params);
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
        /* TODO: Not sure about these 'Nth' rules - might need more eyes */
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
     * @param string $sensitivity
     */
    public function setSensitivity($sensitivity)
    {
        switch ($sensitivity) {
        case 'normal':
            $sensitivity = self::SENSITIVITY_NORMAL;
            break;
        case 'personal':
            $sensitivity = self::SENSITIVITY_PERSONAL;
            break;
        case 'private':
            $sensitivity = self::SENSITIVITY_PRIVATE;
            break;
        case 'confidential':
            $sensitivity = self::SENSITIVITY_CONFIDENTIAL;
            break;
        default:
            return;
        }

        $this->_properties['sensitivity'] = $sensitivity;
    }

    /**
     * Return the sensitivity setting for this appointment
     *
     * @return string  One of: normal, personal, private, confidential
     */
    public function getSensitivity()
    {
        switch ($this->_getAttribute('sensitivity')) {
        case self::SENSITIVITY_NORMAL:
            return 'normal';
            break;
        case self::SENSITIVITY_PERSONAL:
            return 'personal';
            break;
        case self::SENSITIVITY_PRIVATE:
            return 'private';
            break;
        case self::SENSITIVITY_CONFIDENTIAL:
            return 'confidential';
            break;
        default:
            return;
        }
    }

    /**
     * Sets the busy status for this appointment
     *
     * Should be one of:
     *   free, tenative, busy, out
     *
     *
     * @param string  $busy  The busy status to use
     */
    public function setBusyStatus($busy)
    {
        switch ($busy) {
        case 'free':
            $busy = self::BUSYSTATUS_FREE;
            break;
        case 'tenative':
            $busy = self::BUSYSTATUS_TENATIVE;
            break;
        case 'busy':
            $busy = self::BUSYSTATUS_BUSY;
            break;
        case 'out':
            $busy = self::BUSYSTATUS_OUT;
            break;
        default:
            return;
        }

        $this->_properties['busystatus'] = $busy;
    }

    /**
     * Return the busy status for this appointment.
     *
     * @return string  One of free, tenative, busy, out
     */
    public function getBusyStatus()
    {
        switch ($this->_getAttribute('busystatus')) {
        case self::BUSYSTATUS_FREE:
            return 'free';
            break;
        case self::BUSYSTATUS_TENATIVE:
            return 'tenative';
            break;
        case self::BUSYSTATUS_BUSY:
            return 'busy';
            break;
        case self::BUSYSTATUS_OUT:
            return 'out';
            break;
        default:
            return;
        }
    }

    /**
     * Set user response type. Should be one of:
     *   none, organizer, tenative, accepted, declined
     *
     * @param string $response  The response type
     */
    public function setResponseType($response)
    {
        switch ($response) {
        case 'none':
            $response = self::RESPONSE_NONE;
            break;
        case 'organizer':
            $response = self::RESPONSE_ORGANIZER;
            break;
        case 'tenative':
            $response = self::RESPONSE_TENATIVE;
            break;
        case 'accepted':
            $response = self::RESPONSE_ACCEPTED;
            break;
        case 'declined':
            $response = self::RESPONSE_DECLINED;
            break;
        default:
            return;
        }

        $this->_properties['responsetype'] = $response;
    }

    /**
     * Get response type
     *
     * @return string one of: none, organizer, tenatve, accepted, declined
     */
    public function getResponseType()
    {
        switch ($this->_getAttribute('responsetype')) {
        case self::RESPONSE_NONE:
            return 'none';
            break;
        case self::RESPONSE_ORGANIZER:
            return 'organizer';
            break;
        case self::RESPONSE_TENATIVE:
            return 'tenative';
            break;
        case self::RESPONSE_ACCEPTED:
            return 'accepted';
            break;
        case self::RESPONSE_DECLINED:
            return 'declined';
            break;
        default:
            return;
        }
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
     * TODO: Not really sure about when these would be used.
     *
     *
     * @param <type> $status
     */
    public function setMeetingStatus($status)
    {
        switch ($status) {
        case 'none':
            $status = self::MEETING_NOT_MEETING;
            break;
        case 'meeting':
            $status = self::MEETING_IS_MEETING;
            break;
        case 'received':
            $status = self::MEETING_RECEIVED;
            break;
        case 'canceled':
            $status = self::MEETING_CANCELLED;
            break;
        default:
            return;
        }

        $this->_properties['meetingstatus'] = $status;
    }

    /**
     *
     * @return string  One of none, meeting, received, canceled
     */
    public function getMeetingStatus()
    {
        switch ($this->_getAttribute('meetingstatus')) {
        case self::MEETING_NOT_MEETING:
            return 'none';
            break;
        case self::MEETING_IS_MEETING:
            return 'meeting';
            break;
        case  self::MEETING_RECEIVED:
            return 'received';
            break;
        case self::MEETING_CANCELLED:
            return 'canceled';
            break;
        default:
            return;
        }
    }

    /**
     * Add an attendee to this appointment
     *
     * @param array $attendee   'name', 'email' for each attendee
     */
    public function addAttendee($attendee)
    {
        if (!isset($this->_properties['attendees']) || !is_array($this->_properties['attendees'])) {
            $this->_properties['attendees'] = array();
        }

        /* Both email and name are REQUIRED if setting an attendee */
        $this->_properties['attendees'][] = $attendee;
    }

    /**
     * Get a list of this event's attendees
     *
     * @return array  An array of 'name' and 'email' hashes
     */
    public function getAttendees()
    {
        return $this->_getAttribute('attendees', array());
    }

    /**
     * TODO
     *
     * @param <type> $body
     */
    public function setBody($body)
    {

    }

    public function getBody()
    {

    }

    /**
     * Add a category to the appointment
     *
     * TODO
     *
     * @param string $category
     */
    public function addCategory($category)
    {

    }

    public function getCategory()
    {

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

    protected function _getAttribute($name, $default = null)
    {
        if (!empty($this->_properties[$name])) {
            return $this->_properties[$name];
        } else {
            return $default;
        }
    }
}