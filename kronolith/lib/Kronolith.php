<?php
/**
 * Copyright 1999-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */

/**
 * Kronolith base library.
 *
 * The Kronolith class provides functionality common to all of Kronolith.
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
    const ITIP_REPLY   = 3;

    const RANGE_THISANDFUTURE = 'THISANDFUTURE';

    /** The event can be delegated. */
    const PERMS_DELEGATE = 1024;

    /** Calendar Manager Constants */
    const DISPLAY_CALENDARS         = 'displayCalendars';
    const DISPLAY_REMOTE_CALENDARS  = 'displayRemote';
    const DISPLAY_EXTERNAL_CALENDARS= 'displayExternal';
    const DISPLAY_RESOURCE_CALENDARS= 'displayResource';
    const DISPLAY_HOLIDAYS          = 'displayHolidays';
    const ALL_CALENDARS             = 'allCalendars';
    const ALL_REMOTE_CALENDARS      = 'allRemote';
    const ALL_EXTERNAL_CALENDARS    = 'allExternal';
    const ALL_HOLIDAYS              = 'allHolidays';
    const ALL_RESOURCE_CALENDARS    = 'allResource';

    /** Share Types */
    const SHARE_TYPE_USER          = 1;
    const SHARE_TYPE_RESOURCE      = 2;

    /**
     * The virtual path to use for VFS data.
     */
    const VFS_PATH = '.horde/kronolith/documents';

    /**
     * @var Kronolith_Tagger
     */
    private static $_tagger;

    /**
     * Converts a permission object to a json object.
     *
     * This methods filters out any permissions for the owner and converts the
     * user name if necessary.
     *
     * @param Horde_Perms_Permission $perm  A permission object.
     * @param boolean $systemShare          Is this from a system share?
     *
     * @return array  A hash suitable for json.
     */
    public static function permissionToJson(
        Horde_Perms_Permission $perm, $systemShare = false
    )
    {
        $json = $perm->data;
        if (isset($json['users'])) {
            $users = array();
            foreach ($json['users'] as $user => $value) {
                if (!$systemShare && $user == $GLOBALS['registry']->getAuth()) {
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
    public static function listAlarms($date, $calendars, $fullevent = false)
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
    public static function search($query, $calendar = null)
    {
        if ($calendar) {
            $driver = explode('|', $calendar, 2);
            $calendars = array($driver[0] => array($driver[1]));
        } elseif (!empty($query->calendars)) {
            $calendars = $query->calendars;
        } else {
            $calendars = array(
                Horde_String::ucfirst($GLOBALS['conf']['calendar']['driver']) => $GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_CALENDARS),
                'Horde' => $GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_EXTERNAL_CALENDARS),
                'Ical' => $GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_REMOTE_CALENDARS),
                'Holidays' => $GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_HOLIDAYS));
        }

        $events = array();
        foreach ($calendars as $type => $list) {
            if (!empty($list)) {
                $kronolith_driver = self::getDriver($type);
            }
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
    public static function listEvents(
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
            $calendars = $GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_CALENDARS);
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
        if (count($GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_RESOURCE_CALENDARS)) &&
            !empty($GLOBALS['conf']['resources']['enabled'])) {

            $driver = self::getDriver('Resource');
            foreach ($GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_RESOURCE_CALENDARS) as $calendar) {
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
            if (count($GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_EXTERNAL_CALENDARS))) {
                $driver = self::getDriver('Horde');
                foreach ($GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_EXTERNAL_CALENDARS) as $external_cal) {
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
            foreach ($GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_REMOTE_CALENDARS) as $url) {
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
            $display_holidays = $GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_HOLIDAYS);
            if (count($display_holidays) && !empty($GLOBALS['conf']['holidays']['enable'])) {
                $driver = self::getDriver('Holidays');
                foreach ($display_holidays as $holiday) {
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
    public static function mergeEvents(&$results, $events)
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
    public static function addEvents(&$results, &$event, $startDate, $endDate,
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

        // If we are adding coverDates, but have no $endDate, default to
        // +5 years from $startDate. This protects against hitting memory
        // limit and other issues due to extremely long loops if a single event
        // was added with a duration of thousands of years while still
        // providing for reasonable alarm trigger times.
        if ($coverDates && empty($endDate)) {
            $endDate = clone $startDate;
            $endDate->year += 5;
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
                        self::addCoverDates($results, $event, $event->start, $event->end, $json, null, null, $endDate);
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
            if ($next && $convert) {
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
                    $addEvent = clone $event;
                    $addEvent->start = $addEvent->originalStart = $next;
                    $addEvent->end = $addEvent->originalEnd = $nextEnd;
                    if ($coverDates) {
                        self::addCoverDates($results, $addEvent, $next, $nextEnd, $json, null, null, $endDate);
                    } else {
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
                if ($next && $convert) {
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
                        $originalStart = clone $eventStart;
                    } else {
                        $eventStart = clone $startDate;
                        $originalStart = clone $event->start;
                    }
                } else {
                    $eventStart = clone $event->start;
                    $originalStart = clone $event->start;
                }

                /* Work out what day it ends on. */
                if ($endDate &&
                    $event->end->compareDateTime($endDate) > 0) {
                    /* Ends after the end of the period. */
                    if (is_object($endDate)) {
                        $eventEnd = clone $endDate;
                        $originalEnd = clone $event->end;
                    } else {
                        $eventEnd = $endDate;
                        $originalEnd = new Horde_Date($endDate);
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
                    $originalEnd = clone $theEnd;

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

               self::addCoverDates($results, $event, $eventStart,
                    $eventEnd, $json, $originalStart, $originalEnd, $endDate);
            }
        }
        ksort($results);
    }

    /**
     * Adds an event to all the days it covers.
     *
     * @param array $result              The current result list.
     * @param Kronolith_Event $event     An event object.
     * @param Horde_Date $eventStart     The event's start of the actual
     *                                   recurrence.
     * @param Horde_Date $eventEnd       The event's end of the actual
     *                                   recurrence.
     * @param boolean $json              Store the results of the events'
     *                                   toJson() method?
     * @param Horde_Date $originalStart  The actual starting time of a single
     *                                   event spanning multiple days.
     * @param Horde_Date $originalEnd    The actual ending time of a single
     *                                   event spanning multiple days.
     */
    public static function addCoverDates(&$results, $event, $eventStart,
        $eventEnd, $json, $originalStart = null, $originalEnd = null, Horde_Date $endDate = null)
    {
        $loopDate = new Horde_Date(array(
            'month' => $eventStart->month,
            'mday' => $eventStart->mday,
            'year' => $eventStart->year)
        );
        $allDay = $event->isAllDay();
        while ($loopDate->compareDateTime($eventEnd) <= 0 &&
               $loopDate->compareDateTime($endDate) <= 0) {
            if (!$allDay ||
                $loopDate->compareDateTime($eventEnd) != 0) {
                $addEvent = clone $event;
                if ($originalStart) {
                    $addEvent->originalStart = $originalStart;
                }
                if ($originalEnd) {
                    $addEvent->originalEnd = $originalEnd;
                }

                /* If this is the start day, set the start time to
                 * the real start time, otherwise set it to
                 * 00:00 */
                if ($loopDate->compareDate($eventStart) != 0) {
                    $addEvent->start = clone $loopDate;
                    $addEvent->start->hour = $addEvent->start->min = $addEvent->start->sec = 0;
                    $addEvent->first = false;
                } else {
                    $addEvent->start = $eventStart;
                }

                /* If this is the end day, set the end time to the
                 * real event end, otherwise set it to 23:59. */
                if ($loopDate->compareDate($eventEnd) != 0) {
                    $addEvent->end = clone $loopDate;
                    $addEvent->end->hour = 23;
                    $addEvent->end->min = $addEvent->end->sec = 59;
                    $addEvent->last = false;
                } else {
                    $addEvent->end = $eventEnd;
                }
                if ($addEvent->recurs() &&
                    $addEvent->recurrence->hasCompletion($loopDate->year, $loopDate->month, $loopDate->mday)) {
                    $addEvent->status = Kronolith::STATUS_CANCELLED;
                }
                $results[$loopDate->dateString()][$addEvent->id] = $json
                    ? $addEvent->toJson(array('all_day' => $allDay))
                    : $addEvent;
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
    public static function addSearchEvents(&$events, $event, $query, $json)
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
            // Don't include any results that are outside the query range.
            if ((!empty($query->end) && $event->start->after($query->end)) ||
                (!empty($query->start) && $event->end->before($query->start))) {
                return;
            }
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
    public static function countEvents()
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
     */
    public static function initialize()
    {
        $GLOBALS['calendar_manager'] = $GLOBALS['injector']->createInstance('Kronolith_CalendarsManager');
    }

    /**
     * Returns the real name, if available, of a user.
     */
    public static function getUserName($uid)
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
    public static function getUserEmail($uid)
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
     *
     * @param string  $uid    user id to check
     * @param string  $email  email address to check
     */
    public static function isUserEmail($uid, $email)
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
     * Return Kronolith_Attendee object for a local user.
     *
     * @param string $user  The local username.
     *
     * @return mixed Return the Kronolith_Attendee object for $user, or false
     *               if the auth backend supports user listing and the user
     *               is not found.
     */
    public static function validateUserAttendee($user)
    {
        global $injector, $registry;

        $user = $registry->convertUsername($user, true);
        $auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();
        if ($auth->hasCapability('list') && !$auth->exists($user)) {
            return false;
        } else {
            return new Kronolith_Attendee(array(
                'user' => $user,
                'identities' => $injector->getInstance('Horde_Core_Factory_Identity')
            ));
        }
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
    public static function recurToString($type)
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
        case Horde_Date_Recurrence::RECUR_MONTHLY_LAST_WEEKDAY:
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
    public static function statusToString($status)
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
    public static function responseToString($response)
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
    public static function partToString($part)
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
    public static function responseFromICal($response)
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
    public static function buildStatusWidget($name,
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
     * @param string  $user        The user to list calendars for, if not
     *                             the current.
     *
     * @return array  The calendar list.
     */
    public static function listInternalCalendars($owneronly = false,
                                                 $permission = Horde_Perms::SHOW,
                                                 $user = null)
    {
        if ($owneronly && !$GLOBALS['registry']->getAuth()) {
            return array();
        }

        if (empty($user)) {
            $user = $GLOBALS['registry']->getAuth();
        }

        $kronolith_shares = $GLOBALS['injector']->getInstance('Kronolith_Shares');

        if ($owneronly || empty($GLOBALS['conf']['share']['hidden'])) {
            try {
                $calendars = $kronolith_shares->listShares(
                    $user,
                    array('perm' => $permission,
                          'attributes' => $owneronly ? $user : null,
                          'sort_by' => 'name'));
            } catch (Horde_Share_Exception $e) {
                Horde::log($e);
                return array();
            }
        } else {
            try {
                $calendars = $kronolith_shares->listShares(
                    $GLOBALS['registry']->getAuth(),
                    array('perm' => $permission,
                          'attributes' => $user,
                          'sort_by' => 'name'));
            } catch (Horde_Share_Exception $e) {
                Horde::log($e);
                return array();
            }
            $display_calendars = @unserialize($GLOBALS['prefs']->getValue('display_cals'));
            if (is_array($display_calendars)) {
                foreach ($display_calendars as $id) {
                    try {
                        $calendar = $kronolith_shares->getShare($id);
                        if ($calendar->hasPermission($user, $permission)) {
                            $calendars[$id] = $calendar;
                        }
                    } catch (Horde_Exception_NotFound $e) {
                    } catch (Horde_Share_Exception $e) {
                        Horde::log($e);
                        return array();
                    }
                }
            }
        }

        $default_share = $GLOBALS['prefs']->getValue('default_share');
        if (isset($calendars[$default_share])) {
            $calendar = $calendars[$default_share];
            unset($calendars[$default_share]);
            if (!$owneronly || ($owneronly && $calendar->get('owner') == $GLOBALS['registry']->getAuth())) {
                $calendars = array($default_share => $calendar) + $calendars;
            }
        }

        return $calendars;
    }

    /**
     * Returns all calendars a user has access to, according to several
     * parameters/permission levels.
     *
     * @param integer $permission  The permission to filter calendars by.
     * @param boolean $display     Only return calendars that are supposed to
     *                             be displayed per configuration and user
     *                             preference.
     *
     * @return array  The calendar list.
     */
    public static function listCalendars($permission = Horde_Perms::SHOW,
                                         $display = false)
    {
        $calendars = array();
        foreach ($GLOBALS['calendar_manager']->get(Kronolith::ALL_CALENDARS) as $id => $calendar) {
            if ($calendar->hasPermission($permission) &&
                (!$display || $calendar->display())) {
                $calendars['internal_' . $id] = $calendar;
            }
        }

        foreach ($GLOBALS['calendar_manager']->get(Kronolith::ALL_REMOTE_CALENDARS) as $id => $calendar) {
            try {
                if ($calendar->hasPermission($permission) &&
                    (!$display || $calendar->display())) {
                    $calendars['remote_' . $id] = $calendar;
                }
            } catch (Kronolith_Exception $e) {
                $GLOBALS['notification']->push(sprintf(_("The calendar %s returned the error: %s"), $calendar->name(), $e->getMessage()), 'horde.error');
            }
        }

        foreach ($GLOBALS['calendar_manager']->get(Kronolith::ALL_EXTERNAL_CALENDARS) as $id => $calendar) {
            if ($calendar->hasPermission($permission) &&
                (!$display || $calendar->display())) {
                $calendars['external_' . $id] = $calendar;
            }
        }

        foreach ($GLOBALS['calendar_manager']->get(Kronolith::ALL_HOLIDAYS) as $id => $calendar) {
            if ($calendar->hasPermission($permission) &&
                (!$display || $calendar->display())) {
                $calendars['holiday_' . $id] = $calendar;
            }
        }

        return $calendars;
    }

    /**
     * Returns the default calendar for the current user at the specified
     * permissions level.
     *
     * @param integer $permission  Horde_Perms constant for permission level
     *                             required.
     * @param boolean $owner_only  Only consider owner-owned calendars.
     *
     * @return string  The calendar id, or null if none.
     */
    public static function getDefaultCalendar($permission = Horde_Perms::SHOW,
                                              $owner_only = false)
    {
        $calendars = self::listInternalCalendars($owner_only, $permission);

        $default_share = $GLOBALS['prefs']->getValue('default_share');
        if (isset($calendars[$default_share])) {
            return $default_share;
        }

        $default_share = $GLOBALS['injector']
            ->getInstance('Kronolith_Factory_Calendars')
            ->create()
            ->getDefaultShare();

        // If no default share identified via share backend, use the
        // first found share, and set it in the prefs to make it stick.
        if (!isset($calendars[$default_share])) {
            reset($calendars);
            $default_share = key($calendars);
        }

        $GLOBALS['prefs']->setValue('default_share', $default_share);
        return $default_share;
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
    public static function getSyncCalendars($prune = false)
    {
        $haveRemoved = false;
        $cs = unserialize($GLOBALS['prefs']->getValue('sync_calendars'));
        if (!empty($cs)) {
            if ($prune) {
                $calendars = self::listInternalCalendars(false, Horde_Perms::DELETE);
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
    public static function addCalendarLinks()
    {
        foreach ($GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_CALENDARS) as $calendar) {
            $GLOBALS['page_output']->addLinkTag(array(
                'href' => Kronolith::feedUrl($calendar),
                'type' => 'application/atom+xml'
            ));
        }
    }

    /**
     * Returns the label to be used for a calendar.
     *
     * Attaches the owner name of shared calendars if necessary.
     *
     * @param Horde_Share_Object  A calendar.
     *
     * @return string  The calendar's label.
     */
    public static function getLabel($calendar)
    {
        $label = $calendar->get('name');
        if ($calendar->get('owner') &&
            $calendar->get('owner') != $GLOBALS['registry']->getAuth()) {
            $label .= ' [' . $GLOBALS['registry']->convertUsername($calendar->get('owner'), false) . ']';
        }
        return $label;
    }

    /**
     * Returns whether the current user has certain permissions on a calendar.
     *
     * @param string $calendar  A calendar id.
     * @param integer $perm     A Horde_Perms permission mask.
     *
     * @return boolean  True if the current user has the requested permissions.
     */
    public static function hasPermission($calendar, $perm)
    {
        try {
            $share = $GLOBALS['injector']->getInstance('Kronolith_Shares')->getShare($calendar);
            if (!$share->hasPermission($GLOBALS['registry']->getAuth(), $perm)) {
                if ($share->get('owner') == null && $GLOBALS['registry']->isAdmin()) {
                    return true;
                }
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
    public static function addShare($info)
    {
        global $calendar_manager, $injector, $prefs, $registry;

        $kronolith_shares = $injector->getInstance('Kronolith_Shares');

        try {
            $calendar = $kronolith_shares->newShare(
                $registry->getAuth(),
                strval(new Horde_Support_Randomid()),
                $info['name']
            );
        } catch (Horde_Share_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        $calendar->set('color', $info['color']);
        $calendar->set('desc', $info['description']);
        if (!empty($info['system'])) {
            $calendar->set('owner', null);
        }
        $calendar->set('calendar_type', Kronolith::SHARE_TYPE_USER);
        $tagger = self::getTagger();
        $tagger->tag(
            $calendar->getName(),
            $info['tags'],
            $calendar->get('owner'),
            Kronolith_Tagger::TYPE_CALENDAR
        );

        try {
            $kronolith_shares->addShare($calendar);
        } catch (Horde_Share_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        $all_cals = $calendar_manager->get(Kronolith::ALL_CALENDARS);
        $all_cals[$calendar->getName()] = new Kronolith_Calendar_Internal(array('share' => $calendar));
        $calendar_manager->set(Kronolith::ALL_CALENDARS, $all_cals);
        $display_cals = $calendar_manager->get(Kronolith::DISPLAY_CALENDARS);
        $display_cals[] = $calendar->getName();
        $calendar_manager->set(Kronolith::DISPLAY_CALENDARS, $display_cals);
        $prefs->setValue('display_cals', serialize($display_cals));

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
    public static function updateShare(&$calendar, $info)
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
        $tagger->replaceTags($calendar->getName(), $info['tags'], $calendar->get('owner'), Kronolith_Tagger::TYPE_CALENDAR);
    }

    /**
     * Deletes a share and removes all events associated with it.
     *
     * @param Horde_Share $calendar  The share to delete.
     *
     * @throws Kronolith_Exception
     */
    public static function deleteShare($calendar)
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
     * Returns a list of user with read access to a share.
     *
     * @param Horde_Share_Object $share  A share object.
     *
     * @return array  A hash of share users.
     */
    public static function listShareUsers($share)
    {
        global $injector;

        $users = $share->listUsers(Horde_Perms::READ);
        $groups = $share->listGroups(Horde_Perms::READ);
        if (count($groups)) {
            $horde_group = $injector->getInstance('Horde_Group');
            foreach ($groups as $group) {
                $users = array_merge(
                    $users,
                    $horde_group->listUsers($group)
                );
            }
        }

        $users = array_flip($users);
        $factory = $injector->getInstance('Horde_Core_Factory_Identity');
        foreach (array_keys($users) as $user) {
            $fullname = $factory->create($user)->getValue('fullname');
            $users[$user] = strlen($fullname) ? $fullname : $user;
        }

        return $users;
    }

    /**
     * Reads a submitted permissions form and updates the share permissions.
     *
     * @param Horde_Share_Object|Kronolith_Resource_Base $share  The share to update.
     *
     * @return array  A list of error messages.
     * @throws Kronolith_Exception
     */
    public static function readPermsForm($share)
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
        if (!($share instanceof Kronolith_Resource_Base)) {
            $old_owner = $share->get('owner');
            if ($old_owner) {
                $new_owner_backend = Horde_Util::getFormData('owner_select', Horde_Util::getFormData('owner_input', $old_owner));
                $new_owner = $GLOBALS['registry']->convertUsername($new_owner_backend, true);
            } else {
                $new_owner_backend = $new_owner = null;
            }

            // Only set new owner if this isn't a system calendar, and the
            // owner actually changed and the new owner is set at all.
            if (!is_null($old_owner) &&
                $old_owner !== $new_owner &&
                !empty($new_owner)) {
                if ($old_owner != $GLOBALS['registry']->getAuth() &&
                    !$GLOBALS['registry']->isAdmin()) {
                    $errors[] = _("Only the owner or system administrator may change ownership or owner permissions for a share");
                } elseif ($auth->hasCapability('list') &&
                          !$auth->exists($new_owner_backend)) {
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
            if (empty($user) ||
                (!($share instanceof Kronolith_Resource_Base) &&
                 $user == $new_owner)) {
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
    public static function subscribeRemoteCalendar(&$info, $update = false)
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
            $display_remote = $GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_REMOTE_CALENDARS);
            $display_remote[] = $info['url'];
            $GLOBALS['calendar_manager']->set(Kronolith::DISPLAY_REMOTE_CALENDARS, $display_remote);
            $GLOBALS['prefs']->setValue('display_remote_cals', serialize($display_remote));
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
    public static function unsubscribeRemoteCalendar($url)
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
    public static function feedUrl($calendar)
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
    public static function embedCode($calendar)
    {
        /* Get the base url */
        $url = $GLOBALS['registry']->getServiceLink('ajax', 'kronolith', true)->add(array(
            'calendar' => 'internal_' . $calendar,
            'container' => 'kronolithCal',
            'view' => 'Month'
        ));
        $url->url .= 'embed';

        return '<div id="kronolithCal"></div><script src="' . $url .
               '" type="text/javascript"></script>';
    }

    /**
     * Returns a comma separated list of attendees and resources
     *
     * @return string  Attendee/Resource list.
     */
    public static function attendeeList()
    {
        global $session;

        /* Attendees */
        $attendees = array(strval(
            $session->get('kronolith', 'attendees')
        ));

        /* Resources */
        foreach ($session->get('kronolith', 'resources', Horde_Session::TYPE_ARRAY) as $resource) {
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
     * @param  string $range  The range parameter if this is a recurring event.
     *                        Possible values are self::RANGE_THISANDFUTURE
     * @param Kronolith_Attendee_List $cancellations  If $action is 'CANCEL',
     *                                                but it is due to removing
     *                                                attendees and not
     *                                                canceling the entire
     *                                                event, these are the
     *                                                uninvited attendees and
     *                                                are the ONLY people that
     *                                                will receive the CANCEL
     *                                                iTIP.  @since 4.2.10
     */
    public static function sendITipNotifications(
        Kronolith_Event $event, Horde_Notification_Handler $notification,
        $action, Horde_Date $instance = null, $range = null,
        Kronolith_Attendee_List $cancellations = null)
    {
        global $injector, $prefs, $registry;

        if (!count($event->attendees) || $prefs->getValue('itip_silent')) {
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

        if ($action == self::ITIP_CANCEL && count($cancellations)) {
            $mail_attendees = $cancellations;
        } elseif ($event->organizer &&
                  !self::isUserEmail($event->creator, $event->organizer)) {
            /* Only send updates to organizer if the user is not the
             * organizer */
            if (isset($event->attendees['email:' . $event->organizer])) {
                $organizer = $event->attendees['email:' . $event->organizer];
	        } else {
                $organizer = new Kronolith_Attendee(array('email' => $event->organizer));
            }
            $mail_attendees = new Kronolith_Attendee_List(array($organizer));
        } else {
            $mail_attendees = $event->attendees;
        }

        foreach ($mail_attendees as $attendee) {
            /* Don't send notifications to the ORGANIZER if this is the
             * ORGANIZER's copy of the event. */
            if (!$event->organizer &&
                Kronolith::isUserEmail($event->creator, $attendee->email)) {
                continue;
            }

            /* Don't bother sending an invitation/update if the recipient does
             * not need to participate, or has declined participating, or
             * doesn't have an email address. */
            if (strpos($attendee->email, '@') === false ||
                $attendee->response == self::RESPONSE_DECLINED) {
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

            case self::ITIP_REPLY:
                $filename = 'event-reply.ics';
                $events = $event->toiCalendar(new Horde_Icalendar());
                $vEvent = array_shift($events);
                $itipIdentity = new Horde_Itip_Resource_Identity(
                        $ident,
                        $vEvent->getAttribute('ATTENDEE'),
                        (string)$ident->getFromAddress()
                );
                /* Find which of the creator's mail addresses is used here */
                foreach ($event->attendees as $attendee) {
                    if (self::isUserEmail($event->creator, $attendee->email)) {
                        switch ($attendee->response) {
                        case self::RESPONSE_ACCEPTED:
                            $type = new Horde_Itip_Response_Type_Accept($itipIdentity);
                        break;
                        case self::RESPONSE_DECLINED:
                            $type = new Horde_Itip_Response_Type_Decline($itipIdentity);
                        break;
                        case self::RESPONSE_TENTATIVE:
                            $type = new Horde_Itip_Response_Type_Tentative($itipIdentity);
                        break;
                        default:
                            return;
                        }
                        try {
                            // Send the reply.
                            Horde_Itip::factory($vEvent, $itipIdentity)->sendMultiPartResponse(
                                $type,
                                new Horde_Core_Itip_Response_Options_Horde('UTF-8', array()),
                                $injector->getInstance('Horde_Mail')
                            );
                        } catch (Horde_Itip_Exception $e) {
                            $notification->push(sprintf(_("Error sending reply: %s."), $e->getMessage()), 'horde.error');
                        }
                    }
                }
                return;

            case self::ITIP_REQUEST:
            default:
                $method = 'REQUEST';
                if ($attendee->response == self::RESPONSE_NONE) {
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

            $view->organizer = $registry->convertUserName($event->creator, false);

            if ($action == self::ITIP_REQUEST) {
                $attend_link = Horde::url('attend.php', true, -1)
                    ->add(array('c' => $event->calendar,
                                'e' => $event->id,
                                'u' => $attendee->email));
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
                foreach($vevent as &$ve) {
                    try {
                        $uid = $ve->getAttribute('UID');
                    } catch (Horde_Icalendar_Exception $e) {
                        continue;
                    }
                    if ($event->uid == $uid) {
                        $ve->setAttribute('RECURRENCE-ID', $instance);
                        if (!empty($range)) {
                            $ve->setParameter('RECURRENCE-ID', array('RANGE' => $range));
                        }
                        $ve->setAttribute('DTSTART', $instance, array(), false);
                        $diff = $event->end->timestamp() - $event->start->timestamp();
                        $end = clone $instance;
                        $end->sec += $diff;
                        $ve->setAttribute('DTEND', $end, array(), false);
                        $ve->removeAttribute('EXDATE');
			$event->fromiCalendar($ve);
                        break;
                    }
                }
            }
            $iCal->addComponent($vevent);

            /* text/calendar part */
            $ics = new Horde_Mime_Part();
            $ics->setType('text/calendar');
            $ics->setContents($iCal->exportvCalendar());
            $ics->setName($filename);
            $ics->setContentTypeParameter('method', $method);
            $ics->setCharset('UTF-8');
            $ics->setEOL("\r\n");

            /* application/ics part */
            $ics2 = clone $ics;
            $ics2->setType('application/ics');

            /* multipart/mixed part */
            $multipart = new Horde_Mime_Part();
            $multipart->setType('multipart/mixed');
            $inner = self::buildMimeMessage($view, 'notification', $image);
            $inner->addPart($ics);
            $multipart->addPart($inner);
            $multipart->addPart($ics2);

            $recipient = $attendee->addressObject;
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
    public static function sendNotification($event, $action)
    {
        global $injector, $prefs, $registry;

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

        $owner = $share->get('owner');
        if ($owner) {
            $recipients[$owner] = self::_notificationPref($owner, 'owner');
        }

        $senderIdentity = $injector->getInstance('Horde_Core_Factory_Identity')
            ->create($registry->getAuth() ?: $event->creator ?: $owner);

        foreach ($share->listUsers(Horde_Perms::READ) as $user) {
            if (empty($recipients[$user])) {
                $recipients[$user] = self::_notificationPref($user, 'read', $calendar);
            }
        }

        foreach ($share->listGroups(Horde_Perms::READ) as $group) {
            try {
                $group_users = $groups->listUsers($group);
            } catch (Horde_Group_Exception $e) {
                Horde::log($e, 'ERR');
                continue;
            }

            foreach ($group_users as $user) {
                if (empty($recipients[$user])) {
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

        $image = self::getImagePart('big_new.png');
        $view = new Horde_View(array('templatePath' => KRONOLITH_TEMPLATES . '/update'));
        $view->event = $event;
        $view->calendar = Kronolith::getLabel($share);
        $view->imageId = $image->getContentId();
        if (!$prefs->isLocked('event_notification')) {
            $view->prefsUrl = Horde::url($registry->getServiceLink('prefs', 'kronolith'), true)->remove(session_name());
        }
        new Horde_View_Helper_Text($view);

        foreach ($addresses as $lang => $twentyFour) {
            $registry->setLanguageEnvironment($lang);

            switch ($action) {
            case 'add':
                $subject = _("Event added:");
                break;

            case 'edit':
                $subject = _("Event edited:");
                break;

            case 'delete':
                $subject = _("Event deleted:");
                break;
            }

            foreach ($twentyFour as $tf => $dateFormat) {
                foreach ($dateFormat as $df => $df_recipients) {
                    $view->header = $subject . ' ' . $event->title;
                    $mail = new Horde_Mime_Mail(array(
                        'Subject' => $view->header,
                        'To' => implode(',', $df_recipients),
                        'From' => $senderIdentity->getDefaultFromAddress(true),
                        'User-Agent' => 'Kronolith ' . $registry->getVersion(),
                    ));
                    $multipart = self::buildMimeMessage($view, 'notification', $image);
                    $mail->setBasePart($multipart);
                    Horde::log(sprintf('Sending event notifications for %s to %s', $event->title, implode(', ', $df_recipients)), 'DEBUG');
                    $mail->send($injector->getInstance('Horde_Mail'));
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
    public static function notifyOfResourceRejection($event)
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
            $GLOBALS['notification']->push(
                sprintf(
                    ngettext(
                        "The following resource has declined your request: %s",
                        "The following resources have declined your request: %s",
                        count($declined)
                    ),
                    implode(", ", $declined)
                ),
                'horde.error'
            );
        }
        if (count($accepted)) {
             $GLOBALS['notification']->push(
                 sprintf(
                     ngettext(
                         "The following resource has accepted your request: %s",
                         "The following resources have accepted your request: %s",
                         count($accepted)
                     ),
                     implode(", ", $accepted)
                 ),
                 'horde.success'
             );
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
    public static function _notificationPref($user, $mode, $calendar = null)
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
    public static function buildMimeMessage(Horde_View $view, $template,
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
    public static function getImagePart($file)
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
    public static function currentDate()
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
    public static function parseDate($date, $withtime = true, $timezone = null)
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
    public static function tabs($renderer)
    {
        global $injector, $prefs;

        $view = $injector->createInstance('Horde_View');

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
            . _("Today") . '</a>';
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
    public static function eventTabs($tabname, $event)
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
    public static function getDriver($driver = null, $calendar = null)
    {
        $instance = $GLOBALS['injector']
            ->getInstance('Kronolith_Factory_Driver')
            ->create($driver);

        if (!is_null($calendar)) {
            $instance->open($calendar);

            /* Remote calendar parameters are per calendar. */
            if ($instance instanceof Kronolith_Driver_Ical) {
                $instance->setParams(self::getRemoteParams($calendar));
            }
        }

        return $instance;
    }

    /**
     * Returns a Kronolith_Calendar object for a driver instance.
     *
     * @since Kronolith 4.0.1
     *
     * @param Kronolith_Driver  A driver instance.
     *
     * @return Kronolith_Calendar  The matching calendar instance.
     */
    public static function getCalendar(Kronolith_Driver $driver)
    {
        global $calendar_manager;

        switch (true) {
        case $driver instanceof Kronolith_Driver_Sql:
        case $driver instanceof Kronolith_Driver_Kolab:
            return $calendar_manager->getEntry(Kronolith::ALL_CALENDARS, $driver->calendar);

        case $driver instanceof Kronolith_Driver_Ical:
            return $calendar_manager->getEntry(Kronolith::ALL_REMOTE_CALENDARS, $driver->calendar);

        case $driver instanceof Kronolith_Driver_Horde:
            $all = $calendar_manager->get(Kronolith::ALL_EXTERNAL_CALENDARS);
            return $all[$driver->calendar];

        case $driver instanceof Kronolith_Driver_Holidays:
            return $calendar_manager->getEntry(Kronolith::ALL_HOLIDAYS, $driver->calendar);

        case $driver instanceof Kronolith_Driver_Resource_Sql:
            return $calendar_manager->getEntry(Kronolith::ALL_RESOURCE_CALENDARS, $driver->calendar);
        }
    }

    /**
     * Check for HTTP authentication credentials
     */
    public static function getRemoteParams($calendar)
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
    public static function displayedCalendars()
    {
        $calendars = array();
        foreach ($GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_CALENDARS) as $calendarId) {
            $calendars[] = $GLOBALS['calendar_manager']->getEntry(Kronolith::ALL_CALENDARS, $calendarId);
        }
        if (count($GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_RESOURCE_CALENDARS))) {
            $r_driver = self::getDriver('Resource');
            foreach ($GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_RESOURCE_CALENDARS) as $c) {
                try {
                    $resource = $r_driver->getResource($r_driver->getResourceIdByCalendar($c));
                    $calendars[] = new Kronolith_Calendar_Resource(array('resource' => $resource));
                } catch (Horde_Exception_NotFound $e) {
                }
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
    public static function getView($view)
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
    public static function viewShowLocation()
    {
        $show = @unserialize($GLOBALS['prefs']->getValue('show_location'));
        return @in_array('screen', $show);
    }

    /**
     * Should we show event time, based on the show_time preference?
     */
    public static function viewShowTime()
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
    public static function backgroundColor($calendar)
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
    public static function foregroundColor($calendar)
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
    public static function getCSSColors($calendar, $with_attribute = true)
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
    public static function randomColor()
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
    public static function showAjaxView()
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
    public static function sortEvents($days)
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
    protected static function _sortEventStartTime($a, $b)
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
    public static function getTagger()
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
    public static function getInternalCalendar($target)
    {
        if ($GLOBALS['conf']['resources']['enabled'] && self::getDriver('Resource')->isResourceCalendar($target)) {
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
    public static function getAddressbookSearchParams()
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
    public static function hasApiPermission($api, $perm = Horde_Perms::READ)
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
    public static function removeUserEvents($user)
    {
        if (!$GLOBALS['registry']->isAdmin()) {
            throw new Horde_Exception_PermissionDenied();
        }

        try {
            $shares = $GLOBALS['injector']
                ->getInstance('Kronolith_Shares')
                ->listShares($user, array('perm' => Horde_Perms::EDIT));
        } catch (Horde_Share_Exception $e) {
            Horde::log($shares, 'ERR');
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
                if ($event->creator == $user) {
                    $driver->deleteEvent($event->id);
                }
            }
        }
    }

    /**
     * Export an event to a timeslice.
     *
     *
     */
    public static function toTimeslice(Kronolith_Event $event, $type, $client)
    {
        global $registry;

        if (!$registry->hasMethod('time/recordTime')) {
            throw new Kronolith_Exception();
        }

        $data = array(
            'date' => $event->start,
            'type' => $type,
            'client' => $client,
            'hours' => ($event->end->timestamp() - $event->start->timestamp()) / 3600,
            'description' => $event->title,
            'note' => $event->description
        );

        try {
            $registry->time->recordTime($data);
        } catch (Horde_Exception $e) {
            throw new Kronolith_Exception($e->getMessage());
        }
    }

}
