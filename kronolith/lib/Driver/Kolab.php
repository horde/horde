<?php

require_once 'Horde/Kolab.php';

/**
 * Horde Kronolith driver for the Kolab IMAP Server.
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @author  Stuart Binge <omicron@mighty.co.za>
 * @package Kronolith
 */
class Kronolith_Driver_Kolab extends Kronolith_Driver
{
    /**
     * Our Kolab server connection.
     *
     * @var Kolab
     */
    private $_kolab = null;

    /**
     * Internal cache of Kronolith_Event_Kolab. eventID/UID is key
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
            $this->_events_cache[$event['uid']] = new Kronolith_Event_Kolab($this, $event);
        }

        $this->_synchronized = true;
    }

    public function listAlarms($date, $fullevent = false)
    {
        $allevents = $this->listEvents($date, null, false, true);
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
     * Lists all events in the time range, optionally restricting results to
     * only events with alarms.
     *
     * @param Horde_Date $startInterval  Start of range date object.
     * @param Horde_Date $endInterval    End of range data object.
     * @param boolean $showRecurrence    Return every instance of a recurring
     *                                   event? If false, will only return
     *                                   recurring events once inside the
     *                                   $startDate - $endDate range.
     * @param boolean $hasAlarm          Only return events with alarms?
     * @param boolean $json              Store the results of the events'
     *                                   toJson() method?
     *
     * @return array  Events in the given time range.
     */
    public function listEvents($startDate = null, $endDate = null,
                               $showRecurrence = false, $hasAlarm = false,
                               $json = false)
    {
        $result = $this->synchronize();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        if (is_null($startDate)) {
            $startDate = new Horde_Date(array('mday' => 1,
                                              'month' => 1,
                                              'year' => 0000));
        }
        if (is_null($endDate)) {
            $endDate = new Horde_Date(array('mday' => 31,
                                            'month' => 12,
                                            'year' => 9999));
        }

        $startDate = clone $startDate;
        $startDate->hour = $startDate->min = $startDate->sec = 0;
        $endDate = clone $endDate;
        $endDate->hour = 23;
        $endDate->min = $endDate->sec = 59;

        $events = array();
        foreach($this->_events_cache as $event) {
            if ($hasAlarm && !$event->getAlarm()) {
                continue;
            }

            /* Ignore events out of the period. */
            if (
                /* Starts after the period. */
                $event->start->compareDateTime($endDate) > 0 ||
                /* End before the period and doesn't recur. */
                (!$event->recurs() &&
                 $event->end->compareDateTime($startDate) < 0) ||
                /* Recurs and ... */
                ($event->recurs() &&
                  /* ... has a recurrence end before the period. */
                  ($event->recurrence->hasRecurEnd() &&
                   $event->recurrence->recurEnd->compareDateTime($startDate) < 0))) {
                continue;
            }

            Kronolith::addEvents($events, $event, $startDate, $endDate,
                                 $showRecurrence, $json);
        }

        return $events;
    }

    public function getEvent($eventId = null)
    {
        if (!strlen($eventId)) {
            return new Kronolith_Event_Kolab($this);
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
            $calendars = array_keys(Kronolith::listCalendars(true, Horde_Perms::READ));
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
