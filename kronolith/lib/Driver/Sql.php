<?php
/**
 * The Kronolith_Driver_Sql class implements the Kronolith_Driver API for a
 * SQL backend.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Luc Saillard <luc.saillard@fr.alcove.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_Driver_Sql extends Kronolith_Driver
{
    /**
     * The object handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Cache events as we fetch them to avoid fetching the same event from the
     * DB twice.
     *
     * @var array
     */
    protected $_cache = array();

    /**
     * The class name of the event object to instantiate.
     *
     * Can be overwritten by sub-classes.
     *
     * @var string
     */
    protected $_eventClass = 'Kronolith_Event_Sql';

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
     * Returns whether this driver supports per-event timezones.
     *
     * @return boolean  Whether this drivers suppports per-event timezones.
     */
    public function supportsTimezones()
    {
        return $this->getParam('utc');
    }

    /**
     *
     * @param Horde_Date $date    The date to list alarms for
     * @param boolean $fullevent  Return the full event objects?
     *
     * @return array  An array of event ids, or Kronolith_Event objects
     * @throws Kronolith_Exception
     */
    public function listAlarms($date, $fullevent = false)
    {
        $allevents = $this->listEvents($date, null, array('has_alarm' => true));
        $events = array();
        foreach ($allevents as $dayevents) {
            foreach ($dayevents as $event) {
                if (!$event->recurs()) {
                    $start = new Horde_Date($event->start);
                    $start->min -= $event->alarm;
                    if ($start->compareDateTime($date) <= 0 &&
                        $date->compareDateTime($event->end) <= -1) {
                        $events[] = $fullevent ? $event : $event->id;
                    }
                } else {
                    // Need to start at the beginning of the day to catch the
                    // case where we might be within the event's timespan
                    // when we call this, hence nextRecurrence() would miss the
                    // current event.
                    $start = clone $date;
                    $start->min = 0;
                    $start->hour = 0;
                    $start->sec = 0;
                    if ($next = $event->recurrence->nextRecurrence($start)) {
                        if ($event->recurrence->hasException($next->year, $next->month, $next->mday)) {
                            continue;
                        }
                        $start = new Horde_Date($next);
                        $start->min -= $event->alarm;
                        $diff = Date_Calc::dateDiff(
                            $event->start->mday,
                            $event->start->month,
                            $event->start->year,
                            $event->end->mday,
                            $event->end->month,
                            $event->end->year
                        );
                        if ($diff == -1) {
                            $diff = 0;
                        }
                        $end = new Horde_Date(array(
                            'year' => $next->year,
                            'month' => $next->month,
                            'mday' => $next->mday + $diff,
                            'hour' => $event->end->hour,
                            'min' => $event->end->min,
                            'sec' => $event->end->sec)
                        );
                        if ($start->compareDateTime($date) <= 0 &&
                            $date->compareDateTime($end) <= -1) {
                            if ($fullevent) {
                                $event->start = $next;
                                $event->end = $end;
                                $events[] = $event;
                            } else {
                                $events[] = $event->id;
                            }
                        }
                    }
                }
            }
        }

        return $events;
    }

    /**
     * Searches a calendar.
     *
     * @param object $query  An object with the criteria to search for.
     * @param boolean $json  Store the results of the events' toJson() method?
     *
     * @return mixed  An array of Kronolith_Events.
     * @throws Kronolith_Exception
     */
    public function search($query, $json = false)
    {
        /* Build SQL conditions based on the query string. */
        $cond = '((';
        $values = array();

        foreach (array('title', 'location', 'url', 'description') as $field) {
            if (!empty($query->$field)) {
                $binds = $this->_db->buildClause('event_' . $field, 'LIKE', $this->convertToDriver($query->$field), true);
                if (is_array($binds)) {
                    $cond .= $binds[0] . ' AND ';
                    $values = array_merge($values, $binds[1]);
                } else {
                    $cond .= $binds;
                }
            }
        }

        if (!empty($query->baseid)) {
            $binds = $this->_db->buildClause('event_baseid', '=', $query->baseid, true);
            if (is_array($binds)) {
                $cond .= $binds[0] . ' AND ';
                $values = array_merge($values, $binds[1]);
            } else {
                $cond .= $binds;
            }
        }

        if (isset($query->status)) {
            $binds = $this->_db->buildClause('event_status', '=', $query->status, true);
            if (is_array($binds)) {
                $cond .= $binds[0] . ' AND ';
                $values = array_merge($values, $binds[1]);
            } else {
                $cond .= $binds;
            }
        }
        if (!empty($query->creator)) {
            $binds = $this->_db->buildClause('event_creator_id', '=', $query->creator, true);
            if (is_array($binds)) {
                $cond .= $binds[0] . ' AND ';
                $values = array_merge($values, $binds[1]);
            } else {
                $cond .= $binds;
            }
        }

        if ($cond == '((') {
            $cond = '';
        } else {
            $cond = substr($cond, 0, strlen($cond) - 5) . '))';
        }

        $eventIds = $this->_listEventsConditional(empty($query->start) ? null : $query->start,
                                                  empty($query->end) ? null : $query->end,
                                                  $cond,
                                                  $values);
        $events = array();
        foreach ($eventIds as $eventId) {
            Kronolith::addSearchEvents($events, $this->getEvent($eventId), $query, $json);
        }

        return $events;
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
        $query = 'SELECT event_id  FROM ' . $this->_params['table'] . ' WHERE event_uid = ?';
        $values = array($uid);

        if (!is_null($calendar_id)) {
            $query .= ' AND calendar_id = ?';
            $values[] = $calendar_id;
        }

        try {
            $event = $this->_db->selectValue($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        return !empty($event) ? $event : false;
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
        if (!is_null($startDate)) {
            $startDate = clone $startDate;
            $startDate->hour = $startDate->min = $startDate->sec = 0;
        }
        if (!is_null($endDate)) {
            $endDate = clone $endDate;
            $endDate->hour = 23;
            $endDate->min = $endDate->sec = 59;
        }

        $conditions =  $options['has_alarm'] ? 'event_alarm > ?' : '';
        $values = $options['has_alarm'] ? array(0) : array();
        if ($options['hide_exceptions']) {
            if (!empty($conditions)) {
                $conditions .= ' AND ';
            }
            $conditions .= "event_baseid = ''";
        }

        $events = $this->_listEventsConditional($startDate, $endDate, $conditions, $values);
        $results = array();
        $tags = null;
        if ($options['fetch_tags'] && count($events)) {
            $tags = Kronolith::getTagger()->getTags(array_keys($events));
        }
        foreach ($events as $id) {
            $event = $this->getEvent($id);
            if (isset($tags) && !empty($tags[$event->uid])) {
                $event->tags = $tags[$event->uid];
            }
            Kronolith::addEvents(
                $results, $event, $startDate, $endDate, $options['show_recurrence'],
                $options['json'], $options['cover_dates']);
        }

        return $results;
    }

    /**
     * Lists all events that satisfy the given conditions.
     *
     * @param Horde_Date $startInterval  Start of range date object.
     * @param Horde_Date $endInterval    End of range data object.
     * @param string $conditions         Conditions, given as SQL clauses.
     * @param array $vals                SQL bind variables for use with
     *                                   $conditions clauses.
     *
     * @return array  Events in the given time range satisfying the given
     *                conditions.
     * @throws Kronolith_Exception
     */
    private function _listEventsConditional(Horde_Date $startInterval = null,
                                            Horde_Date $endInterval = null,
                                            $conditions = '', array $vals = array())
    {
        if ($this->getParam('utc')) {
            if (!is_null($startInterval)) {
                $startInterval = clone $startInterval;
                $startInterval->setTimezone('UTC');
            }
            if (!is_null($endInterval)) {
                $endInterval = clone $endInterval;
                $endInterval->setTimezone('UTC');
            }
        }
        $q = 'SELECT event_id, event_uid, event_description, event_location,' .
            ' event_private, event_status, event_attendees,' .
            ' event_title, event_recurcount, event_url, event_timezone,' .
            ' event_recurtype, event_recurenddate, event_recurinterval,' .
            ' event_recurdays, event_start, event_end, event_allday,' .
            ' event_alarm, event_alarm_methods, event_modified,' .
            ' event_exceptions, event_creator_id, event_resources, event_baseid,' .
            ' event_exceptionoriginaldate FROM ' . $this->_params['table'] .
            ' WHERE calendar_id = ?';
        $values = array($this->calendar);

        if ($conditions) {
            $q .= ' AND ' . $conditions;
            $values = array_merge($values, $vals);
        }

        if (!is_null($startInterval) && !is_null($endInterval)) {
            $etime = $endInterval->format('Y-m-d H:i:s');
            $stime = $startInterval->format('Y-m-d H:i:s');
            $q .= ' AND ((event_end >= ? AND event_start <= ?) OR (event_recurenddate >= ? AND event_start <= ? AND event_recurtype <> ?))';
            array_push($values, $stime, $etime, $stime, $etime, Horde_Date_Recurrence::RECUR_NONE);
        } elseif (!is_null($startInterval)) {
            $stime = $startInterval->format('Y-m-d H:i:s');
            $q .= ' AND ((event_end >= ?) OR (event_recurenddate >= ? AND event_recurtype <> ?))';
            array_push($values, $stime, $stime, Horde_Date_Recurrence::RECUR_NONE);
        } elseif (!is_null($endInterval)) {
            $q .= ' AND (event_start <= ?)';
            $values[] = $endInterval->format('Y-m-d H:i:s');
        }

        /* Run the query. */
        try {
            $qr = $this->_db->selectAll($q, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        $events = array();
        foreach ($qr as $row) {
            /* If the event did not have a UID before, we need to give
             * it one. */
            if (empty($row['event_uid'])) {
                $row['event_uid'] = (string)new Horde_Support_Guid;

                /* Save the new UID for data integrity. */
                $query = 'UPDATE ' . $this->_params['table'] . ' SET event_uid = ? WHERE event_id = ?';
                $values = array($row['event_uid'], $row['event_id']);
                try {
                    $this->_db->update($query, $values);
                } catch (Horde_Db_Exception $e) {
                    throw new Kronolith_Exception($e);
                }
            }

            /* We have all the information we need to create an event object
             * for this event, so go ahead and cache it. */
            $this->_cache[$this->calendar][$row['event_id']] = new $this->_eventClass($this, $row);
            if ($row['event_recurtype'] == Horde_Date_Recurrence::RECUR_NONE) {
                $events[$row['event_uid']] = $row['event_id'];
            } else {
                $next = $this->nextRecurrence($row['event_id'], $startInterval);
                if ($next &&
                    (is_null($endInterval) ||
                     $next->compareDateTime($endInterval) < 0)) {
                    $events[$row['event_uid']] = $row['event_id'];
                }
            }
        }

        return $events;
    }

    /**
     * Returns the number of events in the current calendar.
     *
     * @return integer  The number of events.
     * @throws Kronolith_Exception
     */
    public function countEvents()
    {
        $query = sprintf('SELECT count(*) FROM %s WHERE calendar_id = ?',
                         $this->_params['table']);

        /* Run the query. */
        try {
            $result = $this->_db->selectValue($query, array($this->calendar));
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        return $result;
    }

    /**
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getEvent($eventId = null)
    {
        if (!strlen($eventId)) {
            return new $this->_eventClass($this);
        }

        if (isset($this->_cache[$this->calendar][$eventId])) {
            return $this->_cache[$this->calendar][$eventId];
        }

        $query = 'SELECT event_id, event_uid, event_description,' .
            ' event_location, event_private, event_status, event_attendees,' .
            ' event_title, event_recurcount, event_url, event_timezone,' .
            ' event_recurtype, event_recurenddate, event_recurinterval,' .
            ' event_recurdays, event_start, event_end, event_allday,' .
            ' event_alarm, event_alarm_methods, event_modified,' .
            ' event_exceptions, event_creator_id, event_resources,' .
            ' event_baseid, event_exceptionoriginaldate FROM ' .
            $this->_params['table'] . ' WHERE event_id = ? AND calendar_id = ?';

        $values = array($eventId, $this->calendar);

        try {
            $event = $this->_db->selectOne($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }
        if ($event) {
            $this->_cache[$this->calendar][$eventId] = new $this->_eventClass($this, $event);
            return $this->_cache[$this->calendar][$eventId];
        }

        throw new Horde_Exception_NotFound(_("Event not found"));
    }

    /**
     * Get an event or events with the given UID value.
     *
     * @param string $uid       The UID to match
     * @param array $calendars  A restricted array of calendar ids to search
     * @param boolean $getAll   Return all matching events?
     *
     * @return Kronolith_Event
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getByUID($uid, $calendars = null, $getAll = false)
    {
        $query = 'SELECT event_id, event_uid, calendar_id, event_description,' .
            ' event_location, event_private, event_status, event_attendees,' .
            ' event_title, event_recurcount, event_url, event_timezone,' .
            ' event_recurtype, event_recurenddate, event_recurinterval,' .
            ' event_recurdays, event_start, event_end, event_allday,' .
            ' event_alarm, event_alarm_methods, event_modified,' .
            ' event_exceptions, event_creator_id, event_resources, event_baseid,' .
            ' event_exceptionoriginaldate FROM ' . $this->_params['table'] .
            ' WHERE event_uid = ?';
        $values = array((string)$uid);

        /* Optionally filter by calendar */
        if (!is_null($calendars)) {
            if (!count($calendars)) {
                throw new Kronolith_Exception(_("No calendars to search"));
            }
            $query .= ' AND calendar_id IN (?' . str_repeat(', ?', count($calendars) - 1) . ')';
            $values = array_merge($values, $calendars);
        }

        try {
            $events = $this->_db->selectAll($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }
        if (!count($events)) {
            throw new Horde_Exception_NotFound($uid . ' not found');
        }

        $eventArray = array();
        foreach ($events as $event) {
            $this->open($event['calendar_id']);
            $this->_cache[$this->calendar][$event['event_id']] = new $this->_eventClass($this, $event);
            $eventArray[] = $this->_cache[$this->calendar][$event['event_id']];
        }

        if ($getAll) {
            return $eventArray;
        }

        /* First try the user's own calendars. */
        $ownerCalendars = Kronolith::listInternalCalendars(true, Horde_Perms::READ);
        $event = null;
        foreach ($eventArray as $ev) {
            if (isset($ownerCalendars[$ev->calendar])) {
                $event = $ev;
                break;
            }
        }

        /* If not successful, try all calendars the user has access too. */
        if (empty($event)) {
            $readableCalendars = Kronolith::listInternalCalendars(false, Horde_Perms::READ);
            foreach ($eventArray as $ev) {
                if (isset($readableCalendars[$ev->calendar])) {
                    $event = $ev;
                    break;
                }
            }
        }

        if (empty($event)) {
            $event = $eventArray[0];
        }

        return $event;
    }

    /**
     * Builds a history hash for a modified event.
     *
     * We don't write it in here because we don't want to commit history before
     * the actual changes are made.
     *
     * @param Kronolith_Event $event  The event to log.
     *
     * @return array  The change log.
     * @throws Horde_Mime_Exception
     * @throws Kronolith_Exception
     */
    protected function _buildEventHistory(Kronolith_Event $event)
    {
        $changes = array('action' => 'modify');

        /* We cannot use getEvent() because of caching. */
        $oldProperties = $this->getbyUID(
            $event->uid,
            array($event->calendar))->toProperties();
        $newProperties = $event->toProperties();
        if (empty($oldProperties)) {
            return $changes;
        }

        foreach (array_keys($newProperties) as $property) {
            if (empty($oldProperties[$property]) || ($oldProperties[$property] != $newProperties[$property])) {
                $changes['new'][$property] = $newProperties[$property];
                $changes['old'][$property] = !empty($oldProperties[$property]) ? $oldProperties[$property] : null;
            }
        }

        return $changes;
    }

    /**
     * Updates an existing event in the backend.
     *
     * @param Kronolith_Event $event  The event to save.
     *
     * @return string  The event id.
     * @throws Horde_Mime_Exception
     * @throws Kronolith_Exception
     */
    protected function _updateEvent(Kronolith_Event $event)
    {
        $values = array();
        $query = 'UPDATE ' . $this->_params['table'] . ' SET ';
        foreach ($event->toProperties() as $key => $val) {
            $query .= " $key = ?,";
            $values[] = $val;
        }
        $query = substr($query, 0, -1);
        $query .= ' WHERE event_id = ?';
        $values[] = $event->id;

        $history = $this->_buildEventHistory($event);

        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        /* Log the modification of this item in the history log. */
        if ($event->uid) {
            try {
                $GLOBALS['injector']->getInstance('Horde_History')->log('kronolith:' . $this->calendar . ':' . $event->uid, $history, false);
            } catch (Exception $e) {
                Horde::logMessage($e, 'ERR');
            }
        }

        /* If this event is an exception, we need to modify the base event's
         * history log also, or some sync clients will never pick up the
         * change. */
        if ($event->baseid) {
            try {
                $GLOBALS['injector']->getInstance('Horde_History')->log('kronolith:' . $this->calendar . ':' . $event->baseid, $history, false);
            } catch (Exception $e) {
                Horde::logMessage($e, 'ERR');
            }
        }
        $this->_updateTags($event);

        /* Update Geolocation */
        try {
            $GLOBALS['injector']->getInstance('Kronolith_Geo')->setLocation($event->id, $event->geoLocation);
        } catch (Kronolith_Exception $e) {

        }

        /* Notify users about the changed event. */
        $this->_handleNotifications($event, 'edit');

        return $event->id;
    }

    /**
     * Adds an event to the backend.
     *
     * @param Kronolith_Event $event  The event to save.
     *
     * @return string  The event id.
     * @throws Horde_Mime_Exception
     * @throws Kronolith_Exception
     */
    protected function _addEvent(Kronolith_Event $event)
    {
        if (!$event->id) {
            $event->id = (string)new Horde_Support_Randomid;
        }
        if (!$event->uid) {
            $event->uid = (string)new Horde_Support_Guid;
        }

        $query = 'INSERT INTO ' . $this->_params['table'];
        $cols_name = ' (event_id, event_uid,';
        $cols_values = ' VALUES (?, ?,';
        $values = array($event->id, $event->uid);
        foreach ($event->toProperties() as $key => $val) {
            $cols_name .= " $key,";
            $cols_values .= ' ?,';
            $values[] = $val;
        }
        $cols_name .= ' calendar_id)';
        $cols_values .= ' ?)';
        $values[] = $this->calendar;
        $query .= $cols_name . $cols_values;

        try {
            $this->_db->insert($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }
        /* Log the creation of this item in the history log. */
        try {
            $GLOBALS['injector']->getInstance('Horde_History')->log('kronolith:' . $this->calendar . ':' . $event->uid, array('action' => 'add'), true);
        } catch (Exception $e) {
            Horde::logMessage($e, 'ERR');
        }

        $this->_addTags($event);

        /* Update Geolocation */
        if ($event->geoLocation) {
            try {
                $GLOBALS['injector']->getInstance('Kronolith_Geo')->setLocation($event->id, $event->geoLocation);
            } catch (Kronolith_Exception $e) {

            }
        }

        /* Notify users about the new event. */
        $this->_handleNotifications($event, 'add');

        return $event->id;
    }

    /**
     * Wrapper for sending notifications, so that we can overwrite this action
     * in Kronolith_Driver_Resource.
     *
     * @param Kronolith_Event $event
     * @param string $action
     */
    protected function _handleNotifications(Kronolith_Event $event, $action)
    {
        Kronolith::sendNotification($event, $action);
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
        /* Fetch the event for later use. */
        $event = $this->getEvent($eventId);

        $query = 'UPDATE ' . $this->_params['table'] . ' SET calendar_id = ? WHERE calendar_id = ? AND event_id = ?';
        $values = array($newCalendar, $this->calendar, $eventId);

        /* Attempt the move query. */
        try {
            $this->_db->update($query, $values);
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
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
        $oldCalendar = $this->calendar;
        $this->open($calendar);
        $events = $this->listEvents(null, null, array('cover_dates' => false));
        $uids = array();
        foreach ($events as $dayevents) {
            foreach ($dayevents as $event) {
                $uids[] = $event->uid;
            }
        }
        foreach ($uids as $uid) {
            $event = $this->getByUID($uid, array($calendar));
            try {
                $this->deleteEvent($event->id);
            } catch (Kronolith_Exception $e) {
                Horde::logMessage($e, 'ERR');
            }
        }

        $this->open($oldCalendar);
    }

    /**
     * Delete an event.
     *
     * @param string $eventId  The ID of the event to delete.
     * @param boolean $silent  Don't send notifications, used when deleting
     *                         events in bulk from maintenance tasks.
     *
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     * @throws Horde_Mime_Exception
     */
    public function deleteEvent($eventId, $silent = false)
    {
        /* Fetch the event for later use. */
        if ($eventId instanceof Kronolith_Event) {
            $event = $eventId;
            $eventId = $event->id;
        } else {
            $event = $this->getEvent($eventId);
        }
        $original_uid = $event->uid;
        $isRecurring = $event->recurs();

        $query = 'DELETE FROM ' . $this->_params['table'] . ' WHERE event_id = ? AND calendar_id = ?';
        try {
            $this->_db->delete($query, array($eventId, $this->calendar));
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }
        /* Log the deletion of this item in the history log. */
        if ($event->uid) {
            try {
                $GLOBALS['injector']->getInstance('Horde_History')->log('kronolith:' . $this->calendar . ':' . $event->uid, array('action' => 'delete'), true);
            } catch (Exception $e) {
                Horde::logMessage($e, 'ERR');
            }
        }

        /* Remove the event from any resources that are attached to it */
        $resources = $event->getResources();
        if (count($resources)) {
            $rd = Kronolith::getDriver('Resource');
            foreach ($resources as $uid => $resource) {
                if ($resource['response'] !== Kronolith::RESPONSE_DECLINED) {
                    $r = $rd->getResource($uid);
                    $r->removeEvent($event);
                }
            }
        }

        /* Remove any pending alarms. */
        $GLOBALS['injector']->getInstance('Horde_Alarm')->delete($event->uid);

        /* Remove any tags */
        $tagger = Kronolith::getTagger();
        $tagger->replaceTags($event->uid, array(), $event->creator, 'event');

        /* Remove any geolocation data */
        try {
            $GLOBALS['injector']->getInstance('Kronolith_Geo')->deleteLocation($event->id);
        } catch (Kronolith_Exception $e) {
        }

        /* Notify about the deleted event. */
        if (!$silent) {
            $this->_handleNotifications($event, 'delete');
        }

        /* See if this event represents an exception - if so, touch the base
         * event's history. The $isRecurring check is to prevent an infinite
         * loop in the off chance that an exception is entered as a recurring
         * event.
         */
        if ($event->baseid && !$isRecurring) {
            try {
                $GLOBALS['injector']->getInstance('Horde_History')->log('kronolith:' . $this->calendar . ':' . $event->baseid, array('action' => 'modify'), true);
            } catch (Exception $e) {
                Horde::logMessage($e, 'ERR');
            }
        }

        /* Now check for any exceptions that THIS event may have */
        if ($isRecurring) {
            $query = 'SELECT event_id FROM ' . $this->_params['table'] . ' WHERE event_baseid = ? AND calendar_id = ?';
            $values = array($original_uid, $this->calendar);

            try {
                $result = $this->_db->selectValues($query, $values);
            } catch (Horde_Db_Exception $e) {
                throw new Kronolith_Exception($e);
            }
            foreach ($result as $id) {
                $this->deleteEvent($id, $silent);
            }
        }
    }

    /**
     * Filters a list of events to return only those that belong to certain
     * calendars.
     *
     * @param array $uids      A list of event UIDs.
     * @param array $calendar  A list of calendar IDs.
     *
     * @return array  Event UIDs filtered by calendar IDs.
     * @throws Kronolith_Exception
     */
    public function filterEventsByCalendar($uids, $calendar)
    {
        $sql = 'SELECT event_uid FROM kronolith_events WHERE calendar_id IN (' . str_repeat('?, ', count($calendar) - 1) . '?) '
            . 'AND event_uid IN (' . str_repeat('?,', count($uids) - 1) . '?)';

        try {
            $result = $this->_db->selectValues($sql, array_merge($calendar, $uids));
        } catch (Horde_Db_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        return $result;
    }

    /**
     * Attempts to open a connection to the SQL server.
     *
     * @throws Kronolith_Exception
     */
    public function initialize()
    {
        if (empty($this->_params['db'])) {
            throw new InvalidArgumentException('Missing required Horde_Db_Adapter instance');
        }
        try {
            $this->_db = $this->_params['db'];
        } catch (Horde_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        $this->_params = array_merge(array(
            'table' => 'kronolith_events'
        ), $this->_params);
    }

    /**
     * Converts a value from the driver's charset to the default
     * charset.
     *
     * @param mixed $value  A value to convert.
     *
     * @return mixed  The converted value.
     */
    public function convertFromDriver($value)
    {
        return Horde_String::convertCharset($value, $this->_params['charset'], 'UTF-8');
    }

    /**
     * Converts a value from the default charset to the driver's
     * charset.
     *
     * @param mixed $value  A value to convert.
     *
     * @return mixed  The converted value.
     */
    public function convertToDriver($value)
    {
        return Horde_String::convertCharset($value, 'UTF-8', $this->_params['charset']);
    }

}
