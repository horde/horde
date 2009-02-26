<?php

require_once 'Horde/Kolab.php';
require_once 'Horde/Identity.php';

/**
 * Horde Kronolith driver for the Kolab IMAP Server.
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @author  Stuart Binge <omicron@mighty.co.za>
 * @package Kronolith
 */
class Kronolith_Driver_kolab extends Kronolith_Driver
{
    /**
     * Our Kolab server connection.
     *
     * @var Kolab
     */
    private $_kolab = null;

    /**
     * Internal cache of Kronolith_Event_kolab. eventID/UID is key
     *
     * @var array
     */
    private $_events_cache;

    /**
     * Indicates if we have synchronized this folder
     *
     * @var boolean
     */
    private $_synchronized;

    /**
     * Shortcut to the imap connection
     *
     * @var Kolab_IMAP
     */
    private $_store;

    /**
     * Attempts to open a Kolab Groupware folder.
     *
     * @return boolean  True on success, PEAR_Error on failure.
     */
    public function initialize()
    {
        $this->_kolab = new Kolab();
        $this->reset();
        return true;
    }

    /**
     * Change current calendar
     */
    public function open($calendar)
    {
        if ($this->_calendar != $calendar) {
            $this->_calendar = $calendar;
            $this->reset();
        }

        return true;
    }

    /**
     * Reset internal variable on share change
     */
    public function reset()
    {
        $this->_events_cache = array();
        $this->_synchronized = false;
    }

    // We delay initial synchronization to the first use
    // so multiple calendars don't add to the total latency.
    // This function must be called before all internal driver functions
    public function synchronize($force = false)
    {
        if ($this->_synchronized && !$force) {
            return;
        }

        // Connect to the Kolab backend
        $result = $this->_kolab->open($this->_calendar, 1);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }
        $this->_store = $this->_kolab->_storage;

        // build internal event cache
        $this->_events_cache = array();
        $events = $this->_store->getObjects();
        foreach ($events as $event) {
            $this->_events_cache[$event['uid']] = new Kronolith_Event_kolab($this, $event);
        }

