<?php
/**
 * Kronolith base library.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */

/**
 * The Kronolith:: class provides functionality common to all of Kronolith.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class Kronolith
{
    /** Event status */
    const STATUS_NONE      = 0;
    const STATUS_TENTATIVE = 1;
    const STATUS_CONFIRMED = 2;
    const STATUS_CANCELLED = 3;
    const STATUS_FREE      = 4;

    /** Invitation responses */
    const RESPONSE_NONE      = 1;
    const RESPONSE_ACCEPTED  = 2;
    const RESPONSE_DECLINED  = 3;
    const RESPONSE_TENTATIVE = 4;

    /** Attendee status */
    const PART_REQUIRED = 1;
    const PART_OPTIONAL = 2;
    const PART_NONE     = 3;
    const PART_IGNORE   = 4;

    /** iTip requests */
    const ITIP_REQUEST = 1;
    const ITIP_CANCEL  = 2;

    /** The event can be delegated. */
    const PERMS_DELEGATE = 1024;

    /**
     * @var Kronolith_Tagger
     */
    static private $_tagger;

    /**
     * Converts a permission object to a json object.
     *
     * This methods filters out any permissions for the owner and converts the
     * user name if necessary.
     *
     * @param Horde_Perms_Permission $perm  A permission object.
     *
     * @return array  A hash suitable for json.
     */
    static public function permissionToJson(Horde_Perms_Permission $perm)
    {
        $json = $perm->data;
        if (isset($json['users'])) {
            $users = array();
            foreach ($json['users'] as $user => $value) {
                if ($user == $GLOBALS['registry']->getAuth()) {
                    continue;
                }
                $user = $GLOBALS['registry']->convertUsername($user, false);
                $users[$user] = $value;
            }
            if ($users) {
                $json['users'] = $users;
            } else {
                unset($json['users']);
            }
        }
        return $json;
   }

    /**
     * Returns all the alarms active on a specific date.
     *
     * @param Horde_Date $date    The date to check for alarms.
     * @param array $calendars    The calendars to check for events.
     * @param boolean $fullevent  Whether to return complete alarm objects or
     *                            only alarm IDs.
     *
     * @return array  The alarms active on the date. A hash with calendar names
     *                as keys and arrays of events or event ids as values.
     * @throws Kronolith_Exception
     */
    static public function listAlarms($date, $calendars, $fullevent = false)
    {
        $kronolith_driver = self::getDriver();

        $alarms = array();
        foreach ($calendars as $cal) {
            $kronolith_driver->open($cal);
            $alarms[$cal] = $kronolith_driver->listAlarms($date, $fullevent);
        }

        return $alarms;
    }

    /**
     * Searches for events with the given properties.
     *
     * @param object $query     The search query.
     * @param string $calendar  The calendar to search in the form
     *                          "Driver|calendar_id".
     *
     * @return array  The events.
     * @throws Kronolith_Exception
     */
    static public function search($query, $calendar = null)
    {
        if ($calendar) {
            $driver = explode('|', $calendar, 2);
            $calendars = array($driver[0] => array($driver[1]));
        } elseif (!empty($query->calendars)) {
            $calendars = $query->calendars;
        } else {
            $calendars = array(
                Horde_String::ucfirst($GLOBALS['conf']['calendar']['driver']) => $GLOBALS['display_calendars'],
                'Horde' => $GLOBALS['display_external_calendars'],
                'Ical' => $GLOBALS['display_remote_calendars'],
                'Holidays' => $GLOBALS['display_holidays']);
        }

        $events = array();
        foreach ($calendars as $type => $list) {
            $kronolith_driver = self::getDriver($type);
            foreach ($list as $cal) {
                $kronolith_driver->open($cal);
                $retevents = $kronolith_driver->search($query);
                self::mergeEvents($events, $retevents);
            }
        }

        return $events;
    }

    /**
     * Returns all the events that happen each day within a time period
     *
     * @deprecated
     *
     * @param Horde_Date $startDate    The start of the time range.
     * @param Horde_Date $endDate      The end of the time range.
     * @param array $calendars         The calendars to check for events.
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
     * @return array  The events happening in this time period.
     */
    static public function listEvents(
        $startDate, $endDate, $calendars = null, array $options = array())
    {
        $options = array_merge(array(
            'show_recurrence' => true,
            'has_alarm' => false,
            'show_remote' => true,
            'hide_exceptions' => false,
            'cover_dates' => true,
            'fetch_tags' => false), $options);

        $results = array();

        /* Internal calendars. */
        if (!isset($calendars)) {
            $calendars = $GLOBALS['display_calendars'];
        }
        $driver = self::getDriver();
        foreach ($calendars as $calendar) {
            try {
                $driver->open($calendar);
                $events = $driver->listEvents($startDate, $endDate, $options);
                self::mergeEvents($results, $events);
            } catch (Kronolith_Exception $e) {
                $GLOBALS['notification']->push($e);
            }
        }

        // Resource calendars
        if (!empty($GLOBALS['display_resource_calendars'])) {
            $driver = self::getDriver('Resource');
            foreach ($GLOBALS['display_resource_calendars'] as $calendar) {
                try {
                    $driver->open($calendar);
                    $events = $driver->listEvents(
                        $startDate, $endDate, array('show_recurrence' => $options['show_recurrence']));
                    self::mergeEvents($results, $events);
                } catch (Kronolith_Exception $e) {
                    $GLOBALS['notification']->push($e);
                }
            }
        }

        if ($options['show_remote']) {
            /* Horde applications providing listTimeObjects. */
            if (count($GLOBALS['display_external_calendars'])) {
                $driver = self::getDriver('Horde');
                foreach ($GLOBALS['display_external_calendars'] as $external_cal) {
                    try {
                        $driver->open($external_cal);
                        $events = $driver->listEvents(
                            $startDate, $endDate, array('show_recurrence' => $options['show_recurrence']));
                        self::mergeEvents($results, $events);
                    } catch (Kronolith_Exception $e) {
                        $GLOBALS['notification']->push($e);
                    }
                }
            }

            /* Remote Calendars. */
            foreach ($GLOBALS['display_remote_calendars'] as $url) {
                try {
                    $driver = self::getDriver('Ical', $url);
                    $events = $driver->listEvents(
                        $startDate, $endDate, array('show_recurrence' => $options['show_recurrence']));
                    self::mergeEvents($results, $events);
                } catch (Kronolith_Exception $e) {
                    $GLOBALS['notification']->push($e);
                }
            }

            /* Holidays. */
            if (count($GLOBALS['display_holidays']) && !empty($GLOBALS['conf']['holidays']['enable'])) {
                $driver = self::getDriver('Holidays');
                foreach ($GLOBALS['display_holidays'] as $holiday) {
                    try {
                        $driver->open($holiday);
                        $events = $driver->listEvents(
                            $startDate, $endDate, array('show_recurrence' => $options['show_recurrence']));
                        self::mergeEvents($results, $events);
                    } catch (Kronolith_Exception $e) {
                        $GLOBALS['notification']->push($e);
                    }
                }
            }
        }

        /* Sort events. */
        $results = self::sortEvents($results);

        return $results;
    }

    /**
     * Merges results from two listEvents() result sets.
     *
     * @param array $results  First list of events.
     * @param array $events   List of events to be merged into the first one.
     */
    static public function mergeEvents(&$results, $events)
    {
        foreach ($events as $day => $day_events) {
            if (isset($results[$day])) {
                $results[$day] = array_merge($results[$day], $day_events);
            } else {
                $results[$day] = $day_events;
            }
        }
        ksort($results);
    }

    /**
     * Calculates recurrences of an event during a certain period.
     */
    static public function addEvents(&$results, &$event, $startDate, $endDate,
                                     $showRecurrence, $json, $coverDates = true)
    {
        /* If the event has a custom timezone, we need to convert the
         * recurrence object to the event's timezone while calculating next
         * recurrences, to take DST changes in both the event's and the local
         * timezone into account. */
        $convert = $event->timezone &&
            $event->getDriver()->supportsTimezones();
        if ($convert) {
            $timezone = date_default_timezone_get();
        }

        if ($event->recurs() && $showRecurrence) {
            /* Recurring Event. */

            /* If the event ends at 12am and does not end at the same time
             * that it starts (0 duration), set the end date to the previous
             * day's end date. */
            if ($event->end->hour == 0 &&
                $event->end->min == 0 &&
                $event->end->sec == 0 &&
                $event->start->compareDateTime($event->end) != 0) {
                $event->end = new Horde_Date(
                    array('hour' =>  23,
                          'min' =>   59,
                          'sec' =>   59,
                          'month' => $event->end->month,
                          'mday' =>  $event->end->mday - 1,
                          'year' =>  $event->end->year));
            }

            /* We can't use the event duration here because we might cover a
             * daylight saving time switch. */
            $diff = array($event->end->year - $event->start->year,
                          $event->end->month - $event->start->month,
                          $event->end->mday - $event->start->mday,
                          $event->end->hour - $event->start->hour,
                          $event->end->min - $event->start->min);

            if ($event->start->compareDateTime($startDate) < 0) {
                /* The first time the event happens was before the period
                 * started. Start searching for recurrences from the start of
                 * the period. */
                $next = new Horde_Date(array('year' => $startDate->year,
                                             'month' => $startDate->month,
                                             'mday' => $startDate->mday),
                                       $event->timezone);
            } else {
                /* The first time the event happens is in the range; unless
                 * there is an exception for this ocurrence, add it. */
                if (!$event->recurrence->hasException($event->start->year,
                                                      $event->start->month,
                                                      $event->start->mday)) {
                    if ($coverDates) {
                        self::addCoverDates($results, $event, $event->start, $event->end, $json);
                    } else {
                        $results[$event->start->dateString()][$event->id] = $json ? $event->toJson() : $event;
                    }
                }

                /* Start searching for recurrences from the day after it
                 * starts. */
                $next = clone $event->start;
                ++$next->mday;
            }

            if ($convert) {
                $event->recurrence->start->setTimezone($event->timezone);
                if ($event->recurrence->hasRecurEnd()) {
                    $event->recurrence->recurEnd->setTimezone($event->timezone);
                }
            }

            /* Add all recurrences of the event. */
            $next = $event->recurrence->nextRecurrence($next);
            if ($convert) {
                /* Resetting after the nextRecurrence() call, because
                 * we need to test if the next recurrence in the
                 * event's timezone actually matches the interval we
                 * check in the local timezone. This is done on each
                 * nextRecurrence() further below. */
                $next->setTimezone($timezone);
            }
            while ($next !== false && $next->compareDate($endDate) <= 0) {
                if (!$event->recurrence->hasException($next->year, $next->month, $next->mday)) {
                    /* Add the event to all the days it covers. */
                    $nextEnd = clone $next;
                    $nextEnd->year  += $diff[0];
                    $nextEnd->month += $diff[1];
                    $nextEnd->mday  += $diff[2];
                    $nextEnd->hour  += $diff[3];
                    $nextEnd->min   += $diff[4];
                    if ($coverDates) {
                        self::addCoverDates($results, $event, $next, $nextEnd, $json);
                    } else {
                        $addEvent = clone $event;
                        $addEvent->start = $next;
                        $addEvent->end = $nextEnd;
                        $results[$addEvent->start->dateString()][$addEvent->id] = $json ? $addEvent->toJson() : $addEvent;

                    }
                }
                if ($convert) {
                    $next->setTimezone($event->timezone);
                }
                $next = $event->recurrence->nextRecurrence(
                    array('year' => $next->year,
                          'month' => $next->month,
                          'mday' => $next->mday + 1,
                          'hour' => $next->hour,
                          'min' => $next->min,
                          'sec' => $next->sec));
                if ($convert) {
                    $next->setTimezone($timezone);
                }
            }
        } else {
            /* Event only occurs once. */
            if (!$coverDates) {
                $results[$event->start->dateString()][$event->id] = $json ? $event->toJson() : $event;
            } else {
                $allDay = $event->isAllDay();

                /* Work out what day it starts on. */
                if ($startDate &&
                    $event->start->compareDateTime($startDate) < 0) {
                    /* It started before the beginning of the period. */
                    if ($event->recurs()) {
                        $eventStart = $event->recurrence->nextRecurrence($startDate);
                    } else {
                        $eventStart = clone $startDate;
                    }
                } else {
                    $eventStart = clone $event->start;
                }

                /* Work out what day it ends on. */
                if ($endDate &&
                    $event->end->compareDateTime($endDate) > 0) {
                    /* Ends after the end of the period. */
                    if (is_object($endDate)) {
                        $eventEnd = clone $endDate;
                    } else {
                        $eventEnd = $endDate;
                    }
                } else {
                    /* Need to perform some magic if this is a single instance
                     * of a recurring event since $event->end would be the
                     * original end date, not the recurrence's end date. */
                    if ($event->recurs()) {

                        $diff = array($event->end->year - $event->start->year,
                                      $event->end->month - $event->start->month,
                                      $event->end->mday - $event->start->mday,
                                      $event->end->hour - $event->start->hour,
                                      $event->end->min - $event->start->min);

                        $theEnd = $event->recurrence->nextRecurrence($eventStart);
                        $theEnd->year  += $diff[0];
                        $theEnd->month += $diff[1];
                        $theEnd->mday  += $diff[2];
                        $theEnd->hour  += $diff[3];
                        $theEnd->min   += $diff[4];
                        if ($convert) {
                            $eventStart->setTimezone($timezone);
                            $theEnd->setTimezone($timezone);
                        }
                    } else {
                        $theEnd = clone $event->end;
                    }

                    /* If the event doesn't end at 12am set the end date to
                     * the current end date. If it ends at 12am and does not
                     * end at the same time that it starts (0 duration), set
                     * the end date to the previous day's end date. */
                    if ($theEnd->hour != 0 ||
                        $theEnd->min != 0 ||
                        $theEnd->sec != 0 ||
                        $event->start->compareDateTime($theEnd) == 0 ||
                        $allDay) {
                        $eventEnd = clone $theEnd;
                    } else {
                        $eventEnd = new Horde_Date(
                            array('hour' =>  23,
                                  'min' =>   59,
                                  'sec' =>   59,
                                  'month' => $theEnd->month,
                                  'mday' =>  $theEnd->mday - 1,
                                  'year' =>  $theEnd->year));
                    }
                }

                /* Add the event to all the days it covers. This is similar to
                 * Kronolith::addCoverDates(), but for days in between the
                 * start and end day, the range is midnight to midnight, and
                 * for the edge days it's start to midnight, and midnight to
                 * end. */
                $i = $eventStart->mday;
                $loopDate = new Horde_Date(array('month' => $eventStart->month,
                                                 'mday' => $i,
                                                 'year' => $eventStart->year));
                while ($loopDate->compareDateTime($eventEnd) <= 0) {
                    if (!$allDay ||
                        $loopDate->compareDateTime($eventEnd) != 0) {
                        $addEvent = clone $event;

                        /* If this is the start day, set the start time to
                         * the real start time, otherwise set it to
                         * 00:00 */
                        if ($loopDate->compareDate($eventStart) == 0) {
                            $addEvent->start = $eventStart;
                        } else {
                            $addEvent->start = clone $loopDate;
                            $addEvent->start->hour = $addEvent->start->min = $addEvent->start->sec = 0;
                            $addEvent->first = false;
                        }

                        /* If this is the end day, set the end time to the
                         * real event end, otherwise set it to 23:59. */
                        if ($loopDate->compareDate($eventEnd) == 0) {
                            $addEvent->end = $eventEnd;
                        } else {
                            $addEvent->end = clone $loopDate;
                            $addEvent->end->hour = 23;
                            $addEvent->end->min = $addEvent->end->sec = 59;
                            $addEvent->last = false;
                        }

                        $results[$loopDate->dateString()][$addEvent->id] = $json ? $addEvent->toJson($allDay) : $addEvent;
                    }

                    $loopDate = new Horde_Date(
                        array('month' => $eventStart->month,
                              'mday' => ++$i,
                              'year' => $eventStart->year));
                }
            }
        }
        ksort($results);
    }

    /**
     * Adds an event to all the days it covers.
     *
     * @param array $result           The current result list.
     * @param Kronolith_Event $event  An event object.
     * @param Horde_Date $eventStart  The event's start at the actual
     *                                recurrence.
     * @param Horde_Date $eventEnd    The event's end at the actual recurrence.
     * @param boolean $json           Store the results of the events' toJson()
     *                                method?
     */
    static public function addCoverDates(&$results, $event, $eventStart,
                                         $eventEnd, $json)
    {
        $loopDate = new Horde_Date($eventStart->year, $eventStart->month, $eventStart->mday);
        $allDay = $event->isAllDay();
        while ($loopDate->compareDateTime($eventEnd) <= 0) {
            if (!$allDay ||
                $loopDate->compareDateTime($eventEnd) != 0) {
                $addEvent = clone $event;
                $addEvent->start = $eventStart;
                $addEvent->end = $eventEnd;
                if ($loopDate->compareDate($eventStart) != 0) {
                    $addEvent->first = false;
                }
                if ($loopDate->compareDate($eventEnd) != 0) {
                    $addEvent->last = false;
                }
                if ($addEvent->recurs() &&
                    $addEvent->recurrence->hasCompletion($loopDate->year, $loopDate->month, $loopDate->mday)) {
                    $addEvent->status = Kronolith::STATUS_CANCELLED;
                }
                $results[$loopDate->dateString()][$addEvent->id] = $json ? $addEvent->toJson($allDay) : $addEvent;
            }
            $loopDate->mday++;
        }
    }

    /**
     * Adds an event to set of search results.
     *
     * @param array $events           The list of events to update with the new
     *                                event.
     * @param Kronolith_Event $event  An event from a search result.
     * @param stdClass $query         A search query.
     * @param boolean $json           Store the results of the events' toJson()
     *                                method?
     */
    static public function addSearchEvents(&$events, $event, $query, $json)
    {
        static $now;
        if (!isset($now)) {
            $now = new Horde_Date($_SERVER['REQUEST_TIME']);
        }
        $showRecurrence = true;
        if ($event->recurs()) {
            if (empty($query->start) && empty($query->end)) {
                $eventStart = $event->start;
                $eventEnd = $event->end;
            } else {
                if (empty($query->end)) {
                    $convert = $event->timezone &&
                        $event->getDriver()->supportsTimezones();
                    if ($convert) {
                        $timezone = date_default_timezone_get();
                        $event->recurrence->start->setTimezone($event->timezone);
                        if ($event->recurrence->hasRecurEnd()) {
                            $event->recurrence->recurEnd->setTimezone($event->timezone);
                        }
                    }
                    $eventEnd = $event->recurrence->nextRecurrence($now);
                    if (!$eventEnd) {
                        return;
                    }
                    if ($convert) {
                        $eventEnd->setTimezone($timezone);
                    }
                } else {
                    $eventEnd = $query->end;
                }
                if (empty($query->start)) {
                    $eventStart = $event->start;
                    $showRecurrence = false;
                } else {
                    $eventStart = $query->start;
                }
            }
        } else {
            $eventStart = $event->start;
            $eventEnd = $event->end;
        }
        self::addEvents($events, $event, $eventStart, $eventEnd, $showRecurrence, $json, false);
    }

    /**
     * Returns the number of events in calendars that the current user owns.
     *
     * @return integer  The number of events.
     */
    static public function countEvents()
    {
        static $count;
        if (isset($count)) {
            return $count;
        }

        $kronolith_driver = self::getDriver();
        $calendars = self::listInternalCalendars(true, Horde_Perms::ALL);
        $current_calendar = $kronolith_driver->calendar;

        $count = 0;
        foreach (array_keys($calendars) as $calendar) {
            $kronolith_driver->open($calendar);
            try {
                $count += $kronolith_driver->countEvents();
            } catch (Exception $e) {
            }
        }

        /* Reopen last calendar. */
        $kronolith_driver->open($current_calendar);

        return $count;
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
        $text = trim($text);
        if (strpos($text, "\n") !== false) {
            list($title, $description) = explode($text, "\n", 2);
        } else {
            $title = $text;
            $description = '';
        }
        $title = trim($title);
        $description = trim($description);

        $dateParser = Horde_Date_Parser::factory(array('locale' => $GLOBALS['language']));
        $r = $dateParser->parse($title, array('return' => 'result'));
        if (!($d = $r->guess())) {
            throw new Horde_Exception(sprintf(_("Cannot parse event description \"%s\""), Horde_String::truncate($text)));
        }

        $title = $r->untaggedText();

        $kronolith_driver = self::getDriver(null, $calendar);
        $event = $kronolith_driver->getEvent();
        $event->initialized = true;
        $event->title = $title;
        $event->description = $description;
        $event->start = $d;
        $event->end = $d->add(array('hour' => 1));
        $event->save();

        return $event;
    }

    /**
     * Initial app setup code.
     *
     * Globals defined:
     *   - all_calendars
     *   - all_remote_calendars
     *   - display_calendars
     *   - display_holidays
     *   - display_external_calendars
     *   - display_remote_calendars
     *   - display_resource_calendars
     */
    static public function initialize()
    {
        global $conf, $prefs, $registry, $session;

        /* Update preferences for which calendars to display. If the
         * user doesn't have any selected calendars to view then fall
         * back to an available calendar. An empty string passed in this
         * parameter will clear any existing session value.*/
        if (($calId = Horde_Util::getFormData('display_cal')) !== null) {
            $session->set('kronolith', 'display_cal', $calId);
        } else {
            $calId = $session->get('kronolith', 'display_cal');
        }

        if (strlen($calId)) {
            /* Specifying a value for display_cal is always to make sure
             * that only the specified calendars are shown. Use the
             * "toggle_calendar" argument to toggle the state of a single
             * calendar. */
            $GLOBALS['display_calendars'] = array();
            $GLOBALS['display_remote_calendars'] = array();
            $GLOBALS['display_external_calendars'] = array();
            $GLOBALS['display_resource_calendars'] = array();
            $GLOBALS['display_holidays'] = array();

            if (strncmp($calId, 'remote_', 7) === 0) {
                $GLOBALS['display_remote_calendars'][] = substr($calId, 7);
            } elseif (strncmp($calId, 'external_', 9) === 0) {
                $GLOBALS['display_external_calendars'][] = substr($calId, 9);
            } elseif (strncmp($calId, 'resource_', 9) === 0) {
                $GLOBALS['display_resource_calendars'][] = substr($calId, 9);
            } elseif (strncmp($calId, 'holidays_', 9) === 0) {
                $GLOBALS['display_holidays'][] = substr($calId, 9);
            } else {
                $GLOBALS['display_calendars'][] = (strncmp($calId, 'internal_', 9) === 0)
                    ? substr($calId, 9)
                    : $calId;
            }
        } else {
            /* Fetch display preferences. */
            $display_prefs = array(
                'display_cals' => 'display_calendars',
                'display_remote_cals' => 'display_remote_calendars',
                'display_external_cals' => 'display_external_calendars',
                'holiday_drivers' => 'display_holidays',
                'display_resource_cals' => 'display_resource_calendars'
            );
            foreach ($display_prefs as $key => $val) {
                $pref_val = @unserialize($prefs->getValue($key));
                $GLOBALS[$val] = is_array($pref_val)
                    ? $pref_val
                    : array();
            }

            if (empty($conf['holidays']['enable'])) {
                $GLOBALS['display_holidays'] = array();
            }
        }

        /* Check for single "toggle" calendars. */
        if (($calId = Horde_Util::getFormData('toggle_calendar')) !== null) {
            if (strncmp($calId, 'remote_', 7) === 0) {
                $calId = substr($calId, 7);
                if (($key = array_search($calId, $GLOBALS['display_remote_calendars'])) === false) {
                    $GLOBALS['display_remote_calendars'][] = $calId;
                } else {
                    unset($GLOBALS['display_remote_calendars'][$key]);
                }
            } elseif ((strncmp($calId, 'external_', 9) === 0 &&
                       ($calId = substr($calId, 9))) ||
                      (strncmp($calId, 'tasklists_', 10) === 0 &&
                       ($calId = substr($calId, 10)))) {
                if (($key = array_search($calId, $GLOBALS['display_external_calendars'])) === false) {
                    $GLOBALS['display_external_calendars'][] = $calId;
                } else {
                    unset($GLOBALS['display_external_calendars'][$key]);
                }

                if (strpos($calId, 'tasks/') === 0) {
                    $tasklists = array();
                    foreach ($GLOBALS['display_external_calendars'] as $id) {
                        if (strpos($id, 'tasks/') === 0) {
                            $tasklists[] = substr($id, 6);
                        }
                    }
                    try {
                        $registry->tasks->setDisplayedTasklists($tasklists);
                    } catch (Horde_Exception $e) {}
                }
            } elseif (strncmp($calId, 'holiday_', 8) === 0) {
                $calId = substr($calId, 8);
                if (($key = array_search($calId, $GLOBALS['display_holidays'])) === false) {
                    $GLOBALS['display_holidays'][] = $calId;
                } else {
                    unset($GLOBALS['display_holidays'][$key]);
                }
            } elseif (strncmp($calId, 'resource_', 9) === 0) {
                $calId = substr($calId, 9);
                if (($key = array_search($calId, $GLOBALS['display_resource_calendars'])) === false) {
                    $GLOBALS['display_resource_calendars'][] = $calId;
                } else {
                    unset($GLOBALS['display_resource_calendars'][$key]);
                }
                $prefs->setValue('display_resource_cals', serialize($GLOBALS['display_resource_calendars']));
            } elseif (($key = array_search($calId, $GLOBALS['display_calendars'])) === false) {
                $GLOBALS['display_calendars'][] = $calId;
            } else {
                unset($GLOBALS['display_calendars'][$key]);
            }

            $prefs->setValue('display_cals', serialize($GLOBALS['display_calendars']));
        }

        /* Make sure all shares exists now to save on checking later. */
        $GLOBALS['all_calendars'] = array();
        foreach (self::listInternalCalendars() as $id => $calendar) {
            $GLOBALS['all_calendars'][$id] = new Kronolith_Calendar_Internal(array('share' => $calendar));
        }
        $GLOBALS['display_calendars'] = array_intersect($GLOBALS['display_calendars'], array_keys($GLOBALS['all_calendars']));

        /* Make sure all the remote calendars still exist. */
        $tmp = $GLOBALS['display_remote_calendars'];
        $GLOBALS['all_remote_calendars'] = $GLOBALS['display_remote_calendars'] = array();
        $calendars = @unserialize($GLOBALS['prefs']->getValue('remote_cals'));
        if (!is_array($calendars)) {
            $calendars = array();
        }

        foreach ($calendars as $calendar) {
            $GLOBALS['all_remote_calendars'][$calendar['url']] = new Kronolith_Calendar_Remote($calendar);
            if (in_array($calendar['url'], $tmp)) {
                $GLOBALS['display_remote_calendars'][] = $calendar['url'];
            }
        }
        $GLOBALS['prefs']->setValue('display_remote_cals', serialize($GLOBALS['display_remote_calendars']));

        /* Make sure all the holiday drivers still exist. */
        $GLOBALS['all_holidays'] = array();
        if (!empty($GLOBALS['conf']['holidays']['enable'])) {
            if (class_exists('Date_Holidays')) {
                foreach (Date_Holidays::getInstalledDrivers() as $driver) {
                    if ($driver['id'] == 'Composite') {
                        continue;
                    }
                    $GLOBALS['all_holidays'][$driver['id']] = new Kronolith_Calendar_Holiday(array('driver' => $driver));
                    ksort($GLOBALS['all_holidays']);
                }
            }
        }
        $_temp = $GLOBALS['display_holidays'];
        $GLOBALS['display_holidays'] = array();
        foreach (array_keys($GLOBALS['all_holidays']) as $id) {
            if (in_array($id, $_temp)) {
                $GLOBALS['display_holidays'][] = $id;
            }
        }
        $GLOBALS['prefs']->setValue('holiday_drivers', serialize($GLOBALS['display_holidays']));

        /* Get a list of external calendars. */
        $GLOBALS['all_external_calendars'] = array();

        /* Make sure all task lists exist. */
        if (self::hasApiPermission('tasks') &&
            $GLOBALS['registry']->hasMethod('tasks/listTimeObjects')) {
            try {
                $tasklists = $GLOBALS['registry']->tasks->listTasklists();
                $categories = $GLOBALS['registry']->call('tasks/listTimeObjectCategories');
                foreach ($categories as $name => $description) {
                    if (!isset($tasklists[$name])) {
                        continue;
                    }
                    $GLOBALS['all_external_calendars']['tasks/' . $name] = new Kronolith_Calendar_External_Tasks(array('api' => 'tasks', 'name' => $description['title'], 'share' => $tasklists[$name], 'type' => 'share'));
                }
            } catch (Horde_Exception $e) {
                Horde::logMessage($e, 'DEBUG');
            }
        }

        if ($GLOBALS['session']->exists('kronolith', 'all_external_calendars')) {
            foreach ($GLOBALS['session']->get('kronolith', 'all_external_calendars') as $calendar) {
                if (!self::hasApiPermission($calendar['a']) ||
                    $calendar['a'] == 'tasks') {
                    continue;
                }
                $GLOBALS['all_external_calendars'][$calendar['a'] . '/' . $calendar['n']] = new Kronolith_Calendar_External(array('api' => $calendar['a'], 'name' => $calendar['d'], 'id' => $calendar['n'], 'type' => $calendar['t']));
            }
        } else {
            $apis = array_unique($GLOBALS['registry']->listAPIs());
            $ext_cals = array();

            foreach ($apis as $api) {
                if ($api == 'tasks' ||
                    !self::hasApiPermission($api) ||
                    !$GLOBALS['registry']->hasMethod($api . '/listTimeObjects')) {
                    continue;
                }
                try {
                    $categories = $GLOBALS['registry']->call($api . '/listTimeObjectCategories');
                } catch (Horde_Exception $e) {
                    Horde::logMessage($e, 'DEBUG');
                    continue;
                }

                foreach ($categories as $name => $description) {
                    $GLOBALS['all_external_calendars'][$api . '/' . $name] = new Kronolith_Calendar_External(array('api' => $api, 'name' => $description['title'], 'id' => $name, 'type' => $description['type']));
                    $ext_cals[] = array(
                        'a' => $api,
                        'n' => $name,
                        'd' => $description['title'],
                        't' => $description['type']
                    );
                }
            }

            $GLOBALS['session']->set('kronolith', 'all_external_calendars', $ext_cals);
        }

        /* Make sure all the external calendars still exist. */
        $_tasklists = $_temp = $GLOBALS['display_external_calendars'];
        if (self::hasApiPermission('tasks')) {
            try {
                $_tasklists = $GLOBALS['registry']->tasks->getDisplayedTasklists();
            } catch (Horde_Exception $e) {
            }
        }
        $GLOBALS['display_external_calendars'] = array();
        foreach ($GLOBALS['all_external_calendars'] as $id => $calendar) {
            if ((substr($id, 0, 6) == 'tasks/' &&
                 in_array(substr($id, 6), $_tasklists)) ||
                in_array($id, $_temp)) {
                $GLOBALS['display_external_calendars'][] = $id;
            }
        }
        $GLOBALS['prefs']->setValue('display_external_cals', serialize($GLOBALS['display_external_calendars']));

        /* If an authenticated user doesn't own a calendar, create it. */
        if (!empty($GLOBALS['conf']['share']['auto_create']) &&
            $GLOBALS['registry']->getAuth() &&
            !count(self::listInternalCalendars(true))) {
            $calendars = $GLOBALS['injector']
                ->getInstance('Kronolith_Factory_Calendars')
                ->create();

            $share = $calendars->createDefaultShare();
            $GLOBALS['all_calendars'][$share->getName()] = new Kronolith_Calendar_Internal(array('share' => $share));
            $GLOBALS['display_calendars'][] = $share->getName();
            $GLOBALS['prefs']->setValue('default_share', $share->getName());

            /* Calendar auto-sharing with the user's groups */
            if ($GLOBALS['conf']['autoshare']['shareperms'] != 'none') {
                $perm_value = 0;
                switch ($GLOBALS['conf']['autoshare']['shareperms']) {
                case 'read':
                    $perm_value = Horde_Perms::READ | Horde_Perms::SHOW;
                    break;
                case 'edit':
                    $perm_value = Horde_Perms::READ | Horde_Perms::SHOW | Horde_Perms::EDIT;
                    break;
                case 'full':
                    $perm_value = Horde_Perms::READ | Horde_Perms::SHOW | Horde_Perms::EDIT | Horde_Perms::DELETE;
                    break;
                }

                try {
                    $group_list = $GLOBALS['injector']
                        ->getInstance('Horde_Group')
                        ->listGroups($GLOBALS['registry']->getAuth());
                    if (count($group_list)) {
                        $perm = $share->getPermission();
                        // Add the default perm, not added otherwise
                        foreach (array_keys($group_list) as $group_id) {
                            $perm->addGroupPermission($group_id, $perm_value, false);
                        }
                        $share->setPermission($perm);
                        $GLOBALS['notification']->push(sprintf(_("New calendar created and automatically shared with the following group(s): %s."), implode(', ', $group_list)), 'horde.success');
                    }
                } catch (Horde_Group_Exception $e) {}
            }

            $GLOBALS['prefs']->setValue('display_cals', serialize($GLOBALS['display_calendars']));
        }
    }

    /**
     * Returns the real name, if available, of a user.
     */
    static public function getUserName($uid)
    {
        static $names = array();

        if (!isset($names[$uid])) {
            $ident = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($uid);
            $ident->setDefault($ident->getDefault());
            $names[$uid] = $ident->getValue('fullname');
            if (empty($names[$uid])) {
                $names[$uid] = $uid;
            }
        }

        return $names[$uid];
    }

    /**
     * Returns the email address, if available, of a user.
     */
    static public function getUserEmail($uid)
    {
        static $emails = array();

        if (!isset($emails[$uid])) {
            $ident = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($uid);
            $emails[$uid] = $ident->getValue('from_addr');
            if (empty($emails[$uid])) {
                $emails[$uid] = $uid;
            }
        }

        return $emails[$uid];
    }

    /**
     * Checks if an email address belongs to a user.
     */
    static public function isUserEmail($uid, $email)
    {
        static $emails = array();

        if (!isset($emails[$uid])) {
            $ident = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($uid);

            $addrs = $ident->getAll('from_addr');
            $addrs[] = $uid;

            $emails[$uid] = $addrs;
        }

        return in_array($email, $emails[$uid]);
    }

    /**
     * Maps a Kronolith recurrence value to a translated string suitable for
     * display.
     *
     * @param integer $type  The recurrence value; one of the
     *                       Horde_Date_Recurrence::RECUR_XXX constants.
     *
     * @return string  The translated displayable recurrence value string.
     */
    static public function recurToString($type)
    {
        switch ($type) {
        case Horde_Date_Recurrence::RECUR_NONE:
            return _("Does not recur");

        case Horde_Date_Recurrence::RECUR_DAILY:
            return _("Recurs daily");

        case Horde_Date_Recurrence::RECUR_WEEKLY:
            return _("Recurs weekly");

        case Horde_Date_Recurrence::RECUR_MONTHLY_DATE:
        case Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY:
            return _("Recurs monthly");

        case Horde_Date_Recurrence::RECUR_YEARLY_DATE:
        case Horde_Date_Recurrence::RECUR_YEARLY_DAY:
        case Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY:
            return _("Recurs yearly");
        }
    }

    /**
     * Maps a Kronolith meeting status string to a translated string suitable
     * for display.
     *
     * @param integer $status  The meeting status; one of the
     *                         Kronolith::STATUS_XXX constants.
     *
     * @return string  The translated displayable meeting status string.
     */
    static public function statusToString($status)
    {
        switch ($status) {
        case self::STATUS_CONFIRMED:
            return _("Confirmed");

        case self::STATUS_CANCELLED:
            return _("Cancelled");

        case self::STATUS_FREE:
            return _("Free");

        case self::STATUS_TENTATIVE:
        default:
            return _("Tentative");
        }
    }

    /**
     * Maps a Kronolith attendee response string to a translated string
     * suitable for display.
     *
     * @param integer $response  The attendee response; one of the
     *                           Kronolith::RESPONSE_XXX constants.
     *
     * @return string  The translated displayable attendee response string.
     */
    static public function responseToString($response)
    {
        switch ($response) {
        case self::RESPONSE_ACCEPTED:
            return _("Accepted");

        case self::RESPONSE_DECLINED:
            return _("Declined");

        case self::RESPONSE_TENTATIVE:
            return _("Tentative");

        case self::RESPONSE_NONE:
        default:
            return _("None");
        }
    }

    /**
     * Maps a Kronolith attendee participation string to a translated string
     * suitable for display.
     *
     * @param integer $part  The attendee participation; one of the
     *                       Kronolith::PART_XXX constants.
     *
     * @return string  The translated displayable attendee participation
     *                 string.
     */
    static public function partToString($part)
    {
        switch ($part) {
        case self::PART_OPTIONAL:
            return _("Optional");

        case self::PART_NONE:
            return _("None");

        case self::PART_REQUIRED:
        default:
            return _("Required");
        }
    }

    /**
     * Maps an iCalendar attendee response string to the corresponding
     * Kronolith value.
     *
     * @param string $response  The attendee response.
     *
     * @return string  The Kronolith response value.
     */
    static public function responseFromICal($response)
    {
        switch (Horde_String::upper($response)) {
        case 'ACCEPTED':
            return self::RESPONSE_ACCEPTED;

        case 'DECLINED':
            return self::RESPONSE_DECLINED;

        case 'TENTATIVE':
            return self::RESPONSE_TENTATIVE;

        case 'NEEDS-ACTION':
        default:
            return self::RESPONSE_NONE;
        }
    }

    /**
     * Builds the HTML for an event status widget.
     *
     * @param string $name     The name of the widget.
     * @param string $current  The selected status value.
     * @param string $any      Whether an 'any' item should be added
     *
     * @return string  The HTML <select> widget.
     */
    static public function buildStatusWidget($name,
                                             $current = self::STATUS_CONFIRMED,
                                             $any = false)
    {
        $html = "<select id=\"$name\" name=\"$name\">";

        $statii = array(
            self::STATUS_FREE,
            self::STATUS_TENTATIVE,
            self::STATUS_CONFIRMED,
            self::STATUS_CANCELLED
        );

        if (!isset($current)) {
            $current = self::STATUS_NONE;
        }

        if ($any) {
            $html .= "<option value=\"" . self::STATUS_NONE . "\"";
            $html .= ($current == self::STATUS_NONE) ? ' selected="selected">' : '>';
            $html .= _("Any") . "</option>";
        }

        foreach ($statii as $status) {
            $html .= "<option value=\"$status\"";
            $html .= ($status == $current) ? ' selected="selected">' : '>';
            $html .= self::statusToString($status) . "</option>";
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * Returns all internal calendars a user has access to, according
     * to several parameters/permission levels.
     *
     * This method takes the $conf['share']['hidden'] setting into account. If
     * this setting is enabled, even if requesting permissions different than
     * SHOW, it will only return calendars that the user owns or has SHOW
     * permissions for. For checking individual calendar's permissions, use
     * hasPermission() instead.
     *
     * @param boolean $owneronly   Only return calenders that this user owns?
     *                             Defaults to false.
     * @param integer $permission  The permission to filter calendars by.
     *
     * @return array  The calendar list.
     */
    static public function listInternalCalendars($owneronly = false,
                                                 $permission = Horde_Perms::SHOW)
    {
        if ($owneronly && !$GLOBALS['registry']->getAuth()) {
            return array();
        }

        $kronolith_shares = $GLOBALS['injector']->getInstance('Kronolith_Shares');

        if ($owneronly || empty($GLOBALS['conf']['share']['hidden'])) {
            try {
                $calendars = $kronolith_shares->listShares(
                    $GLOBALS['registry']->getAuth(),
                    array('perm' => $permission,
                          'attributes' => $owneronly ? $GLOBALS['registry']->getAuth() : null,
                          'sort_by' => 'name'));
            } catch (Horde_Share_Exception $e) {
                Horde::logMessage($e);
                return array();
            }
        } else {
            try {
                $calendars = $kronolith_shares->listShares(
                    $GLOBALS['registry']->getAuth(),
                    array('perm' => $permission,
                          'attributes' => $GLOBALS['registry']->getAuth(),
                          'sort_by' => 'name'));
            } catch (Horde_Share_Exception $e) {
                Horde::logMessage($e);
                return array();
            }
            $display_calendars = @unserialize($GLOBALS['prefs']->getValue('display_cals'));
            if (is_array($display_calendars)) {
                foreach ($display_calendars as $id) {
                    try {
                        $calendar = $kronolith_shares->getShare($id);
                        if ($calendar->hasPermission($GLOBALS['registry']->getAuth(), $permission)) {
                            $calendars[$id] = $calendar;
                        }
                    } catch (Horde_Exception_NotFound $e) {
                    } catch (Horde_Share_Exception $e) {
                        Horde::logMessage($e);
                        return array();
                    }
                }
            }
        }

        $default_share = $GLOBALS['prefs']->getValue('default_share');
        if (isset($calendars[$default_share])) {
            $calendar = $calendars[$default_share];
            unset($calendars[$default_share]);
            $calendars = array($default_share => $calendar) + $calendars;
        }

        return $calendars;
    }

    /**
     * Returns all calendars a user has access to, according to several
     * parameters/permission levels.
     *
     * @param boolean $owneronly   Only return calenders that this user owns?
     *                             Defaults to false.
     * @param boolean $display     Only return calendars that are supposed to
     *                             be displayed per configuration and user
     *                             preference.
     * @param integer $permission  The permission to filter calendars by.
     *
     * @return array  The calendar list.
     */
    static public function listCalendars($permission = Horde_Perms::SHOW,
                                         $display = false,
                                         $flat = true)
    {
        $calendars = array();
        foreach ($GLOBALS['all_calendars'] as $id => $calendar) {
            if ($calendar->hasPermission($permission) &&
                (!$display || $calendar->display())) {
                if ($flat) {
                    $calendars['internal_' . $id] = $calendar;
                }
            }
        }

        foreach ($GLOBALS['all_remote_calendars'] as $id => $calendar) {
            try {
                if ($calendar->hasPermission($permission) &&
                    (!$display || $calendar->display())) {
                    if ($flat) {
                        $calendars['remote_' . $id] = $calendar;
                    }
                }
            } catch (Kronolith_Exception $e) {
                $GLOBALS['notification']->push(sprintf(_("The calendar %s returned the error: %s"), $calendar->name(), $e->getMessage()), 'horde.error');
            }
        }

        foreach ($GLOBALS['all_external_calendars'] as $id => $calendar) {
            if ($calendar->hasPermission($permission) &&
                (!$display || $calendar->display())) {
                if ($flat) {
                    $calendars['external_' . $id] = $calendar;
                }
            }
        }

        foreach ($GLOBALS['all_holidays'] as $id => $calendar) {
            if ($calendar->hasPermission($permission) &&
                (!$display || $calendar->display())) {
                if ($flat) {
                    $calendars['holiday_' . $id] = $calendar;
                }
            }
        }

        return $calendars;
    }

    /**
     * Returns the default calendar for the current user at the specified
     * permissions level.
     *
     * @param integer $permission  Horde_Perms constant for permission level required.
     * @param boolean $owner_only  Only consider owner-owned calendars.
     *
     * @return mixed  The calendar id, or false if none found.
     */
    static public function getDefaultCalendar($permission = Horde_Perms::SHOW, $owner_only = false)
    {
        global $prefs;

        $default_share = $prefs->getValue('default_share');
        $calendars = self::listInternalCalendars($owner_only, $permission);

        if (isset($calendars[$default_share]) ||
            $prefs->isLocked('default_share')) {
            return $default_share;
        } elseif (isset($GLOBALS['all_calendars'][$GLOBALS['registry']->getAuth()]) &&
                  $GLOBALS['all_calendars'][$GLOBALS['registry']->getAuth()]->hasPermission($permission)) {
            // This is for older, existing default shares. New default shares
            // are not named as the username.
            return $GLOBALS['registry']->getAuth();
        } elseif (count($calendars)) {
            return key($calendars);
        }

        return false;
    }

    /**
     * Returns the calendars that should be used for syncing.
     *
     * @param boolean $prune  Remove calendar ids from the sync list that no
     *                        longer exist. The values are pruned *after* the
     *                        results are passed back to the client to give
     *                        sync clients a chance to remove their entries.
     *
     * @return array  An array of calendar ids
     */
    static public function getSyncCalendars($prune = false)
    {
        $haveRemoved = false;
        $cs = unserialize($GLOBALS['prefs']->getValue('sync_calendars'));
        if (!empty($cs)) {
            if ($prune) {
                $calendars = self::listInternalCalendars(true, Horde_Perms::EDIT);
                $cscopy = array_flip($cs);
                foreach ($cs as $c) {
                    if (empty($calendars[$c])) {
                        unset($cscopy[$c]);
                        $haveRemoved = true;
                    }
                }
                if ($haveRemoved) {
                    $GLOBALS['prefs']->setValue('sync_calendars', serialize(array_flip($cscopy)));
                }
            }
            return $cs;
        }

        if ($cs = self::getDefaultCalendar(Horde_Perms::EDIT, true)) {
            return array($cs);
        }

        return array();
    }

    /**
     * Adds <link> tags for calendar feeds to the HTML header.
     */
    static public function addCalendarLinks()
    {
        foreach ($GLOBALS['display_calendars'] as $calendar) {
            $GLOBALS['page_output']->addLinkTag(array(
                'href' => Kronolith::feedUrl($calendar),
                'type' => 'application/atom+xml'
            ));
        }
    }

    /**
     * Returns whether the current user has certain permissions on a calendar.
     *
     * @param string $calendar  A calendar id.
     * @param integer $perm     A Horde_Perms permission mask.
     *
     * @return boolean  True if the current user has the requested permissions.
     */
    static public function hasPermission($calendar, $perm)
    {
        try {
            $share = $GLOBALS['injector']->getInstance('Kronolith_Shares')->getShare($calendar);
            if (!$share->hasPermission($GLOBALS['registry']->getAuth(), $perm)) {
                throw new Horde_Exception_NotFound();
            }
        } catch (Horde_Exception_NotFound $e) {
            return false;
        }
        return true;
    }

    /**
     * Creates a new share.
     *
     * @param array $info  Hash with calendar information.
     *
     * @return Horde_Share  The new share.
     * @throws Kronolith_Exception
     */
    static public function addShare($info)
    {
        $kronolith_shares = $GLOBALS['injector']->getInstance('Kronolith_Shares');

        try {
            $calendar = $kronolith_shares->newShare($GLOBALS['registry']->getAuth(), strval(new Horde_Support_Randomid()), $info['name']);
        } catch (Horde_Share_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        $calendar->set('color', $info['color']);
        $calendar->set('desc', $info['description']);
        if (!empty($info['system'])) {
            $calendar->set('owner', null);
        }
        $tagger = self::getTagger();
        $tagger->tag($calendar->getName(), $info['tags'], $calendar->get('owner'), 'calendar');

        try {
            $kronolith_shares->addShare($calendar);
        } catch (Horde_Share_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        $GLOBALS['display_calendars'][] = $calendar->getName();
        $GLOBALS['prefs']->setValue('display_cals', serialize($GLOBALS['display_calendars']));

        return $calendar;
    }

    /**
     * Updates an existing share.
     *
     * @param Horde_Share $share  The share to update.
     * @param array $info         Hash with calendar information.
     *
     * @throws Kronolith_Exception
     */
    static public function updateShare(&$calendar, $info)
    {
        if (!$GLOBALS['registry']->getAuth() ||
            ($calendar->get('owner') != $GLOBALS['registry']->getAuth() &&
             (!is_null($calendar->get('owner')) || !$GLOBALS['registry']->isAdmin()))) {
            throw new Kronolith_Exception(_("You are not allowed to change this calendar."));
        }

        $calendar->set('name', $info['name']);
        $calendar->set('color', $info['color']);
        $calendar->set('desc', $info['description']);
        $calendar->set('owner', empty($info['system']) ? $GLOBALS['registry']->getAuth() : null);

        try {
            $calendar->save();
        } catch (Horde_Share_Exception $e) {
            throw new Kronolith_Exception(sprintf(_("Unable to save calendar \"%s\": %s"), $info['name'], $e->getMessage()));
        }

        $tagger = self::getTagger();
        $tagger->replaceTags($calendar->getName(), $info['tags'], $calendar->get('owner'), 'calendar');
    }

    /**
     * Deletes a share.
     *
     * @param Horde_Share $calendar  The share to delete.
     *
     * @throws Kronolith_Exception
     */
    static public function deleteShare($calendar)
    {
        if (!$GLOBALS['registry']->getAuth() ||
            ($calendar->get('owner') != $GLOBALS['registry']->getAuth() &&
             (!is_null($calendar->get('owner')) ||
              !$GLOBALS['registry']->isAdmin()))) {
            throw new Kronolith_Exception(_("You are not allowed to delete this calendar."));
        }

        // Delete the calendar.
        try {
            self::getDriver()->delete($calendar->getName());
        } catch (Exception $e) {
            throw new Kronolith_Exception(sprintf(_("Unable to delete \"%s\": %s"), $calendar->get('name'), $e->getMessage()));
        }

        // Remove share and all groups/permissions.
        try {
            $GLOBALS['injector']
                ->getInstance('Kronolith_Shares')
                ->removeShare($calendar);
        } catch (Horde_Share_Exception $e) {
            throw new Kronolith_Exception($e);
        }
    }

    /**
     * Reads a submitted permissions form and updates the share permissions.
     *
     * @param Horde_Share_Object $share  The share to update.
     *
     * @return array  A list of error messages.
     * @throws Kronolith_Exception
     */
    static public function readPermsForm($share)
    {
        $auth = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Auth')->create();
        $perm = $share->getPermission();
        $errors = array();

        if ($GLOBALS['conf']['share']['notify']) {
            $identity = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Identity')
                ->create();
            $mail = new Horde_Mime_Mail(array(
                'From' => $identity->getDefaultFromAddress(true),
                'User-Agent' => 'Kronolith ' . $GLOBALS['registry']->getVersion()));
            $image = self::getImagePart('big_share.png');
            $view = new Horde_View(array('templatePath' => KRONOLITH_TEMPLATES . '/share'));
            new Horde_View_Helper_Text($view);
            $view->identity = $identity;
            $view->calendar = $share->get('name');
            $view->imageId = $image->getContentId();
        }

        // Process owner and owner permissions.
        $old_owner = $share->get('owner');
        $new_owner_backend = Horde_Util::getFormData('owner_select', Horde_Util::getFormData('owner_input', $old_owner));
        $new_owner = $GLOBALS['registry']->convertUsername($new_owner_backend, true);
        if ($old_owner !== $new_owner && !empty($new_owner)) {
            if ($old_owner != $GLOBALS['registry']->getAuth() && !$GLOBALS['registry']->isAdmin()) {
                $errors[] = _("Only the owner or system administrator may change ownership or owner permissions for a share");
            } elseif ($auth->hasCapability('list') && !$auth->exists($new_owner_backend)) {
                $errors[] = sprintf(_("The user \"%s\" does not exist."), $new_owner_backend);
            } else {
                $share->set('owner', $new_owner);
                $share->save();
                if ($GLOBALS['conf']['share']['notify']) {
                    $view->ownerChange = true;
                    $multipart = self::buildMimeMessage($view, 'notification', $image);
                    $to = $GLOBALS['injector']
                        ->getInstance('Horde_Core_Factory_Identity')
                        ->create($new_owner)
                        ->getDefaultFromAddress(true);
                    $mail->addHeader('Subject', _("Ownership assignment"));
                    $mail->addHeader('To', $to);
                    $mail->setBasePart($multipart);
                    $mail->send($GLOBALS['injector']->getInstance('Horde_Mail'));
                    $view->ownerChange = false;
                }
            }
        }

        if ($GLOBALS['conf']['share']['notify']) {
            if ($GLOBALS['conf']['share']['hidden']) {
                $view->subscribe = Horde::url('calendars/subscribe.php', true)->add('calendar', $share->getName());
            }
            $multipart = self::buildMimeMessage($view, 'notification', $image);
        }

        if ($GLOBALS['registry']->isAdmin() ||
            !empty($GLOBALS['conf']['share']['world'])) {
            // Process default permissions.
            if (Horde_Util::getFormData('default_show')) {
                $perm->addDefaultPermission(Horde_Perms::SHOW, false);
            } else {
                $perm->removeDefaultPermission(Horde_Perms::SHOW, false);
            }
            if (Horde_Util::getFormData('default_read')) {
                $perm->addDefaultPermission(Horde_Perms::READ, false);
            } else {
                $perm->removeDefaultPermission(Horde_Perms::READ, false);
            }
            if (Horde_Util::getFormData('default_edit')) {
                $perm->addDefaultPermission(Horde_Perms::EDIT, false);
            } else {
                $perm->removeDefaultPermission(Horde_Perms::EDIT, false);
            }
            if (Horde_Util::getFormData('default_delete')) {
                $perm->addDefaultPermission(Horde_Perms::DELETE, false);
            } else {
                $perm->removeDefaultPermission(Horde_Perms::DELETE, false);
            }
            if (Horde_Util::getFormData('default_delegate')) {
                $perm->addDefaultPermission(self::PERMS_DELEGATE, false);
            } else {
                $perm->removeDefaultPermission(self::PERMS_DELEGATE, false);
            }

            // Process guest permissions.
            if (Horde_Util::getFormData('guest_show')) {
                $perm->addGuestPermission(Horde_Perms::SHOW, false);
            } else {
                $perm->removeGuestPermission(Horde_Perms::SHOW, false);
            }
            if (Horde_Util::getFormData('guest_read')) {
                $perm->addGuestPermission(Horde_Perms::READ, false);
            } else {
                $perm->removeGuestPermission(Horde_Perms::READ, false);
            }
            if (Horde_Util::getFormData('guest_edit')) {
                $perm->addGuestPermission(Horde_Perms::EDIT, false);
            } else {
                $perm->removeGuestPermission(Horde_Perms::EDIT, false);
            }
            if (Horde_Util::getFormData('guest_delete')) {
                $perm->addGuestPermission(Horde_Perms::DELETE, false);
            } else {
                $perm->removeGuestPermission(Horde_Perms::DELETE, false);
            }
            if (Horde_Util::getFormData('guest_delegate')) {
                $perm->addGuestPermission(self::PERMS_DELEGATE, false);
            } else {
                $perm->removeGuestPermission(self::PERMS_DELEGATE, false);
            }
        }

        // Process creator permissions.
        if (Horde_Util::getFormData('creator_show')) {
            $perm->addCreatorPermission(Horde_Perms::SHOW, false);
        } else {
            $perm->removeCreatorPermission(Horde_Perms::SHOW, false);
        }
        if (Horde_Util::getFormData('creator_read')) {
            $perm->addCreatorPermission(Horde_Perms::READ, false);
        } else {
            $perm->removeCreatorPermission(Horde_Perms::READ, false);
        }
        if (Horde_Util::getFormData('creator_edit')) {
            $perm->addCreatorPermission(Horde_Perms::EDIT, false);
        } else {
            $perm->removeCreatorPermission(Horde_Perms::EDIT, false);
        }
        if (Horde_Util::getFormData('creator_delete')) {
            $perm->addCreatorPermission(Horde_Perms::DELETE, false);
        } else {
            $perm->removeCreatorPermission(Horde_Perms::DELETE, false);
        }
        if (Horde_Util::getFormData('creator_delegate')) {
            $perm->addCreatorPermission(self::PERMS_DELEGATE, false);
        } else {
            $perm->removeCreatorPermission(self::PERMS_DELEGATE, false);
        }

        // Process user permissions.
        $u_names = Horde_Util::getFormData('u_names');
        $u_show = Horde_Util::getFormData('u_show');
        $u_read = Horde_Util::getFormData('u_read');
        $u_edit = Horde_Util::getFormData('u_edit');
        $u_delete = Horde_Util::getFormData('u_delete');
        $u_delegate = Horde_Util::getFormData('u_delegate');

        $current = $perm->getUserPermissions();
        if ($GLOBALS['conf']['share']['notify']) {
            $mail->addHeader('Subject', _("Access permissions"));
        }

        $perm->removeUserPermission(null, null, false);
        foreach ($u_names as $key => $user_backend) {
            // Apply backend hooks
            $user = $GLOBALS['registry']->convertUsername($user_backend, true);
            // If the user is empty, or we've already set permissions
            // via the owner_ options, don't do anything here.
            if (empty($user) || $user == $new_owner) {
                continue;
            }
            if ($auth->hasCapability('list') && !$auth->exists($user_backend)) {
                $errors[] = sprintf(_("The user \"%s\" does not exist."), $user_backend);
                continue;
            }

            $has_perms = false;
            if (!empty($u_show[$key])) {
                $perm->addUserPermission($user, Horde_Perms::SHOW, false);
                $has_perms = true;
            }
            if (!empty($u_read[$key])) {
                $perm->addUserPermission($user, Horde_Perms::READ, false);
                $has_perms = true;
            }
            if (!empty($u_edit[$key])) {
                $perm->addUserPermission($user, Horde_Perms::EDIT, false);
                $has_perms = true;
            }
            if (!empty($u_delete[$key])) {
                $perm->addUserPermission($user, Horde_Perms::DELETE, false);
                $has_perms = true;
            }
            if (!empty($u_delegate[$key])) {
                $perm->addUserPermission($user, self::PERMS_DELEGATE, false);
                $has_perms = true;
            }

            // Notify users that have been added.
            if ($GLOBALS['conf']['share']['notify'] &&
                !isset($current[$user]) && $has_perms) {
                $to = $GLOBALS['injector']
                    ->getInstance('Horde_Core_Factory_Identity')
                    ->create($user)
                    ->getDefaultFromAddress(true);
                $mail->addHeader('To', $to);
                $mail->setBasePart($multipart);
                $mail->send($GLOBALS['injector']->getInstance('Horde_Mail'));
            }
        }

        // Process group permissions.
        $g_names = Horde_Util::getFormData('g_names');
        $g_show = Horde_Util::getFormData('g_show');
        $g_read = Horde_Util::getFormData('g_read');
        $g_edit = Horde_Util::getFormData('g_edit');
        $g_delete = Horde_Util::getFormData('g_delete');
        $g_delegate = Horde_Util::getFormData('g_delegate');

        $current = $perm->getGroupPermissions();
        $perm->removeGroupPermission(null, null, false);
        foreach ($g_names as $key => $group) {
            if (empty($group)) {
                continue;
            }

            $has_perms = false;
            if (!empty($g_show[$key])) {
                $perm->addGroupPermission($group, Horde_Perms::SHOW, false);
                $has_perms = true;
            }
            if (!empty($g_read[$key])) {
                $perm->addGroupPermission($group, Horde_Perms::READ, false);
                $has_perms = true;
            }
            if (!empty($g_edit[$key])) {
                $perm->addGroupPermission($group, Horde_Perms::EDIT, false);
                $has_perms = true;
            }
            if (!empty($g_delete[$key])) {
                $perm->addGroupPermission($group, Horde_Perms::DELETE, false);
                $has_perms = true;
            }
            if (!empty($g_delegate[$key])) {
                $perm->addGroupPermission($group, self::PERMS_DELEGATE, false);
                $has_perms = true;
            }

            // Notify users that have been added.
            if ($GLOBALS['conf']['share']['notify'] &&
                !isset($current[$group]) && $has_perms) {
                $groupOb = $GLOBALS['injector']
                    ->getInstance('Horde_Group')
                    ->getData($group);
                if (!empty($groupOb['email'])) {
                    $mail->addHeader('To', $groupOb['name'] . ' <' . $groupOb['email'] . '>');
                    $mail->setBasePart($multipart);
                    $mail->send($GLOBALS['injector']->getInstance('Horde_Mail'));
                }
            }
        }
        try {
            $share->setPermission($perm);
        } catch (Horde_Share_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        return $errors;
    }

    /**
     * Subscribes to or updates a remote calendar.
     *
     * @param array $info     Hash with calendar information.
     * @param string $update  If present, the original URL of the calendar to
     *                        update.
     *
     * @throws Kronolith_Exception
     */
    static public function subscribeRemoteCalendar(&$info, $update = false)
    {
        if (!(strlen($info['name']) && strlen($info['url']))) {
            throw new Kronolith_Exception(_("You must specify a name and a URL."));
        }

        if (!empty($info['user']) || !empty($info['password'])) {
            $key = $GLOBALS['registry']->getAuthCredential('password');
            if ($key) {
                $secret = $GLOBALS['injector']->getInstance('Horde_Secret');
                $info['user'] = base64_encode($secret->write($key, $info['user']));
                $info['password'] = base64_encode($secret->write($key, $info['password']));
            }
        }

        $remote_calendars = unserialize($GLOBALS['prefs']->getValue('remote_cals'));
        if ($update) {
            foreach ($remote_calendars as $key => $calendar) {
                if ($calendar['url'] == $update) {
                    $remote_calendars[$key] = $info;
                    break;
                }
            }
        } else {
            $remote_calendars[] = $info;
            $GLOBALS['display_remote_calendars'][] = $info['url'];
            $GLOBALS['prefs']->setValue('display_remote_cals', serialize($GLOBALS['display_remote_calendars']));
        }

        $GLOBALS['prefs']->setValue('remote_cals', serialize($remote_calendars));
    }

    /**
     * Unsubscribes from a remote calendar.
     *
     * @param string $url  The calendar URL.
     *
     * @return array  Hash with the deleted calendar's information.
     * @throws Kronolith_Exception
     */
    static public function unsubscribeRemoteCalendar($url)
    {
        $url = trim($url);
        if (!strlen($url)) {
            return false;
        }

        $remote_calendars = unserialize($GLOBALS['prefs']->getValue('remote_cals'));
        $remote_calendar = null;
        foreach ($remote_calendars as $key => $calendar) {
            if ($calendar['url'] == $url) {
                $remote_calendar = $calendar;
                unset($remote_calendars[$key]);
                break;
            }
        }
        if (!$remote_calendar) {
            throw new Kronolith_Exception(_("The remote calendar was not found."));
        }

        $GLOBALS['prefs']->setValue('remote_cals', serialize($remote_calendars));

        return $remote_calendar;
    }

    /**
     * Returns the feed URL for a calendar.
     *
     * @param string $calendar  A calendar name.
     *
     * @return string  The calendar's feed URL.
     */
    static public function feedUrl($calendar)
    {
        if (isset($GLOBALS['conf']['urls']['pretty']) &&
            $GLOBALS['conf']['urls']['pretty'] == 'rewrite') {
            return Horde::url('feed/' . $calendar, true, -1);
        }
        return Horde::url('feed/index.php', true, -1)
            ->add('c', $calendar);
    }

    /**
     * Returs the HTML/javascript snippit needed to embed a calendar in an
     * external website.
     *
     * @param string $calendar  A calendar name.
     *
     * @return string  The calendar's embed snippit.
     */
    static public function embedCode($calendar)
    {
        /* Get the base url */
        $url = $GLOBALS['registry']->getServiceLink('ajax', 'kronolith')->add(array(
            'calendar' => 'internal_' . $calendar,
            'container' => 'kronolithCal',
            'view' => 'month'
        ));
        $url->url .= 'embed';

        return '<div id="kronolithCal"></div><script src="' . $url .
               '" type="text/javascript"></script>';
    }

    /**
     * Parses a comma separated list of names and e-mail addresses into a list
     * of attendee hashes.
     *
     * @param string $newAttendees  A comma separated attendee list.
     *
     * @return array  The attendee list with e-mail addresses as keys and
     *                attendee information as values.
     */
    static public function parseAttendees($newAttendees)
    {
        global $injector, $notification;

        if (empty($newAttendees)) {
            return array();
        }

        $parser = $injector->getInstance('Horde_Mail_Rfc822');
        $attendees = array();

        /* Parse the address without validation to see what we can get out
         * of it. We allow email addresses (john@example.com), email
         * address with user information (John Doe <john@example.com>),
         * and plain names (John Doe). */
        $result = $parser->parseAddressList($newAttendees);
        $result->setIteratorFilter(Horde_Mail_Rfc822_List::HIDE_GROUPS);

        foreach ($result as $newAttendee) {
            if (!$newAttendee->valid) {
                // If we can't even get a mailbox out of the address, then it
                // is likely unuseable. Reject it entirely.
                $notification->push(
                    sprintf(_("Unable to recognize \"%s\" as an email address."), $newAttendee),
                    'horde.error'
                );
                continue;
            }

            // If there is only a mailbox part, then it is just a local name.
            if (!is_null($newAttendee->host)) {
                // Build a full email address again and validate it.
                try {
                    $parser->parseAddressList($newAttendee->writeAddress(true));
                } catch (Horde_Mail_Exception $e) {
                    $notification->push($e, 'horde.error');
                    continue;
                }
            }

            // Avoid overwriting existing attendees with the default
            // values.
            $attendees[$newAttendee->bare_address] = array(
                'attendance' => self::PART_REQUIRED,
                'response'   => self::RESPONSE_NONE,
                'name'       => strval($newAttendee)
            );
        }

        return $attendees;
    }

    /**
     * Returns a comma separated list of attendees and resources
     *
     * @return string  Attendee/Resource list.
     */
    static public function attendeeList()
    {
        /* Attendees */
        $attendees = self::getAttendeeEmailList($GLOBALS['session']->get('kronolith', 'attendees', Horde_Session::TYPE_ARRAY))->addresses;

        /* Resources */
        foreach ($GLOBALS['session']->get('kronolith', 'resources', Horde_Session::TYPE_ARRAY) as $resource) {
            $attendees[] = $resource['name'];
        }

        return implode(', ', $attendees);
    }

    /**
     * Sends out iTip event notifications to all attendees of a specific
     * event.
     *
     * Can be used to send event invitations, event updates as well as event
     * cancellations.
     *
     * @param Kronolith_Event $event
     *        The event in question.
     * @param Horde_Notification_Handler $notification
     *        A notification object used to show result status.
     * @param integer $action
     *        The type of notification to send. One of the Kronolith::ITIP_*
     *        values.
     * @param Horde_Date $instance
     *        If cancelling a single instance of a recurring event, the date of
     *        this instance.
     */
    static public function sendITipNotifications(
        Kronolith_Event $event, Horde_Notification_Handler $notification,
        $action, Horde_Date $instance = null)
    {
        global $injector, $registry;

        if (!$event->attendees) {
            return;
        }

        $ident = $injector->getInstance('Horde_Core_Factory_Identity')->create($event->creator);
        if (!$ident->getValue('from_addr')) {
            $notification->push(sprintf(_("You do not have an email address configured in your Personal Information Preferences. You must set one %shere%s before event notifications can be sent."), $registry->getServiceLink('prefs', 'kronolith')->add(array('app' => 'horde', 'group' => 'identities'))->link(), '</a>'), 'horde.error', array('content.raw'));
            return;
        }

        // Generate image mime part first and only once, because we
        // need the Content-ID.
        $image = self::getImagePart('big_invitation.png');

        $share = $injector->getInstance('Kronolith_Shares')->getShare($event->calendar);
        $view = new Horde_View(array('templatePath' => KRONOLITH_TEMPLATES . '/itip'));
        new Horde_View_Helper_Text($view);
        $view->identity = $ident;
        $view->event = $event;
        $view->imageId = $image->getContentId();

        foreach ($event->attendees as $email => $status) {
            /* Don't bother sending an invitation/update if the recipient does
             * not need to participate, or has declined participating, or
             * doesn't have an email address. */
            if (strpos($email, '@') === false ||
                $status['attendance'] == self::PART_NONE ||
                $status['response'] == self::RESPONSE_DECLINED) {
                continue;
            }

            /* Determine all notification-specific strings. */
            switch ($action) {
            case self::ITIP_CANCEL:
                /* Cancellation. */
                $method = 'CANCEL';
                $filename = 'event-cancellation.ics';
                $view->subject = sprintf(_("Cancelled: %s"), $event->getTitle());
                if (empty($instance)) {
                    $view->header = sprintf(_("%s has cancelled \"%s\"."), $ident->getName(), $event->getTitle());
                } else {
                    $view->header = sprintf(_("%s has cancelled an instance of the recurring \"%s\"."), $ident->getName(), $event->getTitle());
                }
                break;

            case self::ITIP_REQUEST:
            default:
                $method = 'REQUEST';
                if ($status['response'] == self::RESPONSE_NONE) {
                    /* Invitation. */
                    $filename = 'event-invitation.ics';
                    $view->subject = $event->getTitle();
                    $view->header = sprintf(_("%s wishes to make you aware of \"%s\"."), $ident->getName(), $event->getTitle());
                } else {
                    /* Update. */
                    $filename = 'event-update.ics';
                    $view->subject = sprintf(_("Updated: %s."), $event->getTitle());
                    $view->header = sprintf(_("%s wants to notify you about changes of \"%s\"."), $ident->getName(), $event->getTitle());
                }
                break;
            }

            if ($event->attendees) {
                $view->attendees = strval(self::getAttendeeEmailList($event->attendees));
                $view->organizer = $registry->convertUserName($event->creator, false);
            }

            if ($action == self::ITIP_REQUEST) {
                $attend_link = Horde::url('attend.php', true, -1)
                    ->add(array('c' => $event->calendar,
                                'e' => $event->id,
                                'u' => $email));
                $view->linkAccept    = (string)$attend_link->add('a', 'accept');
                $view->linkTentative = (string)$attend_link->add('a', 'tentative');
                $view->linkDecline   = (string)$attend_link->add('a', 'decline');
            }

            /* Build the iCalendar data */
            $iCal = new Horde_Icalendar();
            $iCal->setAttribute('METHOD', $method);
            $iCal->setAttribute('X-WR-CALNAME', $share->get('name'));
            $vevent = $event->toiCalendar($iCal);
            if ($action == self::ITIP_CANCEL && !empty($instance)) {
                // Recurring event instance deletion, need to specify the
                // RECURRENCE-ID but NOT the EXDATE.
                $vevent = array_pop($vevent);
                $vevent->setAttribute('RECURRENCE-ID', $instance, array('VALUE' => 'DATE'));
                $vevent->removeAttribute('EXDATE');
            }
            $iCal->addComponent($vevent);

            /* text/calendar part */
            $ics = new Horde_Mime_Part();
            $ics->setType('text/calendar');
            $ics->setContents($iCal->exportvCalendar());
            $ics->setName($filename);
            $ics->setContentTypeParameter('METHOD', $method);
            $ics->setCharset('UTF-8');
            $ics->setEOL("\r\n");

            $multipart = self::buildMimeMessage($view, 'notification', $image);
            $multipart->addPart($ics);

            $recipient = new Horde_Mail_Rfc822_Address($email);
            if (!empty($status['name'])) {
                $recipient->personal = $status['name'];
            }

            $mail = new Horde_Mime_Mail(
                array('Subject' => $view->subject,
                      'To' => $recipient,
                      'From' => $ident->getDefaultFromAddress(true),
                      'User-Agent' => 'Kronolith ' . $registry->getVersion()));
            $mail->setBasePart($multipart);

            try {
                $mail->send($injector->getInstance('Horde_Mail'));
                $notification->push(
                    sprintf(_("The event notification to %s was successfully sent."), $recipient),
                    'horde.success'
                );
            } catch (Horde_Mime_Exception $e) {
                $notification->push(
                    sprintf(_("There was an error sending an event notification to %s: %s"), $recipient, $e->getMessage(), $e->getCode()),
                    'horde.error'
                );
            }
        }
    }

    /**
     * Sends email notifications that a event has been added, edited, or
     * deleted to users that want such notifications.
     *
     * @param Kronolith_Event $event  An event.
     * @param string $action          The event action. One of "add", "edit",
     *                                or "delete".
     *
     * @throws Horde_Mime_Exception
     * @throws Kronolith_Exception
     */
    static public function sendNotification($event, $action)
    {
        global $injector, $registry;

        if (!in_array($action, array('add', 'edit', 'delete'))) {
            throw new Kronolith_Exception('Unknown event action: ' . $action);
        }

        // @TODO: Send notifications to the email addresses stored in the
        // resource object?
        if ($event->calendarType == 'resource') {
            return;
        }
        $groups = $injector->getInstance('Horde_Group');
        $calendar = $event->calendar;
        $recipients = array();
        try {
            $share = $injector->getInstance('Kronolith_Shares')->getShare($calendar);
        } catch (Horde_Share_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        $senderIdentity = $injector->getInstance('Horde_Core_Factory_Identity')->create();

        $owner = $share->get('owner');
        if ($owner) {
            $recipients[$owner] = self::_notificationPref($owner, 'owner');
        }

        foreach ($share->listUsers(Horde_Perms::READ) as $user) {
            if (!isset($recipients[$user])) {
                $recipients[$user] = self::_notificationPref($user, 'read', $calendar);
            }
        }

        foreach ($share->listGroups(Horde_Perms::READ) as $group) {
            try {
                $group_users = $groups->listUsers($group);
            } catch (Horde_Group_Exception $e) {
                Horde::logMessage($e, 'ERR');
                continue;
            }

            foreach ($group_users as $user) {
                if (!isset($recipients[$user])) {
                    $recipients[$user] = self::_notificationPref($user, 'read', $calendar);
                }
            }
        }

        $addresses = array();
        foreach ($recipients as $user => $vals) {
            if (!$vals) {
                continue;
            }
            $identity = $injector->getInstance('Horde_Core_Factory_Identity')->create($user);
            $email = $identity->getValue('from_addr');
            if (strpos($email, '@') === false) {
                continue;
            }
            if (!isset($addresses[$vals['lang']][$vals['tf']][$vals['df']])) {
                $addresses[$vals['lang']][$vals['tf']][$vals['df']] = array();
            }

            $tmp = new Horde_Mail_Rfc822_Address($email);
            $tmp->personal = $identity->getValue('fullname');
            $addresses[$vals['lang']][$vals['tf']][$vals['df']][] = strval($tmp);
        }

        if (!$addresses) {
            return;
        }

        foreach ($addresses as $lang => $twentyFour) {
            $registry->setLanguageEnvironment($lang);

            switch ($action) {
            case 'add':
                $subject = _("Event added:");
                $notification_message = _("You requested to be notified when events are added to your calendars.") . "\n\n" . _("The event \"%s\" has been added to \"%s\" calendar, which is on %s at %s.");
                break;

            case 'edit':
                $subject = _("Event edited:");
                $notification_message = _("You requested to be notified when events are edited in your calendars.") . "\n\n" . _("The event \"%s\" has been edited on \"%s\" calendar, which is on %s at %s.");
                break;

            case 'delete':
                $subject = _("Event deleted:");
                $notification_message = _("You requested to be notified when events are deleted from your calendars.") . "\n\n" . _("The event \"%s\" has been deleted from \"%s\" calendar, which was on %s at %s.");
                break;
            }

            foreach ($twentyFour as $tf => $dateFormat) {
                foreach ($dateFormat as $df => $df_recipients) {
                    $message = "\n"
                        . sprintf($notification_message,
                                  $event->title,
                                  $share->get('name'),
                                  $event->start->strftime($df),
                                  $event->start->strftime($tf ? '%R' : '%I:%M%p'))
                        . "\n\n" . $event->description;

                    $mime_mail = new Horde_Mime_Mail(array(
                        'Subject' => $subject . ' ' . $event->title,
                        'To' => implode(',', $df_recipients),
                        'From' => $senderIdentity->getDefaultFromAddress(true),
                        'User-Agent' => 'Kronolith ' . $registry->getVersion(),
                        'body' => $message));
                    Horde::logMessage(sprintf('Sending event notifications for %s to %s', $event->title, implode(', ', $df_recipients)), 'DEBUG');
                    $mime_mail->send($injector->getInstance('Horde_Mail'));
                }
            }
        }
    }

    /**
     * Check for resource declines and push notice to stack if found.
     *
     * @param Kronolith_Event $event
     *
     * @throws Kronolith_Exception
     */
    static public function notifyOfResourceRejection($event)
    {
        $accepted = $declined = array();

        foreach ($event->getResources() as $id => $resource) {
            if ($resource['response'] == self::RESPONSE_DECLINED) {
                $r = self::getDriver('Resource')->getResource($id);
                $declined[] = $r->get('name');
            } elseif ($resource['response'] == self::RESPONSE_ACCEPTED) {
                $r = self::getDriver('Resource')->getResource($id);
                $accepted[] = $r->get('name');
            }


        }
        if (count($declined)) {
            $GLOBALS['notification']->push(sprintf(ngettext("The following resource has declined your request: %s",
                                                            "The following resources have declined your request: %s",
                                                            count($declined)),
                                                    implode(", ", $declined)),
                                           'horde.error');
        }
        if (count($accepted)) {
             $GLOBALS['notification']->push(sprintf(ngettext("The following resource has accepted your request: %s",
                                                            "The following resources have accepted your request: %s",
                                                            count($accepted)),
                                                    implode(", ", $accepted)),
                                           'horde.success');
        }
    }

    /**
     * Returns whether a user wants email notifications for a calendar.
     *
     * @access private
     *
     * @todo This method is causing a memory leak somewhere, noticeable if
     *       importing a large amount of events.
     *
     * @param string $user      A user name.
     * @param string $mode      The check "mode". If "owner", the method checks
     *                          if the user wants notifications only for
     *                          calendars he owns. If "read", the method checks
     *                          if the user wants notifications for all
     *                          calendars he has read access to, or only for
     *                          shown calendars and the specified calendar is
     *                          currently shown.
     * @param string $calendar  The name of the calendar if mode is "read".
     *
     * @return mixed  The user's email, time, and language preferences if they
     *                want a notification for this calendar.
     */
    static public function _notificationPref($user, $mode, $calendar = null)
    {
        $prefs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Prefs')->create('kronolith', array(
            'cache' => false,
            'user' => $user
        ));
        $vals = array('lang' => $prefs->getValue('language'),
                      'tf' => $prefs->getValue('twentyFour'),
                      'df' => $prefs->getValue('date_format'));

        if ($prefs->getValue('event_notification_exclude_self') &&
            $user == $GLOBALS['registry']->getAuth()) {
            return false;
        }

        switch ($prefs->getValue('event_notification')) {
        case 'owner':
            return $mode == 'owner' ? $vals : false;

        case 'read':
            return $mode == 'read' ? $vals : false;

        case 'show':
            if ($mode == 'read') {
                $display_calendars = unserialize($prefs->getValue('display_cals'));
                return in_array($calendar, $display_calendars) ? $vals : false;
            }
        }

        return false;
    }

    /**
     * Builds the body MIME part of a multipart message.
     *
     * @param Horde_View $view        A view to render the HTML and plain text
     *                                templates for the messate.
     * @param string $template        The template base name for the view.
     * @param Horde_Mime_Part $image  The MIME part of a related image.
     *
     * @return Horde_Mime_Part  A multipart/alternative MIME part.
     */
    static public function buildMimeMessage(Horde_View $view, $template,
                                            Horde_Mime_Part $image)
    {
        $multipart = new Horde_Mime_Part();
        $multipart->setType('multipart/alternative');
        $bodyText = new Horde_Mime_Part();
        $bodyText->setType('text/plain');
        $bodyText->setCharset('UTF-8');
        $bodyText->setContents($view->render($template . '.plain.php'));
        $bodyText->setDisposition('inline');
        $multipart->addPart($bodyText);
        $bodyHtml = new Horde_Mime_Part();
        $bodyHtml->setType('text/html');
        $bodyHtml->setCharset('UTF-8');
        $bodyHtml->setContents($view->render($template . '.html.php'));
        $bodyHtml->setDisposition('inline');
        $related = new Horde_Mime_Part();
        $related->setType('multipart/related');
        $related->setContentTypeParameter('start', $bodyHtml->setContentId());
        $related->addPart($bodyHtml);
        $related->addPart($image);
        $multipart->addPart($related);
        return $multipart;
    }

    /**
     * Returns a MIME part for an image to be embedded into a HTML document.
     *
     * @param string $file  An image file name.
     *
     * @return Horde_Mime_Part  A MIME part representing the image.
     */
    static public function getImagePart($file)
    {
        $background = Horde_Themes::img($file);
        $image = new Horde_Mime_Part();
        $image->setType('image/png');
        $image->setContents(file_get_contents($background->fs));
        $image->setContentId();
        $image->setDisposition('attachment');
        return $image;
    }

    /**
     * @return Horde_Date
     */
    static public function currentDate()
    {
        if ($date = Horde_Util::getFormData('date')) {
            return new Horde_Date($date . '000000');
        }
        if ($date = Horde_Util::getFormData('datetime')) {
            return new Horde_Date($date);
        }

        return new Horde_Date($_SERVER['REQUEST_TIME']);
    }

    /**
     * Parses a complete date-time string into a Horde_Date object.
     *
     * @param string $date       The date-time string to parse.
     * @param boolean $withtime  Whether time is included in the string.
     * @þaram string $timezone   The timezone of the string.
     *
     * @return Horde_Date  The parsed date.
     * @throws Horde_Date_Exception
     */
    static public function parseDate($date, $withtime = true, $timezone = null)
    {
        // strptime() is not available on Windows.
        if (!function_exists('strptime')) {
            return new Horde_Date($date, $timezone);
        }

        // strptime() is locale dependent, i.e. %p is not always matching
        // AM/PM. Set the locale to C to workaround this, but grab the
        // locale's D_FMT before that.
        $format = Horde_Nls::getLangInfo(D_FMT);
        if ($withtime) {
            $format .= ' '
                . ($GLOBALS['prefs']->getValue('twentyFour') ? '%H:%M' : '%I:%M %p');
        }
        $old_locale = setlocale(LC_TIME, 0);
        setlocale(LC_TIME, 'C');

        // Try exact format match first.
        $date_arr = strptime($date, $format);
        setlocale(LC_TIME, $old_locale);

        if (!$date_arr) {
            // Try with locale dependent parsing next.
            $date_arr = strptime($date, $format);
            if (!$date_arr) {
                // Try throwing at Horde_Date finally.
                return new Horde_Date($date, $timezone);
            }
        }

        return new Horde_Date(
            array('year'  => $date_arr['tm_year'] + 1900,
                  'month' => $date_arr['tm_mon'] + 1,
                  'mday'  => $date_arr['tm_mday'],
                  'hour'  => $date_arr['tm_hour'],
                  'min'   => $date_arr['tm_min'],
                  'sec'   => $date_arr['tm_sec']),
            $timezone);
    }

    /**
     * @param object $renderer  A Kronolith view.
     */
    static public function tabs($renderer)
    {
        global $injector, $prefs;

        $view = $injector->createInstance('Horde_View');

        $today = new Horde_Date($_SERVER['REQUEST_TIME']);
        $date = self::currentDate();
        $date_stamp = array('date' => $date->dateString());
        $tabname = basename($_SERVER['PHP_SELF']) == 'index.php'
            ? $GLOBALS['prefs']->getValue('defaultview')
            : str_replace('.php', '', basename($_SERVER['PHP_SELF']));

        $view->active = $tabname;
        $view->previous = $renderer->link(-1);
        $view->next = $renderer->link(1);
        switch ($tabname) {
        case 'day':
            $view->current = $renderer->getTime($prefs->getValue('date_format'));
            break;
        case 'workweek':
        case 'week':
            $view->current =
                $renderer->days[$renderer->startDay]
                    ->getTime($prefs->getValue('date_format'))
                . ' - '
                . $renderer->days[$renderer->endDay]
                    ->getTime($prefs->getValue('date_format'));
            break;
        case 'month':
            $view->current = $renderer->date->strftime('%B %Y');
            break;
        case 'year':
            $view->current = $renderer->year;
            break;
        }
        $view->today = Horde::url($prefs->getValue('defaultview') . '.php')
            ->link(Horde::getAccessKeyAndTitle(_("_Today"), false, true))
            . $today->strftime($prefs->getValue('date_format_mini')) . '</a>';
        $view->day = Horde::widget(array(
            'url' => Horde::url('day.php')->add($date_stamp),
            'id' => 'kronolithNavDay',
            'accesskey' => '1',
            'title' => _("Day")
        ));
        $view->workWeek = Horde::widget(array(
            'url' => Horde::url('workweek.php')->add($date_stamp),
            'id' => 'kronolithNavWorkweek',
            'accesskey' => '2',
            'title' => _("Work Week")
        ));
        $view->week = Horde::widget(array(
            'url' => Horde::url('week.php')->add($date_stamp),
            'id' => 'kronolithNavWeek',
            'accesskey' => '3',
            'title' => _("Week")
        ));
        $view->month = Horde::widget(array(
            'url' => Horde::url('month.php')->add($date_stamp),
            'id' => 'kronolithNavMonth',
            'accesskey' => '4',
            'title' => _("Month")
        ));
        $view->year = Horde::widget(array(
            'url' => Horde::url('year.php')->add($date_stamp),
            'id' => 'kronolithNavYear',
            'accesskey' => '5',
            'title' => _("Year")
        ));

        echo $view->render('buttonbar');
    }

    /**
     * @param string $tabname
     * @param Kronolith_Event $event
     */
    static public function eventTabs($tabname, $event)
    {
        if (!$event->initialized) {
            return;
        }

        $GLOBALS['page_output']->addScriptFile('views.js');
        $tabs = new Horde_Core_Ui_Tabs('event', Horde_Variables::getDefaultVariables());

        $date = self::currentDate();
        $tabs->preserve('datetime', $date->dateString());

        $tabs->addTab(
            htmlspecialchars($event->getTitle()),
            $event->getViewUrl(),
            array('tabname' => 'Event',
                  'id' => 'tabEvent',
                  'onclick' => 'return ShowTab(\'Event\');'));
        /* We check for read permissions, because we can always save a copy if
         * we can read the event. */
        if ((!$event->private ||
             $event->creator == $GLOBALS['registry']->getAuth()) &&
            $event->hasPermission(Horde_Perms::READ) &&
            self::getDefaultCalendar(Horde_Perms::EDIT)) {
            $tabs->addTab(
                $event->hasPermission(Horde_Perms::EDIT) ? _("_Edit") : _("Save As New"),
                $event->getEditUrl(),
                array('tabname' => 'EditEvent',
                      'id' => 'tabEditEvent',
                      'onclick' => 'return ShowTab(\'EditEvent\');'));
        }
        if ($event->hasPermission(Horde_Perms::DELETE)) {
            $tabs->addTab(
                _("De_lete"),
                $event->getDeleteUrl(array('confirm' => 1)),
                array('tabname' => 'DeleteEvent',
                      'id' => 'tabDeleteEvent',
                      'onclick' => 'return ShowTab(\'DeleteEvent\');'));
        }
        $tabs->addTab(
            _("Export"),
            $event->getExportUrl(),
            array('tabname' => 'ExportEvent',
                  'id' => 'tabExportEvent'));

        echo $tabs->render($tabname);
    }

    /**
     * Attempts to return a single, concrete Kronolith_Driver instance based
     * on a driver name.
     *
     * This singleton method automatically retrieves all parameters required
     * for the specified driver.
     *
     * @param string $driver    The type of concrete Kronolith_Driver subclass
     *                          to return.
     * @param string $calendar  The calendar name. The format depends on the
     *                          driver being used.
     *
     * @return Kronolith_Driver  The newly created concrete Kronolith_Driver
     *                           instance.
     * @throws Kronolith_Exception
     */
    static public function getDriver($driver = null, $calendar = null)
    {
        $driver = $GLOBALS['injector']->getInstance('Kronolith_Factory_Driver')->create($driver);

        if (!is_null($calendar)) {
            $driver->open($calendar);

            /* Remote calendar parameters are per calendar. */
            if ($driver == 'Ical') {
                $driver->setParams(self::getRemoteParams($calendar));
            }
        }

        return $driver;
    }

    /**
     * Check for HTTP authentication credentials
     */
    static public function getRemoteParams($calendar)
    {
        if (empty($calendar)) {
            return array();
        }

        $cals = unserialize($GLOBALS['prefs']->getValue('remote_cals'));
        foreach ($cals as $cal) {
            if ($cal['url'] == $calendar) {
                $user = isset($cal['user']) ? $cal['user'] : '';
                $password = isset($cal['password']) ? $cal['password'] : '';
                $key = $GLOBALS['registry']->getAuthCredential('password');
                if ($key && $password) {
                    $secret = $GLOBALS['injector']->getInstance('Horde_Secret');
                    $user = $secret->read($key, base64_decode($user));
                    $password = $secret->read($key, base64_decode($password));
                }
                if (!empty($user)) {
                    return array('user' => $user, 'password' => $password);
                }
                return array();
            }
        }

        return array();
    }

    /**
     * Returns a list of currently displayed calendars.
     *
     * @return array  Currently displayed calendars.
     */
    static public function displayedCalendars()
    {
        $calendars = array();
        foreach ($GLOBALS['display_calendars'] as $calendarId) {
            $calendars[] = $GLOBALS['all_calendars'][$calendarId];
        }
        if (!empty($GLOBALS['display_resource_calendars'])) {
            $r_driver = self::getDriver('Resource');
            foreach ($GLOBALS['display_resource_calendars'] as $c) {
                $resource = $r_driver->getResource($r_driver->getResourceIdByCalendar($c));
                $calendars[] = new Kronolith_Calendar_Resource(array('resource' => $resource));
            }
        }
        return $calendars;
    }

    /**
     * Get a named Kronolith_View_* object and load it with the
     * appropriate date parameters.
     *
     * @param string $view The name of the view.
     */
    static public function getView($view)
    {
        switch ($view) {
        case 'Day':
        case 'Month':
        case 'Week':
        case 'WorkWeek':
        case 'Year':
            $class = 'Kronolith_View_' . $view;
            return new $class(self::currentDate());

        case 'Event':
        case 'EditEvent':
        case 'DeleteEvent':
        case 'ExportEvent':
            try {
                if ($uid = Horde_Util::getFormData('uid')) {
                    $event = self::getDriver()->getByUID($uid);
                } else {
                    $event = self::getDriver(Horde_Util::getFormData('type'),
                                             Horde_Util::getFormData('calendar'))
                        ->getEvent(Horde_Util::getFormData('eventID'),
                                   Horde_Util::getFormData('datetime'));
                }
            } catch (Horde_Exception $e) {
                $event = $e->getMessage();
            }
            switch ($view) {
            case 'Event':
                if (!is_string($event) &&
                    !$event->hasPermission(Horde_Perms::READ)) {
                    $event = _("Permission Denied");
                }
                return new Kronolith_View_Event($event);
            case 'EditEvent':
                /* We check for read permissions, because we can always save a
                 * copy if we can read the event. */
                if (!is_string($event) &&
                    !$event->hasPermission(Horde_Perms::READ)) {
                    $event = _("Permission Denied");
                }
                return new Kronolith_View_EditEvent($event);
            case 'DeleteEvent':
                if (!is_string($event) &&
                    !$event->hasPermission(Horde_Perms::DELETE)) {
                    $event = _("Permission Denied");
                }
                return new Kronolith_View_DeleteEvent($event);
            case 'ExportEvent':
                if (!is_string($event) &&
                    !$event->hasPermission(Horde_Perms::READ)) {
                    $event = _("Permission Denied");
                }
                return new Kronolith_View_ExportEvent($event);
            }
        }
    }

    /**
     * Should we show event location, based on the show_location pref?
     */
    static public function viewShowLocation()
    {
        $show = @unserialize($GLOBALS['prefs']->getValue('show_location'));
        return @in_array('screen', $show);
    }

    /**
     * Should we show event time, based on the show_time preference?
     */
    static public function viewShowTime()
    {
        $show = @unserialize($GLOBALS['prefs']->getValue('show_time'));
        return @in_array('screen', $show);
    }

    /**
     * Returns the background color for a calendar.
     *
     * @param array|Horde_Share_Object $calendar  A calendar share or a hash
     *                                            from a remote calender
     *                                            definition.
     *
     * @return string  A HTML color code.
     */
    static public function backgroundColor($calendar)
    {
        $color = '';
        if (!is_array($calendar)) {
            $color = $calendar->get('color');
        } elseif (isset($calendar['color'])) {
            $color = $calendar['color'];
        }
        return empty($color) ? '#dddddd' : $color;
    }

    /**
     * Returns the foreground color for a calendar or a background color.
     *
     * @param array|Horde_Share_Object|string $calendar  A color string, a
     *                                                   calendar share or a
     *                                                   hash from a remote
     *                                                   calender definition.
     *
     * @return string  A HTML color code.
     */
    static public function foregroundColor($calendar)
    {
        return Horde_Image::brightness(is_string($calendar) ? $calendar : self::backgroundColor($calendar)) < 128 ? '#fff' : '#000';
    }

    /**
     * Returns the CSS color definition for a calendar.
     *
     * @param array|Horde_Share_Object $calendar  A calendar share or a hash
     *                                            from a remote calender
     *                                            definition.
     * @param boolean $with_attribute             Whether to wrap the colors
     *                                            inside a "style" attribute.
     *
     * @return string  A CSS string with color definitions.
     */
    static public function getCSSColors($calendar, $with_attribute = true)
    {
        $css = 'background-color:' . self::backgroundColor($calendar) . ';color:' . self::foregroundColor($calendar);
        if ($with_attribute) {
            $css = ' style="' . $css . '"';
        }
        return $css;
    }

    /**
     * Returns a random CSS color.
     *
     * @return string  A random CSS color string.
     */
    static public function randomColor()
    {
        $color = '#';
        for ($i = 0; $i < 3; $i++) {
            $color .= sprintf('%02x', mt_rand(0, 255));
        }
        return $color;
    }

    /**
     * Returns whether to display the ajax view.
     *
     * return boolean  True if the ajax view should be displayed.
     */
    static public function showAjaxView()
    {
        return $GLOBALS['registry']->getView() == Horde_Registry::VIEW_DYNAMIC && $GLOBALS['prefs']->getValue('dynamic_view');
    }

    /**
     * Sorts an event list.
     *
     * @param array $days  A list of days with events.
     *
     * @return array  The sorted day list.
     */
    static public function sortEvents($days)
    {
        foreach ($days as $day => $devents) {
            if (count($devents)) {
                uasort($devents, array('Kronolith', '_sortEventStartTime'));
                $days[$day] = $devents;
            }
        }
        return $days;
    }

    /**
     * Used with usort() to sort events based on their start times.
     */
    static protected function _sortEventStartTime($a, $b)
    {
        $diff = $a->start->compareDateTime($b->start);
        if ($diff == 0) {
            return strcoll($a->title, $b->title);
        } else {
            return $diff;
        }
    }

    /**
     * Obtain a Kronolith_Tagger instance
     *
     * @return Kronolith_Tagger
     */
    static public function getTagger()
    {
        if (empty(self::$_tagger)) {
            self::$_tagger = new Kronolith_Tagger();
        }
        return self::$_tagger;
    }

    /**
     * Obtain an internal calendar. Use this where we don't know if we will
     * have a Horde_Share or a Kronolith_Resource based calendar.
     *
     * @param string $target  The calendar id to retrieve.
     *
     * @return Kronolith_Resource|Horde_Share_Object
     * @throws Kronolith_Exception
     */
    static public function getInternalCalendar($target)
    {
        if (self::getDriver('Resource')->isResourceCalendar($target)) {
            $driver = self::getDriver('Resource');
            $id = $driver->getResourceIdByCalendar($target);
            return $driver->getResource($id);
        } else {
            return $GLOBALS['injector']->getInstance('Kronolith_Shares')->getShare($target);
        }
    }

    /**
     * Determines parameters needed to do an address search
     *
     * @return array  An array with two keys: 'fields' and 'sources'.
     */
    static public function getAddressbookSearchParams()
    {
        $src = json_decode($GLOBALS['prefs']->getValue('search_sources'));
        if (empty($src)) {
            $src = array();
        }

        $fields = json_decode($GLOBALS['prefs']->getValue('search_fields'), true);
        if (empty($fields)) {
            $fields = array();
        }

        return array(
            'fields' => $fields,
            'sources' => $src
        );
    }

    /**
     * Checks whether an API (application) exists and the user has permission
     * to access it.
     *
     * @param string $api    The API (application) to check.
     * @param integer $perm  The permission to check.
     *
     * @return boolean  True if the API can be accessed.
     */
    static public function hasApiPermission($api, $perm = Horde_Perms::READ)
    {
        $app = $GLOBALS['registry']->hasInterface($api);
        return ($app && $GLOBALS['registry']->hasPermission($app, $perm));
    }

    /**
     * Remove all events owned by the specified user in all calendars.
     *
     * @param string $user  The user name to delete events for.
     *
     * @throws Kronolith_Exception
     * @throws Horde_Exception_NotFound
     * @throws Horde_Exception_PermissionDenied
     */
    static public function removeUserEvents($user)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception_PermissionDenied();
        }

        try {
            $shares = $GLOBALS['injector']
                ->getInstance('Kronolith_Shares')
                ->listShares($user, array('perm' => Horde_Perms::EDIT));
        } catch (Horde_Share_Exception $e) {
            Horde::logMessage($shares, 'ERR');
            throw new Kronolith_Exception($shares);
        }

        foreach (array_keys($shares) as $calendar) {
            $driver = self::getDriver(null, $calendar);
            $events = $driver->listEvents(null, null, array('cover_dates' => false));
            $uids = array();
            foreach ($events as $dayevents) {
                foreach ($dayevents as $event) {
                    $uids[] = $event->uid;
                }
            }
            foreach ($uids as $uid) {
                $event = $driver->getByUID($uid, array($calendar));
                $driver->deleteEvent($event->id);
            }
        }
    }

    /**
     * TODO
     *
     * @param array $attendees
     *
     * @return Horde_Mail_Rfc822_List
     */
    static public function getAttendeeEmailList($attendees)
    {
        $a_list = new Horde_Mail_Rfc822_List();

        foreach ($attendees as $mail => $attendee) {
            $tmp = new Horde_Mail_Rfc822_Address($mail);
            if (!empty($attendee['name'])) {
                $tmp->personal = $attendee['name'];
            }
            $a_list->add($tmp);
        }

        return $a_list;
    }

}
