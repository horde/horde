<?php
/**
 * Kronolith external API interface.
 *
 * This file defines Kronolith's external API interface. Other applications
 * can interact with Kronolith through this API.
 *
 * @package Kronolith
 */
class Kronolith_Api extends Horde_Registry_Api
{
    /**
     * Links.
     *
     * @var array
     */
    public $links = array(
        'show' => '%application%/event.php?calendar=|calendar|&eventID=|event|&uid=|uid|'
    );

    /**
     * Returns the share helper prefix
     *
     * @return string
     */
    public function shareHelp()
    {
        return 'shares';
    }

    /**
     * Returns the last modification timestamp for the given uid.
     *
     * @param string $uid      The uid to look for.
     *
     * @return integer  The timestamp for the last modification of $uid.
     */
    public function modified($uid)
    {
        $modified = $this->getActionTimestamp($uid, 'modify');
        if (empty($modified)) {
            $modified = $this->getActionTimestamp($uid, 'add');
        }
        return $modified;
    }

    /**
     * Browse through Kronolith's object tree.
     *
     * @param string $path       The level of the tree to browse.
     * @param array $properties  The item properties to return. Defaults to 'name',
     *                           'icon', and 'browseable'.
     *
     * @return array  The contents of $path
     * @throws Kronolith_Exception
     */
    public function browse($path = '', $properties = array())
    {
        global $registry;

        // Default properties.
        if (!$properties) {
            $properties = array('name', 'icon', 'browseable');
        }

        if (substr($path, 0, 9) == 'kronolith') {
            $path = substr($path, 9);
        }
        $path = trim($path, '/');
        $parts = explode('/', $path);

        if (empty($path)) {
            // This request is for a list of all users who have calendars
            // visible to the requesting user.
            $calendars = Kronolith::listInternalCalendars(false, Horde_Perms::READ);
            $owners = array();
            foreach ($calendars as $calendar) {
                if ($calendar->get('owner') != $GLOBALS['registry']->getAuth() &&
                    !empty($GLOBALS['conf']['share']['hidden']) &&
                    !in_array($calendar->getName(), $GLOBALS['display_calendars'])) {
                    continue;
                }
                $owners[$calendar->get('owner')] = true;
            }

            $results = array();
            foreach (array_keys($owners) as $owner) {
                $path = 'kronolith/' . $owner;
                if (in_array('name', $properties)) {
                    $results[$path]['name'] = $owner;
                }
                if (in_array('icon', $properties)) {
                    $results[$path]['icon'] = Horde_Themes::img('user.png');
                }
                if (in_array('browseable', $properties)) {
                    $results[$path]['browseable'] = true;
                }
                if (in_array('contenttype', $properties)) {
                    $results[$path]['contenttype'] =
                        'httpd/unix-directory';
                }
                if (in_array('contentlength', $properties)) {
                    $results[$path]['contentlength'] = 0;
                }
                if (in_array('modified', $properties)) {
                    $results[$path]['modified'] =
                        $_SERVER['REQUEST_TIME'];
                }
                if (in_array('created', $properties)) {
                    $results[$path]['created'] = 0;
                }

                // CalDAV Properties from RFC 4791 and
                // draft-desruisseaux-caldav-sched-03
                $caldavns = 'urn:ietf:params:xml:ns:caldav';
                $kronolith_rpc_base = $GLOBALS['registry']->get('webroot', 'horde') . '/rpc/kronolith/';
                if (in_array($caldavns . ':calendar-home-set', $properties)) {
                    $results[$path][$caldavns . ':calendar-home-set'] =  Horde::url($kronolith_rpc_base . urlencode($owner), true);
                }

                if (in_array($caldavns . ':calendar-user-address-set', $properties)) {
                    // FIXME: Add the calendar owner's email address from
                    // their Horde Identity
                }
            }
            return $results;

        } elseif (count($parts) == 1) {
            // This request is for all calendars owned by the requested user
            $calendars = $GLOBALS['kronolith_shares']->listShares($GLOBALS['registry']->getAuth(),
                                                                  Horde_Perms::SHOW,
                                                                  $parts[0]);
            $results = array();
            foreach ($calendars as $calendarId => $calendar) {
                $retpath = 'kronolith/' . $parts[0] . '/' . $calendarId;
                if (in_array('name', $properties)) {
                    $results[$retpath]['name'] = sprintf(_("Events from %s"), $calendar->get('name'));
                    $results[$retpath . '.ics']['name'] = $calendar->get('name');
                }
                if (in_array('displayname', $properties)) {
                    $results[$retpath]['displayname'] = rawurlencode($calendar->get('name'));
                    $results[$retpath . '.ics']['displayname'] = rawurlencode($calendar->get('name')) . '.ics';
                }
                if (in_array('icon', $properties)) {
                    $results[$retpath]['icon'] = Horde_Themes::img('kronolith.png');
                    $results[$retpath . '.ics']['icon'] = Horde_Themes::img('mime/icalendar.png');
                }
                if (in_array('browseable', $properties)) {
                    $results[$retpath]['browseable'] = $calendar->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ);
                    $results[$retpath . '.ics']['browseable'] = false;
                }
                if (in_array('contenttype', $properties)) {
                    $results[$retpath]['contenttype'] = 'httpd/unix-directory';
                    $results[$retpath . '.ics']['contenttype'] = 'text/calendar';
                }
                if (in_array('contentlength', $properties)) {
                    $results[$retpath]['contentlength'] = 0;
                    // FIXME: This is a hack.  If the content length is longer
                    // than the actual data then some WebDAV clients will
                    // report an error when the file EOF is received.  Ideally
                    // we should determine the actual size of the .ics and
                    // report it here, but the performance hit may be
                    // prohibitive.  This requires further investigation.
                    $results[$retpath . '.ics']['contentlength'] = 1;
                }
                if (in_array('modified', $properties)) {
                    $results[$retpath]['modified'] = $_SERVER['REQUEST_TIME'];
                    $results[$retpath . '.ics']['modified'] = $_SERVER['REQUEST_TIME'];
                }
                if (in_array('created', $properties)) {
                    $results[$retpath]['created'] = 0;
                    $results[$retpath . '.ics']['created'] = 0;
                }
            }
            return $results;

        } elseif (count($parts) == 2 &&
                  array_key_exists($parts[1], Kronolith::listInternalCalendars(false, Horde_Perms::READ))) {
            // This request is browsing into a specific calendar.  Generate
            // the list of items and represent them as files within the
            // directory.
            $kronolith_driver = Kronolith::getDriver(null, $parts[1]);
            $events = $kronolith_driver->listEvents();
            $icon = Horde_Themes::img('mime/icalendar.png');
            $results = array();
            foreach ($events as $dayevents) {
                foreach ($dayevents as $event) {
                    $key = 'kronolith/' . $path . '/' . $event->id;
                    if (in_array('name', $properties)) {
                        $results[$key]['name'] = $event->getTitle();
                    }
                    if (in_array('icon', $properties)) {
                        $results[$key]['icon'] = $icon;
                    }
                    if (in_array('browseable', $properties)) {
                        $results[$key]['browseable'] = false;
                    }
                    if (in_array('contenttype', $properties)) {
                        $results[$key]['contenttype'] = 'text/calendar';
                    }
                    if (in_array('contentlength', $properties)) {
                        // FIXME: This is a hack.  If the content length is
                        // longer than the actual data then some WebDAV
                        // clients will report an error when the file EOF is
                        // received.  Ideally we should determine the actual
                        // size of the data and report it here, but the
                        // performance hit may be prohibitive.  This requires
                        // further investigation.
                        $results[$key]['contentlength'] = 1;
                    }
                    if (in_array('modified', $properties)) {
                        $results[$key]['modified'] = $this->modified($event->uid);
                    }
                    if (in_array('created', $properties)) {
                        $results[$key]['created'] = $this->getActionTimestamp($event->uid, 'add');
                    }
                }
            }
            return $results;
        } else {
            // The only valid request left is for either a specific event or
            // for the entire calendar.
            if (count($parts) == 3 &&
                array_key_exists($parts[1], Kronolith::listInternalCalendars(false, Horde_Perms::READ))) {
                // This request is for a specific item within a given calendar.
                $event = Kronolith::getDriver(null, $parts[1])->getEvent($parts[2]);

                $result = array(
                    'data' => $this->export($event->uid, 'text/calendar'),
                    'mimetype' => 'text/calendar');
                $modified = $this->modified($event->uid);
                if (!empty($modified)) {
                    $result['mtime'] = $modified;
                }
                return $result;
            } elseif (count($parts) == 2 &&
                      substr($parts[1], -4, 4) == '.ics' &&
                      array_key_exists(substr($parts[1], 0, -4), Kronolith::listInternalCalendars(false, Horde_Perms::READ))) {
                // This request is for an entire calendar (calendar.ics).
                $ical_data = $this->exportCalendar(substr($parts[1], 0, -4), 'text/calendar');
                $result = array('data'          => $ical_data,
                                'mimetype'      => 'text/calendar',
                                'contentlength' => strlen($ical_data),
                                'mtime'         => $_SERVER['REQUEST_TIME']);

                return $result;
            } else {
                // All other requests are a 404: Not Found
                return false;
            }
        }
    }

    /**
     * Saves a file into the Kronolith tree.
     *
     * @param string $path          The path where to PUT the file.
     * @param string $content       The file content.
     * @param string $content_type  The file's content type.
     *
     * @return array  The event UIDs.
     * @throws Kronolith_Exception
     */
    public function put($path, $content, $content_type)
    {
        if (substr($path, 0, 9) == 'kronolith') {
            $path = substr($path, 9);
        }
        $path = trim($path, '/');
        $parts = explode('/', $path);

        if (count($parts) == 2 && substr($parts[1], -4) == '.ics') {
            // Workaround for WebDAV clients that are not smart enough to send
            // the right content type.  Assume text/calendar.
            if ($content_type == 'application/octet-stream') {
                $content_type = 'text/calendar';
            }
            $calendar = substr($parts[1], 0, -4);
        } elseif (count($parts) == 3) {
            $calendar = $parts[1];
            // Workaround for WebDAV clients that are not smart enough to send
            // the right content type.  Assume text/calendar.
            if ($content_type == 'application/octet-stream') {
                $content_type = 'text/calendar';
            }
        } else {
            throw new Kronolith_Exception("Invalid calendar data supplied.");
        }

        if (!array_key_exists($calendar, Kronolith::listInternalCalendars(false, Horde_Perms::EDIT))) {
            // FIXME: Should we attempt to create a calendar based on the
            // filename in the case that the requested calendar does not
            // exist?
            throw new Kronolith_Exception("Calendar does not exist or no permission to edit");
        }

        // Store all currently existings UIDs. Use this info to delete UIDs not
        // present in $content after processing.
        $ids = array();
        $uids_remove = array_flip($this->listUids($calendar));

        switch ($content_type) {
        case 'text/calendar':
        case 'text/x-vcalendar':
            $iCal = new Horde_Icalendar();
            if (!($content instanceof Horde_Icalendar_Vevent)) {
                if (!$iCal->parsevCalendar($content)) {
                    throw new Kronolith_Exception(_("There was an error importing the iCalendar data."));
                }
            } else {
                $iCal->addComponent($content);
            }

            $kronolith_driver = Kronolith::getDriver();
            foreach ($iCal->getComponents() as $content) {
                if ($content instanceof Horde_Icalendar_Vevent) {
                    $event = $kronolith_driver->getEvent();
                    $event->fromiCalendar($content);
                    $uid = $event->uid;
                    // Remove from uids_remove list so we won't delete in the
                    // end.
                    if (isset($uids_remove[$uid])) {
                        unset($uids_remove[$uid]);
                    }
                    try {
                        $existing_event = $kronolith_driver->getByUID($uid, array($calendar));
                        // Check if our event is newer then the existing - get
                        // the event's history.
                        $created = $modified = null;
                        try {
                            $log = $GLOBALS['injector']->getInstance('Horde_History')->getHistory('kronolith:' . $calendar . ':' . $uid);
                            foreach ($log as $entry) {
                                switch ($entry['action']) {
                                case 'add':
                                    $created = $entry['ts'];
                                    break;

                                case 'modify':
                                    $modified = $entry['ts'];
                                    break;
                                }
                            }
                        } catch (Exception $e) {}
                        if (empty($modified) && !empty($created)) {
                            $modified = $created;
                        }
                        if (!empty($modified) &&
                            $modified >= $content->getAttribute('LAST-MODIFIED')) {
                                // LAST-MODIFIED timestamp of existing entry
                                // is newer: don't replace it.
                                continue;
                            }

                        // Don't change creator/owner.
                        $event->creator = $existing_event->creator;
                    } catch (Horde_Exception_NotFound $e) {}

                    // Save entry.
                    $saved = $event->save();
                    $ids[] = $event->uid;
                }
            }
            break;

        default:
            throw new Kronolith_Exception(sprintf(_("Unsupported Content-Type: %s"), $content_type));
        }

        if (array_key_exists($calendar, Kronolith::listInternalCalendars(false, Horde_Perms::DELETE))) {
            foreach (array_keys($uids_remove) as $uid) {
                $this->delete($uid);
            }
        }

        return $ids;
    }

    /**
     * Deletes a file from the Kronolith tree.
     *
     * @param string $path  The path to the file.
     *
     * @throws Kronolith_Exception
     */
    public function path_delete($path)
    {
        if (substr($path, 0, 9) == 'kronolith') {
            $path = substr($path, 9);
        }
        $path = trim($path, '/');
        $parts = explode('/', $path);

        if (substr($parts[1], -4) == '.ics') {
            $calendarId = substr($parts[1], 0, -4);
        } else {
            $calendarId = $parts[1];
        }

        if (!(count($parts) == 2 || count($parts) == 3) ||
            !array_key_exists($calendarId, Kronolith::listInternalCalendars(false, Horde_Perms::DELETE))) {
                throw new Kronolith_Exception("Calendar does not exist or no permission to delete");
            }

        if (count($parts) == 3) {
            // Delete just a single entry
            return Kronolith::getDriver(null, $calendarId)->deleteEvent($parts[2]);
        } else {
            // Delete the entire calendar
            try {
                Kronolith::getDriver()->delete($calendarId);
                // Remove share and all groups/permissions.
                $share = $GLOBALS['kronolith_shares']->getShare($calendarId);
                $result = $GLOBALS['kronolith_shares']->removeShare($share);
            } catch (Exception $e) {
                throw new Kronolith_Exception(sprintf(_("Unable to delete calendar \"%s\": %s"), $calendarId, $e->getMessage()));
            }
        }
    }

    /**
     * Returns all calendars a user has access to, according to several
     * parameters/permission levels.
     *
     * @param boolean $owneronly   Only return calenders that this user owns?
     *                             Defaults to false.
     * @param integer $permission  The permission to filter calendars by.
     *
     * @return array  The calendar list.
     */
    public function listCalendars($owneronly = false, $permission = null)
    {
        if (is_null($permission)) {
            $permission = Horde_Perms::SHOW;
        }
        return array_keys(Kronolith::listInternalCalendars($owneronly, $permission));
    }

    /**
     * Returns the ids of all the events that happen within a time period.
     *
     * @param string $calendar      The calendar to check for events.
     * @param object $startstamp    The start of the time range.
     * @param object $endstamp      The end of the time range.
     *
     * @return array  The event ids happening in this time period.
     * @throws Kronolith_Exception
     */
    public function listUids($calendar = null, $startstamp = 0, $endstamp = 0)
    {
        if (empty($calendar)) {
            $calendar = Kronolith::getDefaultCalendar();
        }
        if (!array_key_exists($calendar,
            Kronolith::listInternalCalendars(false, Horde_Perms::READ))) {
            throw new Horde_Exception_PermissionDenied();
        }

        $events = Kronolith::getDriver(null, $calendar)
            ->listEvents(new Horde_Date($startstamp), new Horde_Date($endstamp));
        $uids = array();
        foreach ($events as $dayevents) {
            foreach ($dayevents as $event) {
                $uids[] = $event->uid;
            }
        }

        return $uids;
    }

    /**
     * Returns an array of UIDs for events that have had $action happen since
     * $timestamp.
     *
     * @param string  $action     The action to check for - add, modify, or delete.
     * @param integer $timestamp  The time to start the search.
     * @param string  $calendar   The calendar to search in.
     * @param integer $end        The optional ending timestamp
     *
     * @return array  An array of UIDs matching the action and time criteria.
     *
     * @throws Kronolith_Exception
     * @throws Horde_History_Exception
     * @throws InvalidArgumentException
     */
    public function listBy($action, $timestamp, $calendar = null, $end = null)
    {
        if (empty($calendar)) {
            $calendar = Kronolith::getDefaultCalendar();
        }

        if ($calendar === false ||
            !array_key_exists($calendar, Kronolith::listInternalCalendars(false, Horde_Perms::READ))) {
            throw new Horde_Exception_PermissionDenied();
        }

        $filter = array(array('op' => '=', 'field' => 'action', 'value' => $action));
        if (!empty($end)) {
            $filter[] = array('op' => '<', 'field' => 'ts', 'value' => $end);
        }
        $histories = $GLOBALS['injector']->getInstance('Horde_History')->getByTimestamp('>', $timestamp, $filter, 'kronolith:' . $calendar);

        // Strip leading kronolith:username:.
        return preg_replace('/^([^:]*:){2}/', '', array_keys($histories));
    }

    /**
     * Method for obtaining all server changes between two timestamps. Basically
     * a wrapper around listBy(), but returns an array containing all adds,
     * edits and deletions. If $ignoreExceptions is true, events representing
     * recurring event exceptions will not be included in the results.
     *
     * @param integer $start             The starting timestamp
     * @param integer $end               The ending timestamp.
     * @param boolean $ignoreExceptions  Do not include exceptions in results.
     *
     * @return array  An hash with 'add', 'modify' and 'delete' arrays.
     * @throws Horde_Exception_PermissionDenied
     * @throws Kronolith_Exception
     */
    public function getChanges($start, $end, $ignoreExceptions = true)
    {
        /* Only get the calendar once */
        $c = Kronolith::getDefaultCalendar();
        if ($c === false ||
            !array_key_exists($c, Kronolith::listInternalCalendars(false, Horde_Perms::READ))) {
            throw new Horde_Exception_PermissionDenied();
        }

        /* Need to get changes, then ensure they are not from an exception */
        $changes = array('add' => array(),
                         'modify' => array(),
                         'delete' => array());

        /* New events */
        $uids = $this->listBy('add', $start, $c, $end);
        if ($ignoreExceptions) {
            foreach ($uids as $uid) {
                try {
                    $event = Kronolith::getDriver()->getByUID($uid);
                } catch (Kronolith_Exception $e) {
                    continue;
                }
                if (empty($event->baseid)) {
                    $changes['add'][] = $uid;
                }
            }
        } else {
            $changes['add'] = $uids;
        }

        /* Edits */
        $uids = $this->listBy('modify', $start, $c, $end);
        if ($ignoreExceptions) {
            foreach ($uids as $uid) {
                try {
                    $event = Kronolith::getDriver()->getByUID($uid);
                } catch (Kronolith_Exception $e) {
                    continue;
                }
                if (empty($event->baseid)) {
                    $changes['modify'][] = $uid;
                }
            }
        } else {
            $changes['modify'] = $uids;
        }

        /* No way to figure out if this was an exception, so we must include all */
        $changes['delete'] = $this->listBy('delete', $start, $c, $end);

        return $changes;
    }


    /**
     * Returns the timestamp of an operation for a given uid an action
     *
     * @param string $uid      The uid to look for.
     * @param string $action   The action to check for - add, modify, or delete.
     * @param string $calendar The calendar to search in.
     *
     * @return integer  The timestamp for this action.
     *
     * @throws Kronolith_Exception
     * @throws InvalidArgumentException
     */
    public function getActionTimestamp($uid, $action, $calendar = null)
    {
        if (empty($calendar)) {
            $calendar = Kronolith::getDefaultCalendar();
        }

        if (!array_key_exists($calendar,
            Kronolith::listInternalCalendars(false, Horde_Perms::READ))) {
            throw new Horde_Exception_PermissionDenied();
        }

        return $GLOBALS['injector']->getInstance('Horde_History')->getActionTimestamp('kronolith:' . $calendar . ':' . $uid, $action);
    }

    /**
     * Imports an event represented in the specified content type.
     *
     * @param string $content      The content of the event.
     * @param string $contentType  What format is the data in? Currently supports:
     *                             <pre>
     *                             text/calendar
     *                             text/x-vcalendar
     *                             </pre>
     * @param string $calendar     What calendar should the event be added to?
     *
     * @return array  The event's UID.
     * @throws Kronolith_Exception
     */
    public function import($content, $contentType, $calendar = null)
    {
        if (!isset($calendar)) {
            $calendar = Kronolith::getDefaultCalendar(Horde_Perms::EDIT);
        }
        if (!array_key_exists($calendar,
            Kronolith::listInternalCalendars(false, Horde_Perms::EDIT))) {
            throw new Horde_Exception_PermissionDenied();
        }

        $kronolith_driver = Kronolith::getDriver(null, $calendar);

        switch ($contentType) {
        case 'text/calendar':
        case 'text/x-vcalendar':
            $iCal = new Horde_Icalendar();
            if (!($content instanceof Horde_Icalendar_Vevent)) {
                if (!$iCal->parsevCalendar($content)) {
                    throw new Kronolith_Exception(_("There was an error importing the iCalendar data."));
                }
            } else {
                $iCal->addComponent($content);
            }

            $components = $iCal->getComponents();
            if (count($components) == 0) {
                throw new Kronolith_Exception(_("No iCalendar data was found."));
            }

            $ids = array();
            $recurrences = array();
            foreach ($components as $content) {
                if ($content instanceof Horde_Icalendar_Vevent) {
                    // Need to ensure that the original recurring event is
                    // added before any of the instance exceptions. Easiest way
                    // to do that is just add all the recurrence-id entries last
                    try {
                        $recurrenceId = $content->getAttribute('RECURRENCE-ID');
                        $recurrences[] = $content;
                    } catch (Horde_Icalendar_Exception $e) {
                        $ids[] = $this->_addiCalEvent($content, $kronolith_driver);
                    }
                }
            }
            
            if (count($ids) == 0) {
                throw new Kronolith_Exception(_("No iCalendar data was found."));
            } else if (count($ids) == 1) {
                return $ids[0];
            }

            // Now add all the exception instances
            foreach ($recurrences as $recurrence) {
                $ids[] = $this->_addiCalEvent($recurrence, $kronolith_driver);
            }

            return $ids;

            case 'activesync':
                $event = $kronolith_driver->getEvent();
                $event->fromASAppointment($content);
                $event->save();
                return $event->uid;
        }

        throw new Kronolith_Exception(sprintf(_("Unsupported Content-Type: %s"), $contentType));
    }

    /**
     * Imports a single vEvent part to storage.
     *
     * @param Horde_Icalendar_Vevent $content  The vEvent part
     * @param Kronolith_Driver $driver         The kronolith driver
     *
     * @return string  The new event's uid
     */
    protected function _addiCalEvent($content, $driver)
    {
        $event = $driver->getEvent();
        $event->fromiCalendar($content);
        // Check if the entry already exists in the data source,
        // first by UID.
        $uid = $event->uid;
        try {
            $existing_event = $driver->getByUID($uid, array($driver->calendar));
            throw new Kronolith_Exception(_("Already Exists"), 'horde.message', null, null, $uid);
        } catch (Horde_Exception $e) {}
        $result = $driver->search($event);
        // Check if the match really is an exact match:
        if (is_array($result) && count($result) > 0) {
            foreach($result as $match) {
                if ($match->start == $event->start &&
                    $match->end == $event->end &&
                    $match->title == $event->title &&
                    $match->location == $event->location &&
                    $match->hasPermission(Horde_Perms::EDIT)) {
                        throw new Kronolith_Exception(_("Already Exists"), 'horde.message', null, null, $match->uid);
                    }
            }
        }
        $event->save();

        return $event->uid;
    }

    /**
     * Imports an event parsed from a string.
     *
     * @param string $text      The text to parse into an event
     * @param string $calendar  The calendar into which the event will be
     *                          imported.  If 'null', the user's default
     *                          calendar will be used.
     *
     * @return array  The UID of all events that were added.
     * @throws Kronolith_Exception
     */
    public function quickAdd($text, $calendar = null)
    {
        if (!isset($calendar)) {
            $calendar = Kronolith::getDefaultCalendar(Horde_Perms::EDIT);
        }
        if (!array_key_exists($calendar,
            Kronolith::listInternalCalendars(false, Horde_Perms::EDIT))) {
            throw new Horde_Exception_PermissionDenied();
        }
        $event = Kronolith::quickAdd($text, $calendar);
        return $event->uid;
    }

    /**
     * Exports an event, identified by UID, in the requested content type.
     *
     * @param string $uid         Identify the event to export.
     * @param string $contentType  What format should the data be in?
     *                            A string with one of:
     *                            <pre>
     *                             text/calendar (VCALENDAR 2.0. Recommended as
     *                                            this is specified in rfc2445)
     *                             text/x-vcalendar (old VCALENDAR 1.0 format.
     *                                              Still in wide use)
     *                            </pre>
     *
     * @return string  The requested data.
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     */
    public function export($uid, $contentType)
    {
        global $kronolith_shares;

        $event = Kronolith::getDriver()->getByUID($uid);
        if (!$event->hasPermission(Horde_Perms::READ)) {
            throw new Horde_Exception_PermissionDenied();
        }

        $version = '2.0';
        switch ($contentType) {
        case 'text/x-vcalendar':
            $version = '1.0';
        case 'text/calendar':
            $share = $kronolith_shares->getShare($event->calendar);

            $iCal = new Horde_Icalendar($version);
            $iCal->setAttribute('X-WR-CALNAME', Horde_String::convertCharset($share->get('name'), $GLOBALS['registry']->getCharset(), 'utf-8'));

            // Create a new vEvent.
            $iCal->addComponent($event->toiCalendar($iCal));

            return $iCal->exportvCalendar();

        case 'activesync':
            return $event->toASAppointment();
        }

        throw new Kronolith_Exception(sprintf(_("Unsupported Content-Type: %s"), $contentType));
    }

    /**
     * Exports a calendar in the requested content type.
     *
     * @param string $calendar    The calendar to export.
     * @param string $contentType  What format should the data be in?
     *                             A string with one of:
     *                             <pre>
     *                             text/calendar (VCALENDAR 2.0. Recommended as
     *                                            this is specified in rfc2445)
     *                             text/x-vcalendar (old VCALENDAR 1.0 format.
     *                                              Still in wide use)
     *                             </pre>
     *
     * @return string  The iCalendar representation of the calendar.
     * @throws Kronolith_Exception
     */
    public function exportCalendar($calendar, $contentType)
    {
        global $kronolith_shares;

        if (!array_key_exists($calendar,
            Kronolith::listInternalCalendars(false, Horde_Perms::READ))) {
            throw new Horde_Exception_PermissionDenied();
        }

        $kronolith_driver = Kronolith::getDriver(null, $calendar);
        $events = $kronolith_driver->listEvents(null, null, false, false, false, true, true, true);

        $version = '2.0';
        switch ($contentType) {
        case 'text/x-vcalendar':
            $version = '1.0';
        case 'text/calendar':
            $share = $kronolith_shares->getShare($calendar);

            $iCal = new Horde_Icalendar($version);
            $iCal->setAttribute('X-WR-CALNAME', Horde_String::convertCharset($share->get('name'), $GLOBALS['registry']->getCharset(), 'UTF-8'));
            if (strlen($share->get('desc'))) {
                $iCal->setAttribute('X-WR-CALDESC', Horde_String::convertCharset($share->get('desc'), $GLOBALS['registry']->getCharset(), 'UTF-8'));
            }

            foreach ($events as $dayevents) {
                foreach ($dayevents as $event) {
                    $iCal->addComponent($event->toiCalendar($iCal));
                }
            }

            return $iCal->exportvCalendar();
        }

        throw new Kronolith_Exception(sprintf(_("Unsupported Content-Type: %s"), $contentType));
    }

    /**
     * Deletes an event identified by UID.
     *
     * @param string|array $uid     A single UID or an array identifying the
     *                              event(s) to delete.
     *
     * @param string $recurrenceId  The reccurenceId for the event instance, if
     *                              this is a deletion of a recurring event
     *                              instance ($uid must not be an array).
     *
     * @throws Kronolith_Exception
     */
    public function delete($uid, $recurrenceId = null)
    {
        // Handle an array of UIDs for convenience of deleting multiple events
        // at once.
        if (is_array($uid)) {
            foreach ($uid as $g) {
                $result = $this->delete($g);
            }
            return;
        }

        $kronolith_driver = Kronolith::getDriver();
        $events = $kronolith_driver->getByUID($uid, null, true);

        $event = null;
        if ($GLOBALS['registry']->isAdmin()) {
            $event = $events[0];
        }

        // First try the user's own calendars.
        if (empty($event)) {
            $ownerCalendars = Kronolith::listInternalCalendars(true, Horde_Perms::DELETE);
            foreach ($events as $ev) {
                if ($GLOBALS['registry']->isAdmin() || isset($ownerCalendars[$ev->calendar])) {
                    $event = $ev;
                    break;
                }
            }
        }

        // If not successful, try all calendars the user has access to.
        if (empty($event)) {
            $deletableCalendars = Kronolith::listInternalCalendars(false, Horde_Perms::DELETE);
            foreach ($events as $ev) {
                if (isset($deletableCalendars[$ev->calendar])) {
                    $kronolith_driver->open($ev->calendar);
                    $event = $ev;
                    break;
                }
            }
        }

        if (empty($event)) {
            throw new Horde_Exception_PermissionDenied();
        }

        if ($recurrenceId && $event->recurs()) {
            $deleteDate = new Horde_Date($recurrenceId);
            $event->recurrence->addException($deleteDate->format('Y'), $deleteDate->format('m'), $deleteDate->format('d'));
            $event->save();
        } elseif ($recurrenceId) {
            throw new Kronolith_Exception(_("Unable to delete event. An exception date was provided but the event does not seem to be recurring."));
        } else {
            $kronolith_driver->deleteEvent($event->id);
        }
    }

    /**
     * Replaces the event identified by UID with the content represented in the
     * specified contentType.
     *
     * @param string $uid          Idenfity the event to replace.
     * @param mixed  $content      The content of the event. String or
     *                             Horde_Icalendar_Vevent
     * @param string $contentType  What format is the data in? Currently supports:
     *                             text/calendar
     *                             text/x-vcalendar
     *                             (Ignored if content is Horde_Icalendar_Vevent)
     *
     * @throws Kronolith_Exception
     */
    public function replace($uid, $content, $contentType)
    {
        $event = Kronolith::getDriver()->getByUID($uid);

        if (!$event->hasPermission(Horde_Perms::EDIT) ||
            ($event->private && $event->creator != $GLOBALS['registry']->getAuth())) {
            throw new Horde_Exception_PermissionDenied();
        }

        if ($content instanceof Horde_Icalendar_Vevent) {
            $component = $content;
        } elseif ($content instanceof Horde_ActiveSync_Message_Appointment) {
            $event->fromASAppointment($content);
            $event->save();
            $event->uid = $uid;
            return;
        } else {
            switch ($contentType) {
            case 'text/calendar':
            case 'text/x-vcalendar':
                if (!($content instanceof Horde_Icalendar_Vevent)) {
                    $iCal = new Horde_Icalendar();
                    if (!$iCal->parsevCalendar($content)) {
                        throw new Kronolith_Exception(_("There was an error importing the iCalendar data."));
                    }

                    $components = $iCal->getComponents();
                    $component = null;
                    foreach ($components as $content) {
                        if ($content instanceof Horde_Icalendar_Vevent) {
                            if ($component !== null) {
                                throw new Kronolith_Exception(_("Multiple iCalendar components found; only one vEvent is supported."));
                            }
                            $component = $content;
                        }

                    }
                    if ($component === null) {
                        throw new Kronolith_Exception(_("No iCalendar data was found."));
                    }
                }
                break;

            default:
                throw new Kronolith_Exception(sprintf(_("Unsupported Content-Type: %s"), $contentType));
            }
        }

        $event->fromiCalendar($component);
        // Ensure we keep the original UID, even when content does not
        // contain one and fromiCalendar creates a new one.
        $event->uid = $uid;
        $event->save();
    }

    /**
     * Generates free/busy information for a given time period.
     *
     * @param integer $startstamp  The start of the time period to retrieve.
     * @param integer $endstamp    The end of the time period to retrieve.
     * @param string $calendar     The calendar to view free/busy slots for.
     *                             Defaults to the user's default calendar.
     *
     * @return Horde_Icalendar_Vfreebusy  A freebusy object that covers the
     *                                    specified time period.
     * @throws Kronolith_Exception
     */
    public function getFreeBusy($startstamp = null, $endstamp = null,
                                $calendar = null)
    {
        if (is_null($calendar)) {
            $calendar = Kronolith::getDefaultCalendar();
        }
        // Free/Busy information is globally available; no permission
        // check is needed.
        return Kronolith_FreeBusy::generate($calendar, $startstamp, $endstamp, true);
    }

    /**
     * Retrieves a Kronolith_Event object, given an event UID.
     *
     * @param string $uid  The event's UID.
     *
     * @return Kronolith_Event  A valid Kronolith_Event.
     * @throws Kronolith_Exception
     */
    public function eventFromUID($uid)
    {
        $event = Kronolith::getDriver()->getByUID($uid);
        if (!$event->hasPermission(Horde_Perms::SHOW)) {
            throw new Horde_Exception_PermissionDenied();
        }

        return $event;
    }

    /**
     * Updates an attendee's response status for a specified event.
     *
     * @param Horde_Icalender_Vevent $response  A Horde_Icalender_Vevent
     *                                          object, with a valid UID
     *                                          attribute that points to an
     *                                          existing event.  This is
     *                                          typically the vEvent portion
     *                                          of an iTip meeting-request
     *                                          response, with the attendee's
     *                                          response in an ATTENDEE
     *                                          parameter.
     * @param string $sender                    The email address of the
     *                                          person initiating the
     *                                          update. Attendees are only
     *                                          updated if this address
     *                                          matches.
     *
     * @throws Kronolith_Exception
     */
    public function updateAttendee($response, $sender = null)
    {
        try {
            $uid = $response->getAttribute('UID');
        } catch (Horde_Icalendar_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        $events = Kronolith::getDriver()->getByUID($uid, null, true);

        /* First try the user's own calendars. */
        $ownerCalendars = Kronolith::listInternalCalendars(true, Horde_Perms::EDIT);
        $event = null;
        foreach ($events as $ev) {
            if (isset($ownerCalendars[$ev->calendar])) {
                $event = $ev;
                break;
            }
        }

        /* If not successful, try all calendars the user has access to. */
        if (empty($event)) {
            $editableCalendars = Kronolith::listInternalCalendars(false, Horde_Perms::EDIT);
            foreach ($events as $ev) {
                if (isset($editableCalendars[$ev->calendar])) {
                    $event = $ev;
                    break;
                }
            }
        }

        if (empty($event) ||
            ($event->private && $event->creator != $GLOBALS['registry']->getAuth())) {
            throw new Horde_Exception_PermissionDenied();
        }

        $atnames = $response->getAttribute('ATTENDEE');
        if (!is_array($atnames)) {
            $atnames = array($atnames);
        }
        $atparms = $response->getAttribute('ATTENDEE', true);

        $found = false;
        $error = _("No attendees have been updated because none of the provided email addresses have been found in the event's attendees list.");
        $sender_lcase = Horde_String::lower($sender);
        foreach ($atnames as $index => $attendee) {
            $attendee = str_replace('mailto:', '', Horde_String::lower($attendee));
            $name = isset($atparms[$index]['CN']) ? $atparms[$index]['CN'] : null;
            if ($event->hasAttendee($attendee)) {
                if (is_null($sender) || $sender_lcase == $attendee) {
                    $event->addAttendee($attendee, Kronolith::PART_IGNORE, Kronolith::responseFromICal($atparms[$index]['PARTSTAT']), $name);
                    $found = true;
                } else {
                    $error = _("The attendee hasn't been updated because the update was not sent from the attendee.");
                }
            }
        }
        $event->save();

        if (!$found) {
            throw new Kronolith_Exception($error);
        }
    }

    /**
     * Lists events for a given time period.
     *
     * @param integer $startstamp      The start of the time period to
     *                                 retrieve.
     * @param integer $endstamp        The end of the time period to retrieve.
     * @param array   $calendars       The calendars to view events from.
     *                                 Defaults to the user's default calendar.
     * @param boolean $showRecurrence  Return every instance of a recurring
     *                                 event?  If false, will only return
     *                                 recurring events once inside the
     *                                 $startDate - $endDate range.
     * @param boolean $alarmsOnly      Filter results for events with alarms.
     *                                 Defaults to false.
     * @param boolean $showRemote      Return events from remote calendars and
     *                                 listTimeObject API as well?
     *
     * @param boolean $hideExceptions  Hide events that represent exceptions to
     *                                 a recurring event (events with baseid
     *                                 set)?
     * @param boolean $coverDates      Add multi-day events to all dates?
     *
     * @return array  A list of event hashes.
     * @throws Kronolith_Exception
     */
    public function listEvents($startstamp = null, $endstamp = null,
                               $calendars = null, $showRecurrence = true,
                               $alarmsOnly = false, $showRemote = true,
                               $hideExceptions = false, $coverDates = true,
                               $fetchTags = false)
    {
        if (!isset($calendars)) {
            $calendars = array($GLOBALS['prefs']->getValue('default_share'));
        } elseif (!is_array($calendars)) {
            $calendars = array($calendars);
        }
        $allowed_calendars = Kronolith::listInternalCalendars(false, Horde_Perms::READ);
        foreach ($calendars as $calendar) {
            if (!array_key_exists($calendar, $allowed_calendars)) {
                throw new Horde_Exception_PermissionDenied();
            }
        }

        return Kronolith::listEvents(
            new Horde_Date($startstamp),
            new Horde_Date($endstamp),
            $calendars,
            $showRecurrence,
            $alarmsOnly,
            $showRemote,
            $hideExceptions,
            $coverDates,
            $fetchTags);
    }

    /**
     * Lists alarms for a given moment.
     *
     * @param integer $time  The time to retrieve alarms for.
     * @param string $user   The user to retrieve alarms for. All users if null.
     *
     * @return array  An array of UIDs
     * @throws Kronolith_Exception
     */
    public function listAlarms($time, $user = null)
    {
        $current_user = $GLOBALS['registry']->getAuth();
        if ((empty($user) || $user != $current_user) && !$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception_PermissionDenied();
        }

        $group = $GLOBALS['injector']->getInstance('Horde_Group');
        $alarm_list = array();
        $time = new Horde_Date($time);
        $calendars = is_null($user) ? array_keys($GLOBALS['kronolith_shares']->listAllShares()) : $GLOBALS['display_calendars'];
        $alarms = Kronolith::listAlarms($time, $calendars, true);
        foreach ($alarms as $calendar => $cal_alarms) {
            if (!$cal_alarms) {
                continue;
            }
            try {
                $share = $GLOBALS['kronolith_shares']->getShare($calendar);
            } catch (Exception $e) {
                continue;
            }
            if (empty($user)) {
                $users = $share->listUsers(Horde_Perms::READ);
                $groups = $share->listGroups(Horde_Perms::READ);
                foreach ($groups as $gid) {
                    try {
                        $users = array_merge($users, $group->listUsers($gid));
                    } catch (Horde_Group_Exception $e) {}
                }
                $users = array_unique($users);
            } else {
                $users = array($user);
            }
            $owner = $share->get('owner');
            foreach ($cal_alarms as $event) {
                foreach ($users as $alarm_user) {
                    if ($alarm_user == $current_user) {
                        $prefs = $GLOBALS['prefs'];
                    } else {
                        $prefs = $GLOBALS['injector']->getInstance('Horde_Prefs')->getPrefs('kronolith', array(
                            'cache' => false,
                            'user' => $alarm_user
                        ));
                    }
                    $shown_calendars = unserialize($prefs->getValue('display_cals'));
                    $reminder = $prefs->getValue('event_reminder');
                    if (($reminder == 'owner' && $alarm_user == $owner) ||
                        ($reminder == 'show' && in_array($calendar, $shown_calendars)) ||
                        $reminder == 'read') {
                            $GLOBALS['registry']->setLanguageEnvironment($prefs->getValue('language'));
                            $alarm = $event->toAlarm($time, $alarm_user, $prefs);
                            if ($alarm) {
                                $alarm_list[] = $alarm;
                            }
                        }
                }
            }
        }

        return $alarm_list;
    }

    /**
     * Subscribe to a calendar.
     *
     * @param array $calendar  Calendar description hash, with required 'type'
     *                         parameter. Currently supports 'http' and
     *                         'webcal' for remote calendars.
     *
     * @throws Kronolith_Exception
     */
    public function subscribe($calendar)
    {
        if (!isset($calendar['type'])) {
            throw new Kronolith_Exception(_("Unknown calendar protocol"));
        }

        switch ($calendar['type']) {
        case 'http':
        case 'webcal':
            Kronolith::subscribeRemoteCalendar($calendar);
            break;

        case 'external':
            $cals = unserialize($GLOBALS['prefs']->getValue('display_external_cals'));
            if (array_search($calendar['name'], $cals) === false) {
                $cals[] = $calendar['name'];
                $GLOBALS['prefs']->setValue('display_external_cals', serialize($cals));
            }

        default:
            throw new Kronolith_Exception(_("Unknown calendar protocol"));
        }
    }

    /**
     * Unsubscribe from a calendar.
     *
     * @param array $calendar  Calendar description array, with required 'type'
     *                         parameter. Currently supports 'http' and
     *                         'webcal' for remote calendars.
     *
     * @throws Kronolith_Exception
     */
    public function unsubscribe($calendar)
    {
        if (!isset($calendar['type'])) {
            throw new Kronolith_Exception('Unknown calendar specification');
        }

        switch ($calendar['type']) {
        case 'http':
        case 'webcal':
            Kronolith::subscribeRemoteCalendar($calendar['url']);
            break;

        case 'external':
            $cals = unserialize($GLOBALS['prefs']->getValue('display_external_cals'));
            if (($key = array_search($calendar['name'], $cals)) !== false) {
                unset($cals[$key]);
                $GLOBALS['prefs']->setValue('display_external_cals', serialize($cals));
            }

        default:
            throw new Kronolith_Exception('Unknown calendar specification');
        }
    }


    /**
     * Places an exclusive lock for a calendar or an event.
     *
     * @param string $calendar  The id of the calendar to lock
     * @param string $event     The uid for the event to lock
     *
     * @return mixed   A lock ID on success, false if:
     *                   - The calendar is already locked
     *                   - The event is already locked
     *                   - A calendar lock was requested and an event is
     *                     already locked in the calendar
     * @throws Kronolith_Exception
     */
    public function lock($calendar, $event = null)
    {
        if (!array_key_exists($calendar,
            Kronolith::listInternalCalendars(false, Horde_Perms::EDIT))) {
            throw new Horde_Exception_PermissionDenied();
        }
        if (!empty($event)) {
            $uid = $calendar . ':' . $event;
        }
        return $GLOBALS['kronolith_shares']->getShare($calendar)->lock($GLOBALS['injector']->getInstance('Horde_Lock'), $uid);
    }

    /**
     * Releases a lock.
     *
     * @param array $calendar  The event to lock.
     * @param array $lockid    The lock id to unlock.
     *
     * @throws Kronolith_Exception
     */
    public function unlock($calendar, $lockid)
    {
        if (!array_key_exists($calendar,
            Kronolith::listInternalCalendars(false, Horde_Perms::EDIT))) {
            throw new Horde_Exception_PermissionDenied();
        }

        return $GLOBALS['kronolith_shares']->getShare($calendar)->unlock($GLOBALS['injector']->getInstance('Horde_Lock'), $lockid);
    }

    /**
     * Check for existing calendar or event locks.
     *
     * @param array $calendar  The calendar to check locks for.
     * @param array $event     The event to check locks for.
     *
     * @throws Kronolith_Exception
     */
    public function checkLocks($calendar, $event = null)
    {
        if (!array_key_exists($calendar,
            Kronolith::listInternalCalendars(false, Horde_Perms::READ))) {
            throw new Horde_Exception_PermissionDenied();
        }
        if (!empty($event)) {
            $uid = $calendar . ':' . $event;
        }
        return $GLOBALS['kronolith_shares']->getShare($calendar)->checkLocks($GLOBALS['injector']->getInstance('Horde_Lock'), $uid);
    }

    /**
     *
     * @return array  A list of calendars used to display free/busy information
     */
    public function getFbCalendars()
    {
        return (unserialize($GLOBALS['prefs']->getValue('fb_cals')));
    }

}
