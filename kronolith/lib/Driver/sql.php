<?php
/**
 * The Kronolith_Driver_sql:: class implements the Kronolith_Driver
 * API for a SQL backend.
 *
 * @author  Luc Saillard <luc.saillard@fr.alcove.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */
class Kronolith_Driver_sql extends Kronolith_Driver {

    /**
     * The object handle for the current database connection.
     *
     * @var DB
     */
    var $_db;

    /**
     * Handle for the current database connection, used for writing. Defaults
     * to the same handle as $_db if a separate write database is not required.
     *
     * @var DB
     */
    var $_write_db;

    /**
     * Cache events as we fetch them to avoid fetching the same event from the
     * DB twice.
     *
     * @var array
     */
    var $_cache = array();

    function listAlarms($date, $fullevent = false)
    {
        require_once 'Date/Calc.php';

        $allevents = $this->listEvents($date, null, true);
        if (is_a($allevents, 'PEAR_Error')) {
            return $allevents;
        }

        $events = array();
        foreach ($allevents as $eventId) {
            $event = $this->getEvent($eventId);
            if (is_a($event, 'PEAR_Error')) {
                continue;
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
                    $diff = Date_Calc::dateDiff($event->start->mday,
                                                $event->start->month,
                                                $event->start->year,
                                                $event->end->mday,
                                                $event->end->month,
                                                $event->end->year);
                    if ($diff == -1) {
                        $diff = 0;
                    }
                    $end = new Horde_Date(array('year' => $next->year,
                                                'month' => $next->month,
                                                'mday' => $next->mday + $diff,
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

    function search($query)
    {
        require_once 'Horde/SQL.php';

        /* Build SQL conditions based on the query string. */
        $cond = '((';
        $values = array();

        if (!empty($query->title)) {
            $binds = Horde_SQL::buildClause($this->_db, 'event_title', 'LIKE', $this->convertToDriver($query->title), true);
            if (is_array($binds)) {
                $cond .= $binds[0] . ' AND ';
                $values = array_merge($values, $binds[1]);
            } else {
                $cond .= $binds;
            }
        }
        if (!empty($query->location)) {
            $binds = Horde_SQL::buildClause($this->_db, 'event_location', 'LIKE', $this->convertToDriver($query->location), true);
            if (is_array($binds)) {
                $cond .= $binds[0] . ' AND ';
                $values = array_merge($values, $binds[1]);
            } else {
                $cond .= $binds;
            }
        }
        if (!empty($query->description)) {
            $binds = Horde_SQL::buildClause($this->_db, 'event_description', 'LIKE', $this->convertToDriver($query->description), true);
            if (is_array($binds)) {
                $cond .= $binds[0] . ' AND ';
                $values = array_merge($values, $binds[1]);
            } else {
                $cond .= $binds;
            }
        }
        if (isset($query->status)) {
            $binds = Horde_SQL::buildClause($this->_db, 'event_status', '=', $query->status, true);
            if (is_array($binds)) {
                $cond .= $binds[0] . ' AND ';
                $values = array_merge($values, $binds[1]);
            } else {
                $cond .= $binds;
            }
        }

        if (!empty($query->creatorID)) {
            $binds = Horde_SQL::buildClause($this->_db, 'event_creator_id', '=', $query->creatorID, true);
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

        $eventIds = $this->listEventsConditional($query->start,
                                                 empty($query->end)
                                                 ? new Horde_Date(array('mday' => 31, 'month' => 12, 'year' => 9999))
                                                 : $query->end,
                                                 $cond,
                                                 $values);
        if (is_a($eventIds, 'PEAR_Error')) {
            return $eventIds;
        }

        $events = array();
        foreach ($eventIds as $eventId) {
            $event = $this->getEvent($eventId);
            if (is_a($event, 'PEAR_Error')) {
                return $event;
            }
            $events[] = $event;
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
     */
    function exists($uid, $calendar_id = null)
    {
        $query = 'SELECT event_id  FROM ' . $this->_params['table'] . ' WHERE event_uid = ?';
        $values = array($uid);

        if (!is_null($calendar_id)) {
            $query .= ' AND calendar_id = ?';
            $values[] = $calendar_id;
        }

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Kronolith_Driver_sql::exists(): user = "%s"; query = "%s"',
                                  Auth::getAuth(), $query),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $event = $this->_db->getRow($query, $values, DB_FETCHMODE_ASSOC);
        if (is_a($event, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $event;
        }

        if ($event) {
            return $event['event_id'];
        } else {
            return false;
        }
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
    function listEvents($startDate = null, $endDate = null, $hasAlarm = false)
    {
        if (empty($endDate)) {
            $endInterval = new Horde_Date(array('mday' => 31, 'month' => 12,
                                                'year' => 9999));
        } else {
            $endInterval = clone $endDate;
            $endInterval->mday++;
        }

        $startInterval = null;
        if (empty($startDate)) {
            $startInterval = new Horde_Date(array('mday' => 1, 'month' => 1,
                                                  'year' => 0000));
        } else {
            $startInterval = clone $startDate;
        }

        return $this->listEventsConditional($startInterval, $endInterval,
                                            $hasAlarm ? 'event_alarm > ?' : '',
                                            $hasAlarm ? array(0) : array());
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
     */
    function listEventsConditional($startInterval, $endInterval,
                                   $conditions = '', $vals = array())
    {
        $q = 'SELECT event_id, event_uid, event_description, event_location,' .
            ' event_private, event_status, event_attendees,' .
            ' event_title, event_recurcount,' .
            ' event_recurtype, event_recurenddate, event_recurinterval,' .
            ' event_recurdays, event_start, event_end, event_allday,' .
            ' event_alarm, event_alarm_methods, event_modified,' .
            ' event_exceptions, event_creator_id' .
            ' FROM ' . $this->_params['table'] .
            ' WHERE calendar_id = ? AND ((';
        $values = array($this->_calendar);

        if ($conditions) {
            $q .= $conditions . ')) AND ((';
            $values = array_merge($values, $vals);
        }

        $etime = $endInterval->format('Y-m-d H:i:s');
        $stime = null;
        if (isset($startInterval)) {
            $stime = $startInterval->format('Y-m-d H:i:s');
            $q .= 'event_end > ? AND ';
            $values[] = $stime;
        }
        $q .= 'event_start < ?) OR (';
        $values[] = $etime;
        if (isset($stime)) {
            $q .= 'event_recurenddate >= ? AND ';
            $values[] = $stime;
        }
        $q .= 'event_start <= ?' .
            ' AND event_recurtype <> ?))';
        array_push($values, $etime, Horde_Date_Recurrence::RECUR_NONE);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Kronolith_Driver_sql::listEventsConditional(): user = "%s"; query = "%s"; values = "%s"',
                                  Auth::getAuth(), $q, implode(',', $values)),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Run the query. */
        $qr = $this->_db->query($q, $values);
        if (is_a($qr, 'PEAR_Error')) {
            Horde::logMessage($qr, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $qr;
        }

        $events = array();
        $row = $qr->fetchRow(DB_FETCHMODE_ASSOC);
        while ($row && !is_a($row, 'PEAR_Error')) {
            /* If the event did not have a UID before, we need to give
             * it one. */
            if (empty($row['event_uid'])) {
                $row['event_uid'] = $this->generateUID();

                /* Save the new UID for data integrity. */
                $query = 'UPDATE ' . $this->_params['table'] . ' SET event_uid = ? WHERE event_id = ?';
                $values = array($row['event_uid'], $row['event_id']);

                /* Log the query at a DEBUG log level. */
                Horde::logMessage(sprintf('Kronolith_Driver_sql::listEventsConditional(): user = %s; query = "%s"; values = "%s"',
                                          Auth::getAuth(), $query, implode(',', $values)),
                                  __FILE__, __LINE__, PEAR_LOG_DEBUG);

                $result = $this->_write_db->query($query, $values);
                if (is_a($result, 'PEAR_Error')) {
                    Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                }
            }

            /* We have all the information we need to create an event
             * object for this event, so go ahead and cache it. */
            $this->_cache[$this->_calendar][$row['event_id']] = new Kronolith_Event_sql($this, $row);
            if ($row['event_recurtype'] == Horde_Date_Recurrence::RECUR_NONE) {
                $events[$row['event_uid']] = $row['event_id'];
            } else {
                $next = $this->nextRecurrence($row['event_id'], $startInterval);
                if ($next && $next->compareDate($endInterval) < 0) {
                    $events[$row['event_uid']] = $row['event_id'];
                }
            }

            $row = $qr->fetchRow(DB_FETCHMODE_ASSOC);
        }

        return $events;
    }

    function getEvent($eventId = null)
    {
        if (is_null($eventId)) {
            return new Kronolith_Event_sql($this);
        }

        if (isset($this->_cache[$this->_calendar][$eventId])) {
            return $this->_cache[$this->_calendar][$eventId];
        }

        $query = 'SELECT event_id, event_uid, event_description,' .
            ' event_location, event_private, event_status, event_attendees,' .
            ' event_title, event_recurcount,' .
            ' event_recurtype, event_recurenddate, event_recurinterval,' .
            ' event_recurdays, event_start, event_end, event_allday,' .
            ' event_alarm, event_alarm_methods, event_modified,' .
            ' event_exceptions, event_creator_id' .
            ' FROM ' . $this->_params['table'] . ' WHERE event_id = ? AND calendar_id = ?';
        $values = array($eventId, $this->_calendar);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Kronolith_Driver_sql::getEvent(): user = "%s"; query = "%s"; values = "%s"',
                                  Auth::getAuth(), $query, implode(',', $values)),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $event = $this->_db->getRow($query, $values, DB_FETCHMODE_ASSOC);
        if (is_a($event, 'PEAR_Error')) {
            Horde::logMessage($event, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $event;
        }

        if ($event) {
            $this->_cache[$this->_calendar][$eventId] = new Kronolith_Event_sql($this, $event);
            return $this->_cache[$this->_calendar][$eventId];
        } else {
            return PEAR::raiseError(_("Event not found"));
        }
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
    function getByUID($uid, $calendars = null, $getAll = false)
    {
        $query = 'SELECT event_id, event_uid, calendar_id, event_description,' .
            ' event_location, event_private, event_status, event_attendees,' .
            ' event_title, event_recurcount,' .
            ' event_recurtype, event_recurenddate, event_recurinterval,' .
            ' event_recurdays, event_start, event_end, event_allday,' .
            ' event_alarm, event_alarm_methods, event_modified,' .
            ' event_exceptions, event_creator_id' .
            ' FROM ' . $this->_params['table'] . ' WHERE event_uid = ?';
        $values = array($uid);

        /* Optionally filter by calendar */
        if (!is_null($calendars)) {
            if (!count($calendars)) {
                return PEAR::raiseError(_("No calendars to search"));
            }
            $query .= ' AND calendar_id IN (?' . str_repeat(', ?', count($calendars) - 1) . ')';
            $values = array_merge($values, $calendars);
        }

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Kronolith_Driver_sql::getByUID(): user = "%s"; query = "%s"; values = "%s"',
                                  Auth::getAuth(), $query, implode(',', $values)),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $events = $this->_db->getAll($query, $values, DB_FETCHMODE_ASSOC);
        if (is_a($events, 'PEAR_Error')) {
            Horde::logMessage($events, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $events;
        }
        if (!count($events)) {
            return PEAR::raiseError($uid . ' not found');
        }

        $eventArray = array();
        foreach ($events as $event) {
            $this->open($event['calendar_id']);
            $this->_cache[$this->_calendar][$event['event_id']] = new Kronolith_Event_sql($this, $event);
            $eventArray[] = $this->_cache[$this->_calendar][$event['event_id']];
        }

        if ($getAll) {
            return $eventArray;
        }

        /* First try the user's own calendars. */
        $ownerCalendars = Kronolith::listCalendars(true, PERMS_READ);
        $event = null;
        foreach ($eventArray as $ev) {
            if (isset($ownerCalendars[$ev->getCalendar()])) {
                $event = $ev;
                break;
            }
        }

        /* If not successful, try all calendars the user has access too. */
        if (empty($event)) {
            $readableCalendars = Kronolith::listCalendars(false, PERMS_READ);
            foreach ($eventArray as $ev) {
                if (isset($readableCalendars[$ev->getCalendar()])) {
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
     * Saves an event in the backend.
     * If it is a new event, it is added, otherwise the event is updated.
     *
     * @param Kronolith_Event $event  The event to save.
     */
    function saveEvent(&$event)
    {
        if ($event->isStored() || $event->exists()) {
            $values = array();

            $query = 'UPDATE ' . $this->_params['table'] . ' SET ';

            foreach ($event->getProperties() as $key => $val) {
                $query .= " $key = ?,";
                $values[] = $val;
            }
            $query = substr($query, 0, -1);
            $query .= ' WHERE event_id = ?';
            $values[] = $event->getId();

            /* Log the query at a DEBUG log level. */
            Horde::logMessage(sprintf('Kronolith_Driver_sql::saveEvent(): user = "%s"; query = "%s"; values = "%s"',
                                      Auth::getAuth(), $query, implode(',', $values)),
                              __FILE__, __LINE__, PEAR_LOG_DEBUG);

            $result = $this->_write_db->query($query, $values);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                return $result;
            }

            /* Log the modification of this item in the history log. */
            if ($event->getUID()) {
                $history = Horde_History::singleton();
                $history->log('kronolith:' . $this->_calendar . ':' . $event->getUID(), array('action' => 'modify'), true);
            }

            /* Update tags */
            $tagger = Kronolith::getTagger();
            $tagger->replaceTags($event->getUID(), $event->tags, 'event');

            /* Notify users about the changed event. */
            $result = Kronolith::sendNotification($event, 'edit');
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            }

            return $event->getId();
        } else {
            if ($event->getId()) {
                $id = $event->getId();
            } else {
                $id = hash('md5', uniqid(mt_rand(), true));
                $event->setId($id);
            }

            if ($event->getUID()) {
                $uid = $event->getUID();
            } else {
                $uid = $this->generateUID();
                $event->setUID($uid);
            }

            $query = 'INSERT INTO ' . $this->_params['table'];
            $cols_name = ' (event_id, event_uid,';
            $cols_values = ' VALUES (?, ?,';
            $values = array($id, $uid);

            foreach ($event->getProperties() as $key => $val) {
                $cols_name .= " $key,";
                $cols_values .= ' ?,';
                $values[] = $val;
            }

            $cols_name .= ' calendar_id)';
            $cols_values .= ' ?)';
            $values[] = $this->_calendar;

            $query .= $cols_name . $cols_values;

            /* Log the query at a DEBUG log level. */
            Horde::logMessage(sprintf('Kronolith_Driver_sql::saveEvent(): user = "%s"; query = "%s"; values = "%s"',
                                Auth::getAuth(), $query, implode(',', $values)),
                                __FILE__, __LINE__, PEAR_LOG_DEBUG);

            $result = $this->_write_db->query($query, $values);
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                return $result;
            }

            /* Log the creation of this item in the history log. */
            $history = Horde_History::singleton();
            $history->log('kronolith:' . $this->_calendar . ':' . $uid, array('action' => 'add'), true);

            /* Deal with any tags */
            $tagger = Kronolith::getTagger();
            $tagger->tag($event->getUID(), $event->tags, 'event');

            /* Notify users about the new event. */
            $result = Kronolith::sendNotification($event, 'add');
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            }

            return $id;
        }
    }

    /**
     * Move an event to a new calendar.
     *
     * @param string $eventId      The event to move.
     * @param string $newCalendar  The new calendar.
     */
    function move($eventId, $newCalendar)
    {
        /* Fetch the event for later use. */
        $event = $this->getEvent($eventId);
        if (is_a($event, 'PEAR_Error')) {
            return $event;
        }

        $query = 'UPDATE ' . $this->_params['table'] . ' SET calendar_id = ? WHERE calendar_id = ? AND event_id = ?';
        $values = array($newCalendar, $this->_calendar, $eventId);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Kronolith_Driver_sql::move(): %s; values = "%s"',
                                  $query, implode(',', $values)),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Attempt the move query. */
        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }

        /* Log the moving of this item in the history log. */
        $uid = $event->getUID();
        if ($uid) {
            $history = Horde_History::singleton();
            $history->log('kronolith:' . $this->_calendar . ':' . $uid, array('action' => 'delete'), true);
            $history->log('kronolith:' . $newCalendar . ':' . $uid, array('action' => 'add'), true);
        }

        return true;
    }

    /**
     * Delete a calendar and all its events.
     *
     * @param string $calendar  The name of the calendar to delete.
     *
     * @return mixed  True or a PEAR_Error on failure.
     */
    function delete($calendar)
    {
        $query = 'DELETE FROM ' . $this->_params['table'] . ' WHERE calendar_id = ?';
        $values = array($calendar);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Kronolith_Driver_sql::delete(): user = "%s"; query = "%s"; values = "%s"',
                                  Auth::getAuth(), $query, implode(',', $values)),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        return $this->_write_db->query($query, $values);
    }

    /**
     * Delete an event.
     *
     * @param string $eventId  The ID of the event to delete.
     * @param boolean $silent  Don't send notifications, used when deleting
     *                         events in bulk from maintenance tasks.
     *
     * @return mixed  True or a PEAR_Error on failure.
     */
    function deleteEvent($eventId, $silent = false)
    {
        /* Fetch the event for later use. */
        $event = $this->getEvent($eventId);
        if (is_a($event, 'PEAR_Error')) {
            return $event;
        }

        $query = 'DELETE FROM ' . $this->_params['table'] . ' WHERE event_id = ? AND calendar_id = ?';
        $values = array($eventId, $this->_calendar);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Kronolith_Driver_sql::deleteEvent(): user = "%s"; query = "%s"; values = "%s"',
                                  Auth::getAuth(), $query, implode(',', $values)),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        $result = $this->_write_db->query($query, $values);
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            return $result;
        }

        /* Log the deletion of this item in the history log. */
        if ($event->getUID()) {
            $history = Horde_History::singleton();
            $history->log('kronolith:' . $this->_calendar . ':' . $event->getUID(), array('action' => 'delete'), true);
        }

        /* Remove any pending alarms. */
        if (@include_once 'Horde/Alarm.php') {
            $alarm = Horde_Alarm::factory();
            $alarm->delete($event->getUID());
        }

        /* Remove any tags */
        $tagger = Kronolith::getTagger();
        $tagger->replaceTags($event->getUID(), array(), 'event');

        /* Notify about the deleted event. */
        if (!$silent) {
            $result = Kronolith::sendNotification($event, 'delete');
            if (is_a($result, 'PEAR_Error')) {
                Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
            }
        }
        return true;
    }

    /**
     * Attempts to open a connection to the SQL server.
     *
     * @return boolean True.
     */
    function initialize()
    {
        Horde::assertDriverConfig($this->_params, 'calendar',
            array('phptype'));

        if (!isset($this->_params['database'])) {
            $this->_params['database'] = '';
        }
        if (!isset($this->_params['username'])) {
            $this->_params['username'] = '';
        }
        if (!isset($this->_params['hostspec'])) {
            $this->_params['hostspec'] = '';
        }
        if (!isset($this->_params['table'])) {
            $this->_params['table'] = 'kronolith_events';
        }

        /* Connect to the SQL server using the supplied parameters. */
        $this->_write_db = DB::connect($this->_params,
                                       array('persistent' => !empty($this->_params['persistent'])));
        if (is_a($this->_write_db, 'PEAR_Error')) {
            return $this->_write_db;
        }
        $this->_initConn($this->_write_db);

        /* Check if we need to set up the read DB connection
         * seperately. */
        if (!empty($this->_params['splitread'])) {
            $params = array_merge($this->_params, $this->_params['read']);
            $this->_db = DB::connect($params,
                                     array('persistent' => !empty($params['persistent'])));
            if (is_a($this->_db, 'PEAR_Error')) {
                return $this->_db;
            }
            $this->_initConn($this->_db);
        } else {
            /* Default to the same DB handle for the writer too. */
            $this->_db = $this->_write_db;
        }

        return true;
    }

    /**
     */
    function _initConn(&$db)
    {
        // Set DB portability options.
        switch ($db->phptype) {
        case 'mssql':
            $db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
            break;
        default:
            $db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
        }

        /* Handle any database specific initialization code to run. */
        switch ($db->dbsyntax) {
        case 'oci8':
            $query = "ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'";

            /* Log the query at a DEBUG log level. */
            Horde::logMessage(sprintf('Kronolith_Driver_sql::_initConn(): user = "%s"; query = "%s"',
                                      Auth::getAuth(), $query),
                              __FILE__, __LINE__, PEAR_LOG_DEBUG);

            $db->query($query);
            break;

        case 'pgsql':
            $query = "SET datestyle TO 'iso'";

            /* Log the query at a DEBUG log level. */
            Horde::logMessage(sprintf('Kronolith_Driver_sql::_initConn(): user = "%s"; query = "%s"',
                                      Auth::getAuth(), $query),
                              __FILE__, __LINE__, PEAR_LOG_DEBUG);

            $db->query($query);
            break;
        }
    }

    /**
     * Converts a value from the driver's charset to the default
     * charset.
     *
     * @param mixed $value  A value to convert.
     *
     * @return mixed  The converted value.
     */
    function convertFromDriver($value)
    {
        return String::convertCharset($value, $this->_params['charset']);
    }

    /**
     * Converts a value from the default charset to the driver's
     * charset.
     *
     * @param mixed $value  A value to convert.
     *
     * @return mixed  The converted value.
     */
    function convertToDriver($value)
    {
        return String::convertCharset($value, NLS::getCharset(), $this->_params['charset']);
    }

    /**
     * Remove all events owned by the specified user in all calendars.
     *
     *
     * @param string $user  The user name to delete events for.
     *
     * @param mixed  True | PEAR_Error
     */
    function removeUserData($user)
    {
        if (!Auth::isAdmin()) {
            return PEAR::raiseError(_("Permission Denied"));
        }

        $shares = $GLOBALS['kronolith_shares']->listShares($user, PERMS_EDIT);
        if (is_a($shares, 'PEAR_Error')) {
            return $shares;
        }

        foreach (array_keys($shares) as $calendar) {
            $ids = Kronolith::listEventIds(null, null, $calendar);
            if (is_a($ids, 'PEAR_Error')) {
                return $ids;
            }
            $uids = array();
            foreach ($ids as $cal) {
                $uids = array_merge($uids, array_keys($cal));
            }

            foreach ($uids as $uid) {
                $event = $this->getByUID($uid);
                if (is_a($event, 'PEAR_Error')) {
                    return $event;
                }

                $this->deleteEvent($event->getId());
            }
        }
        return true;
    }
}

/**
 * @package Kronolith
 */
class Kronolith_Event_sql extends Kronolith_Event {

    /**
     * @var array
     */
    var $_properties = array();

    function fromDriver($SQLEvent)
    {
        $driver = $this->getDriver();

        $this->allday = (bool)$SQLEvent['event_allday'];
        if (!$this->allday && $driver->getParam('utc')) {
            $tz_local = date_default_timezone_get();
            $this->start = new Horde_Date($SQLEvent['event_start'], 'UTC');
            $this->start->setTimezone($tz_local);
            $this->end = new Horde_Date($SQLEvent['event_end'], 'UTC');
            $this->end->setTimezone($tz_local);
        } else {
            $this->start = new Horde_Date($SQLEvent['event_start']);
            $this->end = new Horde_Date($SQLEvent['event_end']);
        }

        $this->durMin = ($this->end->timestamp() - $this->start->timestamp()) / 60;

        $this->title = $driver->convertFromDriver($SQLEvent['event_title']);
        $this->eventID = $SQLEvent['event_id'];
        $this->setUID($SQLEvent['event_uid']);
        $this->creatorID = $SQLEvent['event_creator_id'];

        if (!empty($SQLEvent['event_recurtype'])) {
            $this->recurrence = new Horde_Date_Recurrence($this->start);
            $this->recurrence->setRecurType((int)$SQLEvent['event_recurtype']);
            $this->recurrence->setRecurInterval((int)$SQLEvent['event_recurinterval']);
            if (isset($SQLEvent['event_recurenddate'])) {
                if ($driver->getParam('utc')) {
                    $recur_end = new Horde_Date($SQLEvent['event_recurenddate'], 'UTC');
                    if ($recur_end->min == 0) {
                        /* Old recurrence end date format. */
                        $recur_end = new Horde_Date($SQLEvent['event_recurenddate']);
                        $recur_end->hour = 23;
                        $recur_end->min = 59;
                        $recur_end->sec = 59;
                    } else {
                        $recur_end->setTimezone(date_default_timezone_get());
                    }
                } else {
                    $recur_end = new Horde_Date($SQLEvent['event_recurenddate']);
                    $recur_end->hour = 23;
                    $recur_end->min = 59;
                    $recur_end->sec = 59;
                }
                $this->recurrence->setRecurEnd($recur_end);
            }
            if (isset($SQLEvent['event_recurcount'])) {
                $this->recurrence->setRecurCount((int)$SQLEvent['event_recurcount']);
            }
            if (isset($SQLEvent['event_recurdays'])) {
                $this->recurrence->recurData = (int)$SQLEvent['event_recurdays'];
            }
            if (!empty($SQLEvent['event_exceptions'])) {
                $this->recurrence->exceptions = explode(',', $SQLEvent['event_exceptions']);
            }
        }

        if (isset($SQLEvent['event_location'])) {
            $this->location = $driver->convertFromDriver($SQLEvent['event_location']);
        }
        if (isset($SQLEvent['event_private'])) {
            $this->private = (bool)($SQLEvent['event_private']);
        }
        if (isset($SQLEvent['event_status'])) {
            $this->status = $SQLEvent['event_status'];
        }
        if (isset($SQLEvent['event_attendees'])) {
            $this->attendees = array_change_key_case($driver->convertFromDriver(unserialize($SQLEvent['event_attendees'])));
        }
        if (isset($SQLEvent['event_description'])) {
            $this->description = $driver->convertFromDriver($SQLEvent['event_description']);
        }
        if (isset($SQLEvent['event_alarm'])) {
            $this->alarm = (int)$SQLEvent['event_alarm'];
        }
        if (isset($SQLEvent['event_alarm_methods'])) {
            $this->methods = $driver->convertFromDriver(unserialize($SQLEvent['event_alarm_methods']));
        }
        $this->initialized = true;
        $this->stored = true;
    }

    function toDriver()
    {
        $driver = $this->getDriver();

        /* Basic fields. */
        $this->_properties['event_creator_id'] = $driver->convertToDriver($this->getCreatorId());
        $this->_properties['event_title'] = $driver->convertToDriver($this->title);
        $this->_properties['event_description'] = $driver->convertToDriver($this->getDescription());
        $this->_properties['event_location'] = $driver->convertToDriver($this->getLocation());
        $this->_properties['event_private'] = (int)$this->isPrivate();
        $this->_properties['event_status'] = $this->getStatus();
        $this->_properties['event_attendees'] = serialize($driver->convertToDriver($this->getAttendees()));
        $this->_properties['event_modified'] = $_SERVER['REQUEST_TIME'];

        if ($this->isAllDay()) {
            $this->_properties['event_start'] = $this->start->strftime('%Y-%m-%d %H:%M:%S');
            $this->_properties['event_end'] = $this->end->strftime('%Y-%m-%d %H:%M:%S');
            $this->_properties['event_allday'] = 1;
        } else {
            if ($driver->getParam('utc')) {
                $start = clone $this->start;
                $end = clone $this->end;
                $start->setTimezone('UTC');
                $end->setTimezone('UTC');
            } else {
                $start = $this->start;
                $end = $this->end;
            }
            $this->_properties['event_start'] = $start->strftime('%Y-%m-%d %H:%M:%S');
            $this->_properties['event_end'] = $end->strftime('%Y-%m-%d %H:%M:%S');
            $this->_properties['event_allday'] = 0;
        }

        /* Alarm. */
        $this->_properties['event_alarm'] = (int)$this->getAlarm();

        /* Alarm Notification Methods. */
        $this->_properties['event_alarm_methods'] = serialize($driver->convertToDriver($this->methods));

        /* Recurrence. */
        if (!$this->recurs()) {
            $this->_properties['event_recurtype'] = 0;
        } else {
            $recur = $this->recurrence->getRecurType();
            if ($this->recurrence->hasRecurEnd()) {
                if ($driver->getParam('utc')) {
                    $recur_end = clone $this->recurrence->recurEnd;
                    $recur_end->setTimezone('UTC');
                } else {
                    $recur_end = $this->recurrence->recurEnd;
                }
            } else {
                $recur_end = new Horde_Date(array('year' => 9999, 'month' => 12, 'mday' => 31, 'hour' => 23, 'min' => 59, 'sec' => 59));
            }

            $this->_properties['event_recurtype'] = $recur;
            $this->_properties['event_recurinterval'] = $this->recurrence->getRecurInterval();
            $this->_properties['event_recurenddate'] = $recur_end->format('Y-m-d H:i:s');
            $this->_properties['event_recurcount'] = $this->recurrence->getRecurCount();

            switch ($recur) {
            case Horde_Date_Recurrence::RECUR_WEEKLY:
                $this->_properties['event_recurdays'] = $this->recurrence->getRecurOnDays();
                break;
            }
            $this->_properties['event_exceptions'] = implode(',', $this->recurrence->getExceptions());
        }
    }

    function getProperties()
    {
        return $this->_properties;
    }

}
