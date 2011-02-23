<?php
/**
 * Kronolith_Event defines a generic API for events.
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
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
    protected $_id = null;

    /**
     * The UID for this event.
     *
     * @var string
     */
    public $uid = null;

    /**
     * The iCalendar SEQUENCE for this event.
     *
     * @var integer
     */
    public $sequence = null;

    /**
     * The user id of the creator of the event.
     *
     * @var string
     */
    protected $_creator = null;

    /**
     * The title of this event.
     *
     * For displaying in the interface use getTitle() instead.
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
     * URL to an icon of this event.
     *
     * @var string
     */
    public $icon = '';

    /**
     * The description for this event.
     *
     * @var string
     */
    public $description = '';

    /**
     * URL of this event.
     *
     * @var string
     */
    public $url = '';

    /**
     * Whether the event is private.
     *
     * @var boolean
     */
    public $private = false;

    /**
     * This tag's events.
     *
     * @var array|string
     */
    protected $_tags = null;

    /**
     * Geolocation
     *
     * @var array
     */
    protected $_geoLocation;

    /**
     * Whether this is the event on the first day of a multi-day event.
     *
     * @var boolen
     */
    public $first = true;

    /**
     * Whether this is the event on the last day of a multi-day event.
     *
     * @var boolen
     */
    public $last = true;

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
     * associative arrays with keys attendance and response.
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
    public $calendar;

    /**
     * The type of the calender this event exists on.
     *
     * @var string
     */
    public $calendarType;

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
    protected $_foregroundColor = '#000000';

    /**
     * The VarRenderer class to use for printing select elements.
     *
     * @var Horde_Core_Ui_VarRenderer
     */
    private $_varRenderer;

    /**
     * The Horde_Date_Recurrence class for this event.
     *
     * @var Horde_Date_Recurrence
     */
    public $recurrence;

    /**
     * Used in view renderers.
     *
     * @var integer
     */
    protected $_overlap;

    /**
     * Used in view renderers.
     *
     * @var integer
     */
    protected $_indent;

    /**
     * Used in view renderers.
     *
     * @var integer
     */
    protected $_span;

    /**
     * Used in view renderers.
     *
     * @var integer
     */
    protected $_rowspan;

    /**
     * The baseid. For events that represent exceptions this is the UID of the
     * original, recurring event.
     *
     * @var string
     */
    public $baseid;

    /**
     * For exceptions, the date of the original recurring event that this is an
     * exception for.
     *
     * @var Horde_Date
     */
    public $exceptionoriginaldate;

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
        $this->calendar = $driver->calendar;
        list($this->_backgroundColor, $this->_foregroundColor) = $driver->colors();

        if (!is_null($eventObject)) {
            $this->fromDriver($eventObject);
        }
    }

    /**
     * Setter.
     *
     * Sets the 'id' and 'creator' properties.
     *
     * @param string $name  Property name.
     * @param mixed $value  Property value.
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case 'id':
            if (substr($value, 0, 10) == 'kronolith:') {
                $value = substr($value, 10);
            }
            // Fall through.
        case 'creator':
        case 'overlap':
        case 'indent':
        case 'span':
        case 'rowspan':
        case 'geoLocation':
        case 'tags':
            $this->{'_' . $name} = $value;
            return;
        }
        $trace = debug_backtrace();
        trigger_error('Undefined property via __set(): ' . $name
                      . ' in ' . $trace[0]['file']
                      . ' on line ' . $trace[0]['line'],
                      E_USER_NOTICE);
    }

    /**
     * Getter.
     *
     * Returns the 'id' and 'creator' properties.
     *
     * @param string $name  Property name.
     *
     * @return mixed  Property value.
     */
    public function __get($name)
    {
        switch ($name) {
        case 'creator':
            if (empty($this->_creator)) {
                $this->_creator = $GLOBALS['registry']->getAuth();
            }
            // Fall through.
        case 'id':
        case 'overlap':
        case 'indent':
        case 'span':
        case 'rowspan':
            return $this->{'_' . $name};
        case 'tags':
            if (!isset($this->_tags)) {
                $this->_tags = Kronolith::getTagger()->getTags($this->uid, 'event');
            }
            return $this->_tags;
        case 'geoLocation':
            if (!isset($this->_geoLocation)) {
                try {
                    $this->_geoLocation = $GLOBALS['injector']->getInstance('Kronolith_Geo')->getLocation($this->id);
                } catch (Kronolith_Exception $e) {}
            }
            return $this->_geoLocation;
        }

        $trace = debug_backtrace();
        trigger_error('Undefined property via __set(): ' . $name
                      . ' in ' . $trace[0]['file']
                      . ' on line ' . $trace[0]['line'],
                      E_USER_NOTICE);
        return null;
    }

    /**
     * Returns a reference to a driver that's valid for this event.
     *
     * @return Kronolith_Driver  A driver that this event can use to save
     *                           itself, etc.
     */
    public function getDriver()
    {
        return Kronolith::getDriver(str_replace('Kronolith_Event_', '', get_class($this)), $this->calendar);
    }

    /**
     * Returns the share this event belongs to.
     *
     * @return Horde_Share  This event's share.
     * @throws Kronolith_Exception
     */
    public function getShare()
    {
        if (isset($GLOBALS['all_calendars'][$this->calendar])) {
            return $GLOBALS['all_calendars'][$this->calendar]->share();
        }
        throw new Kronolith_Exception('Share not found');
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
            $user = $GLOBALS['registry']->getAuth();
        }
        try {
            $share = $this->getShare();
        } catch (Exception $e) {
            return false;
        }
        return $share->hasPermission($user, $permission, $this->creator);
    }

    /**
     * Saves changes to this event.
     *
     * @return integer  The event id.
     * @throws Kronolith_Exception
     */
    public function save()
    {
        if (!$this->initialized) {
            throw new Kronolith_Exception('Event not yet initialized');
        }

        /* Check for acceptance/denial of this event's resources. */
        $add_events = array();
        $locks = $GLOBALS['injector']->getInstance('Horde_Lock');
        $lock = array();
        $failed_resources = array();
        foreach ($this->getResources() as $id => $resourceData) {
            /* Get the resource and protect against infinite recursion in case
             * someone is silly enough to add a resource to it's own event.*/
            $resource = Kronolith::getDriver('Resource')->getResource($id);
            $rcal = $resource->get('calendar');
            if ($rcal == $this->calendar) {
                continue;
            }

            /* Lock the resource and get the response */
            if ($resource->get('response_type') == Kronolith_Resource::RESPONSETYPE_AUTO) {
                $principle = 'calendar/' . $rcal;
                $lock[$resource->getId()] = $locks->setLock($GLOBALS['registry']->getAuth(), 'kronolith', $principle, 5, Horde_Lock::TYPE_EXCLUSIVE);
                $haveLock = true;
            } else {
                $haveLock = false;
            }
            if ($haveLock && !$lock[$resource->getId()]) {
                // Already locked
                // For now, just fail. Not sure how else to capture the locked
                // resources and notify the user.
                throw new Kronolith_Exception(sprintf(_("The resource \"%s\" was locked. Please try again."), $resource->get('name')));
            } else {
                $response = $resource->getResponse($this);
            }

            /* Remember accepted resources so we can add the event to their
             * calendars. Otherwise, clear the lock. */
            if ($response == Kronolith::RESPONSE_ACCEPTED) {
                $add_events[] = $resource;
            } else {
                $locks->clearLock($lock[$resource->getId()]);
            }

            /* Add the resource to the event */
            $this->addResource($resource, $response);
        }

        /* Save */
        $result = $this->getDriver()->saveEvent($this);

        /* Now that the event is definitely commited to storage, we can add
         * the event to each resource that has accepted. Not very efficient,
         * but this also solves the problem of not having a GUID for the event
         * until after it's saved. If we add the event to the resources
         * calendar before it is saved, they will have different GUIDs, and
         * hence no longer refer to the same event. */
        foreach ($add_events as $resource) {
            $resource->addEvent($this);
            if ($resource->get('response_type') == Kronolith_Resource::RESPONSETYPE_AUTO) {
                $locks->clearLock($lock[$resource->getId()]);
            }
        }

        if ($alarm = $this->toAlarm(new Horde_Date($_SERVER['REQUEST_TIME']))) {
            $alarm['start'] = new Horde_Date($alarm['start']);
            $alarm['end'] = new Horde_Date($alarm['end']);
            $GLOBALS['injector']->getInstance('Horde_Alarm')->set($alarm);
        } else {
            $GLOBALS['injector']->getInstance('Horde_Alarm')->delete($this->uid);
        }

        return $result;
    }

    /**
     * Imports a backend specific event object.
     *
     * @param mixed $eventObject  Backend specific event object that this
     *                            object will represent.
     */
    public function fromDriver($event)
    {
    }

    /**
     * Exports this event in iCalendar format.
     *
     * @param Horde_Icalendar $calendar  A Horde_Icalendar object that acts as
     *                                   a container.
     *
     * @return array  An array of Horde_Icalendar_Vevent objects for this event.
     */
    public function toiCalendar($calendar)
    {
        $vEvent = Horde_Icalendar::newComponent('vevent', $calendar);
        $v1 = $calendar->getAttribute('VERSION') == '1.0';
        $vEvents = array();
        if ($this->isAllDay()) {
            $vEvent->setAttribute('DTSTART', $this->start, array('VALUE' => 'DATE'));
            $vEvent->setAttribute('DTEND', $this->end, array('VALUE' => 'DATE'));
        } else {
            $vEvent->setAttribute('DTSTART', $this->start);
            $vEvent->setAttribute('DTEND', $this->end);
        }

        $vEvent->setAttribute('DTSTAMP', $_SERVER['REQUEST_TIME']);
        $vEvent->setAttribute('UID', $this->uid);

        /* Get the event's history. */
        $created = $modified = null;
        try {
            $log = $GLOBALS['injector']->getInstance('Horde_History')->getHistory('kronolith:' . $this->calendar . ':' . $this->uid);
            foreach ($log as $entry) {
                switch ($entry['action']) {
                case 'add':
                    $created = $entry['ts'];
                    break;

                case 'modify':
                    $modified = $entry['ts'];
                    break;
                }
            }
        } catch (Exception $e) {}
        if (!empty($created)) {
            $vEvent->setAttribute($v1 ? 'DCREATED' : 'CREATED', $created);
            if (empty($modified)) {
                $modified = $created;
            }
        }
        if (!empty($modified)) {
            $vEvent->setAttribute('LAST-MODIFIED', $modified);
        }

        $vEvent->setAttribute('SUMMARY', $this->getTitle());
        $name = Kronolith::getUserName($this->creator);
        $vEvent->setAttribute('ORGANIZER',
                              'mailto:' . Kronolith::getUserEmail($this->creator),
                              array('CN' => $name));
        if (!$this->private || $this->creator == $GLOBALS['registry']->getAuth()) {
            if (!empty($this->description)) {
                $vEvent->setAttribute('DESCRIPTION', $this->description);
            }

            // Tags
            if ($this->tags) {
                $tags = implode(', ', $this->tags);
                $vEvent->setAttribute('CATEGORIES', $tags);
            }

            // Location
            if (!empty($this->location)) {
                $vEvent->setAttribute('LOCATION', $this->location);
            }
            if ($this->geoLocation) {
                $vEvent->setAttribute('GEO', array('latitude' => $this->geoLocation['lat'], 'longitude' => $this->geoLocation['lon']));
            }

            // URL
            if (!empty($this->url)) {
                $vEvent->setAttribute('URL', $this->url);
            }
        }
        $vEvent->setAttribute('CLASS', $this->private ? 'PRIVATE' : 'PUBLIC');

        // Status.
        switch ($this->status) {
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
        foreach ($this->attendees as $email => $status) {
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
                    $params['CN'] = $status['name'];
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
                $vAlarm = Horde_Icalendar::newComponent('valarm', $vEvent);
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

            // Exceptions. An exception with no replacement event is represented
            // by EXDATE, and those with replacement events are represented by
            // a new vEvent element. We get all known replacement events first,
            // then remove the exceptionoriginaldate from the list of the event
            // exceptions. Any exceptions left should represent exceptions with
            // no replacement.
            $exceptions = $this->recurrence->getExceptions();
            $kronolith_driver = Kronolith::getDriver(null, $this->calendar);
            $search = new StdClass();
            $search->start = $this->recurrence->getRecurStart();
            $search->end = $this->recurrence->getRecurEnd();
            $search->baseid = $this->uid;
            $results = $kronolith_driver->search($search);
            foreach ($results as $days) {
                foreach ($days as $exceptionEvent) {
                    // Need to change the UID so it links to the original
                    // recurring event.
                    $exceptionEvent->uid = $this->uid;
                    $vEventException = $exceptionEvent->toiCalendar($calendar);
                    // This should never happen, but protect against it anyway.
                    if (count($vEventException) > 1) {
                        throw new Kronolith_Exception(_("Unable to parse event."));
                    }
                    $vEventException = array_pop($vEventException);
                    $vEventException->setAttribute('RECURRENCE-ID', $exceptionEvent->exceptionoriginaldate->timestamp());
                    $originaldate = $exceptionEvent->exceptionoriginaldate->format('Ymd');
                    $key = array_search($originaldate, $exceptions);
                    if ($key !== false) {
                        unset($exceptions[$key]);
                    }
                    $vEvents[] = $vEventException;
                }
            }

            /* The remaining exceptions represent deleted recurrences */
            $exdates = array();
            foreach ($exceptions as $exception) {
                if (!empty($exception)) {
                    list($year, $month, $mday) = sscanf($exception, '%04d%02d%02d');
                    $exdates[] = new Horde_Date($year, $month, $mday);
                }
            }
            if ($exdates) {
                $vEvent->setAttribute('EXDATE', $exdates);
            }
        }
        array_unshift($vEvents, $vEvent);

        return $vEvents;
    }

    /**
     * Updates the properties of this event from a Horde_Icalendar_Vevent
     * object.
     *
     * @param Horde_Icalendar_Vevent $vEvent  The iCalendar data to update
     *                                        from.
     */
    public function fromiCalendar($vEvent)
    {
        // Unique ID.
        try {
            $uid = $vEvent->getAttribute('UID');
            if (!empty($uid)) {
                $this->uid = $uid;
            }
        } catch (Horde_Icalendar_Exception $e) {}

        // Sequence.
        try {
            $seq = $vEvent->getAttribute('SEQUENCE');
            if (is_int($seq)) {
                $this->sequence = $seq;
            }
        } catch (Horde_Icalendar_Exception $e) {}

        // Title, tags and description.
        try {
            $title = $vEvent->getAttribute('SUMMARY');
            if (!is_array($title)) {
                $this->title = $title;
            }
        } catch (Horde_Icalendar_Exception $e) {}

        // Tags
        try {
            $this->_tags = $vEvent->getAttributeValues('CATEGORIES');
        } catch (Horde_Icalendar_Exception $e) {}

        // Description
        try {
            $desc = $vEvent->getAttribute('DESCRIPTION');
            if (!is_array($desc)) {
                $this->description = $desc;
            }
        } catch (Horde_Icalendar_Exception $e) {}

        // Remote Url
        try {
            $url = $vEvent->getAttribute('URL');
            if (!is_array($url)) {
                $this->url = $url;
            }
        } catch (Horde_Icalendar_Exception $e) {}

        // Location
        try {
            $location = $vEvent->getAttribute('LOCATION');
            if (!is_array($location)) {
                $this->location = $location;
            }
        } catch (Horde_Icalendar_Exception $e) {}

        try {
            $geolocation = $vEvent->getAttribute('GEO');
            $this->geoLocation = array(
                'lat' => $geolocation['latitude'],
                'lon' => $geolocation['longitude']
            );
        } catch (Horde_Icalendar_Exception $e) {}

        // Class
        try {
            $class = $vEvent->getAttribute('CLASS');
            if (!is_array($class)) {
                $class = Horde_String::upper($class);
                $this->private = $class == 'PRIVATE' || $class == 'CONFIDENTIAL';
            }
        } catch (Horde_Icalendar_Exception $e) {}

        // Status.
        try {
            $status = $vEvent->getAttribute('STATUS');
            if (!is_array($status)) {
                $status = Horde_String::upper($status);
                if ($status == 'DECLINED') {
                    $status = 'CANCELLED';
                }
                if (defined('Kronolith::STATUS_' . $status)) {
                    $this->status = constant('Kronolith::STATUS_' . $status);
                }
            }
        } catch (Horde_Icalendar_Exception $e) {}

        // Reset allday flag in case this has changed. Will be recalculated
        // next time isAllDay() is called.
        $this->allday = false;

        // Start and end date.
        try {
            $start = $vEvent->getAttribute('DTSTART');
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
        } catch (Horde_Icalendar_Exception $e) {}

        try {
            $end = $vEvent->getAttribute('DTEND');
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
            } else {
                // Date field
                $this->end = new Horde_Date(
                    array('year'  => (int)$end['year'],
                          'month' => (int)$end['month'],
                          'mday'  => (int)$end['mday']));
            }
        } catch (Horde_Icalendar_Exception $e) {
            $end = null;
        }

        if (is_null($end)) {
            try {
                $duration = $vEvent->getAttribute('DURATION');
                if (!is_array($duration)) {
                    $this->end = new Horde_Date($this->start);
                    $this->end->sec += $duration;
                    $end = 1;
                }
            } catch (Horde_Icalendar_Exception $e) {}

            if (is_null($end)) {
                // End date equal to start date as per RFC 2445.
                $this->end = new Horde_Date($this->start);
                if (is_array($start)) {
                    // Date field
                    $this->end->mday++;
                }
            }
        }

        // vCalendar 1.0 alarms
        try {
            $alarm = $vEvent->getAttribute('AALARM');
            if (!is_array($alarm) && intval($alarm)) {
                $this->alarm = intval(($this->start->timestamp() - $alarm) / 60);
            }
        } catch (Horde_Icalendar_Exception $e) {}

        // @TODO: vCalendar 2.0 alarms

        // Attendance.
        // Importing attendance may result in confusion: editing an imported
        // copy of an event can cause invitation updates to be sent from
        // people other than the original organizer. So we don't import by
        // default. However to allow updates by SyncML replication, the custom
        // X-ATTENDEE attribute is used which has the same syntax as
        // ATTENDEE.
        try {
            $attendee = $vEvent->getAttribute('X-ATTENDEE');
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
        } catch (Horde_Icalendar_Exception $e) {}

        $this->_handlevEventRecurrence($vEvent);

        $this->initialized = true;
    }

    /**
     * Handle parsing recurrence related fields.
     *
     * @param Horde_Icalendar $vEvent
     */
    protected function _handlevEventRecurrence($vEvent)
    {
        // Recurrence.
        try {
            $rrule = $vEvent->getAttribute('RRULE');
            if (!is_array($rrule)) {
                $this->recurrence = new Horde_Date_Recurrence($this->start);
                if (strpos($rrule, '=') !== false) {
                    $this->recurrence->fromRRule20($rrule);
                } else {
                    $this->recurrence->fromRRule10($rrule);
                }

                /* Delete all existing exceptions to this event if it already exists */
                if (!empty($this->uid)) {
                    $kronolith_driver = Kronolith::getDriver(null, $this->calendar);
                    $search = new StdClass();
                    $search->start = $this->recurrence->getRecurStart();
                    $search->end = $this->recurrence->getRecurEnd();
                    $search->baseid = $this->uid;
                    $results = $kronolith_driver->search($search);
                    foreach ($results as $days) {
                        foreach ($days as $exception) {
                            $kronolith_driver->deleteEvent($exception->id);
                        }
                    }
                }

                // Exceptions. EXDATE represents deleted events, just add the
                // exception, no new event is needed.
                $exdates = $vEvent->getAttributeValues('EXDATE');
                if (is_array($exdates)) {
                    foreach ($exdates as $exdate) {
                        if (is_array($exdate)) {
                            $this->recurrence->addException(
                                (int)$exdate['year'],
                                (int)$exdate['month'],
                                (int)$exdate['mday']);
                        }
                    }
                }
            }
        } catch (Horde_Icalendar_Exception $e) {}

        // RECURRENCE-ID indicates that this event represents an exception
        try {
            $recurrenceid = $vEvent->getAttribute('RECURRENCE-ID');
            $kronolith_driver = Kronolith::getDriver(null, $this->calendar);
            $originaldt = new Horde_Date($recurrenceid);
            $this->exceptionoriginaldate = $originaldt;
            $this->baseid = $this->uid;
            $this->uid = null;
            $originalEvent = $kronolith_driver->getByUID($this->baseid);
            $originalEvent->recurrence->addException($originaldt->format('Y'),
                $originaldt->format('m'),
                $originaldt->format('d'));
            $originalEvent->save();
        } catch (Horde_Icalendar_Exception $e) {}
    }

    /**
     * Imports the values for this event from a MS ActiveSync Message.
     *
     * @see Horde_ActiveSync_Message_Appointment
     */
    public function fromASAppointment(Horde_ActiveSync_Message_Appointment $message)
    {
        /* New event? */
        if ($this->id === null) {
            $this->creator = $GLOBALS['registry']->getAuth();
        }
        if (strlen($title = $message->getSubject())) {
            $this->title = $title;
        }
        if (strlen($description = $message->getBody())) {
            $this->description = $description;
        }
        if (strlen($location = $message->getLocation())) {
            $this->location = $location;
        }

        /* Date/times */
        $dates = $message->getDatetime();
        $this->start = $dates['start'];
        $this->end = $dates['end'];
        $this->allday = $dates['allday'];

        /* Sensitivity */
        $this->private = ($message->getSensitivity() == Horde_ActiveSync_Message_Appointment::SENSITIVITY_PRIVATE || $message->getSensitivity() == Horde_ActiveSync_Message_Appointment::SENSITIVITY_CONFIDENTIAL) ? true :  false;

        /* Busy Status */
        $status = $message->getBusyStatus();
        switch ($status) {
        case Horde_ActiveSync_Message_Appointment::BUSYSTATUS_BUSY:
            $status = Kronolith::STATUS_CONFIRMED;
            break;

        case Horde_ActiveSync_Message_Appointment::BUSYSTATUS_FREE:
            $status = Kronolith::STATUS_FREE;
            break;

        case Horde_ActiveSync_Message_Appointment::BUSYSTATUS_TENTATIVE:
            $status = Kronolith::STATUS_TENTATIVE;
            break;
        // @TODO: not sure how "Out" should show in kronolith...
        case Horde_ActiveSync_Message_Appointment::BUSYSTATUS_OUT:
            $status = Kronolith::STATUS_CONFIRMED;
        default:
            $status = Kronolith::STATUS_NONE;
        }
        $this->status = $status;

        /* Alarm */
        if ($alarm = $message->getReminder()) {
            $this->alarm = $alarm;
        }

        /* Recurrence */
        if ($rrule = $message->getRecurrence()) {

            /* Exceptions */
            /* Since AS keeps exceptions as part of the original event, we need to
             * delete all existing exceptions and re-create them. The only drawback
             * to this is that the UIDs will change.
             */
            $this->recurrence = $rrule;
            if (!empty($this->uid)) {
                $kronolith_driver = Kronolith::getDriver(null, $this->calendar);
                $search = new StdClass();
                $search->start = $rrule->getRecurStart();
                $search->end = $rrule->getRecurEnd();
                $search->baseid = $this->uid;
                $results = $kronolith_driver->search($search);
                foreach ($results as $days) {
                    foreach ($days as $exception) {
                        $kronolith_driver->deleteEvent($exception->id);
                    }
                }
            }

            $erules = $message->getExceptions();
            foreach ($erules as $rule){
                /* Readd the exception event, but only if not deleted */
                if (!$rule->deleted) {
                    $event = $kronolith_driver->getEvent();
                    $times = $rule->getDatetime();
                    $original = $rule->getExceptionStartTime();
                    $this->recurrence->addException($original->format('Y'), $original->format('m'), $original->format('d'));
                    $event->start = $times['start'];
                    $event->end = $times['end'];
                    $event->allday = $times['allday'];
                    $event->title = $rule->getSubject();
                    $event->description = $rule->getBody();
                    $event->baseid = $this->uid;
                    $event->exceptionoriginaldate = $original;
                    $event->initialized = true;
                    $event->save();
                } else {
                    /* For exceptions that are deletions, just add the exception */
                    $exceptiondt = $rule->getExceptionStartTime();
                    $this->recurrence->addException($exceptiondt->format('Y'), $exceptiondt->format('m'), $exceptiondt->format('d'));
               }
            }
        }

        /* Attendees */
        $attendees = $message->getAttendees();
        foreach ($attendees as $attendee) {
            // TODO: participation and response are not supported in AS <= 2.5
            $this->addAttendee($attendee->email,
                               Kronolith::PART_NONE,
                               Kronolith::RESPONSE_NONE,
                               $attendee->name);
        }

        /* Categories (Tags) */
        $this->_tags = $message->getCategories();

        /* Flag that we are initialized */
        $this->initialized = true;
    }

    /**
     * Export this event as a MS ActiveSync Message
     *
     * @return Horde_ActiveSync_Message_Appointment
     */
    public function toASAppointment()
    {
        $message = new Horde_ActiveSync_Message_Appointment(array('logger' => $GLOBALS['injector']->getInstance('Horde_Log_Logger')));
        $message->setSubject($this->getTitle());
        $message->setBody($this->description);
        $message->setLocation($this->location);

        /* Start and End */
        $message->setDatetime(array('start' => $this->start,
                                    'end' => $this->end,
                                    'allday' => $this->isAllDay()));

        /* Timezone */
        $message->setTimezone($this->start);

        /* Organizer */
        $name = Kronolith::getUserName($this->creator);
        $message->setOrganizer(
                array('name' => $name,
                      'email' => Kronolith::getUserEmail($this->creator))
        );

        /* Privacy */
        $message->setSensitivity($this->private ?
            Horde_ActiveSync_Message_Appointment::SENSITIVITY_PRIVATE :
            Horde_ActiveSync_Message_Appointment::SENSITIVITY_NORMAL);

        /* Busy Status */
        switch ($this->status) {
        case Kronolith::STATUS_CANCELLED:
            $status = Horde_ActiveSync_Message_Appointment::BUSYSTATUS_FREE;
            break;
        case Kronolith::STATUS_CONFIRMED:
            $status = Horde_ActiveSync_Message_Appointment::BUSYSTATUS_BUSY;
            break;
        case Kronolith::STATUS_TENTATIVE:
            $status = Horde_ActiveSync_Message_Appointment::BUSYSTATUS_TENTATIVE;
        case Kronolith::STATUS_FREE:
        case Kronolith::STATUS_NONE:
            $status = Horde_ActiveSync_Message_Appointment::BUSYSTATUS_FREE;
        }
        $message->setBusyStatus($status);

        /* DTStamp */
        $message->setDTStamp($_SERVER['REQUEST_TIME']);

        /* Recurrence */
        if ($this->recurs()) {
            $message->setRecurrence($this->recurrence);

            /* Exceptions are tricky. Exceptions, even those are that represent
             * deleted instances of a recurring event, must be added. To do this
             * we query the storage for all the events that represent exceptions
             * (those with the baseid == $this->uid) and then remove the
             * exceptionoriginaldate from the list of exceptions we know about.
             * Any dates left in this list when we are done, must represent
             * deleted instances of this recurring event.*/
            if (!empty($this->recurrence) && $exceptions = $this->recurrence->getExceptions()) {
                $kronolith_driver = Kronolith::getDriver(null, $this->calendar);
                $search = new StdClass();
                $search->start = $this->recurrence->getRecurStart();
                $search->end = $this->recurrence->getRecurEnd();
                $search->baseid = $this->uid;
                $results = $kronolith_driver->search($search);
                foreach ($results as $days) {
                    foreach ($days as $exception) {
                        $e = new Horde_ActiveSync_Message_Exception();
                        /* Times */
                        $e->setDateTime(
                            array('start' => $exception->start,
                                  'end' => $exception->end,
                                  'allday' => $exception->isAllDay()));
                        /* The start time of the *original* recurring event */
                        $e->setExceptionStartTime($exception->exceptionoriginaldate);
                        $originaldate = $exception->exceptionoriginaldate->format('Ymd');
                        $key = array_search($originaldate, $exceptions);
                        if ($key !== false) {
                            unset($exceptions[$key]);
                        }

                        /* Remaining properties that could be different */
                        $e->setSubject($exception->getTitle());
                        $e->setLocation($exception->location);
                        $e->setBody($exception->description);

                        $e->setSensitivity($exception->private ?
                            Horde_ActiveSync_Message_Appointment::SENSITIVITY_PRIVATE :
                            Horde_ActiveSync_Message_Appointment::SENSITIVITY_NORMAL);

                        $e->setReminder($exception->alarm);
                        $e->setDTStamp($_SERVER['REQUEST_TIME']);
                        /* Response Status */
                        switch ($exception->status) {
                        case Kronolith::STATUS_CANCELLED:
                            $status = 'declined';
                            break;
                        case Kronolith::STATUS_CONFIRMED:
                            $status = 'accepted';
                            break;
                        case Kronolith::STATUS_TENTATIVE:
                            $status = 'tentative';
                        case Kronolith::STATUS_FREE:
                        case Kronolith::STATUS_NONE:
                            $status = 'none';
                        }
                        $e->setResponseType($status);

                        /* Tags/Categories */
                        foreach ($exception->tags as $tag) {
                            $e->addCategory($tag);
                        }
                        $message->addexception($e);

                    }
                }

                /* Any dates left in $exceptions must be deleted exceptions */
                foreach ($exceptions as $deleted) {
                    $e = new Horde_ActiveSync_Message_Exception();
                    $e->setExceptionStartTime(new Horde_Date($deleted));
                    $e->deleted = true;
                    $message->addException($e);
                }
            }
        }

        /* Attendees */
        if (count($this->attendees)) {
            $message->setMeetingStatus(Horde_ActiveSync_Message_Appointment::MEETING_IS_MEETING);
            foreach ($this->attendees as $email => $properties) {
                $attendee = new Horde_ActiveSync_Message_Attendee();
                $attendee->email = $email;
                // AS only as required or opitonal
                //$attendee->type = ($properties['attendance'] !== Kronolith::PART_REQUIRED ? Kronolith::PART_OPTIONAL : Kronolith::PART_REQUIRED);
                //$attendee->status = $properties['response'];
                $message->addAttendee($attendee);
            }
        }

//        /* Resources */
//        $r = $this->getResources();
//        foreach ($r as $id => $data) {
//            $resource = Kronolith::getDriver('Resource')->getResource($id);
//            $attendee = new Horde_ActiveSync_Message_Attendee();
//            $attendee->email = $resource->get('email');
//            $attendee->type = Horde_ActiveSync_Message_Attendee::TYPE_RESOURCE;
//            $attendee->name = $data['name'];
//            $attendee->status = $data['response'];
//            $message->addAttendee($attendee);
//        }

        /* Reminder */
        $message->setReminder($this->alarm);

        /* Categories (tags) */
        foreach ($this->tags as $tag) {
            $message->addCategory($tag);
        }

        return $message;
    }

    /**
     * Imports the values for this event from an array of values.
     *
     * @param array $hash  Array containing all the values.
     *
     * @throws Kronolith_Exception
     */
    public function fromHash($hash)
    {
        // See if it's a new event.
        if ($this->id === null) {
            $this->creator = $GLOBALS['registry']->getAuth();
        }
        if (!empty($hash['title'])) {
            $this->title = $hash['title'];
        } else {
            throw new Kronolith_Exception(_("Events must have a title."));
        }
        if (!empty($hash['description'])) {
            $this->description = $hash['description'];
        }
        if (!empty($hash['location'])) {
            $this->location = $hash['location'];
        }
        if (!empty($hash['private'])) {
            $this->private = true;
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
            throw new Kronolith_Exception(_("Events must have a start date."));
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
            $this->alarm = (int)$hash['alarm'];
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
                $this->alarm = ($this->start->timestamp() - $alarm->timestamp()) / 60;
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
        if (!$this->alarm) {
            return;
        }

        if ($this->recurs()) {
            $eventDate = $this->recurrence->nextRecurrence($time);
            if ($eventDate && $this->recurrence->hasException($eventDate->year, $eventDate->month, $eventDate->mday)) {
                return;
            }
        }

        $serverName = $_SERVER['SERVER_NAME'];
        $serverConf = $GLOBALS['conf']['server']['name'];
        if (!empty($GLOBALS['conf']['reminder']['server_name'])) {
            $_SERVER['SERVER_NAME'] = $GLOBALS['conf']['server']['name'] = $GLOBALS['conf']['reminder']['server_name'];
        }

        if (empty($user)) {
            $user = $GLOBALS['registry']->getAuth();
        }
        if (empty($prefs)) {
            $prefs = $GLOBALS['prefs'];
        }

        $methods = !empty($this->methods) ? $this->methods : @unserialize($prefs->getValue('event_alarms'));
        $start = clone $this->start;
        $start->min -= $this->alarm;
        if (isset($methods['notify'])) {
            $methods['notify']['show'] = array(
                '__app' => $GLOBALS['registry']->getApp(),
                'event' => $this->id,
                'calendar' => $this->calendar);
            $methods['notify']['ajax'] = 'event:' . $this->calendarType . '|' . $this->calendar . ':' . $this->id . ':' . $start->dateString();
            if (!empty($methods['notify']['sound'])) {
                if ($methods['notify']['sound'] == 'on') {
                    // Handle boolean sound preferences.
                    $methods['notify']['sound'] = (string)Horde_Themes::sound('theetone.wav');
                } else {
                    // Else we know we have a sound name that can be
                    // served from Horde.
                    $methods['notify']['sound'] = (string)Horde_Themes::sound($methods['notify']['sound']);
                }
            }
            if ($this->isAllDay()) {
                if ($this->start->compareDate($this->end) == 0) {
                    $methods['notify']['subtitle'] = sprintf(_("On %s"), '<strong>' . $this->start->strftime($prefs->getValue('date_format')) . '</strong>');
                } else {
                    $methods['notify']['subtitle'] = sprintf(_("From %s to %s"), '<strong>' . $this->start->strftime($prefs->getValue('date_format')) . '</strong>', '<strong>' . $this->end->strftime($prefs->getValue('date_format')) . '</strong>');
                }
            } else {
                $methods['notify']['subtitle'] = sprintf(_("From %s at %s to %s at %s"), '<strong>' . $this->start->strftime($prefs->getValue('date_format')), $this->start->format($prefs->getValue('twentyFour') ? 'H:i' : 'h:ia') . '</strong>', '<strong>' . $this->end->strftime($prefs->getValue('date_format')), $this->end->format($prefs->getValue('twentyFour') ? 'H:i' : 'h:ia') . '</strong>');
            }
        }
        if (isset($methods['mail'])) {
            $image = Kronolith::getImagePart('big_alarm.png');

            $view = new Horde_View(array('templatePath' => KRONOLITH_TEMPLATES . '/alarm', 'encoding' => 'UTF-8'));
            new Horde_View_Helper_Text($view);
            $view->event = $this;
            $view->imageId = $image->getContentId();
            $view->user = $user;
            $view->dateFormat = $prefs->getValue('date_format');
            $view->timeFormat = $prefs->getValue('twentyFour') ? 'H:i' : 'h:ia';
            if (!$prefs->isLocked('event_reminder')) {
                $view->prefsUrl = Horde::url(Horde::getServiceLink('prefs', 'kronolith'), true)->remove(session_name());
            }
            if ($this->attendees) {
                $attendees = array();
                foreach ($this->attendees as $mail => $attendee) {
                    $attendees[] = empty($attendee['name']) ? $mail : Horde_Mime_Address::trimAddress($attendee['name'] . (strpos($mail, '@') === false ? '' : ' <' . $mail . '>'));
                }
                $view->attendees = $attendees;
            }

            $methods['mail']['mimepart'] = Kronolith::buildMimeMessage($view, 'mail', $image);
        }

        $alarm = array(
            'id' => $this->uid,
            'user' => $user,
            'start' => $start,
            'end' => $this->end,
            'methods' => array_keys($methods),
            'params' => $methods,
            'title' => $this->getTitle($user),
            'text' => $this->description);

        $_SERVER['SERVER_NAME'] = $serverName;
        $GLOBALS['conf']['server']['name'] = $serverConf;

        return $alarm;
    }

    /**
     * Returns a simple object suitable for json transport representing this
     * event.
     *
     * Possible properties are:
     * - t: title
     * - d: description
     * - c: calendar id
     * - s: start date
     * - e: end date
     * - fi: first day of a multi-day event
     * - la: last day of a multi-day event
     * - x: status (Kronolith::STATUS_* constant)
     * - al: all-day?
     * - bg: background color
     * - fg: foreground color
     * - pe: edit permissions?
     * - pd: delete permissions?
     * - vl: variable, i.e. editable length?
     * - a: alarm text or minutes
     * - r: recurrence type (Horde_Date_Recurrence::RECUR_* constant) or json
     *      representation of Horde_Date_Recurrence object.
     * - bid: The baseid for an event representing an exception
     * - eod: The original date that an exception is replacing
     * - ic: icon
     * - ln: link
     * - aj: ajax link
     * - id: event id
     * - ty: calendar type (driver)
     * - l: location
     * - u: url
     * - sd: formatted start date
     * - st: formatted start time
     * - ed: formatted end date
     * - et: formatted end time
     * - at: attendees
     * - tg: tag list
     *
     * @param boolean $allDay      If not null, overrides whether the event is
     *                             an all-day event.
     * @param boolean $full        Whether to return all event details.
     * @param string $time_format  The date() format to use for time formatting.
     *
     * @return stdClass  A simple object.
     */
    public function toJson($allDay = null, $full = false, $time_format = 'H:i')
    {
        $json = new stdClass;
        $json->t = $this->getTitle();
        $json->c = $this->calendar;
        $json->s = $this->start->toJson();
        $json->e = $this->end->toJson();
        $json->fi = $this->first;
        $json->la = $this->last;
        $json->x = (int)$this->status;
        $json->al = is_null($allDay) ? $this->isAllDay() : $allDay;
        $json->pe = $this->hasPermission(Horde_Perms::EDIT);
        $json->pd = $this->hasPermission(Horde_Perms::DELETE);
        $json->l = $this->location;
        if ($this->icon) {
            $json->ic = $this->icon;
        }
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
        } elseif ($this->baseid) {
            $json->bid = $this->baseid;
            if ($this->exceptionoriginaldate) {
                $json->eod = sprintf(_("%s at %s"), $this->exceptionoriginaldate->strftime($GLOBALS['prefs']->getValue('date_format')), $this->exceptionoriginaldate->strftime(($GLOBALS['prefs']->getValue('twentyFour') ? '%H:%M' : '%I:%M %p')));
            }
        }

        if ($full) {
            $json->id = $this->id;
            $json->ty = $this->calendarType;
            $json->d = $this->description;
            $json->u = $this->url;
            $json->sd = $this->start->strftime('%x');
            $json->st = $this->start->format($time_format);
            $json->ed = $this->end->strftime('%x');
            $json->et = $this->end->format($time_format);
            $json->a = $this->alarm;
            $json->pv = $this->private;
            $json->tg = array_values($this->tags);
            $json->gl = $this->geoLocation;
            if ($this->recurs()) {
                $json->r = $this->recurrence->toJson();
            }
            if ($this->attendees) {
                $attendees = array();
                foreach ($this->attendees as $email => $info) {
                    $attendee = array('a' => $info['attendance'],
                                      'r' => $info['response'],
                                      'l' => empty($info['name']) ? $email : Horde_Mime_Address::trimAddress($info['name'] . (strpos($email, '@') === false ? '' : ' <' . $email . '>')));
                    if (strpos($email, '@') !== false) {
                        $attendee['e'] = $email;
                    }
                    $attendees[] = $attendee;
                }
                $json->at = $attendees;
            }
            if ($this->methods) {
                $json->m = $this->methods;
            }
        }

        return $json;
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
        if (!isset($this->uid) || !isset($this->calendar)) {
            return false;
        }
        try {
            $eventID = $this->getDriver()->exists($this->uid, $this->calendar);
            if (!$eventID) {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
        $this->id = $eventID;
        return true;
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
            !$this->recurrence->hasRecurType(Horde_Date_Recurrence::RECUR_NONE) &&
            empty($this->baseid);
    }

    /**
     * Returns a description of this event's recurring type.
     *
     * @return string  Human readable recurring type.
     */
    public function getRecurName()
    {
        if (empty($this->baseid)) {
            return $this->recurs()
                ? $this->recurrence->getRecurName()
                : _("No recurrence");
        } else {
            return _("Exception");
        }
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
            . Horde::url('edit.php')
            ->add(array('calendar' => $this->calendar,
                        'eventID' => $this->id,
                        'del_exception' => $date,
                        'url' => Horde_Util::getFormData('url')))
            ->link(array('title' => sprintf(_("Delete exception on %s"), $formatted)))
            . Horde::img('delete-small.png', _("Delete"))
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
        $exceptions = $this->recurrence->getExceptions();
        asort($exceptions);
        return implode(', ', array_map(array($this, 'exceptionLink'), $exceptions));
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
        if (!$this->initialized) {
            return '';
        }

        if ($user === null) {
            $user = $GLOBALS['registry']->getAuth();
        }

        // We explicitly allow admin access here for the alarms notifications.
        if (!$GLOBALS['registry']->isAdmin() && $this->private &&
            $this->creator != $user) {
            return _("busy");
        } elseif ($GLOBALS['registry']->isAdmin() || $this->hasPermission(Horde_Perms::READ, $user)) {
            return strlen($this->title) ? $this->title : _("[Unnamed event]");
        } else {
            return _("busy");
        }
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
        return isset($this->attendees[Horde_String::lower($email)]);
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
     * Adds a single resource to this event.
     *
     * No validation or acceptence/denial is done here...it should be done
     * when saving the event.
     *
     * @param Kronolith_Resource $resource  The resource to add.
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
     * Removes a resource from this event.
     *
     * @param Kronolith_Resource $resource  The resource to remove.
     */
    public function removeResource($resource)
    {
        if (isset($this->_resources[$resource->getId()])) {
            unset($this->_resources[$resource->getId()]);
        }
    }

    /**
     * Returns all resources.
     *
     * @return array  A copy of the resources array.
     */
    public function getResources()
    {
        return $this->_resources;
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

    public function readForm()
    {
        global $prefs, $cManager, $session;

        // Event owner.
        $targetcalendar = Horde_Util::getFormData('targetcalendar');
        if (strpos($targetcalendar, '\\')) {
            list(, $this->creator) = explode('\\', $targetcalendar, 2);
        } elseif (!isset($this->id)) {
            $this->creator = $GLOBALS['registry']->getAuth();
        }

        // Basic fields.
        $this->title = Horde_Util::getFormData('title', $this->title);
        $this->description = Horde_Util::getFormData('description', $this->description);
        $this->location = Horde_Util::getFormData('location', $this->location);
        $this->private = (bool)Horde_Util::getFormData('private');

        // URL.
        $url = Horde_Util::getFormData('eventurl', $this->url);
        if (strlen($url)) {
            // Analyze and re-construct.
            $url = @parse_url($url);
            if ($url) {
                if (function_exists('http_build_url')) {
                    if (empty($url['path'])) {
                        $url['path'] = '/';
                    }
                    $url = http_build_url($url);
                } else {
                    $new_url = '';
                    if (isset($url['scheme'])) {
                        $new_url .= $url['scheme'] . '://';
                    }
                    if (isset($url['user'])) {
                        $new_url .= $url['user'];
                        if (isset($url['pass'])) {
                            $new_url .= ':' . $url['pass'];
                        }
                        $new_url .= '@';
                    }
                    if (isset($url['host'])) {
                        // Convert IDN hosts to ASCII.
                        if (function_exists('idn_to_ascii')) {
                            $url['host'] = @idn_to_ascii($url['host']);
                        } elseif (Horde_Mime::is8bit($url['host'])) {
                            //throw new Kronolith_Exception(_("Invalid character in URL."));
                            $url['host'] = '';
                        }
                        $new_url .= $url['host'];
                    }
                    if (isset($url['path'])) {
                        $new_url .= $url['path'];
                    }
                    if (isset($url['query'])) {
                        $new_url .= '?' . $url['query'];
                    }
                    if (isset($url['fragment'])) {
                        $new_url .= '#' . $url['fragment'];
                    }
                    $url = $new_url;
                }
            }
        }
        $this->url = $url;

        // Status.
        $this->status = Horde_Util::getFormData('status', $this->status);

        // Attendees.
        if ($attendees = Horde_Util::getFormData('attendees')) {
            $attendees = Kronolith::parseAttendees(trim($attendees));
        } else {
            $attendees = $session->get('kronolith', 'attendees', Horde_Session::TYPE_ARRAY);
        }
        $this->attendees = $attendees;

        // Resources
        $this->_resources = $session->get('kronolith', 'resources', Horde_Session::TYPE_ARRAY);

        // strptime() is locale dependent, i.e. %p is not always matching
        // AM/PM. Set the locale to C to workaround this, but grab the
        // locale's D_FMT before that.
        $date_format = Horde_Nls::getLangInfo(D_FMT);
        $old_locale = setlocale(LC_TIME, 0);
        setlocale(LC_TIME, 'C');

        // Event start.
        $allDay = Horde_Util::getFormData('whole_day');
        if ($start_date = Horde_Util::getFormData('start_date')) {
            // From ajax interface.
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
                          'hour'  => $allDay ? 0 : $date_arr['tm_hour'],
                          'min'   => $allDay ? 0 : $date_arr['tm_min'],
                          'sec'   => $allDay ? 0 : $date_arr['tm_sec']));
            } else {
                try {
                    $this->start = new Horde_Date($start);
                } catch (Horde_Date_Exception $e) {
                    setlocale(LC_TIME, $old_locale);
                    throw $e;
                }
            }
        } else {
            // From traditional interface.
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
                if ($allDay) {
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

        // Event end.
        if ($end_date = Horde_Util::getFormData('end_date')) {
            // From ajax interface.
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
                          'hour'  => $allDay ? 23 : $date_arr['tm_hour'],
                          'min'   => $allDay ? 59 : $date_arr['tm_min'],
                          'sec'   => $allDay ? 59 : $date_arr['tm_sec']));
            } else {
                try {
                    $this->end = new Horde_Date($end);
                } catch (Horde_Date_Exception $e) {
                    setlocale(LC_TIME, $old_locale);
                    throw $e;
                }
            }
        } elseif (Horde_Util::getFormData('end_or_dur') == 1) {
            // Event duration from traditional interface.
            $this->end = new Horde_Date(array('hour' => $start_hour + $dur_hour,
                                              'min' => $start_min + $dur_min,
                                              'month' => $start_month,
                                              'mday' => $start_day + $dur_day,
                                              'year' => $start_year));
        } else {
            // From traditional interface.
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
                $value = Horde_Util::getFormData('alarm_value');
                $unit = Horde_Util::getFormData('alarm_unit');
                if ($value == 0) {
                    $value = $unit = 1;
                }
                $this->alarm = $value * $unit;
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
                $this->alarm = 0;
                $this->methods = array();
            }
        }

        // Recurrence.
        $recur = Horde_Util::getFormData('recur');
        if ($recur !== null && $recur !== '') {
            if (!isset($this->recurrence)) {
                $this->recurrence = new Horde_Date_Recurrence($this->start);
            } else {
                $this->recurrence->setRecurStart($this->start);
            }
            if (Horde_Util::getFormData('recur_end_type') == 'date') {
                if ($end_date = Horde_Util::getFormData('recur_end_date')) {
                    // Try exact format match first.
                    if ($date_arr = strptime($end_date, $date_format)) {
                        $recur_enddate =
                            array('year'  => $date_arr['tm_year'] + 1900,
                                  'month' => $date_arr['tm_mon'] + 1,
                                  'day'  => $date_arr['tm_mday']);
                    } else {
                        $date_ob = new Horde_Date($end_date);
                        $recur_enddate = array('year'  => $date_ob->year,
                                               'month' => $date_ob->month,
                                               'day'  => $date_ob->mday);
                    }
                } else {
                    $recur_enddate = Horde_Util::getFormData('recur_end');
                }
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
            } elseif (Horde_Util::getFormData('recur_end_type') == 'count') {
                $this->recurrence->setRecurCount(Horde_Util::getFormData('recur_count'));
            } elseif (Horde_Util::getFormData('recur_end_type') == 'none') {
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
                switch (Horde_Util::getFormData('recur_monthly_scheme')) {
                case Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY:
                    $this->recurrence->setRecurType(Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY);
                case Horde_Date_Recurrence::RECUR_MONTHLY_DATE:
                    $this->recurrence->setRecurInterval(Horde_Util::getFormData('recur_monthly') ? 1 : Horde_Util::getFormData('recur_monthly_interval', 1));
                    break;
                default:
                    $this->recurrence->setRecurInterval(Horde_Util::getFormData('recur_day_of_month_interval', 1));
                    break;
                }
                break;

            case Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY:
                $this->recurrence->setRecurInterval(Horde_Util::getFormData('recur_week_of_month_interval', 1));
                break;

            case Horde_Date_Recurrence::RECUR_YEARLY_DATE:
                switch (Horde_Util::getFormData('recur_yearly_scheme')) {
                case Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY:
                case Horde_Date_Recurrence::RECUR_YEARLY_DAY:
                    $this->recurrence->setRecurType(Horde_Util::getFormData('recur_yearly_scheme'));
                case Horde_Date_Recurrence::RECUR_YEARLY_DATE:
                    $this->recurrence->setRecurInterval(Horde_Util::getFormData('recur_yearly') ? 1 : Horde_Util::getFormData('recur_yearly_interval', 1));
                    break;
                default:
                    $this->recurrence->setRecurInterval(Horde_Util::getFormData('recur_yearly_interval', 1));
                    break;
                }
                break;

            case Horde_Date_Recurrence::RECUR_YEARLY_DAY:
                $this->recurrence->setRecurInterval(Horde_Util::getFormData('recur_yearly_day_interval', $yearly_interval));
                break;

            case Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY:
                $this->recurrence->setRecurInterval(Horde_Util::getFormData('recur_yearly_weekday_interval', $yearly_interval));
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

        // Geolocation
        if (Horde_Util::getFormData('lat') && Horde_Util::getFormData('lon')) {
            $this->geoLocation = array('lat' => Horde_Util::getFormData('lat'),
                                       'lon' => Horde_Util::getFormData('lon'),
                                       'zoom' => Horde_Util::getFormData('zoom'));
        }

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
                '" type="text"' .
                ' id="' . $this->_formIDEncode($property) . '" size="4" maxlength="4" />';

        case 'start[month]':
            $sel = $this->start->month;
            for ($i = 1; $i < 13; ++$i) {
                $options[$i] = strftime('%b', mktime(1, 1, 1, $i, 1));
            }
            $label = _("Start Month");
            break;

        case 'start[day]':
            $sel = $this->start->mday;
            for ($i = 1; $i < 32; ++$i) {
                $options[$i] = $i;
            }
            $label = _("Start Day");
            break;

        case 'start_hour':
            $sel = $this->start->format($prefs->getValue('twentyFour') ? 'G' : 'g');
            $hour_min = $prefs->getValue('twentyFour') ? 0 : 1;
            $hour_max = $prefs->getValue('twentyFour') ? 24 : 13;
            for ($i = $hour_min; $i < $hour_max; ++$i) {
                $options[$i] = $i;
            }
            $label = _("Start Hour");
            break;

        case 'start_min':
            $sel = sprintf('%02d', $this->start->min);
            for ($i = 0; $i < 12; ++$i) {
                $min = sprintf('%02d', $i * 5);
                $options[$min] = $min;
            }
            $label = _("Start Minute");
            break;

        case 'end[year]':
            return  '<label for="' . $this->_formIDEncode($property) . '" class="hidden">' . _("End Year") . '</label>' .
                '<input name="' . $property . '" value="' . $this->end->year .
                '" type="text"' .
                ' id="' . $this->_formIDEncode($property) . '" size="4" maxlength="4" />';

        case 'end[month]':
            $sel = $this->end ? $this->end->month : $this->start->month;
            for ($i = 1; $i < 13; ++$i) {
                $options[$i] = strftime('%b', mktime(1, 1, 1, $i, 1));
            }
            $label = _("End Month");
            break;

        case 'end[day]':
            $sel = $this->end ? $this->end->mday : $this->start->mday;
            for ($i = 1; $i < 32; ++$i) {
                $options[$i] = $i;
            }
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
            $label = _("End Hour");
            break;

        case 'end_min':
            $sel = $this->end ? $this->end->min : $this->start->min;
            $sel = sprintf('%02d', $sel);
            for ($i = 0; $i < 12; ++$i) {
                $min = sprintf('%02d', $i * 5);
                $options[$min] = $min;
            }
            $label = _("End Minute");
            break;

        case 'dur_day':
            $dur = $this->getDuration();
            return  '<label for="' . $property . '" class="hidden">' . _("Duration Day") . '</label>' .
                '<input name="' . $property . '" value="' . $dur->day .
                '" type="text"' .
                ' id="' . $property . '" size="4" maxlength="4" />';

        case 'dur_hour':
            $dur = $this->getDuration();
            $sel = $dur->hour;
            for ($i = 0; $i < 24; ++$i) {
                $options[$i] = $i;
            }
            $label = _("Duration Hour");
            break;

        case 'dur_min':
            $dur = $this->getDuration();
            $sel = $dur->min;
            for ($i = 0; $i < 13; ++$i) {
                $min = sprintf('%02d', $i * 5);
                $options[$min] = $min;
            }
            $label = _("Duration Minute");
            break;

        case 'recur_end[year]':
            if ($this->end) {
                $end = ($this->recurs() && $this->recurrence->hasRecurEnd())
                        ? $this->recurrence->recurEnd->year
                        : $this->end->year;
            } else {
                $end = $this->start->year;
            }
            return  '<label for="' . $this->_formIDEncode($property) . '" class="hidden">' . _("Recurrence End Year") . '</label>' .
                '<input name="' . $property . '" value="' . $end .
                '" type="text"' .
                ' id="' . $this->_formIDEncode($property) . '" size="4" maxlength="4" />';

        case 'recur_end[month]':
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
            $label = _("Recurrence End Month");
            break;

        case 'recur_end[day]':
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
            $label = _("Recurrence End Day");
            break;
        }

        if (!$this->_varRenderer) {
            $this->_varRenderer = Horde_Core_Ui_VarRenderer::factory('Html');
        }

        return '<label for="' . $this->_formIDEncode($property) . '" class="hidden">' . $label . '</label>' .
            '<select name="' . $property . '"' . $attributes . ' id="' . $this->_formIDEncode($property) . '">' .
            $this->_varRenderer->selectOptions($options, $sel) .
            '</select>';
    }

    /**
     * @param array $params
     *
     * @return Horde_Url
     */
    public function getViewUrl($params = array(), $full = false, $encoded = true)
    {
        $params['eventID'] = $this->id;
        $params['calendar'] = $this->calendar;
        $params['type'] = $this->calendarType;

        return Horde::url('event.php', $full)->setRaw(!$encoded)->add($params);
    }

    /**
     * @param array $params
     *
     * @return Horde_Url
     */
    public function getEditUrl($params = array())
    {
        $params['view'] = 'EditEvent';
        $params['eventID'] = $this->id;
        $params['calendar'] = $this->calendar;
        $params['type'] = $this->calendarType;

        return Horde::url('event.php')->add($params);
    }

    /**
     * @param array $params
     *
     * @return Horde_Url
     */
    public function getDeleteUrl($params = array())
    {
        $params['view'] = 'DeleteEvent';
        $params['eventID'] = $this->id;
        $params['calendar'] = $this->calendar;
        $params['type'] = $this->calendarType;

        return Horde::url('event.php')->add($params);
    }

    /**
     * @param array $params
     *
     * @return Horde_Url
     */
    public function getExportUrl($params = array())
    {
        $params['view'] = 'ExportEvent';
        $params['eventID'] = $this->id;
        $params['calendar'] = $this->calendar;
        $params['type'] = $this->calendarType;

        return Horde::url('event.php')->add($params);
    }

    public function getLink($datetime = null, $icons = true, $from_url = null,
                            $full = false, $encoded = true)
    {
        global $prefs, $registry;

        if (is_null($datetime)) {
            $datetime = $this->start;
        }
        if (is_null($from_url)) {
            $from_url = Horde::selfUrl(true, false, true);
        }

        $event_title = $this->getTitle();
        $view_url = $this->getViewUrl(array('datetime' => $datetime->strftime('%Y%m%d%H%M%S'), 'url' => $from_url), $full, $encoded);
        $read_permission = $this->hasPermission(Horde_Perms::READ);

        $link = '<span' . $this->getCSSColors() . '>';
        if ($read_permission && $view_url) {
            $link .= Horde::linkTooltip($view_url,
                                       $event_title,
                                       $this->getStatusClass(),
                                       '',
                                       '',
                                       $this->getTooltip(),
                                       '',
                                       array('style' => $this->getCSSColors(false)));
        }
        $link .= htmlspecialchars($event_title);
        if ($read_permission && $view_url) {
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
            } elseif ($this->baseid) {
                $title = _("Exception");
                $status .= Horde::fullSrcImg('exception-' . $icon_color . '.png', array('attr' => array('alt' => $title, 'title' => $title, 'class' => 'iconRecur')));
            }

            if ($this->private) {
                $title = _("Private event");
                $status .= Horde::fullSrcImg('private-' . $icon_color . '.png', array('attr' => array('alt' => $title, 'title' => $title, 'class' => 'iconPrivate')));
            }

            if (!empty($this->attendees)) {
                $status .= Horde::fullSrcImg('attendees-' . $icon_color . '.png', array('attr' => array('alt' => _("Meeting"), 'title' => _("Meeting"), 'class' => 'iconPeople')));
            }

            if (!empty($this->icon)) {
                $link = $status . '<img src="' . $this->icon . '" /> ' . $link;
            } elseif (!empty($status)) {
                $link .= ' ' . $status;
            }

            if (!$this->private ||
                $this->creator == $GLOBALS['registry']->getAuth()) {
                $url = $this->getEditUrl(
                    array('datetime' => $datetime->strftime('%Y%m%d%H%M%S'),
                          'url' => $from_url));
                if ($url) {
                    $link .= $url->link(array('title' => sprintf(_("Edit %s"), $event_title),
                                              'class' => 'iconEdit'))
                        . Horde::fullSrcImg('edit-' . $icon_color . '.png',
                                            array('attr' => array('alt' => _("Edit"))))
                        . '</a>';
                }
            }
            if ($this->hasPermission(Horde_Perms::DELETE)) {
                $url = $this->getDeleteUrl(
                    array('datetime' => $datetime->strftime('%Y%m%d%H%M%S'),
                          'url' => $from_url));
                if ($url) {
                    $link .= $url->link(array('title' => sprintf(_("Delete %s"), $event_title),
                                              'class' => 'iconDelete'))
                        . Horde::fullSrcImg('delete-' . $icon_color . '.png',
                                            array('attr' => array('alt' => _("Delete"))))
                        . '</a>';
                }
            }
        }

        return $link . '</span>';
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
            . "\n" . sprintf(_("Owner: %s"), ($this->creator == $GLOBALS['registry']->getAuth() ?
                                              _("Me") : Kronolith::getUserName($this->creator)));

        if (!$this->private || $this->creator == $GLOBALS['registry']->getAuth()) {
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
            return 'kronolithEventCancelled';

        case Kronolith::STATUS_TENTATIVE:
        case Kronolith::STATUS_FREE:
            return 'kronolithEventTentative';
        }

        return 'kronolithEvent';
    }

    private function _formIDEncode($id)
    {
        return str_replace(array('[', ']'),
                           array('_', ''),
                           $id);
    }

}
