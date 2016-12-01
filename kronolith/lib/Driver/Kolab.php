<?php
/**
 * Horde Kronolith driver for the Kolab IMAP Server.
 *
 * Copyright 2004-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
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
     * Internal cache of Kronolith_Event_Kolab. eventID is key
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
     * The current calendar.
     *
     * @var Horde_Kolab_Storage_Data
     */
    private $_data;

    /**
     * Attempts to open a Kolab Groupware folder.
     */
    public function initialize()
    {
        if (empty($this->_params['storage'])) {
            throw new InvalidArgumentException('Missing required Horde_Kolab_Storage instance');
        }
        $this->_kolab = $this->_params['storage'];
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
        if ($GLOBALS['calendar_manager']->getEntry(Kronolith::ALL_CALENDARS, $this->calendar) !== false) {
            return $GLOBALS['calendar_manager']->getEntry(Kronolith::ALL_CALENDARS, $this->calendar)->background();
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

    /**
     * Synchronize kolab storage backend.
     *
     * We delay initial synchronization to the first use so multiple calendars
     * don't add to the total latency. This function must be called before all
     * internal driver functions.
     *
     * @param boolean $force  If true, forces synchronization, even if we have
     *                        already done so.
     */
    public function synchronize($force = false, $token = false)
    {
        // Only sync once unless $force.
        if ($this->_synchronized && !$force) {
            return;
        }

        // If we are synching and have a token, only synch if it is different.
        $last_token = $GLOBALS['session']->get('kronolith', 'kolab/token/' . $this->calendar);
        if (!empty($token) && $last_token == $token) {
            return;
        }

        if (!empty($token)) {
            $GLOBALS['session']->set('kronolith', 'kolab/token/' . $this->calendar, $token);
        }

        // Connect to the Kolab backend
        try {
            $this->_data = $this->_kolab->getData(
                $GLOBALS['calendar_manager']
                    ->getEntry(Kronolith::ALL_CALENDARS, $this->calendar)
                    ->share()->get('folder'),
                'event'
            );
            $this->_data->synchronize();
        } catch (Kolab_Storage_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        // build internal event cache
        $this->_events_cache = $uids = array();
        $events = $this->_data->getObjects();
        foreach ($events as $event) {
            $this->_events_cache[Horde_Url::uriB64Encode($event['uid'])] = new Kronolith_Event_Kolab($this, $event);
            $uids[] = $event['uid'];
        }
        $tags = Kronolith::getTagger()->getTags(array_unique($uids));
        foreach ($this->_events_cache as &$event) {
            if (isset($tags[$event->uid])) {
                $event->synchronizeTags($tags[$event->uid]);
            }
        }

        $this->_synchronized = true;
    }

    /**
     * @throws Kronolith_Exception
     */
    public function listAlarms($date, $fullevent = false)
    {
        $allevents = $this->listEvents($date, null, array('has_alarm' => true));
        $events = array();

        foreach (array_keys($allevents) as $eventId) {
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
            throw new BadMethodCallException(sprintf('Kolab::exists called for calendar %s. Currently active is %s.', $calendar_id, $this->calendar));
        }

        $this->synchronize();

        if ($this->_data->objectIdExists($uid)) {
            return $uid;
        }

        return false;
    }

    /**
     * Lists all events in the time range, optionally restricting results to
     * only events with alarms.
     *
     * @param Horde_Date $startDate  The start of range date.
     * @param Horde_Date $endDate    The end of date range.
     * @param array $options         Additional options:
     *   - show_recurrence: (boolean) Return every instance of a recurring
     *                       event?
     *                      DEFAULT: false (Only return recurring events once
     *                      inside $startDate - $endDate range)
     *   - has_alarm:       (boolean) Only return events with alarms.
     *                      DEFAULT: false (Return all events)
     *   - json:            (boolean) Store the results of the event's toJson()
     *                      method?
     *                      DEFAULT: false
     *   - cover_dates:     (boolean) Add the events to all days that they
     *                      cover?
     *                      DEFAULT: true
     *   - hide_exceptions: (boolean) Hide events that represent exceptions to
     *                      a recurring event.
     *                      DEFAULT: false (Do not hide exception events)
     *   - fetch_tags:      (boolean) Fetch tags for all events.
     *                      DEFAULT: false (Do not fetch event tags)
     *
     * @throws Kronolith_Exception
     */
    protected function _listEvents(Horde_Date $startDate = null,
                                   Horde_Date $endDate = null,
                                   array $options = array())
    {
        $this->synchronize();

        if (empty($startDate)) {
            $startDate = new Horde_Date(
                array('mday' => 1, 'month' => 1, 'year' => 0000));
        }
        if (empty($endDate)) {
            $endDate = new Horde_Date(
                array('mday' => 31, 'month' => 12, 'year' => 9999));
        }
        if (!($startDate instanceof Horde_Date)) {
            $startDate = new Horde_Date($startDate);
        }
        if (!($endDate instanceof Horde_Date)) {
            $endDate = new Horde_Date($endDate);
        }

        $startDate = clone $startDate;
        $startDate->hour = $startDate->min = $startDate->sec = 0;
        $endDate = clone $endDate;
        $endDate->hour = 23;
        $endDate->min = $endDate->sec = 59;

        $events = array();
        foreach ($this->_events_cache as $event) {
            if ($options['has_alarm'] && !$event->alarm) {
                continue;
            }

            if ($options['hide_exceptions'] && !empty($event->baseid)) {
                continue;
            }

            /* Ignore events out of the period. */
            $recurs = $event->recurs();
            if (
                /* Starts after the period. */
                $event->start->compareDateTime($endDate) > 0 ||
                /* End before the period and doesn't recur. */
                (!$recurs &&
                 $event->end->compareDateTime($startDate) < 0)) {
                continue;
            }

            if ($recurs) {
                // Fixed end date? Check if end is before start period.
                if ($event->recurrence->hasRecurEnd() &&
                    $event->recurrence->recurEnd->compareDateTime($startDate) < 0) {
                    continue;
                } else {
                    $next = $event->recurrence->nextRecurrence($startDate);
                    if ($next == false || $next->compareDateTime($endDate) > 0) {
                        continue;
                    }
                }
            }

            Kronolith::addEvents(
                $events, $event, $startDate, $endDate,
                $options['show_recurrence'],
                $options['json'],
                $options['cover_dates']);
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

        $this->synchronize();

        if (isset($this->_events_cache[$eventId])) {
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
            $calendars = array_keys(Kronolith::listInternalCalendars(false, Horde_Perms::READ));
        }
        $id = Horde_Url::uriB64Encode($uid);

        foreach ($calendars as $calendar) {
            $this->open($calendar);
            $this->synchronize();

            if (!isset($this->_events_cache[$id])) {
                continue;
            }

            // Ok, found event
            $event = $this->_events_cache[$id];

            if ($getAll) {
                return array($event);
            }

            return $event;
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
            $event->uid = $this->_data->generateUID();
            $event->id = Horde_Url::uriB64Encode($event->uid);
        }

        $object = $event->toKolab();
        if ($edit) {
            $this->_data->modify($object);
        } else {
            $this->_data->create($object);
        }

        /* Deal with tags */
        if ($edit) {
            $this->_updateTags($event);
        } else {
            $this->_addTags($event);
        }

        /* Notify about the changed event. */
        Kronolith::sendNotification($event, $edit ? 'edit' : 'add');

        /* Log the creation/modification of this item in the history log. */
        try {
            $GLOBALS['injector']->getInstance('Horde_History')->log('kronolith:' . $event->calendar . ':' . $event->uid, $action, true);
        } catch (Exception $e) {
            Horde::log($e, 'ERR');
        }

        // refresh IMAP cache
        $this->synchronize(true);

        if (is_callable('Kolab', 'triggerFreeBusyUpdate')) {
            //Kolab::triggerFreeBusyUpdate($this->_data->parseFolder($event->calendar));
        }

        return $event->id;
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
        $this->synchronize();

        $target = $GLOBALS['injector']
            ->getInstance('Kronolith_Shares')
            ->getShare($newCalendar)
            ->get('folder');

        $this->_data->move($event->uid, $target);
        unset($this->_events_cache[$eventId]);
        try {
            $this->_kolab->getData($target, 'event')->synchronize();
        } catch (Kolab_Storage_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        if (is_callable('Kolab', 'triggerFreeBusyUpdate')) {
            //Kolab::triggerFreeBusyUpdate($this->_data->parseFolder($this->calendar));
            //Kolab::triggerFreeBusyUpdate($this->_data->parseFolder($newCalendar));
        }

        return $event;
    }

    /**
     * Delete all of a calendar's events.
     *
     * @param string $calendar  The name of the calendar to delete.
     *
     * @throws Kronolith_Exception
     */
    public function delete($calendar)
    {
        $this->open($calendar);
        $result = $this->synchronize();

        $result = $this->_data->deleteAll($calendar);
        if (is_callable('Kolab', 'triggerFreeBusyUpdate')) {
            //Kolab::triggerFreeBusyUpdate($this->_data->parseFolder($calendar));
        }
    }

    /**
     * Deletes an event.
     *
     * @param string $eventId  The ID of the event to delete.
     *
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     * @throws Horde_Mime_Exception
     */
    protected function _deleteEvent($eventId, $silent = false)
    {
        if ($eventId instanceof Kronolith_Event) {
            $event = $eventId;
            $this->synchronize();
        } else {
            $event = $this->getEvent($eventId);
        }

        $this->_data->delete($event->uid);
        unset($this->_events_cache[$event->id]);

        /* Notify about the deleted event. */
        if (!$silent) {
            $this->_handleNotifications($event, 'delete');
        }

        return $event;
    }

}
