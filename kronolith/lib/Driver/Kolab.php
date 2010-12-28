<?php
/**
 * Horde Kronolith driver for the Kolab IMAP Server.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
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
     * The session handler.
     *
     * @var Horde_Kolab_Session
     */
    private $_session;

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
     */
    public function initialize()
    {
        $this->_kolab = $GLOBALS['injector']->getInstance('Horde_Kolab_Storage');
        $this->reset();
    }

    /**
     * Selects a calendar as the currently opened calendar.
     *
     * @param string $calendar  A calendar identifier.
     */
    public function open($calendar)
    {
        if ($this->calendar == $calendar) {
            return;
        }
        $this->calendar = $calendar;
        $this->reset();
    }

    /**
     * Returns the background color of the current calendar.
     *
     * @return string  The calendar color.
     */
    public function backgroundColor()
    {
        if (isset($GLOBALS['all_calendars'][$this->calendar])) {
            return $GLOBALS['all_calendars'][$this->calendar]->background();
        }
        return '#dddddd';
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
        $this->_store = $this->_kolab->getShareData($this->calendar, 'event');

        // build internal event cache
        $this->_events_cache = array();
        $events = $this->_store->getObjects();
        foreach ($events as $event) {
            $this->_events_cache[$event['uid']] = new Kronolith_Event_Kolab($this, $event);
        }

        $this->_synchronized = true;
    }

    /**
     * @throws Kronolith_Exception
     */
    public function listAlarms($date, $fullevent = false)
    {
        $allevents = $this->listEvents($date, null, false, true);
        $events = array();

        foreach ($allevents as $eventId => $data) {
            $event = $this->getEvent($eventId);
            if (!$event->recurs()) {
                $start = new Horde_Date($event->start);
                $start->min -= $event->alarm;
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
                    $start->min -= $event->alarm;
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
     * @throws Kronolith_Exception
     */
    public function exists($uid, $calendar_id = null)
    {
        // Log error if someone uses this function in an unsupported way
        if ($calendar_id != $this->calendar) {
            Horde::logMessage(sprintf("Kolab::exists called for calendar %s. Currently active is %s.", $calendar_id, $this->calendar), 'ERR');
            throw new Kronolith_Exception(sprintf("Kolab::exists called for calendar %s. Currently active is %s.", $calendar_id, $this->calendar));
        }

        $result = $this->synchronize();

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
     * @param boolean $coverDates        Whether to add the events to all days
     *                                   that they cover.
     *
     * @return array  Events in the given time range.
     */
    public function listEvents($startDate = null, $endDate = null,
                               $showRecurrence = false, $hasAlarm = false,
                               $json = false, $coverDates = true,
                               $fetchTags = false)
    {
        $result = $this->synchronize();

        if (empty($startDate)) {
            $startDate = new Horde_Date(array('mday' => 1,
                                              'month' => 1,
                                              'year' => 0000));
        }
        if (empty($endDate)) {
            $endDate = new Horde_Date(array('mday' => 31,
                                            'month' => 12,
                                            'year' => 9999));
        }
        if (!is_a($startDate, 'Horde_Date')) {
            $startDate = new Horde_Date($startDate);
        }
        if (!is_a($endDate, 'Horde_Date')) {
            $endDate = new Horde_Date($endDate);
        }

        $startDate = clone $startDate;
        $startDate->hour = $startDate->min = $startDate->sec = 0;
        $endDate = clone $endDate;
        $endDate->hour = 23;
        $endDate->min = $endDate->sec = 59;

        $events = array();
        foreach($this->_events_cache as $event) {
            if ($hasAlarm && !$event->alarm) {
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
                                 $showRecurrence, $json, $coverDates);
        }

        return $events;
    }

    /**
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getEvent($eventId = null)
    {
        if (!strlen($eventId)) {
            return new Kronolith_Event_Kolab($this);
        }

        $result = $this->synchronize();

        if (array_key_exists($eventId, $this->_events_cache)) {
            return $this->_events_cache[$eventId];
        }

        throw new Horde_Exception_NotFound(sprintf(_("Event not found: %s"), $eventId));
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
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getByUID($uid, $calendars = null, $getAll = false)
    {
        if (!is_array($calendars)) {
            $calendars = array_keys(Kronolith::listInternalCalendars(true, Horde_Perms::READ));
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

        throw new Horde_Exception_NotFound(sprintf(_("Event not found: %s"), $uid));
    }

    /**
     * Updates an existing event in the backend.
     *
     * @param Kronolith_Event $event  The event to save.
     *
     * @return string  The event id.
     * @throws Horde_Mime_Exception
     */
    protected function _updateEvent(Kronolith_Event $event)
    {
        return $this->_saveEvent($event, true);
    }

    /**
     * Adds an event to the backend.
     *
     * @param Kronolith_Event $event  The event to save.
     *
     * @return string  The event id.
     * @throws Horde_Mime_Exception
     */
    protected function _addEvent(Kronolith_Event $event)
    {
        return $this->_saveEvent($event, false);
    }

    /**
     * Saves an event in the backend.
     *
     * @param Kronolith_Event $event  The event to save.
     *
     * @return string  The event id.
     * @throws Horde_Mime_Exception
     */
    protected function _saveEvent($event, $edit)
    {
        $this->synchronize();

        $action = $edit
            ? array('action' => 'modify')
            : array('action' => 'add');

        if (!$event->uid) {
            $event->uid = $this->_store->generateUID();
        }
        $this->_store->save($event->toKolab(), $edit ? $event->uid : null);

        /* Deal with tags */
        if ($edit) {
            Kronolith::getTagger()->replaceTags($event->uid, $event->tags, $event->creator, 'event');
        } else {
            Kronolith::getTagger()->tag($event->uid, $event->tags, $event->creator, 'event');
        }

        $cal = $GLOBALS['kronolith_shares']->getShare($event->calendar);
        $tagger->tag($event->uid, $event->tags, $cal->get('owner'), 'event');

        /* Notify about the changed event. */
        Kronolith::sendNotification($event, $edit ? 'edit' : 'add');

        /* Log the creation/modification of this item in the history log. */
        try {
            $GLOBALS['injector']->getInstance('Horde_History')->log('kronolith:' . $event->calendar . ':' . $event->uid, $action, true);
        } catch (Exception $e) {
            Horde::logMessage($e, 'ERR');
        }

        // refresh IMAP cache
        $this->synchronize(true);

        if (is_callable('Kolab', 'triggerFreeBusyUpdate')) {
            //Kolab::triggerFreeBusyUpdate($this->_store->parseFolder($event->calendar));
        }

        return $event->uid;
    }

    /**
     * Moves an event to a new calendar.
     *
     * @param string $eventId      The event to move.
     * @param string $newCalendar  The new calendar.
     *
     * @return Kronolith_Event  The old event.
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     */
    protected function _move($eventId, $newCalendar)
    {
        $event = $this->getEvent($eventId);
        $result = $this->synchronize();

        global $kronolith_shares;
        $target = $kronolith_shares->getShare($newCalendar);
        $folder = $target->getId();

        $result = $this->_store->move($eventId, $folder);
        if ($result) {
            unset($this->_events_cache[$eventId]);
        }

        if (is_callable('Kolab', 'triggerFreeBusyUpdate')) {
            //Kolab::triggerFreeBusyUpdate($this->_store->parseFolder($this->calendar));
            //Kolab::triggerFreeBusyUpdate($this->_store->parseFolder($newCalendar));
        }

        return $event;
    }

    /**
     * Delete a calendar and all its events.
     *
     * @param string $calendar  The name of the calendar to delete.
     *
     * @throws Kronolith_Exception
     */
    public function delete($calendar)
    {
        $this->open($calendar);
        $result = $this->synchronize();

        $result = $this->_store->deleteAll($calendar);
        if (is_callable('Kolab', 'triggerFreeBusyUpdate')) {
            //Kolab::triggerFreeBusyUpdate($this->_store->parseFolder($calendar));
        }
    }

    /**
     * Delete an event.
     *
     * @param string $eventId  The ID of the event to delete.
     *
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     * @throws Horde_Mime_Exception
     */
    public function deleteEvent($eventId, $silent = false)
    {
        $result = $this->synchronize();

        if (!$this->_store->objectUidExists($eventId)) {
            throw new Kronolith_Exception(sprintf(_("Event not found: %s"), $eventId));
        }

        $event = $this->getEvent($eventId);
        if ($this->_store->delete($eventId)) {
            // Notify about the deleted event.
            if (!$silent) {
                Kronolith::sendNotification($event, 'delete');
            }

            /* Log the deletion of this item in the history log. */
            try {
                $GLOBALS['injector']->getInstance('Horde_History')->log('kronolith:' . $event->calendar . ':' . $event->uid, array('action' => 'delete'), true);
            } catch (Exception $e) {
                Horde::logMessage($e, 'ERR');
            }

            if (is_callable('Kolab', 'triggerFreeBusyUpdate')) {
                //Kolab::triggerFreeBusyUpdate($this->_store->parseFolder($event->calendar));
            }

            unset($this->_events_cache[$eventId]);
        } else {
            throw new Kronolith_Exception(sprintf(_("Cannot delete event: %s"), $eventId));
        }
    }

}
