<?php
/**
 * Kronolith_Event defines a generic API for events.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
abstract class Kronolith_Event
{
    /**
     * Flag that is set to true if this event has data from either a storage
     * backend or a form or other import method.
     *
     * @var boolean
     */
    public $initialized = false;

    /**
     * Flag that is set to true if this event exists in a storage driver.
     *
     * @var boolean
     */
    public $stored = false;

    /**
     * The driver unique identifier for this event.
     *
     * @var string
     */
    public $eventID = null;

    /**
     * The UID for this event.
     *
     * @var string
     */
    protected $_uid = null;

    /**
     * The iCalendar SEQUENCE for this event.
     *
     * @var integer
     */
    protected $_sequence = null;

    /**
     * The user id of the creator of the event.
     *
     * @var string
     */
    public $creatorID = null;

    /**
     * The title of this event.
     *
     * @var string
     */
    public $title = '';

    /**
     * The location this event occurs at.
     *
     * @var string
     */
    public $location = '';

    /**
     * The status of this event.
     *
     * @var integer
     */
    public $status = Kronolith::STATUS_CONFIRMED;

    /**
     * The description for this event
     *
     * @var string
     */
    public $description = '';

    /**
     * Remote description of this event (URL).
     *
     * @var string
     */
    public $remoteUrl = '';

    /**
     * Remote calendar name.
     *
     * @var string
     */
    public $remoteCal = '';

    /**
     * Whether the event is private.
     *
     * @var boolean
     */
    public $private = false;

    /**
     * This tag's events.
     *
     * @var mixed  Array of tags or comma delimited string.
     */
    public $tags = array();

    /**
     * All the attendees of this event.
     *
     * This is an associative array where the keys are the email addresses
     * of the attendees, and the values are also associative arrays with
     * keys 'attendance' and 'response' pointing to the attendees' attendance
     * and response values, respectively.
     *
     * @var array
     */
    public $attendees = array();

    /**
     * All resources of this event.
     *
     * This is an associative array where keys are resource uids values are
     * associative arrays with keys attendance and response... actually, do we
     * *need* an attendence setting for resources? Shouldn't they be required
     * by definition?
     *
     * @var unknown_type
     */
    protected $_resources = array();

    /**
     * The start time of the event.
     *
     * @var Horde_Date
     */
    public $start;

    /**
     * The end time of the event.
     *
     * @var Horde_Date
     */
    public $end;

    /**
     * The duration of this event in minutes
     *
     * @var integer
     */
    public $durMin = 0;

    /**
     * Whether this is an all-day event.
     *
     * @var boolean
     */
    public $allday = false;

    /**
     * Number of minutes before the event starts to trigger an alarm.
     *
     * @var integer
     */
    public $alarm = 0;

    /**
     * The particular alarm methods overridden for this event.
     *
     * @var array
     */
    public $methods;

    /**
     * The identifier of the calender this event exists on.
     *
     * @var string
     */
    protected $_calendar;

    /**
     * The type of the calender this event exists on.
     *
     * @var string
     */
    protected $_calendarType;

    /**
     * The HTML background color to be used for this event.
     *
     * @var string
     */
    protected $_backgroundColor = '#dddddd';

    /**
     * The HTML foreground color to be used for this event.
     *
     * @var string
     */
    protected $_foregroundColor = '#000';

    /**
     * The VarRenderer class to use for printing select elements.
     *
     * @var Horde_UI_VarRenderer
     */
    private $_varRenderer;

    /**
     * The Horde_Date_Recurrence class for this event.
     *
     * @var Horde_Date_Recurrence
     */
    public $recurrence;

    /**
     * Constructor.
     *
     * @param Kronolith_Driver $driver  The backend driver that this event is
     *                                  stored in.
     * @param mixed $eventObject        Backend specific event object
     *                                  that this will represent.
     */
    public function __construct($driver, $eventObject = null)
    {
        $this->_calendar = $driver->getCalendar();
        // FIXME: Move color definitions anywhere else.
        if (!empty($this->_calendar) &&
            isset($GLOBALS['all_calendars'][$this->_calendar])) {
            $share = $GLOBALS['all_calendars'][$this->_calendar];
            $backgroundColor = $share->get('color');
            if (!empty($backgroundColor)) {
                $this->_backgroundColor = $backgroundColor;
                $this->_foregroundColor = Horde_Image::brightness($this->_backgroundColor) < 128 ? '#fff' : '#000';
            }
        }

        if ($eventObject !== null) {
            $this->fromDriver($eventObject);
            $tagger = Kronolith::getTagger();
            $this->tags = $tagger->getTags($this->getUID(), 'event');
        }
    }

    /**
     * Returns a reference to a driver that's valid for this event.
     *
     * @return Kronolith_Driver  A driver that this event can use to save
     *                           itself, etc.
     */
    public function getDriver()
    {
        return Kronolith::getDriver(null, $this->_calendar);
    }

    /**
     * Returns the share this event belongs to.
     *
     * @return Horde_Share  This event's share.
     */
    public function getShare()
    {
        if (isset($GLOBALS['all_calendars'][$this->getCalendar()])) {
            $share = $GLOBALS['all_calendars'][$this->getCalendar()];
        } else {
            $share = PEAR::raiseError('Share not found');
        }
        return $share;
    }

    /**
     * Encapsulates permissions checking.
     *
     * @param integer $permission  The permission to check for.
     * @param string $user         The user to check permissions for.
     *
     * @return boolean
     */
    public function hasPermission($permission, $user = null)
    {
        if ($user === null) {
            $user = Horde_Auth::getAuth();
        }

        if ($this->remoteCal) {
            switch ($permission) {
            case PERMS_SHOW:
            case PERMS_READ:
            case PERMS_EDIT:
                return true;

            default:
                return false;
            }
        }

        return (!is_a($share = &$this->getShare(), 'PEAR_Error') &&
                $share->hasPermission($user, $permission, $this->getCreatorId()));
    }

    /**
     * Saves changes to this event.
     *
     * @return mixed  True or a PEAR_Error on failure.
     */
    public function save()
    {
        if (!$this->isInitialized()) {
            return PEAR::raiseError('Event not yet initialized');
        }

        /* Check for acceptance/denial of this event's resources.
         *
         * @TODO: Need to look at how to lock the resource to avoid two events
         * inadvertantly getting accepted. (Two simultaneous requests, both
         * return RESPONSE_ACCEPTED from getResponse()) Maybe Horde_Lock?
         */
        foreach ($this->getResources() as $id => $resourceData) {
            $resource = Kronolith::getDriver('Resource')->getResource($id);
            $response = $resource->getResponse($this);
            if ($response == Kronolith::RESPONSE_ACCEPTED) {
                $resource->addEvent($this);
            }
            $this->addResource($resource, $response);
        }

        $this->toDriver();
        $result = $this->getDriver()->saveEvent($this);
        if (!is_a($result, 'PEAR_Error') &&
            !empty($GLOBALS['conf']['alarms']['driver'])) {
            $alarm = $this->toAlarm(new Horde_Date($_SERVER['REQUEST_TIME']));
            if ($alarm) {
                $alarm['start'] = new Horde_Date($alarm['start']);
                $alarm['end'] = new Horde_Date($alarm['end']);
                $horde_alarm = Horde_Alarm::factory();
                $horde_alarm->set($alarm);
            }
        }

        return $result;
    }

    /**
     * Exports this event in iCalendar format.
     *
     * @param Horde_iCalendar &$calendar  A Horde_iCalendar object that acts as
     *                                    a container.
     *
     * @return Horde_iCalendar_vevent  The vEvent object for this event.
     */
    public function toiCalendar(&$calendar)
    {
        $vEvent = &Horde_iCalendar::newComponent('vevent', $calendar);
        $v1 = $calendar->getAttribute('VERSION') == '1.0';

        if ($this->isAllDay()) {
            $vEvent->setAttribute('DTSTART', $this->start, array('VALUE' => 'DATE'));
            $vEvent->setAttribute('DTEND', $this->end, array('VALUE' => 'DATE'));
        } else {
            $vEvent->setAttribute('DTSTART', $this->start);
            $vEvent->setAttribute('DTEND', $this->end);
        }

        $vEvent->setAttribute('DTSTAMP', $_SERVER['REQUEST_TIME']);
        $vEvent->setAttribute('UID', $this->_uid);

        /* Get the event's history. */
        $history = &Horde_History::singleton();
        $created = $modified = null;
        $log = $history->getHistory('kronolith:' . $this->_calendar . ':' . $this->_uid);
        if ($log && !is_a($log, 'PEAR_Error')) {
            foreach ($log->getData() as $entry) {
                switch ($entry['action']) {
                case 'add':
                    $created = $entry['ts'];
                    break;

                case 'modify':
                    $modified = $entry['ts'];
                    break;
                }
            }
        }
        if (!empty($created)) {
            $vEvent->setAttribute($v1 ? 'DCREATED' : 'CREATED', $created);
            if (empty($modified)) {
                $modified = $created;
            }
        }
        if (!empty($modified)) {
            $vEvent->setAttribute('LAST-MODIFIED', $modified);
        }

        $vEvent->setAttribute('SUMMARY', $v1 ? $this->getTitle() : Horde_String::convertCharset($this->getTitle(), Horde_Nls::getCharset(), 'utf-8'));
        $name = Kronolith::getUserName($this->getCreatorId());
        if (!$v1) {
            $name = Horde_String::convertCharset($name, Horde_Nls::getCharset(), 'utf-8');
        }
        $vEvent->setAttribute('ORGANIZER',
                              'mailto:' . Kronolith::getUserEmail($this->getCreatorId()),
                              array('CN' => $name));
        if (!$this->isPrivate() || $this->getCreatorId() == Horde_Auth::getAuth()) {
            if (!empty($this->description)) {
                $vEvent->setAttribute('DESCRIPTION', $v1 ? $this->description : Horde_String::convertCharset($this->description, Horde_Nls::getCharset(), 'utf-8'));
            }

            // Tags
            $tags = $this->tags;
            if (is_array($tags)) {
                $tags = implode(', ', $tags);
            }
            if (!empty($tags)) {
                $vEvent->setAttribute('CATEGORIES', $v1 ? $tags : Horde_String::convertCharset($tags, Horde_Nls::getCharset(), 'utf-8'));
            }

            // Location
            if (!empty($this->location)) {
                $vEvent->setAttribute('LOCATION', $v1 ? $this->location : Horde_String::convertCharset($this->location, Horde_Nls::getCharset(), 'utf-8'));
            }
        }
        $vEvent->setAttribute('CLASS', $this->isPrivate() ? 'PRIVATE' : 'PUBLIC');

        // Status.
        switch ($this->getStatus()) {
        case Kronolith::STATUS_FREE:
            // This is not an official iCalendar value, but we need it for
            // synchronization.
            $vEvent->setAttribute('STATUS', 'FREE');
            $vEvent->setAttribute('TRANSP', $v1 ? 1 : 'TRANSPARENT');
            break;
        case Kronolith::STATUS_TENTATIVE:
            $vEvent->setAttribute('STATUS', 'TENTATIVE');
            $vEvent->setAttribute('TRANSP', $v1 ? 0 : 'OPAQUE');
            break;
        case Kronolith::STATUS_CONFIRMED:
            $vEvent->setAttribute('STATUS', 'CONFIRMED');
            $vEvent->setAttribute('TRANSP', $v1 ? 0 : 'OPAQUE');
            break;
        case Kronolith::STATUS_CANCELLED:
            if ($v1) {
                $vEvent->setAttribute('STATUS', 'DECLINED');
                $vEvent->setAttribute('TRANSP', 1);
            } else {
                $vEvent->setAttribute('STATUS', 'CANCELLED');
                $vEvent->setAttribute('TRANSP', 'TRANSPARENT');
            }
            break;
        }

        // Attendees.
        foreach ($this->getAttendees() as $email => $status) {
            $params = array();
            switch ($status['attendance']) {
            case Kronolith::PART_REQUIRED:
                if ($v1) {
                    $params['EXPECT'] = 'REQUIRE';
                } else {
                    $params['ROLE'] = 'REQ-PARTICIPANT';
                }
                break;

            case Kronolith::PART_OPTIONAL:
                if ($v1) {
                    $params['EXPECT'] = 'REQUEST';
                } else {
                    $params['ROLE'] = 'OPT-PARTICIPANT';
                }
                break;

            case Kronolith::PART_NONE:
                if ($v1) {
                    $params['EXPECT'] = 'FYI';
                } else {
                    $params['ROLE'] = 'NON-PARTICIPANT';
                }
                break;
            }

            switch ($status['response']) {
            case Kronolith::RESPONSE_NONE:
                if ($v1) {
                    $params['STATUS'] = 'NEEDS ACTION';
                    $params['RSVP'] = 'YES';
                } else {
                    $params['PARTSTAT'] = 'NEEDS-ACTION';
                    $params['RSVP'] = 'TRUE';
                }
                break;

            case Kronolith::RESPONSE_ACCEPTED:
                if ($v1) {
                    $params['STATUS'] = 'ACCEPTED';
                } else {
                    $params['PARTSTAT'] = 'ACCEPTED';
                }
                break;

            case Kronolith::RESPONSE_DECLINED:
                if ($v1) {
                    $params['STATUS'] = 'DECLINED';
                } else {
                    $params['PARTSTAT'] = 'DECLINED';
                }
                break;

            case Kronolith::RESPONSE_TENTATIVE:
                if ($v1) {
                    $params['STATUS'] = 'TENTATIVE';
                } else {
                    $params['PARTSTAT'] = 'TENTATIVE';
                }
                break;
            }

            if (strpos($email, '@') === false) {
                $email = '';
            }
            if ($v1) {
                if (!empty($status['name'])) {
                    if (!empty($email)) {
                        $email = ' <' . $email . '>';
                    }
                    $email = $status['name'] . $email;
                    $email = Horde_Mime_Address::trimAddress($email);
                }
            } else {
                if (!empty($status['name'])) {
                    $params['CN'] = Horde_String::convertCharset($status['name'], Horde_Nls::getCharset(), 'utf-8');
                }
                if (!empty($email)) {
                    $email = 'mailto:' . $email;
                }
            }

            $vEvent->setAttribute('ATTENDEE', $email, $params);
        }

        // Alarms.
        if (!empty($this->alarm)) {
            if ($v1) {
                $alarm = new Horde_Date($this->start);
                $alarm->min -= $this->alarm;
                $vEvent->setAttribute('AALARM', $alarm);
            } else {
                $vAlarm = &Horde_iCalendar::newComponent('valarm', $vEvent);
                $vAlarm->setAttribute('ACTION', 'DISPLAY');
                $vAlarm->setAttribute('TRIGGER;VALUE=DURATION', '-PT' . $this->alarm . 'M');
                $vEvent->addComponent($vAlarm);
            }
        }

        // Recurrence.
        if ($this->recurs()) {
            if ($v1) {
                $rrule = $this->recurrence->toRRule10($calendar);
            } else {
                $rrule = $this->recurrence->toRRule20($calendar);
            }
            if (!empty($rrule)) {
                $vEvent->setAttribute('RRULE', $rrule);
            }

            // Exceptions.
            $exceptions = $this->recurrence->getExceptions();
            foreach ($exceptions as $exception) {
                if (!empty($exception)) {
                    list($year, $month, $mday) = sscanf($exception, '%04d%02d%02d');
                    $exdate = new Horde_Date(array(
                        'year' => $year,
                        'month' => $month,
                        'mday' => $mday,
                        'hour' => $this->start->hour,
                        'min' => $this->start->min,
                        'sec' => $this->start->sec,
                    ));
                    $vEvent->setAttribute('EXDATE', array($exdate));
                }
            }
        }

        return $vEvent;
    }

    /**
     * Updates the properties of this event from a Horde_iCalendar_vevent
     * object.
     *
     * @param Horde_iCalendar_vevent $vEvent  The iCalendar data to update
     *                                        from.
     */
    public function fromiCalendar($vEvent)
    {
        // Unique ID.
        $uid = $vEvent->getAttribute('UID');
        if (!empty($uid) && !is_a($uid, 'PEAR_Error')) {
            $this->setUID($uid);
        }

        // Sequence.
        $seq = $vEvent->getAttribute('SEQUENCE');
        if (is_int($seq)) {
            $this->_sequence = $seq;
        }

        // Title, tags and description.
        $title = $vEvent->getAttribute('SUMMARY');
        if (!is_array($title) && !is_a($title, 'PEAR_Error')) {
            $this->setTitle($title);
        }

        // Tags
        $categories = $vEvent->getAttributeValues('CATEGORIES');
        if (!is_a($categories, 'PEAR_Error')) {
            $this->tags = $categories;
        }

        // Description
        $desc = $vEvent->getAttribute('DESCRIPTION');
        if (!is_array($desc) && !is_a($desc, 'PEAR_Error')) {
            $this->setDescription($desc);
        }

        // Remote Url
        $url = $vEvent->getAttribute('URL');
        if (!is_array($url) && !is_a($url, 'PEAR_Error')) {
            $this->remoteUrl = $url;
        }

        // Location
        $location = $vEvent->getAttribute('LOCATION');
        if (!is_array($location) && !is_a($location, 'PEAR_Error')) {
            $this->setLocation($location);
        }

        // Class
        $class = $vEvent->getAttribute('CLASS');
        if (!is_array($class) && !is_a($class, 'PEAR_Error')) {
            $class = Horde_String::upper($class);
            if ($class == 'PRIVATE' || $class == 'CONFIDENTIAL') {
                $this->setPrivate(true);
            } else {
                $this->setPrivate(false);
            }
        }

        // Status.
        $status = $vEvent->getAttribute('STATUS');
        if (!is_array($status) && !is_a($status, 'PEAR_Error')) {
            $status = Horde_String::upper($status);
            if ($status == 'DECLINED') {
                $status = 'CANCELLED';
            }
            if (defined('Kronolith::STATUS_' . $status)) {
                $this->setStatus(constant('Kronolith::STATUS_' . $status));
            }
        }

        // Start and end date.
        $start = $vEvent->getAttribute('DTSTART');
        if (!is_a($start, 'PEAR_Error')) {
            if (!is_array($start)) {
                // Date-Time field
                $this->start = new Horde_Date($start);
            } else {
                // Date field
                $this->start = new Horde_Date(
                    array('year'  => (int)$start['year'],
                          'month' => (int)$start['month'],
                          'mday'  => (int)$start['mday']));
            }
        }
        $end = $vEvent->getAttribute('DTEND');
        if (!is_a($end, 'PEAR_Error')) {
            if (!is_array($end)) {
                // Date-Time field
                $this->end = new Horde_Date($end);
                // All day events are transferred by many device as
                // DSTART: YYYYMMDDT000000 DTEND: YYYYMMDDT2359(59|00)
                // Convert accordingly
                if (is_object($this->start) && $this->start->hour == 0 &&
                    $this->start->min == 0 && $this->start->sec == 0 &&
                    $this->end->hour == 23 && $this->end->min == 59) {
                    $this->end = new Horde_Date(
                        array('year'  => (int)$this->end->year,
                              'month' => (int)$this->end->month,
                              'mday'  => (int)$this->end->mday + 1));
                }
            } elseif (is_array($end) && !is_a($end, 'PEAR_Error')) {
                // Date field
                $this->end = new Horde_Date(
                    array('year'  => (int)$end['year'],
                          'month' => (int)$end['month'],
                          'mday'  => (int)$end['mday']));
            }
        } else {
            $duration = $vEvent->getAttribute('DURATION');
            if (!is_array($duration) && !is_a($duration, 'PEAR_Error')) {
                $this->end = new Horde_Date($this->start);
                $this->end->sec += $duration;
            } else {
                // End date equal to start date as per RFC 2445.
                $this->end = new Horde_Date($this->start);
                if (is_array($start)) {
                    // Date field
                    $this->end->mday++;
                }
            }
        }

        // vCalendar 1.0 alarms
        $alarm = $vEvent->getAttribute('AALARM');
        if (!is_array($alarm) &&
            !is_a($alarm, 'PEAR_Error') &&
            intval($alarm)) {
            $this->alarm = intval(($this->start->timestamp() - $alarm) / 60);
        }

        // @TODO: vCalendar 2.0 alarms

        // Attendance.
        // Importing attendance may result in confusion: editing an imported
        // copy of an event can cause invitation updates to be sent from
        // people other than the original organizer. So we don't import by
        // default. However to allow updates by SyncML replication, the custom
        // X-ATTENDEE attribute is used which has the same syntax as
        // ATTENDEE.
        $attendee = $vEvent->getAttribute('X-ATTENDEE');
        if (!is_a($attendee, 'PEAR_Error')) {

            if (!is_array($attendee)) {
                $attendee = array($attendee);
            }
            $params = $vEvent->getAttribute('X-ATTENDEE', true);
            if (!is_array($params)) {
                $params = array($params);
            }
            for ($i = 0; $i < count($attendee); ++$i) {
                $attendee[$i] = str_replace(array('MAILTO:', 'mailto:'), '',
                                            $attendee[$i]);
                $email = Horde_Mime_Address::bareAddress($attendee[$i]);
                // Default according to rfc2445:
                $attendance = Kronolith::PART_REQUIRED;
                // vCalendar 2.0 style:
                if (!empty($params[$i]['ROLE'])) {
                    switch($params[$i]['ROLE']) {
                    case 'OPT-PARTICIPANT':
                        $attendance = Kronolith::PART_OPTIONAL;
                        break;

                    case 'NON-PARTICIPANT':
                        $attendance = Kronolith::PART_NONE;
                        break;
                    }
                }
                // vCalendar 1.0 style;
                if (!empty($params[$i]['EXPECT'])) {
                    switch($params[$i]['EXPECT']) {
                    case 'REQUEST':
                        $attendance = Kronolith::PART_OPTIONAL;
                        break;

                    case 'FYI':
                        $attendance = Kronolith::PART_NONE;
                        break;
                    }
                }
                $response = Kronolith::RESPONSE_NONE;
                if (empty($params[$i]['PARTSTAT']) &&
                    !empty($params[$i]['STATUS'])) {
                    $params[$i]['PARTSTAT']  = $params[$i]['STATUS'];
                }

                if (!empty($params[$i]['PARTSTAT'])) {
                    switch($params[$i]['PARTSTAT']) {
                    case 'ACCEPTED':
                        $response = Kronolith::RESPONSE_ACCEPTED;
                        break;

                    case 'DECLINED':
                        $response = Kronolith::RESPONSE_DECLINED;
                        break;

                    case 'TENTATIVE':
                        $response = Kronolith::RESPONSE_TENTATIVE;
                        break;
                    }
                }
                $name = isset($params[$i]['CN']) ? $params[$i]['CN'] : null;

                $this->addAttendee($email, $attendance, $response, $name);
            }
        }

        // Recurrence.
        $rrule = $vEvent->getAttribute('RRULE');
        if (!is_array($rrule) && !is_a($rrule, 'PEAR_Error')) {
            $this->recurrence = new Horde_Date_Recurrence($this->start);
            if (strpos($rrule, '=') !== false) {
                $this->recurrence->fromRRule20($rrule);
            } else {
                $this->recurrence->fromRRule10($rrule);
            }

            // Exceptions.
            $exdates = $vEvent->getAttributeValues('EXDATE');
            if (is_array($exdates)) {
                foreach ($exdates as $exdate) {
                    if (is_array($exdate)) {
                        $this->recurrence->addException((int)$exdate['year'],
                                                        (int)$exdate['month'],
                                                        (int)$exdate['mday']);
                    }
                }
            }
        }

        $this->initialized = true;
    }

    /**
     * Imports the values for this event from an array of values.
     *
     * @param array $hash  Array containing all the values.
     */
    public function fromHash($hash)
    {
        // See if it's a new event.
        if ($this->getId() === null) {
            $this->setCreatorId(Horde_Auth::getAuth());
        }
        if (!empty($hash['title'])) {
            $this->setTitle($hash['title']);
        } else {
            return PEAR::raiseError(_("Events must have a title."));
        }
        if (!empty($hash['description'])) {
            $this->setDescription($hash['description']);
        }
        if (!empty($hash['location'])) {
            $this->setLocation($hash['location']);
        }
        if (!empty($hash['start_date'])) {
            $date = explode('-', $hash['start_date']);
            if (empty($hash['start_time'])) {
                $time = array(0, 0, 0);
            } else {
                $time = explode(':', $hash['start_time']);
                if (count($time) == 2) {
                    $time[2] = 0;
                }
            }
            if (count($time) == 3 && count($date) == 3) {
                $this->start = new Horde_Date(array('year' => $date[0],
                                                    'month' => $date[1],
                                                    'mday' => $date[2],
                                                    'hour' => $time[0],
                                                    'min' => $time[1],
                                                    'sec' => $time[2]));
            }
        } else {
            return PEAR::raiseError(_("Events must have a start date."));
        }
        if (empty($hash['duration'])) {
            if (empty($hash['end_date'])) {
                $hash['end_date'] = $hash['start_date'];
            }
            if (empty($hash['end_time'])) {
                $hash['end_time'] = $hash['start_time'];
            }
        } else {
            $weeks = str_replace('W', '', $hash['duration'][1]);
            $days = str_replace('D', '', $hash['duration'][2]);
            $hours = str_replace('H', '', $hash['duration'][4]);
            $minutes = isset($hash['duration'][5]) ? str_replace('M', '', $hash['duration'][5]) : 0;
            $seconds = isset($hash['duration'][6]) ? str_replace('S', '', $hash['duration'][6]) : 0;
            $hash['duration'] = ($weeks * 60 * 60 * 24 * 7) + ($days * 60 * 60 * 24) + ($hours * 60 * 60) + ($minutes * 60) + $seconds;
            $this->end = new Horde_Date($this->start);
            $this->end->sec += $hash['duration'];
        }
        if (!empty($hash['end_date'])) {
            $date = explode('-', $hash['end_date']);
            if (empty($hash['end_time'])) {
                $time = array(0, 0, 0);
            } else {
                $time = explode(':', $hash['end_time']);
                if (count($time) == 2) {
                    $time[2] = 0;
                }
            }
            if (count($time) == 3 && count($date) == 3) {
                $this->end = new Horde_Date(array('year' => $date[0],
                                                  'month' => $date[1],
                                                  'mday' => $date[2],
                                                  'hour' => $time[0],
                                                  'min' => $time[1],
                                                  'sec' => $time[2]));
            }
        }
        if (!empty($hash['alarm'])) {
            $this->setAlarm($hash['alarm']);
        } elseif (!empty($hash['alarm_date']) &&
                  !empty($hash['alarm_time'])) {
            $date = explode('-', $hash['alarm_date']);
            $time = explode(':', $hash['alarm_time']);
            if (count($time) == 2) {
                $time[2] = 0;
            }
            if (count($time) == 3 && count($date) == 3) {
                $alarm = new Horde_Date(array('hour'  => $time[0],
                                              'min'   => $time[1],
                                              'sec'   => $time[2],
                                              'month' => $date[1],
                                              'mday'  => $date[2],
                                              'year'  => $date[0]));
                $this->setAlarm(($this->start->timestamp() - $alarm->timestamp()) / 60);
            }
        }
        if (!empty($hash['recur_type'])) {
            $this->recurrence = new Horde_Date_Recurrence($this->start);
            $this->recurrence->setRecurType($hash['recur_type']);
            if (!empty($hash['recur_end_date'])) {
                $date = explode('-', $hash['recur_end_date']);
                $this->recurrence->setRecurEnd(new Horde_Date(array('year' => $date[0], 'month' => $date[1], 'mday' => $date[2])));
            }
            if (!empty($hash['recur_interval'])) {
                $this->recurrence->setRecurInterval($hash['recur_interval']);
            }
            if (!empty($hash['recur_data'])) {
                $this->recurrence->setRecurOnDay($hash['recur_data']);
            }
        }

        $this->initialized = true;
    }

    /**
     * Returns an alarm hash of this event suitable for Horde_Alarm.
     *
     * @param Horde_Date $time  Time of alarm.
     * @param string $user      The user to return alarms for.
     * @param Prefs $prefs      A Prefs instance.
     *
     * @return array  Alarm hash or null.
     */
    public function toAlarm($time, $user = null, $prefs = null)
    {
        if (!$this->getAlarm()) {
            return;
        }

        if ($this->recurs()) {
            $eventDate = $this->recurrence->nextRecurrence($time);
            if ($eventDate && $this->recurrence->hasException($eventDate->year, $eventDate->month, $eventDate->mday)) {
                return;
            }
        }

        if (empty($user)) {
            $user = Horde_Auth::getAuth();
        }
        if (empty($prefs)) {
            $prefs = $GLOBALS['prefs'];
        }

        $methods = !empty($this->methods) ? $this->methods : @unserialize($prefs->getValue('event_alarms'));
        $start = clone $this->start;
        $start->min -= $this->getAlarm();
        if (isset($methods['notify'])) {
            $methods['notify']['show'] = array(
                '__app' => $GLOBALS['registry']->getApp(),
                'event' => $this->getId(),
                'calendar' => $this->getCalendar());
            if (!empty($methods['notify']['sound'])) {
                if ($methods['notify']['sound'] == 'on') {
                    // Handle boolean sound preferences.
                    $methods['notify']['sound'] = $GLOBALS['registry']->get('themesuri') . '/sounds/theetone.wav';
                } else {
                    // Else we know we have a sound name that can be
                    // served from Horde.
                    $methods['notify']['sound'] = $GLOBALS['registry']->get('themesuri', 'horde') . '/sounds/' . $methods['notify']['sound'];
                }
            }
        }
        if (isset($methods['popup'])) {
            $methods['popup']['message'] = $this->getTitle($user);
            $description = $this->getDescription();
            if (!empty($description)) {
                $methods['popup']['message'] .= "\n\n" . $description;
            }
        }
        if (isset($methods['mail'])) {
            $methods['mail']['body'] = sprintf(
                _("We would like to remind you of this upcoming event.\n\n%s\n\nLocation: %s\n\nDate: %s\nTime: %s\n\n%s"),
                $this->getTitle($user),
                $this->location,
                $this->start->strftime($prefs->getValue('date_format')),
                $this->start->format($prefs->getValue('twentyFour') ? 'H:i' : 'h:ia'),
                $this->getDescription());
        }

        return array(
            'id' => $this->getUID(),
            'user' => $user,
            'start' => $start->timestamp(),
            'end' => $this->end->timestamp(),
            'methods' => array_keys($methods),
            'params' => $methods,
            'title' => $this->getTitle($user),
            'text' => $this->getDescription());
    }

    /**
     * Returns a simple object suitable for json transport representing this
     * event.
     *
     * Possible properties are:
     * - t: title
     * - c: calendar id
     * - s: start date
     * - e: end date
     * - x: status (Kronolith::STATUS_* constant)
     * - al: all-day?
     * - bg: background color
     * - fg: foreground color
     * - pe: edit permissions?
     * - pd: delete permissions?
     * - a: alarm text
     * - r: recurrence type (Horde_Date_Recurrence::RECUR_* constant)
     * - ic: icon
     * - ln: link
     * - id: event id
     * - ty: calendar type (driver)
     * - l: location
     * - sd: formatted start date
     * - st: formatted start time
     * - ed: formatted end date
     * - et: formatted end time
     * - tg: tag list
     *
     * @param boolean $allDay      If not null, overrides whether the event is
     *                             an all-day event.
     * @param boolean $full        Whether to return all event details.
     * @param string $time_format  The date() format to use for time formatting.
     *
     * @return object  A simple object.
     */
    public function toJson($allDay = null, $full = false, $time_format = 'H:i')
    {
        $json = new stdClass;
        $json->t = $this->getTitle();
        $json->c = $this->getCalendar();
        $json->s = $this->start->toJson();
        $json->e = $this->end->toJson();
        $json->x = $this->status;
        $json->al = is_null($allDay) ? $this->isAllDay() : $allDay;
        $json->bg = $this->_backgroundColor;
        $json->fg = $this->_foregroundColor;
        $json->pe = $this->hasPermission(PERMS_EDIT);
        $json->pd = $this->hasPermission(PERMS_DELETE);
        if ($this->alarm) {
            if ($this->alarm % 10080 == 0) {
                $alarm_value = $this->alarm / 10080;
                $json->a = sprintf(ngettext("%d week", "%d weeks", $alarm_value), $alarm_value);
            } elseif ($this->alarm % 1440 == 0) {
                $alarm_value = $this->alarm / 1440;
                $json->a = sprintf(ngettext("%d day", "%d days", $alarm_value), $alarm_value);
            } elseif ($this->alarm % 60 == 0) {
                $alarm_value = $this->alarm / 60;
                $json->a = sprintf(ngettext("%d hour", "%d hours", $alarm_value), $alarm_value);
            } else {
                $alarm_value = $this->alarm;
                $json->a = sprintf(ngettext("%d minute", "%d minutes", $alarm_value), $alarm_value);
            }
        }
        if ($this->recurs()) {
            $json->r = $this->recurrence->getRecurType();
        }

        if ($full) {
            $json->id = $this->getId();
            $json->ty = $this->_calendarType;
            $json->l = $this->getLocation();
            $json->sd = $this->start->strftime('%x');
            $json->st = $this->start->format($time_format);
            $json->ed = $this->end->strftime('%x');
            $json->et = $this->end->format($time_format);
            $json->tg = array_values($this->tags);
        }

        return $json;
    }

    /**
     * TODO
     */
    public function isInitialized()
    {
        return $this->initialized;
    }

    /**
     * TODO
     */
    public function isStored()
    {
        return $this->stored;
    }

    /**
     * Checks if the current event is already present in the calendar.
     *
     * Does the check based on the uid.
     *
     * @return boolean  True if event exists, false otherwise.
     */
    public function exists()
    {
        if (!isset($this->_uid) || !isset($this->_calendar)) {
            return false;
        }

        $eventID = $this->getDriver()->exists($this->_uid, $this->_calendar);
        if (is_a($eventID, 'PEAR_Error') || !$eventID) {
            return false;
        } else {
            $this->eventID = $eventID;
            return true;
        }
    }

    public function getDuration()
    {
        static $duration = null;
        if (isset($duration)) {
            return $duration;
        }

        if ($this->start && $this->end) {
            $dur_day_match = Date_Calc::dateDiff($this->start->mday,
                                                 $this->start->month,
                                                 $this->start->year,
                                                 $this->end->mday,
                                                 $this->end->month,
                                                 $this->end->year);
            $dur_hour_match = $this->end->hour - $this->start->hour;
            $dur_min_match = $this->end->min - $this->start->min;
            while ($dur_min_match < 0) {
                $dur_min_match += 60;
                --$dur_hour_match;
            }
            while ($dur_hour_match < 0) {
                $dur_hour_match += 24;
                --$dur_day_match;
            }
            if ($dur_hour_match == 0 && $dur_min_match == 0 &&
                $this->end->mday - $this->start->mday == 1) {
                $dur_day_match = 1;
                $dur_hour_match = 0;
                $dur_min_match = 0;
                $whole_day_match = true;
            } else {
                $whole_day_match = false;
            }
        } else {
            $dur_day_match = 0;
            $dur_hour_match = 1;
            $dur_min_match = 0;
            $whole_day_match = false;
        }

        $duration = new stdClass;
        $duration->day = $dur_day_match;
        $duration->hour = $dur_hour_match;
        $duration->min = $dur_min_match;
        $duration->wholeDay = $whole_day_match;

        return $duration;
    }

    /**
     * Returns whether this event is a recurring event.
     *
     * @return boolean  True if this is a recurring event.
     */
    public function recurs()
    {
        return isset($this->recurrence) &&
            !$this->recurrence->hasRecurType(Horde_Date_Recurrence::RECUR_NONE);
    }

    /**
     * Returns a description of this event's recurring type.
     *
     * @return string  Human readable recurring type.
     */
    public function getRecurName()
    {
        return $this->recurs()
            ? $this->recurrence->getRecurName()
            : _("No recurrence");
    }

    /**
     * Returns a correcty formatted exception date for recurring events and a
     * link to delete this exception.
     *
     * @param string $date  Exception in the format Ymd.
     *
     * @return string  The formatted date and delete link.
     */
    public function exceptionLink($date)
    {
        if (!preg_match('/(\d{4})(\d{2})(\d{2})/', $date, $match)) {
            return '';
        }
        $horde_date = new Horde_Date(array('year' => $match[1],
                                           'month' => $match[2],
                                           'mday' => $match[3]));
        $formatted = $horde_date->strftime($GLOBALS['prefs']->getValue('date_format'));
        return $formatted
            . Horde::link(Horde_Util::addParameter(Horde::applicationUrl('edit.php'), array('calendar' => $this->getCalendar(), 'eventID' => $this->eventID, 'del_exception' => $date, 'url' => Horde_Util::getFormData('url'))), sprintf(_("Delete exception on %s"), $formatted))
            . Horde::img('delete-small.png', _("Delete"), '', $GLOBALS['registry']->getImageDir('horde'))
            . '</a>';
    }

    /**
     * Returns a list of exception dates for recurring events including links
     * to delete them.
     *
     * @return string  List of exception dates and delete links.
     */
    public function exceptionsList()
    {
        return implode(', ', array_map(array($this, 'exceptionLink'), $this->recurrence->getExceptions()));
    }

    public function getCalendar()
    {
        return $this->_calendar;
    }

    public function setCalendar($calendar)
    {
        $this->_calendar = $calendar;
    }

    public function getCalendarType()
    {
        return $this->_calendarType;
    }

    public function isRemote()
    {
        return (bool)$this->remoteCal;
    }

    /**
     * Returns the locally unique identifier for this event.
     *
     * @return string  The local identifier for this event.
     */
    public function getId()
    {
        return $this->eventID;
    }

    /**
     * Sets the locally unique identifier for this event.
     *
     * @param string $eventId  The local identifier for this event.
     */
    public function setId($eventId)
    {
        if (substr($eventId, 0, 10) == 'kronolith:') {
            $eventId = substr($eventId, 10);
        }
        $this->eventID = $eventId;
    }

    /**
     * Returns the global UID for this event.
     *
     * @return string  The global UID for this event.
     */
    public function getUID()
    {
        return $this->_uid;
    }

    /**
     * Sets the global UID for this event.
     *
     * @param string $uid  The global UID for this event.
     */
    public function setUID($uid)
    {
        $this->_uid = $uid;
    }

    /**
     * Returns the iCalendar SEQUENCE for this event.
     *
     * @return integer  The sequence for this event.
     */
    public function getSequence()
    {
        return $this->_sequence;
    }

    /**
     * Returns the id of the user who created the event.
     *
     * @return string  The creator id
     */
    public function getCreatorId()
    {
        return !empty($this->creatorID) ? $this->creatorID : Horde_Auth::getAuth();
    }

    /**
     * Sets the id of the creator of the event.
     *
     * @param string $creatorID  The user id for the user who created the event
     */
    public function setCreatorId($creatorID)
    {
        $this->creatorID = $creatorID;
    }

    /**
     * Returns the title of this event.
     *
     * @param string $user  The current user.
     *
     * @return string  The title of this event.
     */
    public function getTitle($user = null)
    {
        if (isset($this->external) ||
            isset($this->contactID) ||
            $this->remoteCal) {
            return !empty($this->title) ? $this->title : _("[Unnamed event]");
        }

        if (!$this->isInitialized()) {
            return '';
        }

        if ($user === null) {
            $user = Horde_Auth::getAuth();
        }

        $twentyFour = $GLOBALS['prefs']->getValue('twentyFour');
        $start = $this->start->format($twentyFour ? 'G:i' : 'g:ia');
        $end = $this->end->format($twentyFour ? 'G:i' : 'g:ia');

        // We explicitly allow admin access here for the alarms notifications.
        if (!Horde_Auth::isAdmin() && $this->isPrivate() &&
            $this->getCreatorId() != $user) {
            return _("busy");
        } elseif (Horde_Auth::isAdmin() || $this->hasPermission(PERMS_READ, $user)) {
            return strlen($this->title) ? $this->title : _("[Unnamed event]");
        } else {
            return _("busy");
        }
    }

    /**
     * Sets the title of this event.
     *
     * @param string  The new title for this event.
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Returns the description of this event.
     *
     * @return string  The description of this event.
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the description of this event.
     *
     * @param string $description  The new description for this event.
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Returns the location this event occurs at.
     *
     * @return string  The location of this event.
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Sets the location this event occurs at.
     *
     * @param string $location  The new location for this event.
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * Returns whether this event is private.
     *
     * @return boolean  Whether this even is private.
     */
    public function isPrivate()
    {
        return $this->private;
    }

    /**
     * Sets the private flag of this event.
     *
     * @param boolean $private  Whether this event should be marked private.
     */
    public function setPrivate($private)
    {
        $this->private = !empty($private);
    }

    /**
     * Returns the event status.
     *
     * @return integer  The status of this event.
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Checks whether the events status is the same as the specified value.
     *
     * @param integer $status  The status value to check against.
     *
     * @return boolean  True if the events status is the same as $status.
     */
    public function hasStatus($status)
    {
        return ($status == $this->status);
    }

    /**
     * Sets the status of this event.
     *
     * @param integer $status  The new event status.
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Returns the entire attendees array.
     *
     * @return array  A copy of the attendees array.
     */
    public function getAttendees()
    {
        return $this->attendees;
    }

    /**
     * Checks to see whether the specified attendee is associated with the
     * current event.
     *
     * @param string $email  The email address of the attendee.
     *
     * @return boolean  True if the specified attendee is present for this
     *                  event.
     */
    public function hasAttendee($email)
    {
        $email = Horde_String::lower($email);
        return isset($this->attendees[$email]);
    }

    /**
     * Sets the entire attendee array.
     *
     * @param array $attendees  The new attendees array. This should be of the
     *                          correct format to avoid driver problems.
     */
    public function setAttendees($attendees)
    {
        $this->attendees = array_change_key_case($attendees);
    }

    /**
     * Adds a new attendee to the current event.
     *
     * This will overwrite an existing attendee if one exists with the same
     * email address.
     *
     * @param string $email        The email address of the attendee.
     * @param integer $attendance  The attendance code of the attendee.
     * @param integer $response    The response code of the attendee.
     * @param string $name         The name of the attendee.
     */
    public function addAttendee($email, $attendance, $response, $name = null)
    {
        $email = Horde_String::lower($email);
        if ($attendance == Kronolith::PART_IGNORE) {
            if (isset($this->attendees[$email])) {
                $attendance = $this->attendees[$email]['attendance'];
            } else {
                $attendance = Kronolith::PART_REQUIRED;
            }
        }
        if (empty($name) && isset($this->attendees[$email]) &&
            !empty($this->attendees[$email]['name'])) {
            $name = $this->attendees[$email]['name'];
        }

        $this->attendees[$email] = array(
            'attendance' => $attendance,
            'response' => $response,
            'name' => $name
        );
    }

    /**
     * Removes the specified attendee from the current event.
     *
     * @param string $email  The email address of the attendee.
     */
    public function removeAttendee($email)
    {
        $email = Horde_String::lower($email);
        if (isset($this->attendees[$email])) {
            unset($this->attendees[$email]);
        }
    }

    /**
     * Adds a single Kronolith_Resource to this event.
     * No validation or acceptence/denial is done here...it should be done
     * when saving the Event.
     *
     * @param Kronolith_Resource $resource  The resource to add
     *
     * @return void
     */
    public function addResource($resource, $response)
    {
        $this->_resources[$resource->getId()] = array(
            'attendance' => Kronolith::PART_REQUIRED,
            'response' => $response,
            'name' => $resource->get('name')
        );
    }

    /**
     * Directly set/replace the _resources array. Called from Event::readForm
     * to bulk load the resources from $_SESSION
     *
     * @param $resources
     * @return unknown_type
     */
    public function setResources($resources)
    {
        $this->_resources = $resources;
    }

    /**
     * Remove a Kronolith_Resource from this event
     *
     * @param Kronolith_Resource $resource  The resource to remove
     *
     * @return void
     */
    function removeResource($resource)
    {
        if (isset($this->_resources[$resource->id])) {
            unset ($this->_resources[$resource->id]);
        }
    }

    /**
     * Returns the entire resources array.
     *
     * @return array  A copy of the attendees array.
     */
    public function getResources()
    {
        return $this->_resources;
    }

    /**
     * Checks to see whether the specified resource is associated with this
     * event.
     *
     * @param string $uid  The resource uid.
     *
     * @return boolean  True if the specified attendee is present for this
     *                  event.
     */
    public function hasResource($uid)
    {
        return isset($this->_resources[$uid]);
    }

    public function isAllDay()
    {
        return $this->allday ||
            ($this->start->hour == 0 && $this->start->min == 0 && $this->start->sec == 0 &&
             (($this->end->hour == 23 && $this->end->min == 59) ||
              ($this->end->hour == 0 && $this->end->min == 0 && $this->end->sec == 0 &&
               ($this->end->mday > $this->start->mday ||
                $this->end->month > $this->start->month ||
                $this->end->year > $this->start->year))));
    }

    public function getAlarm()
    {
        return $this->alarm;
    }

    public function setAlarm($alarm)
    {
        $this->alarm = $alarm;
    }

    public function readForm()
    {
        global $prefs, $cManager;

        // Event owner.
        $targetcalendar = Horde_Util::getFormData('targetcalendar');
        if (strpos($targetcalendar, ':')) {
            list(, $creator) = explode(':', $targetcalendar, 2);
        } else {
            $creator = isset($this->eventID) ? $this->getCreatorId() : Horde_Auth::getAuth();
        }
        $this->setCreatorId($creator);

        // Basic fields.
        $this->setTitle(Horde_Util::getFormData('title', $this->title));
        $this->setDescription(Horde_Util::getFormData('description', $this->description));
        $this->setLocation(Horde_Util::getFormData('location', $this->location));
        $this->setPrivate(Horde_Util::getFormData('private'));

        // Status.
        $this->setStatus(Horde_Util::getFormData('status', $this->status));

        // Attendees.
        if (isset($_SESSION['kronolith']['attendees']) && is_array($_SESSION['kronolith']['attendees'])) {
            $this->setAttendees($_SESSION['kronolith']['attendees']);
        }

        // Resources
        if (isset($_SESSION['kronolith']['resources']) && is_array($_SESSION['kronolith']['resources'])) {
            $this->setResources($_SESSION['kronolith']['resources']);
        }

        // strptime() is locale dependent, i.e. %p is not always matching
        // AM/PM. Set the locale to C to workaround this, but grab the
        // locale's D_FMT before that.
        $date_format = Horde_Nls::getLangInfo(D_FMT);
        $old_locale = setlocale(LC_TIME, 0);
        setlocale(LC_TIME, 'C');

        // Event start.
        if ($start_date = Horde_Util::getFormData('start_date')) {
            $start_time = Horde_Util::getFormData('start_time');
            $start = $start_date . ' ' . $start_time;
            $format = $date_format . ' '
                . ($prefs->getValue('twentyFour') ? '%H:%M' : '%I:%M %p');
            // Try exact format match first.
            if ($date_arr = strptime($start, $format)) {
                $this->start = new Horde_Date(
                    array('year'  => $date_arr['tm_year'] + 1900,
                          'month' => $date_arr['tm_mon'] + 1,
                          'mday'  => $date_arr['tm_mday'],
                          'hour'  => $date_arr['tm_hour'],
                          'min'   => $date_arr['tm_min'],
                          'sec'   => $date_arr['tm_sec']));
            } else {
                $this->start = new Horde_Date($start);
            }
        } else {
            $start = Horde_Util::getFormData('start');
            $start_year = $start['year'];
            $start_month = $start['month'];
            $start_day = $start['day'];
            $start_hour = Horde_Util::getFormData('start_hour');
            $start_min = Horde_Util::getFormData('start_min');
            $am_pm = Horde_Util::getFormData('am_pm');

            if (!$prefs->getValue('twentyFour')) {
                if ($am_pm == 'PM') {
                    if ($start_hour != 12) {
                        $start_hour += 12;
                    }
                } elseif ($start_hour == 12) {
                    $start_hour = 0;
                }
            }

            if (Horde_Util::getFormData('end_or_dur') == 1) {
                if (Horde_Util::getFormData('whole_day') == 1) {
                    $start_hour = 0;
                    $start_min = 0;
                    $dur_day = 0;
                    $dur_hour = 24;
                    $dur_min = 0;
                } else {
                    $dur_day = (int)Horde_Util::getFormData('dur_day');
                    $dur_hour = (int)Horde_Util::getFormData('dur_hour');
                    $dur_min = (int)Horde_Util::getFormData('dur_min');
                }
            }

            $this->start = new Horde_Date(array('hour' => $start_hour,
                                                'min' => $start_min,
                                                'month' => $start_month,
                                                'mday' => $start_day,
                                                'year' => $start_year));
        }

        if ($end_date = Horde_Util::getFormData('end_date')) {
            // Event end.
            $end_time = Horde_Util::getFormData('end_time');
            $end = $end_date . ' ' . $end_time;
            $format = $date_format . ' '
                . ($prefs->getValue('twentyFour') ? '%H:%M' : '%I:%M %p');
            // Try exact format match first.
            if ($date_arr = strptime($end, $format)) {
                $this->end = new Horde_Date(
                    array('year'  => $date_arr['tm_year'] + 1900,
                          'month' => $date_arr['tm_mon'] + 1,
                          'mday'  => $date_arr['tm_mday'],
                          'hour'  => $date_arr['tm_hour'],
                          'min'   => $date_arr['tm_min'],
                          'sec'   => $date_arr['tm_sec']));
            } else {
                $this->end = new Horde_Date($end);
            }
        } elseif (Horde_Util::getFormData('end_or_dur') == 1) {
            // Event duration.
            $this->end = new Horde_Date(array('hour' => $start_hour + $dur_hour,
                                              'min' => $start_min + $dur_min,
                                              'month' => $start_month,
                                              'mday' => $start_day + $dur_day,
                                              'year' => $start_year));
        } else {
            // Event end.
            $end = Horde_Util::getFormData('end');
            $end_year = $end['year'];
            $end_month = $end['month'];
            $end_day = $end['day'];
            $end_hour = Horde_Util::getFormData('end_hour');
            $end_min = Horde_Util::getFormData('end_min');
            $end_am_pm = Horde_Util::getFormData('end_am_pm');

            if (!$prefs->getValue('twentyFour')) {
                if ($end_am_pm == 'PM') {
                    if ($end_hour != 12) {
                        $end_hour += 12;
                    }
                } elseif ($end_hour == 12) {
                    $end_hour = 0;
                }
            }

            $this->end = new Horde_Date(array('hour' => $end_hour,
                                              'min' => $end_min,
                                              'month' => $end_month,
                                              'mday' => $end_day,
                                              'year' => $end_year));
            if ($this->end->compareDateTime($this->start) < 0) {
                $this->end = new Horde_Date($this->start);
            }
        }

        $this->allday = false;

        setlocale(LC_TIME, $old_locale);

        // Alarm.
        if (!is_null($alarm = Horde_Util::getFormData('alarm'))) {
            if ($alarm) {
                $this->setAlarm(Horde_Util::getFormData('alarm_value') * Horde_Util::getFormData('alarm_unit'));
                // Notification.
                if (Horde_Util::getFormData('alarm_change_method')) {
                    $types = Horde_Util::getFormData('event_alarms');
                    if (!empty($types)) {
                        $methods = array();
                        foreach ($types as $type) {
                            $methods[$type] = array();
                            switch ($type){
                            case 'notify':
                                $methods[$type]['sound'] = Horde_Util::getFormData('event_alarms_sound');
                                break;
                            case 'mail':
                                $methods[$type]['email'] = Horde_Util::getFormData('event_alarms_email');
                                break;
                            case 'popup':
                                break;
                            }
                        }
                        $this->methods = $methods;
                    }
                } else {
                    $this->methods = array();
                }
            } else {
                $this->setAlarm(0);
                $this->methods = array();
            }
        }

        // Recurrence.
        $recur = Horde_Util::getFormData('recur');
        if ($recur !== null && $recur !== '') {
            if (!isset($this->recurrence)) {
                $this->recurrence = new Horde_Date_Recurrence($this->start);
            }
            if (Horde_Util::getFormData('recur_enddate_type') == 'date') {
                $recur_enddate = Horde_Util::getFormData('recur_enddate');
                if ($this->recurrence->hasRecurEnd()) {
                    $recurEnd = $this->recurrence->recurEnd;
                    $recurEnd->month = $recur_enddate['month'];
                    $recurEnd->mday = $recur_enddate['day'];
                    $recurEnd->year = $recur_enddate['year'];
                } else {
                    $recurEnd = new Horde_Date(
                        array('hour' => 23,
                              'min' => 59,
                              'sec' => 59,
                              'month' => $recur_enddate['month'],
                              'mday' => $recur_enddate['day'],
                              'year' => $recur_enddate['year']));
                }
                $this->recurrence->setRecurEnd($recurEnd);
            } elseif (Horde_Util::getFormData('recur_enddate_type') == 'count') {
                $this->recurrence->setRecurCount(Horde_Util::getFormData('recur_count'));
            } elseif (Horde_Util::getFormData('recur_enddate_type') == 'none') {
                $this->recurrence->setRecurCount(0);
                $this->recurrence->setRecurEnd(null);
            }

            $this->recurrence->setRecurType($recur);
            switch ($recur) {
            case Horde_Date_Recurrence::RECUR_DAILY:
                $this->recurrence->setRecurInterval(Horde_Util::getFormData('recur_daily_interval', 1));
                break;

            case Horde_Date_Recurrence::RECUR_WEEKLY:
                $weekly = Horde_Util::getFormData('weekly');
                $weekdays = 0;
                if (is_array($weekly)) {
                    foreach ($weekly as $day) {
                        $weekdays |= $day;
                    }
                }

                if ($weekdays == 0) {
                    // Sunday starts at 0.
                    switch ($this->start->dayOfWeek()) {
                    case 0: $weekdays |= Horde_Date::MASK_SUNDAY; break;
                    case 1: $weekdays |= Horde_Date::MASK_MONDAY; break;
                    case 2: $weekdays |= Horde_Date::MASK_TUESDAY; break;
                    case 3: $weekdays |= Horde_Date::MASK_WEDNESDAY; break;
                    case 4: $weekdays |= Horde_Date::MASK_THURSDAY; break;
                    case 5: $weekdays |= Horde_Date::MASK_FRIDAY; break;
                    case 6: $weekdays |= Horde_Date::MASK_SATURDAY; break;
                    }
                }

                $this->recurrence->setRecurInterval(Horde_Util::getFormData('recur_weekly_interval', 1));
                $this->recurrence->setRecurOnDay($weekdays);
                break;

            case Horde_Date_Recurrence::RECUR_MONTHLY_DATE:
                $this->recurrence->setRecurInterval(Horde_Util::getFormData('recur_day_of_month_interval', 1));
                break;

            case Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY:
                $this->recurrence->setRecurInterval(Horde_Util::getFormData('recur_week_of_month_interval', 1));
                break;

            case Horde_Date_Recurrence::RECUR_YEARLY_DATE:
                $this->recurrence->setRecurInterval(Horde_Util::getFormData('recur_yearly_interval', 1));
                break;

            case Horde_Date_Recurrence::RECUR_YEARLY_DAY:
                $this->recurrence->setRecurInterval(Horde_Util::getFormData('recur_yearly_day_interval', 1));
                break;

            case Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY:
                $this->recurrence->setRecurInterval(Horde_Util::getFormData('recur_yearly_weekday_interval', 1));
                break;
            }

            if ($exceptions = Horde_Util::getFormData('exceptions')) {
                foreach ($exceptions as $exception) {
                    $this->recurrence->addException((int)substr($exception, 0, 4),
                                                    (int)substr($exception, 4, 2),
                                                    (int)substr($exception, 6, 2));
                }
            }
        }

        // Tags.
        $this->tags = Horde_Util::getFormData('tags', $this->tags);

        $this->initialized = true;
    }

    public function html($property)
    {
        global $prefs;

        $options = array();
        $attributes = '';
        $sel = false;
        $label = '';

        switch ($property) {
        case 'start[year]':
            return  '<label for="' . $this->_formIDEncode($property) . '" class="hidden">' . _("Start Year") . '</label>' .
                '<input name="' . $property . '" value="' . $this->start->year .
                '" type="text" onchange="' . $this->js($property) .
                '" id="' . $this->_formIDEncode($property) . '" size="4" maxlength="4" />';

        case 'start[month]':
            $sel = $this->start->month;
            for ($i = 1; $i < 13; ++$i) {
                $options[$i] = strftime('%b', mktime(1, 1, 1, $i, 1));
            }
            $attributes = ' onchange="' . $this->js($property) . '"';
            $label = _("Start Month");
            break;

        case 'start[day]':
            $sel = $this->start->mday;
            for ($i = 1; $i < 32; ++$i) {
                $options[$i] = $i;
            }
            $attributes = ' onchange="' . $this->js($property) . '"';
            $label = _("Start Day");
            break;

        case 'start_hour':
            $sel = $this->start->format($prefs->getValue('twentyFour') ? 'G' : 'g');
            $hour_min = $prefs->getValue('twentyFour') ? 0 : 1;
            $hour_max = $prefs->getValue('twentyFour') ? 24 : 13;
            for ($i = $hour_min; $i < $hour_max; ++$i) {
                $options[$i] = $i;
            }
            $attributes = ' onchange="document.eventform.whole_day.checked = false; KronolithEventForm.updateEndDate();"';
            $label = _("Start Hour");
            break;

        case 'start_min':
            $sel = sprintf('%02d', $this->start->min);
            for ($i = 0; $i < 12; ++$i) {
                $min = sprintf('%02d', $i * 5);
                $options[$min] = $min;
            }
            $attributes = ' onchange="document.eventform.whole_day.checked = false; KronolithEventForm.updateEndDate();"';
            $label = _("Start Minute");
            break;

        case 'end[year]':
            return  '<label for="' . $this->_formIDEncode($property) . '" class="hidden">' . _("End Year") . '</label>' .
                '<input name="' . $property . '" value="' . $this->end->year .
                '" type="text" onchange="' . $this->js($property) .
                '" id="' . $this->_formIDEncode($property) . '" size="4" maxlength="4" />';

        case 'end[month]':
            $sel = $this->end ? $this->end->month : $this->start->month;
            for ($i = 1; $i < 13; ++$i) {
                $options[$i] = strftime('%b', mktime(1, 1, 1, $i, 1));
            }
            $attributes = ' onchange="' . $this->js($property) . '"';
            $label = _("End Month");
            break;

        case 'end[day]':
            $sel = $this->end ? $this->end->mday : $this->start->mday;
            for ($i = 1; $i < 32; ++$i) {
                $options[$i] = $i;
            }
            $attributes = ' onchange="' . $this->js($property) . '"';
            $label = _("End Day");
            break;

        case 'end_hour':
            $sel = $this->end
                ? $this->end->format($prefs->getValue('twentyFour') ? 'G' : 'g')
                : $this->start->format($prefs->getValue('twentyFour') ? 'G' : 'g') + 1;
            $hour_min = $prefs->getValue('twentyFour') ? 0 : 1;
            $hour_max = $prefs->getValue('twentyFour') ? 24 : 13;
            for ($i = $hour_min; $i < $hour_max; ++$i) {
                $options[$i] = $i;
            }
            $attributes = ' onchange="KronolithEventForm.updateDuration(); document.eventform.end_or_dur[0].checked = true"';
            $label = _("End Hour");
            break;

        case 'end_min':
            $sel = $this->end ? $this->end->min : $this->start->min;
            $sel = sprintf('%02d', $sel);
            for ($i = 0; $i < 12; ++$i) {
                $min = sprintf('%02d', $i * 5);
                $options[$min] = $min;
            }
            $attributes = ' onchange="KronolithEventForm.updateDuration(); document.eventform.end_or_dur[0].checked = true"';
            $label = _("End Minute");
            break;

        case 'dur_day':
            $dur = $this->getDuration();
            return  '<label for="' . $property . '" class="hidden">' . _("Duration Day") . '</label>' .
                '<input name="' . $property . '" value="' . $dur->day .
                '" type="text" onchange="' . $this->js($property) .
                '" id="' . $property . '" size="4" maxlength="4" />';

        case 'dur_hour':
            $dur = $this->getDuration();
            $sel = $dur->hour;
            for ($i = 0; $i < 24; ++$i) {
                $options[$i] = $i;
            }
            $attributes = ' onchange="' . $this->js($property) . '"';
            $label = _("Duration Hour");
            break;

        case 'dur_min':
            $dur = $this->getDuration();
            $sel = $dur->min;
            for ($i = 0; $i < 13; ++$i) {
                $min = sprintf('%02d', $i * 5);
                $options[$min] = $min;
            }
            $attributes = ' onchange="' . $this->js($property) . '"';
            $label = _("Duration Minute");
            break;

        case 'recur_enddate[year]':
            if ($this->end) {
                $end = ($this->recurs() && $this->recurrence->hasRecurEnd())
                        ? $this->recurrence->recurEnd->year
                        : $this->end->year;
            } else {
                $end = $this->start->year;
            }
            return  '<label for="' . $this->_formIDEncode($property) . '" class="hidden">' . _("Recurrence End Year") . '</label>' .
                '<input name="' . $property . '" value="' . $end .
                '" type="text" onchange="' . $this->js($property) .
                '" id="' . $this->_formIDEncode($property) . '" size="4" maxlength="4" />';

        case 'recur_enddate[month]':
            if ($this->end) {
                $sel = ($this->recurs() && $this->recurrence->hasRecurEnd())
                    ? $this->recurrence->recurEnd->month
                    : $this->end->month;
            } else {
                $sel = $this->start->month;
            }
            for ($i = 1; $i < 13; ++$i) {
                $options[$i] = strftime('%b', mktime(1, 1, 1, $i, 1));
            }
            $attributes = ' onchange="' . $this->js($property) . '"';
            $label = _("Recurrence End Month");
            break;

        case 'recur_enddate[day]':
            if ($this->end) {
                $sel = ($this->recurs() && $this->recurrence->hasRecurEnd())
                    ? $this->recurrence->recurEnd->mday
                    : $this->end->mday;
            } else {
                $sel = $this->start->mday;
            }
            for ($i = 1; $i < 32; ++$i) {
                $options[$i] = $i;
            }
            $attributes = ' onchange="' . $this->js($property) . '"';
            $label = _("Recurrence End Day");
            break;
        }

        if (!$this->_varRenderer) {
            $this->_varRenderer = Horde_UI_VarRenderer::factory('html');
        }

        return '<label for="' . $this->_formIDEncode($property) . '" class="hidden">' . $label . '</label>' .
            '<select name="' . $property . '"' . $attributes . ' id="' . $this->_formIDEncode($property) . '">' .
            $this->_varRenderer->_selectOptions($options, $sel) .
            '</select>';
    }

    public function js($property)
    {
        switch ($property) {
        case 'start[month]':
        case 'start[year]':
        case 'start[day]':
        case 'start':
            return 'KronolithEventForm.updateWday(\'start_wday\'); document.eventform.whole_day.checked = false; KronolithEventForm.updateEndDate();';

        case 'end[month]':
        case 'end[year]':
        case 'end[day]':
        case 'end':
            return 'KronolithEventForm.updateWday(\'end_wday\'); updateDuration(); document.eventform.end_or_dur[0].checked = true;';

        case 'recur_enddate[month]':
        case 'recur_enddate[year]':
        case 'recur_enddate[day]':
        case 'recur_enddate':
            return 'KronolithEventForm.updateWday(\'recur_end_wday\'); document.eventform.recur_enddate_type[1].checked = true;';

        case 'dur_day':
        case 'dur_hour':
        case 'dur_min':
            return 'document.eventform.whole_day.checked = false; KronolithEventForm.updateEndDate(); document.eventform.end_or_dur[1].checked = true;';
        }
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getViewUrl($params = array(), $full = false)
    {
        $params['eventID'] = $this->eventID;
        if ($this->remoteUrl) {
            return $this->remoteUrl;
        } elseif ($this->remoteCal) {
            $params['calendar'] = '**remote';
            $params['remoteCal'] = $this->remoteCal;
        } else {
            $params['calendar'] = $this->getCalendar();
        }

        return Horde::applicationUrl(Horde_Util::addParameter('event.php', $params), $full);
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getEditUrl($params = array())
    {
        $params['view'] = 'EditEvent';
        $params['eventID'] = $this->eventID;
        if ($this->remoteCal) {
            $params['calendar'] = '**remote';
            $params['remoteCal'] = $this->remoteCal;
        } else {
            $params['calendar'] = $this->getCalendar();
        }

        return Horde::applicationUrl(Horde_Util::addParameter('event.php', $params));
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getDeleteUrl($params = array())
    {
        $params['view'] = 'DeleteEvent';
        $params['eventID'] = $this->eventID;
        $params['calendar'] = $this->getCalendar();
        return Horde::applicationUrl(Horde_Util::addParameter('event.php', $params));
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function getExportUrl($params = array())
    {
        $params['view'] = 'ExportEvent';
        $params['eventID'] = $this->eventID;
        if ($this->remoteCal) {
            $params['calendar'] = '**remote';
            $params['remoteCal'] = $this->remoteCal;
        } else {
            $params['calendar'] = $this->getCalendar();
        }

        return Horde::applicationUrl(Horde_Util::addParameter('event.php', $params));
    }

    public function getLink($datetime = null, $icons = true, $from_url = null, $full = false)
    {
        global $prefs, $registry;

        if (is_null($datetime)) {
            $datetime = $this->start;
        }
        if (is_null($from_url)) {
            $from_url = Horde::selfUrl(true, false, true);
        }

        $link = '';
        $event_title = $this->getTitle();
        if (isset($this->external) && !empty($this->external_link)) {
            $link = $this->external_link;
            $link = Horde::linkTooltip(Horde::url($link), '', 'event-tentative', '', '', Horde_String::wrap($this->description));
        } elseif (isset($this->eventID) && $this->hasPermission(PERMS_READ)) {
            $link = Horde::linkTooltip($this->getViewUrl(array('datetime' => $datetime->strftime('%Y%m%d%H%M%S'), 'url' => $from_url), $full),
                                       $event_title,
                                       $this->getStatusClass(), '', '',
                                       $this->getTooltip(),
                                       '',
                                       array('style' => $this->getCSSColors(false)));
        }

        $link .= @htmlspecialchars($event_title, ENT_QUOTES, Horde_Nls::getCharset());

        if ($this->hasPermission(PERMS_READ) &&
            (isset($this->eventID) ||
             isset($this->external))) {
            $link .= '</a>';
        }

        if ($icons && $prefs->getValue('show_icons')) {
            $icon_color = $this->_foregroundColor == '#000' ? '000' : 'fff';
            $status = '';
            if ($this->alarm) {
                if ($this->alarm % 10080 == 0) {
                    $alarm_value = $this->alarm / 10080;
                    $title = sprintf(ngettext("Alarm %d week before", "Alarm %d weeks before", $alarm_value), $alarm_value);
                } elseif ($this->alarm % 1440 == 0) {
                    $alarm_value = $this->alarm / 1440;
                    $title = sprintf(ngettext("Alarm %d day before", "Alarm %d days before", $alarm_value), $alarm_value);
                } elseif ($this->alarm % 60 == 0) {
                    $alarm_value = $this->alarm / 60;
                    $title = sprintf(ngettext("Alarm %d hour before", "Alarm %d hours before", $alarm_value), $alarm_value);
                } else {
                    $alarm_value = $this->alarm;
                    $title = sprintf(ngettext("Alarm %d minute before", "Alarm %d minutes before", $alarm_value), $alarm_value);
                }
                $status .= Horde::fullSrcImg('alarm-' . $icon_color . '.png', array('attr' => array('alt' => $title, 'title' => $title, 'class' => 'iconAlarm')));
            }

            if ($this->recurs()) {
                $title = Kronolith::recurToString($this->recurrence->getRecurType());
                $status .= Horde::fullSrcImg('recur-' . $icon_color . '.png', array('attr' => array('alt' => $title, 'title' => $title, 'class' => 'iconRecur')));
            }

            if ($this->isPrivate()) {
                $title = _("Private event");
                $status .= Horde::fullSrcImg('private-' . $icon_color . '.png', array('attr' => array('alt' => $title, 'title' => $title, 'class' => 'iconPrivate')));
            }

            if (!empty($this->attendees)) {
                $status .= Horde::fullSrcImg('attendees.png', array('attr' => array('alt' => _("Meeting"), 'title' => _("Meeting"), 'class' => 'iconPeople')));
            }

            if (!empty($this->external) && !empty($this->external_icon)) {
                $link = $status . '<img src="' . $this->external_icon . '" /> ' . $link;
            } else if (!empty($status)) {
                $link .= ' ' . $status;
            }

            if (!$this->eventID || !empty($this->external)) {
                return $link;
            }

            $edit = '';
            $delete = '';
            if ((!$this->isPrivate() || $this->getCreatorId() == Horde_Auth::getAuth())
                && $this->hasPermission(PERMS_EDIT)) {
                $editurl = $this->getEditUrl(array('datetime' => $datetime->strftime('%Y%m%d%H%M%S'),
                                                   'url' => $from_url));
                $edit = Horde::link($editurl, sprintf(_("Edit %s"), $event_title), 'iconEdit')
                    . Horde::fullSrcImg('edit-' . $icon_color . '.png', array('attr' => 'alt="' . _("Edit") . '"'))
                    . '</a>';
            }
            if ($this->hasPermission(PERMS_DELETE)) {
                $delurl = $this->getDeleteUrl(array('datetime' => $datetime->strftime('%Y%m%d%H%M%S'),
                                                    'url' => $from_url));
                $delete = Horde::link($delurl, sprintf(_("Delete %s"), $event_title), 'iconDelete')
                    . Horde::fullSrcImg('delete-' . $icon_color . '.png', array('attr' => 'alt="' . _("Delete") . '"'))
                    . '</a>';
            }

            if ($edit || $delete) {
                $link .= $edit . $delete;
            }
        }

        return $link;
    }

    /**
     * Returns the CSS color definition for this event.
     *
     * @param boolean $with_attribute  Whether to wrap the colors inside a
     *                                 "style" attribute.
     *
     * @return string  A CSS string with color definitions.
     */
    public function getCSSColors($with_attribute = true)
    {
        $css = 'background-color:' . $this->_backgroundColor . ';color:' . $this->_foregroundColor;
        if ($with_attribute) {
            $css = ' style="' . $css . '"';
        }
        return $css;
    }

    /**
     * @return string  A tooltip for quick descriptions of this event.
     */
    public function getTooltip()
    {
        $tooltip = $this->getTimeRange()
            . "\n" . sprintf(_("Owner: %s"), ($this->getCreatorId() == Horde_Auth::getAuth() ?
                                              _("Me") : Kronolith::getUserName($this->getCreatorId())));

        if (!$this->isPrivate() || $this->getCreatorId() == Horde_Auth::getAuth()) {
            if ($this->location) {
                $tooltip .= "\n" . _("Location") . ': ' . $this->location;
            }

            if ($this->description) {
                $tooltip .= "\n\n" . Horde_String::wrap($this->description);
            }
        }

        return $tooltip;
    }

    /**
     * @return string  The time range of the event ("All Day", "1:00pm-3:00pm",
     *                 "08:00-22:00").
     */
    public function getTimeRange()
    {
        if ($this->isAllDay()) {
            return _("All day");
        } elseif (($cmp = $this->start->compareDate($this->end)) > 0) {
            $df = $GLOBALS['prefs']->getValue('date_format');
            if ($cmp > 0) {
                return $this->end->strftime($df) . '-'
                    . $this->start->strftime($df);
            } else {
                return $this->start->strftime($df) . '-'
                    . $this->end->strftime($df);
            }
        } else {
            $twentyFour = $GLOBALS['prefs']->getValue('twentyFour');
            return $this->start->format($twentyFour ? 'G:i' : 'g:ia')
                . '-'
                . $this->end->format($twentyFour ? 'G:i' : 'g:ia');
        }
    }

    /**
     * @return string  The CSS class for the event based on its status.
     */
    public function getStatusClass()
    {
        switch ($this->status) {
        case Kronolith::STATUS_CANCELLED:
            return 'event-cancelled';

        case Kronolith::STATUS_TENTATIVE:
        case Kronolith::STATUS_FREE:
            return 'event-tentative';
        }

        return 'event';
    }

    private function _formIDEncode($id)
    {
        return str_replace(array('[', ']'),
                           array('_', ''),
                           $id);
    }

}
