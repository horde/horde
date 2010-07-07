<?php
/**
 * The Kronolith_Driver_Horde class implements the Kronolith_Driver API for
 * time objects retrieved from other Horde applications.
 *
 * Possible driver parameters:
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
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
     * @throws Kronolith_Exception
     */
    public function listEvents($startDate = null, $endDate = null,
                               $showRecurrence = false, $hasAlarm = false,
                               $json = false, $coverDates = true)
    {
        list($this->api, $category) = explode('/', $this->calendar, 2);
        if (!$this->_params['registry']->hasMethod($this->api . '/listTimeObjects')) {
            return array();
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

        $eventsList = $this->_params['registry']->call($this->api . '/listTimeObjects', array(array($category), $startDate, $endDate));

        $results = array();
        foreach ($eventsList as $eventsListItem) {
            $event = new Kronolith_Event_Horde($this, $eventsListItem);

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

            Kronolith::addEvents($results, $event, $startDate,
                                 $endDate, $showRecurrence, $json, $coverDates);
        }

        return $results;
    }

    /**
     * Saves an (existing) event in the backend.
     *
     * @param Kronolith_Event_Horde $event  The event to save.
     *
     * @return integer  The event id.
     * @throws Horde_Exception
     */
    public function saveEvent($event)
    {
        if (!isset($this->api)) {
            list($this->api, $category) = explode('/', $this->calendar, 2);
        }
        $this->_params['registry']->call($this->api . '/saveTimeObject', array($event->timeobject));
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

        $events = $this->listEvents($start, $end, (bool)$start);
        foreach ($events as $day) {
            if (isset($day[$eventId])) {
                return $day[$eventId];
            }
        }

        throw new Horde_Exception_NotFound(_("Event not found"));
    }

}
