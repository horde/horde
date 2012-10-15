<?php
/**
 * The Kronolith_Driver_Horde class implements the Kronolith_Driver API for
 * time objects retrieved from other Horde applications.
 *
 * Possible driver parameters:
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_Driver_Horde extends Kronolith_Driver
{
    /**
     * The API (application) of the current calendar.
     *
     * @var string
     */
    public $api;

    public function open($calendar)
    {
        parent::open($calendar);
        list($this->api,) = explode('/', $this->calendar, 2);
    }

    public function listAlarms($date, $fullevent = false)
    {
        return array();
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
        list($this->api, $category) = explode('/', $this->calendar, 2);
        if (!$this->_params['registry']->hasMethod($this->api . '/listTimeObjects')) {
            return array();
        }

        if (is_null($startDate)) {
            $startDate = new Horde_Date(
                array('mday' => 1, 'month' => 1, 'year' => 0000));
        }
        if (is_null($endDate)) {
            $endDate = new Horde_Date(
                array('mday' => 31, 'month' => 12, 'year' => 9999));
        }

        $startDate = clone $startDate;
        $startDate->hour = $startDate->min = $startDate->sec = 0;
        $endDate = clone $endDate;
        $endDate->hour = 23;
        $endDate->min = $endDate->sec = 59;

        try {
            $eventsList = $this->_params['registry']->call(
                $this->api . '/listTimeObjects',
                array(array($category), $startDate, $endDate));
        } catch (Horde_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        $results = array();
        foreach ($eventsList as $eventsListItem) {
            $event = new Kronolith_Event_Horde($this, $eventsListItem);
            // Ignore events out of the period.
            if (
                // Starts after the period.
                $event->start->compareDateTime($endDate) > 0 ||
                // End before the period and doesn't recur.
                (!$event->recurs() &&
                 $event->end->compareDateTime($startDate) < 0) ||
                // Recurs and ...
                ($event->recurs() &&
                 // ... has a recurrence end before the period.
                 ($event->recurrence->hasRecurEnd() &&
                  $event->recurrence->recurEnd->compareDateTime($startDate) < 0))) {
                continue;
            }

            Kronolith::addEvents(
                $results, $event, $startDate, $endDate,
                $options['show_recurrence'], $options['json'], $options['cover_dates']);
        }

        return $results;
    }

    /**
     * Updates an existing event in the backend.
     *
     * @param Kronolith_Event_Horde $event  The event to save.
     *
     * @return string  The event id.
     * @throws Kronolith_Exception
     */
    protected function _updateEvent(Kronolith_Event $event)
    {
        if (!isset($this->api)) {
            list($this->api,) = explode('/', $this->calendar, 2);
        }
        try {
            $this->_params['registry']->call($this->api . '/saveTimeObject', array($event->toTimeobject()));
        } catch (Horde_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        return $event->timeobject['id'];
    }

    /**
     * @todo: implement getTimeObject in timeobjects API.
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getEvent($eventId = null, $start = null)
    {
        $end = null;
        if ($start) {
            $start = new Horde_Date($start);
            $end = clone $start;
            $end->mday++;
        }

        $events = $this->listEvents(
            $start,
            $end,
            array('show_recurrence' => (bool)$start));
        foreach ($events as $day) {
            if (isset($day[$eventId])) {
                return $day[$eventId];
            }
        }

        throw new Horde_Exception_NotFound(_("Event not found"));
    }

}
