<?php
/**
 * The Kronolith_Driver_Ical class implements the Kronolith_Driver API for
 * iCalendar data.
 *
 * Possible driver parameters:
 * - url:      The location of the remote calendar.
 * - proxy:    A hash with HTTP proxy information.
 * - user:     The user name for HTTP Basic Authentication.
 * - password: The password for HTTP Basic Authentication.
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @todo Replace session cache
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_Driver_Ical extends Kronolith_Driver
{
    /**
     * Cache events as we fetch them to avoid fetching or parsing the same
     * event twice.
     *
     * @var array
     */
    private $_cache = array();

    public function listAlarms($date, $fullevent = false)
    {
        return array();
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
    public function listEvents($startDate = null, $endDate = null,
                               $hasAlarm = false)
    {
        $data = $this->_getRemoteCalendar();
        if (is_a($data, 'PEAR_Error')) {
            return $data;
        }

        $iCal = new Horde_iCalendar();
        if (!$iCal->parsevCalendar($data)) {
            return array();
        }

        $components = $iCal->getComponents();
        $events = array();
        $count = count($components);
        $exceptions = array();
        for ($i = 0; $i < $count; $i++) {
            $component = $components[$i];
            if ($component->getType() == 'vEvent') {
                $event = new Kronolith_Event_Ical($this);
                $event->status = Kronolith::STATUS_FREE;
                $event->fromiCalendar($component);
                $event->remoteCal = $this->_calendar;
                $event->eventID = $i;

                /* Catch RECURRENCE-ID attributes which mark single recurrence
                 * instances. */
                $recurrence_id = $component->getAttribute('RECURRENCE-ID');
                if (is_int($recurrence_id) &&
                    is_string($uid = $component->getAttribute('UID')) &&
                    is_int($seq = $component->getAttribute('SEQUENCE'))) {
                    $exceptions[$uid][$seq] = $recurrence_id;
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

                $events[] = $event;
            }
        }

        /* Loop through all explicitly defined recurrence intances and create
         * exceptions for those in the event with the matchin recurrence. */
        foreach ($events as $key => $event) {
            if ($event->recurs() &&
                isset($exceptions[$event->getUID()][$event->getSequence()])) {
                $timestamp = $exceptions[$event->getUID()][$event->getSequence()];
                $events[$key]->recurrence->addException(date('Y', $timestamp), date('m', $timestamp), date('d', $timestamp));
            }
        }

        return $events;
    }

    public function getEvent($eventId = null)
    {
        $data = $this->_getRemoteCalendar();
        if (is_a($data, 'PEAR_Error')) {
            return $data;
        }

        $iCal = new Horde_iCalendar();
        if (!$iCal->parsevCalendar($data)) {
            return array();
        }

        $components = $iCal->getComponents();
        if (isset($components[$eventId]) &&
            $components[$eventId]->getType() == 'vEvent') {
            $event = new Kronolith_Event_Ical($this);
            $event->status = Kronolith::STATUS_FREE;
            $event->fromiCalendar($components[$eventId]);
            $event->remoteCal = $this->_calendar;
            $event->eventID = $eventId;

            return $event;
        }

        return false;
    }

    /**
     * Fetches a remote calendar into the session and return the data.
     *
     * @return mixed  Either the calendar data, or an error on failure.
     */
    private function _getRemoteCalendar()
    {
        $url = trim($this->_calendar);

        /* Treat webcal:// URLs as http://. */
        if (substr($url, 0, 9) == 'webcal://') {
            $url = str_replace('webcal://', 'http://', $url);
        }

        if (empty($_SESSION['kronolith']['remote'][$url])) {
            $options['method'] = 'GET';
            $options['timeout'] = 5;
            $options['allowRedirects'] = true;

            if (isset($this->_params['proxy'])) {
                $options = array_merge($options, $this->_params['proxy']);
            }

            $http = new HTTP_Request($url, $options);
            if (!empty($this->_params['user'])) {
                $http->setBasicAuth($this->_params['user'],
                                    $this->_params['password']);
            }
            @$http->sendRequest();
            if ($http->getResponseCode() != 200) {
                Horde::logMessage(sprintf('Failed to retrieve remote calendar: url = "%s", status = %s',
                                          $url, $http->getResponseCode()),
                                  __FILE__, __LINE__, PEAR_LOG_ERR);
                return PEAR::raiseError(sprintf(_("Could not open %s."), $url));
            }
            $_SESSION['kronolith']['remote'][$url] = $http->getResponseBody();

            /* Log fetch at DEBUG level. */
            Horde::logMessage(sprintf('Retrieved remote calendar for %s: url = "%s"',
                                      Auth::getAuth(), $url),
                              __FILE__, __LINE__, PEAR_LOG_DEBUG);
        }

        return $_SESSION['kronolith']['remote'][$url];
    }

}