        $this->_synchronized = true;
    }

    public function listAlarms($date, $fullevent = false)
    {
        $allevents = $this->listEvents($date, null, true);
        $events = array();

        foreach ($allevents as $eventId) {
            $event = $this->getEvent($eventId);
            if (is_a($event, 'PEAR_Error')) {
                return $event;
            }

            if (!$event->recurs()) {
                $start = new Horde_Date($event->start);
                $start->min -= $event->getAlarm();
                if ($start->compareDateTime($date) <= 0 &&
                    $date->compareDateTime($event->end) <= -1) {
                    $events[] = $fullevent ? $event : $eventId;
                }
            } else {
                if ($next = $event->recurrence->nextRecurrence($date)) {
                    if ($event->recurrence->hasException($next->year, $next->month, $next->mday)) {
                        continue;
                    }
                    $start = new Horde_Date($next);
                    $start->min -= $event->getAlarm();
                    $end = new Horde_Date(array('year' => $next->year,
                                                'month' => $next->month,
                                                'mday' => $next->mday,
                                                'hour' => $event->end->hour,
                                                'min' => $event->end->min,
                                                'sec' => $event->end->sec));
                    if ($start->compareDateTime($date) <= 0 &&
                        $date->compareDateTime($end) <= -1) {
                        if ($fullevent) {
                            $event->start = $start;
                            $event->end = $end;
                            $events[] = $event;
                        } else {
                            $events[] = $eventId;
                        }
                    }
                }
            }
        }

        return is_array($events) ? $events : array();
    }

    /**
     * Checks if the event's UID already exists and returns all event
     * ids with that UID.
     *
     * @param string $uid          The event's uid.
     * @param string $calendar_id  Calendar to search in.
     *
     * @return string|boolean  Returns a string with event_id or false if
     *                         not found.
     */
    public function exists($uid, $calendar_id = null)
    {
        // Log error if someone uses this function in an unsupported way
        if ($calendar_id != $this->_calendar) {
            Horde::logMessage(sprintf("Kolab::exists called for calendar %s. Currently active is %s.", $calendar_id, $this->_calendar), __FILE__, __LINE__, PEAR_LOG_ERR);
            return PEAR::raiseError(sprintf("Kolab::exists called for calendar %s. Currently active is %s.", $calendar_id, $this->_calendar));
        }

        $result = $this->synchronize();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        if ($this->_store->objectUidExists($uid)) {
            return $uid;
        }

        return false;
    }

    /**
     * Lists all events in the time range, optionally restricting
     * results to only events with alarms.
     *
     * @param Horde_Date $startInterval  Start of range date object.
     * @param Horde_Date $endInterval    End of range data object.
     * @param boolean $hasAlarm          Only return events with alarms?
     *                                   Defaults to all events.
     *
     * @return array  Events in the given time range.
     */
    public function listEvents($startDate = null, $endDate = null, $hasAlarm = false)
    {
        $result = $this->synchronize();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        if (is_null($startDate)) {
            $startDate = new Horde_Date(array('mday' => 1, 'month' => 1, 'year' => 0000));
        }
        if (is_null($endDate)) {
            $endDate = new Horde_Date(array('mday' => 31, 'month' => 12, 'year' => 9999));
        }

        $ids = array();

        foreach($this->_events_cache as $event) {
            if ($hasAlarm && !$event->getAlarm()) {
                continue;
            }

            $keep_event = false;
            /* check if event period intersects with given period */
            if (!(($endDate->compareDateTime($event->start) < 0) ||
                  ($startDate->compareDateTime($event->end) > 0))) {
                $keep_event = true;
            }

            /* do recurrence expansion if not keeping anyway */
            if (!$keep_event && $event->recurs()) {
                $next = $event->recurrence->nextRecurrence($startDate);
                while ($next !== false &&
                       $event->recurrence->hasException($next->year, $next->month, $next->mday)) {
                    $next->mday++;
                    $next = $event->recurrence->nextRecurrence($next);
                }

                if ($next !== false) {
                    $duration = $next->timestamp() - $event->start->timestamp();
                    $next_end = new Horde_Date($event->end->timestamp() + $duration);

                    if ((!(($endDate->compareDateTime($next) < 0) ||
                           ($startDate->compareDateTime($next_end) > 0)))) {
                        $keep_event = true;
                    }
                }
            }

            if ($keep_event) {
                $ids[$event->getUID()] = $event->getUID();
            }
        }

        return $ids;
    }

    public function getEvent($eventId = null)
    {
        if (is_null($eventId)) {
            return new Kronolith_Event_kolab($this);
        }

        $result = $this->synchronize();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        if (array_key_exists($eventId, $this->_events_cache)) {
            return $this->_events_cache[$eventId];
        }

        return PEAR::raiseError(sprintf(_("Event not found: %s"), $eventId));
    }

    /**
     * Get an event or events with the given UID value.
     *
     * @param string $uid The UID to match
     * @param array $calendars A restricted array of calendar ids to search
     * @param boolean $getAll Return all matching events? If this is false,
     * an error will be returned if more than one event is found.
     *
     * @return Kronolith_Event
     */
    public function getByUID($uid, $calendars = null, $getAll = false)
    {
        if (!is_array($calendars)) {
            $calendars = array_keys(Kronolith::listCalendars(true, PERMS_READ));
        }

        foreach ($calendars as $calendar) {
            $this->open($calendar);
            $this->synchronize();

            if (!array_key_exists($uid, $this->_events_cache)) {
                continue;
            }

            // Ok, found event
            $event = $this->_events_cache[$uid];

            if ($getAll) {
                $events = array();
                $events[] = $event;
                return $events;
            } else {
                return $event;
            }
        }

        return PEAR::raiseError(sprintf(_("Event not found: %s"), $uid));
    }

    /**
     * Saves an event in the backend.
     * If it is a new event, it is added, otherwise the event is updated.
     *
     * @param Kronolith_Event $event  The event to save.
     *
     * @return mixed  UID on success, a PEAR error otherwise
     */
    public function saveEvent(&$event)
    {
        $result = $this->synchronize();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $uid = $event->getUID();
        if ($uid == null) {
            $event->setUID($this->_store->generateUID());
        }

        $attributes = $event->toDriver();

        $edit = false;
        $stored_uid = null;
        if ($event->isStored() || $event->exists()) {
            $stored_uid = $attributes['uid'];
            $action = array('action' => 'modify');
            $edit = true;
        } else {
            $action = array('action' => 'add');
        }

        $result = $this->_store->save($attributes, $stored_uid);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        /* Deal with tags */
        $tagger = Kronolith::getTagger();
        if (!empty($edit)) {
            $tagger->replaceTags($event->getUID(), $event->tags, 'event');
        } else {
            $tagger->tag($event->getUID(), $event->tags, 'event');
        }

        /* Notify about the changed event. */
        $result = Kronolith::sendNotification($event, $edit ? 'edit' : 'add');
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
        }

        /* Log the creation/modification of this item in the history log. */
        $history = Horde_History::singleton();
        $history->log('kronolith:' . $event->getCalendar() . ':' . $event->getUID(), $action, true);

        // refresh IMAP cache
        $this->synchronize(true);

        if (is_callable('Kolab', 'triggerFreeBusyUpdate')) {
            Kolab::triggerFreeBusyUpdate($this->_store->parseFolder($event->getCalendar()));
        }

        return $event->getUID();
    }

    /**
     * Move an event to a new calendar.
     *
     * @param string $eventId      The event to move.
     * @param string $newCalendar  The new calendar.
     */
    public function move($eventId, $newCalendar)
    {
        $event = $this->getEvent($eventId);

        $result = $this->synchronize();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        global $kronolith_shares;
        $target = $kronolith_shares->getShare($newCalendar);
        $folder = $target->get('folder');

        $result = $this->_store->move($eventId, $folder);
        if ($result) {
            unset($this->_events_cache[$eventId]);
        }

        if (is_callable('Kolab', 'triggerFreeBusyUpdate')) {
            Kolab::triggerFreeBusyUpdate($this->_store->parseFolder($this->_calendar));
            Kolab::triggerFreeBusyUpdate($this->_store->parseFolder($newCalendar));
        }

        /* Log the moving of this item in the history log. */
        $uid = $event->getUID();
        $history = Horde_History::singleton();
        $history->log('kronolith:' . $event->getCalendar() . ':' . $uid, array('action' => 'delete'), true);
        $history->log('kronolith:' . $newCalendar . ':' . $uid, array('action' => 'add'), true);

        return $result;
    }

    /**
     * Delete a calendar and all its events.
     *
     * @param string $calendar  The name of the calendar to delete.
     *
     * @return mixed  True or a PEAR_Error on failure.
     */
    public function delete($calendar)
    {
        $this->open($calendar);
        $result = $this->synchronize();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $result = $this->_store->deleteAll($calendar);
        if (is_callable('Kolab', 'triggerFreeBusyUpdate')) {
            Kolab::triggerFreeBusyUpdate($this->_store->parseFolder($calendar));
        }
        return true;
    }

    /**
     * Rename a calendar.
     *
     * @param string $from  The current name of the calendar.
     * @param string $to    The new name of the calendar.
     *
     * @return mixed  True or a PEAR_Error on failure.
     */
    public function rename($from, $to)
    {
        // FIXME: We can't do much here. We'd need to be called after
        // renaming the share here. This needs to be checked again.
        // kolab/issue2249 ([horde/kronolith] Kronolith is unable to
        // trigger a free/busy update on a folder rename)
        return true;
    }

    /**
     * Delete an event.
     *
     * @param string $eventId  The ID of the event to delete.
     *
     * @return mixed  True or a PEAR_Error on failure.
     */
    public function deleteEvent($eventId, $silent = false)
    {
        $result = $this->synchronize();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        if (!$this->_store->objectUidExists($eventId)) {
            return PEAR::raiseError(sprintf(_("Event not found: %s"), $eventId));
        }

        $event = $this->getEvent($eventId);

        if ($this->_store->delete($eventId)) {
            // Notify about the deleted event.
            if (!$silent) {
                $result = Kronolith::sendNotification($event, 'delete');
                if (is_a($result, 'PEAR_Error')) {
                    Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                }
            }

            /* Log the deletion of this item in the history log. */
            $history = Horde_History::singleton();
            $history->log('kronolith:' . $event->getCalendar() . ':' . $event->getUID(), array('action' => 'delete'), true);

            if (is_callable('Kolab', 'triggerFreeBusyUpdate')) {
                Kolab::triggerFreeBusyUpdate($this->_store->parseFolder($event->getCalendar()));
            }

            unset($this->_events_cache[$eventId]);
        } else {
            return PEAR::raiseError(sprintf(_("Cannot delete event: %s"), $eventId));
        }

        return true;
    }

}

