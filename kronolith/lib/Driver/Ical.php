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
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
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
    protected $_cache = array();

    /**
     * HTTP client object.
     *
     * @var Horde_Http_Client
     */
    protected $_client;

    /**
     * A list of DAV support levels.
     *
     * @var array
     */
    protected $_davSupport;

    /**
     * The Horde_Perms permissions mask matching the CalDAV ACL.
     *
     * @var integer
     */
    protected $_permission;

    /**
     * Selects a calendar as the currently opened calendar.
     *
     * @param string $calendar  A calendar identifier.
     */
    public function open($calendar)
    {
        parent::open($calendar);
        $this->_client = null;
        unset($this->_davSupport, $this->_permission);
    }

    /**
     * Returns the background color of the current calendar.
     *
     * @return string  The calendar color.
     */
    public function backgroundColor()
    {
        return empty($GLOBALS['all_remote_calendars'][$this->calendar])
            ? '#dddddd'
            : $GLOBALS['all_remote_calendars'][$this->calendar]->background();
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
        if ($this->isCalDAV()) {
            return $this->_listCalDAVEvents(
                $startDate, $endDate, $options['show_recurrence'],
                $options['has_alarm'], $options['json'],
                $options['cover_dates']);
        }
        return $this->_listWebDAVEvents(
            $startDate, $endDate, $options['show_recurrence'],
            $options['has_alarm'], $options['json'],
            $options['cover_dates']);
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
    protected function _listWebDAVEvents($startDate = null, $endDate = null,
                                         $showRecurrence = false,
                                         $hasAlarm = false, $json = false,
                                         $coverDates = true)
    {
        $ical = $this->getRemoteCalendar();

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

        $results = array();
        $this->_processComponents($results, $ical, $startDate, $endDate,
                                  $showRecurrence, $json, $coverDates);

        return $results;
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
    protected function _listCalDAVEvents($startDate = null, $endDate = null,
                                         $showRecurrence = false,
                                         $hasAlarm = false, $json = false,
                                         $coverDates = true)
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

        /* Build report query. */
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument();
        $xml->startElementNS('C', 'calendar-query', 'urn:ietf:params:xml:ns:caldav');
        $xml->writeAttribute('xmlns:D', 'DAV:');
        $xml->startElement('D:prop');
        $xml->writeElement('D:getetag');
        $xml->startElement('C:calendar-data');
        $xml->startElement('C:comp');
        $xml->writeAttribute('name', 'VCALENDAR');
        $xml->startElement('C:comp');
        $xml->writeAttribute('name', 'VEVENT');
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $xml->endElement();
        $xml->startElement('C:filter');
        $xml->startElement('C:comp-filter');
        $xml->writeAttribute('name', 'VCALENDAR');
        $xml->startElement('C:comp-filter');
        $xml->writeAttribute('name', 'VEVENT');
        if (!is_null($startDate) ||
            !is_null($endDate)) {
            $xml->startElement('C:time-range');
            if (!is_null($startDate)) {
                $xml->writeAttribute('start', $startDate->toiCalendar());
            }
            if (!is_null($endDate)) {
                $xml->writeAttribute('end', $endDate->toiCalendar());
            }
        }
        $xml->endDocument();

        $url = $this->_getUrl();
        list($response, $events) = $this->_request('REPORT', $url, $xml,
                                          array('Depth' => 1));
        if (!$events->children('DAV:')->response) {
            return array();
        }
        if (!($path = $response->getHeader('content-location'))) {
            $parsedUrl = parse_url($url);
            $path = $parsedUrl['path'];
        }

        $results = array();
        foreach ($events->children('DAV:')->response as $response) {
            if (!$response->children('DAV:')->propstat) {
                continue;
            }
            $ical = new Horde_Icalendar();
            try {
                $ical->parsevCalendar($response->children('DAV:')->propstat->prop->children('urn:ietf:params:xml:ns:caldav')->{'calendar-data'});
            } catch (Horde_Icalendar_Exception $e) {
                throw new Kronolith_Exception($e);
            }
            $this->_processComponents($results, $ical, $startDate, $endDate,
                                      $showRecurrence, $json, $coverDates,
                                      trim(str_replace($path, '', $response->href), '/'));
        }

        return $results;
    }

    /**
     * Processes the components of a Horde_Icalendar container into an event
     * list.
     *
     * @param array $results             Gets filled with the events in the
     *                                   given time range.
     * @param Horde_Icalendar $ical      An Horde_Icalendar container.
     * @param Horde_Date $startInterval  Start of range date.
     * @param Horde_Date $endInterval    End of range date.
     * @param boolean $showRecurrence    Return every instance of a recurring
     *                                   event? If false, will only return
     *                                   recurring events once inside the
     *                                   $startDate - $endDate range.
     * @param boolean $json              Store the results of the events'
     *                                   toJson() method?
     * @param boolean $coverDates        Whether to add the events to all days
     *                                   that they cover.
     * @param string $id                 Enforce a certain event id (not UID).
     *
     * @throws Kronolith_Exception
     */
    protected function _processComponents(&$results, $ical, $startDate,
                                          $endDate, $showRecurrence, $json,
                                          $coverDates, $id = null)
    {
        $components = $ical->getComponents();
        $events = array();
        $count = count($components);
        $exceptions = array();
        for ($i = 0; $i < $count; $i++) {
            $component = $components[$i];
            if ($component->getType() == 'vEvent') {
                $event = new Kronolith_Event_Ical($this);
                $event->status = Kronolith::STATUS_FREE;
                $event->permission = $this->getPermission();
                $event->fromDriver($component);
                // Force string so JSON encoding is consistent across drivers.
                $event->id = $id ? $id : 'ical' . $i;

                /* Catch RECURRENCE-ID attributes which mark single recurrence
                 * instances. */
                try {
                    $recurrence_id = $component->getAttribute('RECURRENCE-ID');
                    if (is_int($recurrence_id) &&
                        is_string($uid = $component->getAttribute('UID')) &&
                        is_int($seq = $component->getAttribute('SEQUENCE'))) {
                        $exceptions[$uid][$seq] = $recurrence_id;
                        $event->id .= '/' . $recurrence_id;
                    }
                } catch (Horde_Icalendar_Exception $e) {}

                /* Ignore events out of the period. */
                if (
                    /* Starts after the period. */
                    ($endDate && $event->start->compareDateTime($endDate) > 0) ||
                    /* End before the period and doesn't recur. */
                    ($startDate && !$event->recurs() &&
                     $event->end->compareDateTime($startDate) < 0) ||
                    /* Recurs and ... */
                    ($startDate && $event->recurs() &&
                      /* ... has a recurrence end before the period. */
                      ($event->recurrence->hasRecurEnd() &&
                       $event->recurrence->recurEnd->compareDateTime($startDate) < 0))) {
                    continue;
                }

                $events[] = $event;
            }
        }

        /* Loop through all explicitly defined recurrence intances and create
         * exceptions for those in the event with the matching recurrence. */
        foreach ($events as $key => $event) {
            if ($event->recurs() &&
                isset($exceptions[$event->uid][$event->sequence])) {
                $timestamp = $exceptions[$event->uid][$event->sequence];
                $events[$key]->recurrence->addException(date('Y', $timestamp), date('m', $timestamp), date('d', $timestamp));
            }
            Kronolith::addEvents($results, $event, $startDate, $endDate,
                                 $showRecurrence, $json, $coverDates);
        }
    }

    /**
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     */
    public function getEvent($eventId = null)
    {
        if (!$eventId) {
            $event = new Kronolith_Event_Ical($this);
            $event->permission = $this->getPermission();
            return $event;
        }

        if ($this->isCalDAV()) {
            if (preg_match('/(.*)-(\d+)$/', $eventId, $matches)) {
                $eventId = $matches[1];
                //$recurrenceId = $matches[2];
            }
            $url = trim($this->_getUrl(), '/') . '/' . $eventId;
            $response = $this->_getClient()->get($url);
            if ($response->code == 200) {
                $ical = new Horde_Icalendar();
                try {
                    $ical->parsevCalendar($response->getBody());
                } catch (Horde_Icalendar_Exception $e) {
                    throw new Kronolith_Exception($e);
                }
                $results = array();
                $this->_processComponents($results, $ical, null, null, false,
                                          false, false, $eventId);
                $event = reset(reset($results));
                if (!$event) {
                    throw new Horde_Exception_NotFound(_("Event not found"));
                }
                return $event;
            }
        }

        $eventId = str_replace('ical', '', $eventId);
        $ical = $this->getRemoteCalendar();
        $components = $ical->getComponents();
        if (isset($components[$eventId]) &&
            $components[$eventId]->getType() == 'vEvent') {
            $event = new Kronolith_Event_Ical($this);
            $event->status = Kronolith::STATUS_FREE;
            $event->permission = $this->getPermission();
            $event->fromDriver($components[$eventId]);
            $event->id = 'ical' . $eventId;
            return $event;
        }

        throw new Horde_Exception_NotFound(_("Event not found"));
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
        $response = $this->_saveEvent($event);
        if (!in_array($response->code, array(200, 204))) {
            Horde::logMessage(sprintf('Failed to update event on remote calendar: url = "%s", status = %s',
                                      $response->url, $response->code), 'INFO');
            throw new Kronolith_Exception(_("The event could not be updated on the remote server."));
        }
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
        if (!$event->uid) {
            $event->uid = (string)new Horde_Support_Uuid;
        }
        if (!$event->id) {
            $event->id = $event->uid . '.ics';
        }

        $response = $this->_saveEvent($event);
        if (!in_array($response->code, array(200, 201, 204))) {
            Horde::logMessage(sprintf('Failed to create event on remote calendar: status = %s',
                                      $response->code), 'INFO');
            throw new Kronolith_Exception(_("The event could not be added to the remote server."));
        }
        return $event->id;
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
    protected function _saveEvent($event)
    {
        $ical = new Horde_Icalendar();
        $ical->addComponent($event->toiCalendar($ical));

        $url = trim($this->_getUrl(), '/') . '/' . $event->id;
        try {
            $response = $this->_getClient()->put($url, $ical->exportvCalendar(), array('Content-Type' => 'text/calendar'));
        } catch (Horde_Http_Exception $e) {
            Horde::logMessage($e, 'INFO');
            throw new Kronolith_Exception($e);
        }
        return $response;
    }

    /**
     * Deletes an event.
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
        if ($eventId instanceof Kronolith_Event) {
            $eventId = $eventId->id;
        }
        if (!$this->isCalDAV()) {
            throw new Kronolith_Exception(_("Deleting events is not supported with this remote calendar."));
        }

        if (preg_match('/(.*)-(\d+)$/', $eventId)) {
            throw new Kronolith_Exception(_("Cannot delete exceptions (yet)."));
        }

        $url = trim($this->_getUrl(), '/') . '/' . $eventId;
        try {
            $response = $this->_getClient()->delete($url);
        } catch (Horde_Http_Exception $e) {
            Horde::logMessage($e, 'INFO');
            throw new Kronolith_Exception($e);
        }
        if (!in_array($response->code, array(200, 202, 204))) {
            Horde::logMessage(sprintf('Failed to delete event from remote calendar: url = "%s", status = %s',
                                      $url, $response->code), 'INFO');
            throw new Kronolith_Exception(_("The event could not be deleted from the remote server."));
        }
    }

    /**
     * Fetches a remote calendar into the cache and return the data.
     *
     * @param boolean $cache  Whether to return data from the cache.
     *
     * @return Horde_Icalendar  The calendar data.
     * @throws Kronolith_Exception
     */
    public function getRemoteCalendar($cache = true)
    {
        $url = $this->_getUrl();
        $cacheOb = $GLOBALS['injector']->getInstance('Horde_Cache');
        $cacheVersion = 2;
        $signature = 'kronolith_remote_'  . $cacheVersion . '_' . $url . '_' . serialize($this->_params);
        if ($cache) {
            $calendar = $cacheOb->get($signature, 3600);
            if ($calendar) {
                $calendar = unserialize($calendar);
                if (!is_object($calendar)) {
                    throw new Kronolith_Exception($calendar);
                }
                return $calendar;
            }
        }

        $http = $this->_getClient();
        try {
            $response = $http->get($url);
        } catch (Horde_Http_Exception $e) {
            Horde::logMessage($e, 'INFO');
            if ($cache) {
                $cacheOb->set($signature, serialize($e->getMessage()));
            }
            throw new Kronolith_Exception($e);
        }
        if ($response->code != 200) {
            Horde::logMessage(sprintf('Failed to retrieve remote calendar: url = "%s", status = %s',
                                      $url, $response->code), 'INFO');
            $error = sprintf(_("Could not open %s."), $url);
            $body = $response->getBody();
            if ($body) {
                $error .= ' ' . _("This is what the server said:")
                    . ' ' . Horde_String::truncate(strip_tags($body));
            }
            if ($cache) {
                $cacheOb->set($signature, serialize($error));
            }
            throw new Kronolith_Exception($error, $response->code);
        }

        /* Log fetch at DEBUG level. */
        Horde::logMessage(sprintf('Retrieved remote calendar for %s: url = "%s"',
                                  $GLOBALS['registry']->getAuth(), $url), 'DEBUG');

        $data = $response->getBody();
        $ical = new Horde_Icalendar();
        try {
            $ical->parsevCalendar($data);
        } catch (Horde_Icalendar_Exception $e) {
            if ($cache) {
                $cacheOb->set($signature, serialize($e->getMessage()));
            }
            throw new Kronolith_Exception($e);
        }

        if ($cache) {
            $cacheOb->set($signature, serialize($ical));
        }

        return $ical;
    }

    /**
     * Returns whether the remote calendar is a CalDAV server, and propagates
     * the $_davSupport propery with the server's DAV capabilities.
     *
     * @return boolean  True if the remote calendar is a CalDAV server.
     * @throws Kronolith_Exception
     */
    public function isCalDAV()
    {
        if (isset($this->_davSupport)) {
            return $this->_davSupport
                ? in_array('calendar-access', $this->_davSupport)
                : false;
        }

        $url = $this->_getUrl();
        $http = $this->_getClient();
        try {
            $response = $http->request('OPTIONS', $url);
        } catch (Horde_Http_Exception $e) {
            Horde::logMessage($e, 'INFO');
            return false;
        }
        if ($response->code != 200) {
            $this->_davSupport = false;
            return false;
        }

        if ($dav = $response->getHeader('dav')) {
            /* Check for DAV support. */
            if (is_array($dav)) {
                $dav = implode (',', $dav);
            }
            $this->_davSupport = preg_split('/,\s*/', $dav);
            if (!in_array('3', $this->_davSupport)) {
                Horde::logMessage(sprintf('The remote server at %s doesn\'t support an WebDAV protocol version 3.', $url), 'WARN');
            }
            if (!in_array('calendar-access', $this->_davSupport)) {
                return false;
            }

            /* Check if this URL is a collection. */
            $xml = new XMLWriter();
            $xml->openMemory();
            $xml->startDocument();
            $xml->startElement('propfind');
            $xml->writeAttribute('xmlns', 'DAV:');
            $xml->startElement('prop');
            $xml->writeElement('resourcetype');
            $xml->writeElement('current-user-privilege-set');
            $xml->endDocument();
            list(, $properties) = $this->_request('PROPFIND', $url, $xml,
                                                  array('Depth' => 0));
            if (!$properties->children('DAV:')->response->propstat->prop->resourcetype->collection) {
                throw new Kronolith_Exception(_("The remote server URL does not point to a CalDAV directory."));
            }

            /* Read ACLs. */
            if ($properties->children('DAV:')->response->propstat->prop->{'current-user-privilege-set'}) {
                foreach ($properties->children('DAV:')->response->propstat->prop->{'current-user-privilege-set'}->privilege as $privilege) {
                    if ($privilege->all) {
                        $this->_permission = Horde_Perms::ALL;
                        break;
                    } elseif ($privilege->read) {
                        /* GET access. */
                        $this->_permission |= Horde_Perms::SHOW;
                        $this->_permission |= Horde_Perms::READ;
                    } elseif ($privilege->write || $privilege->{'write-content'}) {
                        /* PUT access. */
                        $this->_permission |= Horde_Perms::EDIT;
                    } elseif ($privilege->unbind) {
                        /* DELETE access. */
                        $this->_permission |= Horde_Perms::DELETE;
                    }
                }
            }

            return true;
        }

        $this->_davSupport = false;
        return false;
    }

    /**
     * Returns the permissions for the current calendar.
     *
     * @return integer  A Horde_Perms permission bit mask.
     */
    public function getPermission()
    {
        if ($this->isCalDAV()) {
            return $this->_permission;
        }
        return Horde_Perms::SHOW | Horde_Perms::READ;
    }

    /**
     * Sends a CalDAV request.
     *
     * @param string $method  A request method.
     * @param string $url     A request URL.
     * @param XMLWriter $xml  An XMLWriter object with the body content.
     * @param array $headers  A hash with additional request headers.
     *
     * @return array  The Horde_Http_Response object and the parsed
     *                SimpleXMLElement results.
     * @throws Kronolith_Exception
     */
    protected function _request($method, $url, XMLWriter $xml = null,
                                array $headers = array())
    {
        try {
            $response = $this->_getClient()
                ->request($method,
                          $url,
                          $xml ? $xml->outputMemory() : null,
                          array_merge(array('Cache-Control' => 'no-cache',
                                            'Pragma' => 'no-cache',
                                            'Content-Type' => 'application/xml'),
                                      $headers));
        } catch (Horde_Http_Exception $e) {
            Horde::logMessage($e, 'INFO');
            throw new Kronolith_Exception($e);
        }
        if ($response->code != 207) {
            throw new Kronolith_Exception(_("Unexpected response from remote server."));
        }
        libxml_use_internal_errors(true);
        try {
            $body = $response->getBody();
            $xml = new SimpleXMLElement($body);
        } catch (Exception $e) {
            throw new Kronolith_Exception($e);
        }
        return array($response, $xml);
    }

    /**
     * Returns the URL of this calendar.
     *
     * Does any necessary trimming and URL scheme fixes on the user-provided
     * calendar URL.
     *
     * @return string  The URL of this calendar.
     */
    protected function _getUrl()
    {
        $url = trim($this->calendar);
        if (strpos($url, 'http') !== 0) {
            $url = str_replace(array('webcal://', 'webdav://', 'webdavs://'),
                               array('http://', 'http://', 'https://'),
                               $url);
        }
        return $url;
    }

    /**
     * Returns a configured, cached HTTP client.
     *
     * @return Horde_Http_Client  A HTTP client.
     */
    protected function _getClient()
    {
        $options = array('request.timeout' => isset($this->_params['timeout'])
                                              ? $this->_params['timeout']
                                              : 5);
        if (!empty($this->_params['user'])) {
            $options['request.username'] = $this->_params['user'];
            $options['request.password'] = $this->_params['password'];
        }

        $this->_client = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_HttpClient')
            ->create($options);

        return $this->_client;
    }

}