/**
 * @package Kronolith
 */
class Kronolith_Event_kolab extends Kronolith_Event
{

    public function fromDriver($event)
    {
        $this->eventID = $event['uid'];
        $this->setUID($this->eventID);

        if (isset($event['summary'])) {
            $this->title = $event['summary'];
        }
        if (isset($event['body'])) {
            $this->description = $event['body'];
        }
        if (isset($event['location'])) {
            $this->location = $event['location'];
        }

        if (isset($event['sensitivity']) &&
            ($event['sensitivity'] == 'private' || $event['sensitivity'] == 'confidential')) {
            $this->setPrivate(true);
        }

        if (isset($event['organizer']['smtp-address'])) {
            if (Kronolith::isUserEmail(Auth::getAuth(), $event['organizer']['smtp-address'])) {
                $this->creatorID = Auth::getAuth();
            } else {
                $this->creatorID = $event['organizer']['smtp-address'];
            }
        }

        if (isset($event['alarm'])) {
            $this->alarm = $event['alarm'];
        }

        $this->start = new Horde_Date($event['start-date']);
        $this->end = new Horde_Date($event['end-date']);
        $this->durMin = ($this->end->timestamp() - $this->start->timestamp()) / 60;

        if (isset($event['show-time-as'])) {
            switch ($event['show-time-as']) {
                case 'free':
                    $this->status = Kronolith::STATUS_FREE;
                    break;

                case 'tentative':
                    $this->status = Kronolith::STATUS_TENTATIVE;
                    break;

                case 'busy':
                case 'outofoffice':
                default:
                    $this->status = Kronolith::STATUS_CONFIRMED;
            }
        } else {
            $this->status = Kronolith::STATUS_CONFIRMED;
        }

        // Recurrence
        if (isset($event['recurrence'])) {
            $this->recurrence = new Horde_Date_Recurrence($this->start);
            $this->recurrence->fromHash($event['recurrence']);
        }

        // Attendees
        $attendee_count = 0;
        foreach($event['attendee'] as $attendee) {
            $name = $attendee['display-name'];
            $email = $attendee['smtp-address'];

            $role = $attendee['role'];
            switch ($role) {
            case 'optional':
                $role = Kronolith::PART_OPTIONAL;
                break;

            case 'resource':
                $role = Kronolith::PART_NONE;
                break;

            case 'required':
            default:
                $role = Kronolith::PART_REQUIRED;
                break;
            }

            $status = $attendee['status'];
            switch ($status) {
            case 'accepted':
                $status = Kronolith::RESPONSE_ACCEPTED;
                break;

            case 'declined':
                $status = Kronolith::RESPONSE_DECLINED;
                break;

            case 'tentative':
                $status = Kronolith::RESPONSE_TENTATIVE;
                break;

            case 'none':
            default:
                $status = Kronolith::RESPONSE_NONE;
                break;
            }

            // Attendees without an email address get added as incremented number
            if (empty($email)) {
                $email = $attendee_count;
                $attendee_count++;
            }

            $this->addAttendee($email, $role, $status, $name);
        }

        $this->initialized = true;
        $this->stored = true;
    }

    public function toDriver()
    {
        $event = array();
        $event['uid'] = $this->getUID();
        $event['summary'] = $this->title;
        $event['body']  = $this->description;
        $event['location'] = $this->location;

        if ($this->isPrivate()) {
            $event['sensitivity'] = 'private';
        } else {
            $event['sensitivity'] = 'public';
        }

        // Only set organizer if this is a new event
        if ($this->getID() == null) {
            $organizer = array(
                            'display-name' => Kronolith::getUserName($this->getCreatorId()),
                            'smtp-address' => Kronolith::getUserEmail($this->getCreatorId())
                         );
            $event['organizer'] = $organizer;
        }

        if ($this->alarm != 0) {
            $event['alarm'] = $this->alarm;
        }

        $event['start-date'] = $this->start->timestamp();
        $event['end-date'] = $this->end->timestamp();
        $event['_is_all_day'] = $this->isAllDay();

        switch ($this->status) {
        case Kronolith::STATUS_FREE:
        case Kronolith::STATUS_CANCELLED:
            $event['show-time-as'] = 'free';
            break;

        case Kronolith::STATUS_TENTATIVE:
            $event['show-time-as'] = 'tentative';
            break;

        // No mapping for outofoffice
        case Kronolith::STATUS_CONFIRMED:
        default:
            $event['show-time-as'] = 'busy';
        }

        // Recurrence
        if ($this->recurs()) {
            $event['recurrence'] = $this->recurrence->toHash();
        } else {
            $event['recurrence'] = array();
        }


        // Attendees
        $event['attendee'] = array();
        $attendees = $this->getAttendees();

        foreach($attendees as $email => $attendee) {
            $new_attendee = array();
            $new_attendee['display-name'] = $attendee['name'];

            // Attendee without an email address
            if (is_int($email)) {
                $new_attendee['smtp-address'] = '';
            } else {
                $new_attendee['smtp-address'] = $email;
            }

            switch ($attendee['attendance']) {
            case Kronolith::PART_OPTIONAL:
                $new_attendee['role'] = 'optional';
                break;

            case Kronolith::PART_NONE:
                $new_attendee['role'] = 'resource';
                break;

            case Kronolith::PART_REQUIRED:
            default:
                $new_attendee['role'] = 'required';
                break;
            }

            $new_attendee['request-response'] = '0';

            switch ($attendee['response']) {
            case Kronolith::RESPONSE_ACCEPTED:
                $new_attendee['status'] = 'accepted';
                break;

            case Kronolith::RESPONSE_DECLINED:
                $new_attendee['status'] = 'declined';
                break;

            case Kronolith::RESPONSE_TENTATIVE:
                $new_attendee['status'] = 'tentative';
                break;

            case Kronolith::RESPONSE_NONE:
            default:
                $new_attendee['status'] = 'none';
                break;
            }

            $event['attendee'][] = $new_attendee;
        }

        return $event;
    }

}
