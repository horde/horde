<?php
/**
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Kronolith
 */

/**
 * The Kronolith:: class provides functionality common to all of Kronolith.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
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

    /** Free/Busy not found */
    const ERROR_FB_NOT_FOUND = 1;

    /** The event can be delegated. */
    const PERMS_DELEGATE = 1024;

    /**
     * Driver singleton instances.
     *
     * @var array
     */
    static private $_instances = array();

    /**
     * @var Kronolith_Tagger
     */
    static private $_tagger;

    /**
     * Output everything for the AJAX interface up to but not including the
     * <body> tag.
     */
    public static function header()
    {
        // Need to include script files before we start output
        $datejs = str_replace('_', '-', $GLOBALS['language']) . '.js';
        if (!file_exists($GLOBALS['registry']->get('jsfs') . '/' . $datejs)) {
            $datejs = 'en-US.js';
        }
        Horde::addScriptFile('effects.js', 'horde');
        Horde::addScriptFile('horde.js', 'horde');
        Horde::addScriptFile('dragdrop2.js', 'horde');
        Horde::addScriptFile('Growler.js', 'horde');
        Horde::addScriptFile('dhtmlHistory.js', 'horde');
        Horde::addScriptFile('redbox.js', 'horde');
        Horde::addScriptFile('tooltips.js', 'horde');
        Horde::addScriptFile('kronolith.js', 'kronolith');
        Horde::addScriptFile($datejs, 'kronolith');
        Horde::addScriptFile('date.js', 'kronolith');

        // No IE 8 code at the moment.
        header('X-UA-Compatible: IE=7');

        if (isset($GLOBALS['language'])) {
            header('Content-type: text/html; charset=' . Horde_Nls::getCharset());
            header('Vary: Accept-Language');
        }

        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">' . "\n" .
             (!empty($GLOBALS['language']) ? '<html lang="' . strtr($GLOBALS['language'], '_', '-') . '"' : '<html') . ">\n".
             "<head>\n" .
             '<title>' . htmlspecialchars($GLOBALS['registry']->get('name')) . "</title>\n" .
             '<link href="' . $GLOBALS['registry']->getImageDir() . "/favicon.ico\" rel=\"SHORTCUT ICON\" />\n".
             Horde::wrapInlineScript(self::includeJSVars());

        Horde::includeStylesheetFiles();

        echo "</head>\n";

        // Send what we have currently output so the browser can start
        // loading CSS/JS. See:
        // http://developer.yahoo.com/performance/rules.html#flush
        flush();
    }

    /**
     * Outputs the javascript code which defines all javascript variables
     * that are dependent on the local user's account.
     *
     * @private
     *
     * @return string
     */
    public static function includeJSVars()
    {
        global $browser, $conf, $prefs, $registry;

        $kronolith_webroot = $registry->get('webroot');
        $horde_webroot = $registry->get('webroot', 'horde');
        $has_tasks = $GLOBALS['registry']->hasInterface('tasks');

        /* Variables used in core javascript files. */
        $code['conf'] = array(
            'URI_AJAX' => Horde::url($kronolith_webroot . '/ajax.php', true, -1),
            'URI_IMG' => $registry->getImageDir() . '/',
            'URI_SNOOZE' => Horde::url($registry->get('webroot', 'horde') . '/services/snooze.php', true, -1),
            'SESSION_ID' => defined('SID') ? SID : '',
            'prefs_url' => str_replace('&amp;', '&', Horde::getServiceLink('options', 'kronolith')),
            'name' => $registry->get('name'),
            'is_ie6' => ($browser->isBrowser('msie') && ($browser->getMajor() < 7)),
            'login_view' => $prefs->getValue('defaultview'),
            'default_calendar' => 'internal|' . self::getDefaultCalendar(Horde_Perms::EDIT),
            'week_start' => (int)$prefs->getValue('week_start_monday'),
            'date_format' => str_replace(array('%e', '%d', '%a', '%A', '%m', '%h', '%b', '%B', '%y', '%Y'),
                                         array('d', 'dd', 'ddd', 'dddd', 'MM', 'MMM', 'MMM', 'MMMM', 'yy', 'yyyy'),
                                         Horde_Nls::getLangInfo(D_FMT)),
            'time_format' => $prefs->getValue('twentyFour') ? 'HH:mm' : 'hh:mm tt',
            'status' => array('tentative' => self::STATUS_TENTATIVE,
                             'confirmed' => self::STATUS_CONFIRMED,
                             'cancelled' => self::STATUS_CANCELLED,
                             'free' => self::STATUS_FREE),
            'recur' => array(Horde_Date_Recurrence::RECUR_NONE => 'None',
                             Horde_Date_Recurrence::RECUR_DAILY => 'Daily',
                             Horde_Date_Recurrence::RECUR_WEEKLY => 'Weekly',
                             Horde_Date_Recurrence::RECUR_MONTHLY_DATE => 'Monthly',
                             Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY => 'Monthly',
                             Horde_Date_Recurrence::RECUR_YEARLY_DATE => 'Yearly',
                             Horde_Date_Recurrence::RECUR_YEARLY_DAY => 'Yearly',
                             Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY => 'Yearly'),
            'snooze' => array('' => _("Snooze..."),
                              '-1' => _("Dismiss"),
                              '5' => _("5 minutes"),
                              '15' => _("15 minutes"),
                              '60' => _("1 hour"),
                              '360' => _("6 hours"),
                              '1440' => _("1 day")),
        );

        if ($has_tasks) {
            $code['conf']['tasks'] = $GLOBALS['registry']->tasks->ajaxDefaults();
        }

        // Calendars
        foreach (array(true, false) as $my) {
            foreach ($GLOBALS['all_calendars'] as $id => $calendar) {
                $owner = $calendar->get('owner') == Horde_Auth::getAuth();
                if (($my && $owner) || (!$my && !$owner)) {
                    $code['conf']['calendars']['internal'][$id] = array(
                        'name' => ($owner ? '' : '[' . Horde_Auth::convertUsername($calendar->get('owner'), false) . '] ')
                            . $calendar->get('name'),
                        'owner' => $owner,
                        'fg' => self::foregroundColor($calendar),
                        'bg' => self::backgroundColor($calendar),
                        'show' => in_array($id, $GLOBALS['display_calendars']),
                        'edit' => $calendar->hasPermission(Horde_Auth::getAuth(), Horde_Perms::EDIT));
                }
            }

            // Tasklists
            if (!$has_tasks) {
                continue;
            }
            foreach ($GLOBALS['registry']->tasks->listTasklists($my, Horde_Perms::SHOW) as $id => $tasklist) {
                $owner = $tasklist->get('owner') == Horde_Auth::getAuth();
                if (($my && $owner) || (!$my && !$owner)) {
                    $code['conf']['calendars']['tasklists']['tasks/' . $id] = array(
                        'name' => ($owner ? '' : '[' . Horde_Auth::convertUsername($tasklist->get('owner'), false) . '] ')
                            . $tasklist->get('name'),
                        'owner' => $owner,
                        'fg' => self::foregroundColor($tasklist),
                        'bg' => self::backgroundColor($tasklist),
                        'show' => in_array('tasks/' . $id, $GLOBALS['display_external_calendars']),
                        'edit' => $tasklist->hasPermission(Horde_Auth::getAuth(), Horde_Perms::EDIT));
                }
            }
        }

        // Timeobjects
        foreach ($GLOBALS['all_external_calendars'] as $api => $categories) {
            foreach ($categories as $id => $name) {
                if ($api == 'tasks') {
                    continue;
                }
                $calendar = $api . '/' . $id;
                $code['conf']['calendars']['external'][$calendar] = array(
                    'name' => $name,
                    'fg' => '#000',
                    'bg' => '#ddd',
                    'api' => $GLOBALS['registry']->get('name', $GLOBALS['registry']->hasInterface($api)),
                    'show' => in_array($calendar, $GLOBALS['display_external_calendars']));
            }
        }

        // Remote calendars
        foreach ($GLOBALS['all_remote_calendars'] as $calendar) {
            $code['conf']['calendars']['remote'][$calendar['url']] = array(
                'name' => $calendar['name'],
                'fg' => self::foregroundColor($calendar),
                'bg' => self::backgroundColor($calendar),
                'show' => in_array($calendar['url'], $GLOBALS['display_remote_calendars']));
        }

        // Holidays
        foreach ($GLOBALS['all_holidays'] as $holiday) {
            $code['conf']['calendars']['holiday'][$holiday['id']] = array(
                'name' => $holiday['title'],
                'fg' => self::foregroundColor($holiday),
                'bg' => self::backgroundColor($holiday),
                'show' => in_array($holiday['id'], $GLOBALS['display_holidays']));
        }

        /* Gettext strings used in core javascript files. */
        $code['text'] = array(
            'ajax_timeout' => _("There has been no contact with the remote server for several minutes. The server may be temporarily unavailable or network problems may be interrupting your session. You will not see any updates until the connection is restored."),
            'ajax_recover' => _("The connection to the remote server has been restored."),
            'alarm' => _("Alarm:"),
            'noalerts' => _("No Notifications"),
            'alerts' => str_replace('%d', '#{count}', _("%d notifications")),
            'hidelog' => _("Hide Notifications"),
            'week' => str_replace('%d', '#{week}', _("Week %d")),
            'agenda' => _("Agenda"),
            'searching' => str_replace('%s', '#{term}', _("Events matching \"%s\"")),
            'allday' => _("All day"),
            'prefs' => _("Options"),
        );
        for ($i = 1; $i <= 12; ++$i) {
            $code['text']['month'][$i - 1] = Horde_Nls::getLangInfo(constant('MON_' . $i));
        }
        for ($i = 1; $i <= 7; ++$i) {
            $code['text']['weekday'][$i] = Horde_Nls::getLangInfo(constant('DAY_' . $i));
        }
        foreach (array(Horde_Date_Recurrence::RECUR_DAILY,
                       Horde_Date_Recurrence::RECUR_WEEKLY,
                       Horde_Date_Recurrence::RECUR_MONTHLY_DATE,
                       Horde_Date_Recurrence::RECUR_MONTHLY_WEEKDAY,
                       Horde_Date_Recurrence::RECUR_YEARLY_DATE,
                       Horde_Date_Recurrence::RECUR_YEARLY_DAY,
                       Horde_Date_Recurrence::RECUR_YEARLY_WEEKDAY) as $recurType) {
            $code['text']['recur'][$recurType] = self::recurToString($recurType);
        }

        return array('var Kronolith = ' . Horde_Serialize::serialize($code, Horde_Serialize::JSON, Horde_Nls::getCharset()) . ';');
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
     */
    public static function listAlarms($date, $calendars, $fullevent = false)
    {
        $kronolith_driver = self::getDriver();

        $alarms = array();
        foreach ($calendars as $cal) {
            $kronolith_driver->open($cal);
            $alarms[$cal] = $kronolith_driver->listAlarms($date, $fullevent);
            if (is_a($alarms[$cal], 'PEAR_Error')) {
                return $alarms[$cal];
            }
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
     */
    public static function search($query, $calendar = null)
    {
        if ($calendar) {
            $driver = explode('|', $calendar, 2);
            $calendars = array($driver[0] => array($driver[1]));
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
     * @param Horde_Date $startDate    The start of the time range.
     * @param Horde_Date $endDate      The end of the time range.
     * @param array $calendars         The calendars to check for events.
     * @param boolean $showRecurrence  Return every instance of a recurring
     *                                 event? If false, will only return
     *                                 recurring events once inside the
     *                                 $startDate - $endDate range.
     *                                 Defaults to true
     * @param boolean $alarmsOnly      Filter results for events with alarms
     *                                 Defaults to false
     * @param boolean $showRemote      Return events from remote and
     *                                 listTimeObjects as well?
     *
     * @return array  The events happening in this time period.
     */
    public static function listEvents($startDate, $endDate, $calendars = null,
                                      $showRecurrence = true,
                                      $alarmsOnly = false, $showRemote = true)
    {
        $results = array();

        /* Internal calendars. */
        if (!isset($calendars)) {
            $calendars = $GLOBALS['display_calendars'];
        }
        $driver = self::getDriver();
        foreach ($calendars as $calendar) {
            $driver->open($calendar);
            $events = $driver->listEvents($startDate, $endDate, true);
            if (!is_a($events, 'PEAR_Error')) {
                self::mergeEvents($results, $events);
            }
        }

        /* Resource calendars (this would only be populated if explicitly
         * requested in the request, so include them if this is set regardless
         * of $calendars value).
         *
         * @TODO: Should we disallow even *viewing* these if the user is not an
         *        admin?
         */
        if (!empty($GLOBALS['display_resource_calendars'])) {
            $driver = self::getDriver('Resource');
            foreach ($GLOBALS['display_resource_calendars'] as $calendar) {
                $driver->open($calendar);
                $events = $driver->listEvents($startDate, $endDate, true);
                if (!is_a($events, 'PEAR_Error')) {
                    self::mergeEvents($results, $events);
                }
            }
        }

        if ($showRemote) {
            /* Horde applications providing listTimeObjects. */
            $driver = self::getDriver('Horde');
            foreach ($GLOBALS['display_external_calendars'] as $external_cal) {
                $driver->open($external_cal);
                $events = $driver->listEvents($startDate, $endDate, true);
                if (!is_a($events, 'PEAR_Error')) {
                    self::mergeEvents($results, $events);
                }
            }

            /* Remote Calendars. */
            $driver = self::getDriver('Ical');
            foreach ($GLOBALS['display_remote_calendars'] as $url) {
                $driver->open($url);
                foreach (self::getRemoteParams($url) as $param => $value) {
                    $driver->setParam($param, $value);
                }
                $events = $driver->listEvents($startDate, $endDate, true);
                if (!is_a($events, 'PEAR_Error')) {
                    self::mergeEvents($results, $events);
                }
            }

            /* Holidays. */
            $driver = self::getDriver('Holidays');
            foreach ($GLOBALS['display_holidays'] as $holiday) {
                $driver->open($holiday);
                $events = $driver->listEvents($startDate, $endDate, true);
                if (!is_a($events, 'PEAR_Error')) {
                    self::mergeEvents($results, $events);
                }
            }
        }

        /* Sort events. */
        foreach ($results as $day => $devents) {
            if (count($devents)) {
                uasort($devents, array('Kronolith', '_sortEventStartTime'));
                $results[$day] = $devents;
            }
        }

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
     *
     * @access private
     */
    public static function addEvents(&$results, &$event, $startDate, $endDate,
                                     $showRecurrence, $json,
                                     $coverDates = true)
    {
        if ($event->recurs() && $showRecurrence) {
            /* Recurring Event. */

            /* We can't use the event duration here because we might cover a
             * daylight saving time switch. */
            $diff = array($event->end->year - $event->start->year,
                          $event->end->month - $event->start->month,
                          $event->end->mday - $event->start->mday,
                          $event->end->hour - $event->start->hour,
                          $event->end->min - $event->start->min);
            while ($diff[4] < 0) {
                --$diff[3];
                $diff[4] += 60;
            }
            while ($diff[3] < 0) {
                --$diff[2];
                $diff[3] += 24;
            }
            while ($diff[2] < 0) {
                --$diff[1];
                $diff[2] += Horde_Date_Utils::daysInMonth($event->start->month, $event->start->year);
            }
            while ($diff[1] < 0) {
                --$diff[0];
                $diff[1] += 12;
            }

            if ($event->start->compareDateTime($startDate) < 0) {
                /* The first time the event happens was before the period
                 * started. Start searching for recurrences from the start of
                 * the period. */
                $next = array('year' => $startDate->year,
                              'month' => $startDate->month,
                              'mday' => $startDate->mday);
            } else {
                /* The first time the event happens is in the range; unless
                 * there is an exception for this ocurrence, add it. */
                if (!$event->recurrence->hasException($event->start->year,
                                                      $event->start->month,
                                                      $event->start->mday)) {
                    if ($coverDates) {
                        self::addCoverDates($results, $event, $event->start, $event->end, $json);
                    } else {
                        $results[$event->start->dateString()][$event->getId()] = $json ? $event->toJson() : $event;
                    }
                }

                /* Start searching for recurrences from the day after it
                 * starts. */
                $next = clone $event->start;
                ++$next->mday;
            }

            /* Add all recurrences of the event. */
            $next = $event->recurrence->nextRecurrence($next);
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
                        $results[$addEvent->start->dateString()][$addEvent->getId()] = $json ? $addEvent->toJson() : $addEvent;

                    }
                }
                $next = $event->recurrence->nextRecurrence(
                    array('year' => $next->year,
                          'month' => $next->month,
                          'mday' => $next->mday + 1,
                          'hour' => $next->hour,
                          'min' => $next->min,
                          'sec' => $next->sec));
            }
        } else {
            if (!$coverDates) {
                $results[$event->start->dateString()][$event->getId()] = $json ? $event->toJson() : $event;
            } else {
                /* Event only occurs once. */
                $allDay = $event->isAllDay();

                /* Work out what day it starts on. */
                if ($event->start->compareDateTime($startDate) < 0) {
                    /* It started before the beginning of the period. */
                    $eventStart = clone $startDate;
                } else {
                    $eventStart = clone $event->start;
                }

                /* Work out what day it ends on. */
                if ($event->end->compareDateTime($endDate) > 0) {
                    /* Ends after the end of the period. */
                    if (is_object($endDate)) {
                        $eventEnd = clone $endDate;
                    } else {
                        $eventEnd = $endDate;
                    }
                } else {
                    /* If the event doesn't end at 12am set the end date to
                     * the current end date. If it ends at 12am and does not
                     * end at the same time that it starts (0 duration), set
                     * the end date to the previous day's end date. */
                    if ($event->end->hour != 0 ||
                        $event->end->min != 0 ||
                        $event->end->sec != 0 ||
                        $event->start->compareDateTime($event->end) == 0 ||
                        $allDay) {
                        $eventEnd = clone $event->end;
                    } else {
                        $eventEnd = new Horde_Date(
                            array('hour' =>  23,
                                  'min' =>   59,
                                  'sec' =>   59,
                                  'month' => $event->end->month,
                                  'mday' =>  $event->end->mday - 1,
                                  'year' =>  $event->end->year));
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
                            $addEvent->start = new Horde_Date(array(
                                'hour' => 0, 'min' => 0, 'sec' => 0,
                                'month' => $loopDate->month, 'mday' => $loopDate->mday, 'year' => $loopDate->year));
                            $addEvent->first = false;
                        }

                        /* If this is the end day, set the end time to the
                         * real event end, otherwise set it to 23:59. */
                        if ($loopDate->compareDate($eventEnd) == 0) {
                            $addEvent->end = $eventEnd;
                        } else {
                            $addEvent->end = new Horde_Date(array(
                                'hour' => 23, 'min' => 59, 'sec' => 59,
                                'month' => $loopDate->month, 'mday' => $loopDate->mday, 'year' => $loopDate->year));
                            $addEvent->last = false;
                        }

                        $results[$loopDate->dateString()][$addEvent->getId()] = $json ? $addEvent->toJson($allDay) : $addEvent;
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
    public static function addCoverDates(&$results, $event, $eventStart,
                                         $eventEnd, $json)
    {
        $loopDate = new Horde_Date($eventStart->year, $eventStart->month, $eventStart->mday);
        $allDay = $event->isAllDay();
        $first = true;
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
                $results[$loopDate->dateString()][$addEvent->getId()] = $json ? $addEvent->toJson($allDay) : $addEvent;
            }
            $loopDate->mday++;
        }
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
        $calendars = self::listCalendars(true, Horde_Perms::ALL);
        $current_calendar = $kronolith_driver->getCalendar();

        $count = 0;
        foreach (array_keys($calendars) as $calendar) {
            $kronolith_driver->open($calendar);
            $count += $kronolith_driver->countEvents();
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

        $dateParser = Horde_Date_Parser::factory();
        $r = $dateParser->parse($title, array('return' => 'result'));
        if (!($d = $r->guess())) {
            throw new Horde_Exception(sprintf(_("Cannot parse event description \"%s\""), Horde_String::truncate($text)));
        }

        $title = $r->untaggedText();
        $start = $d->timestamp();

        $kronolith_driver = Kronolith::getDriver(null, $calendar);
        $event = $kronolith_driver->getEvent();
        $event->initialized = true;
        $event->setTitle($title);
        $event->setDescription($description);
        $event->start = $d;
        $event->end = $d->add(array('hour' => 1));

        $eventId = $event->save();
        if (is_a($eventId, 'PEAR_Error')) {
            return $eventId;
        }
        return $event;
    }

    /**
     * Initial app setup code.
     */
    public static function initialize()
    {
        /* Store the request timestamp if it's not already present. */
        if (!isset($_SERVER['REQUEST_TIME'])) {
            $_SERVER['REQUEST_TIME'] = time();
        }

        /* Initialize Kronolith session if we don't have one */
        if (!isset($_SESSION['kronolith'])) {
            $_SESSION['kronolith'] = array();
        }

        /* Fetch display preferences. */
        $GLOBALS['display_calendars'] = @unserialize($GLOBALS['prefs']->getValue('display_cals'));
        $GLOBALS['display_remote_calendars'] = @unserialize($GLOBALS['prefs']->getValue('display_remote_cals'));
        $GLOBALS['display_external_calendars'] = @unserialize($GLOBALS['prefs']->getValue('display_external_cals'));
        $GLOBALS['display_holidays'] = @unserialize($GLOBALS['prefs']->getValue('holiday_drivers'));

        if (!is_array($GLOBALS['display_calendars'])) {
            $GLOBALS['display_calendars'] = array();
        }
        if (!is_array($GLOBALS['display_remote_calendars'])) {
            $GLOBALS['display_remote_calendars'] = array();
        }
        if (!is_array($GLOBALS['display_external_calendars'])) {
            $GLOBALS['display_external_calendars'] = array();
        }
        if (!is_array($GLOBALS['display_holidays']) ||
            empty($GLOBALS['conf']['holidays']['enable'])) {
            $GLOBALS['display_holidays'] = array();
        }

        /* Update preferences for which calendars to display. If the
         * user doesn't have any selected calendars to view then fall
         * back to an available calendar. An empty string passed in this
         * parameter will clear any existing session value.*/
        if (($calendarId = Horde_Util::getFormData('display_cal')) !== null) {
            $_SESSION['kronolith']['display_cal'] = $calendarId;
        }
        if (!empty($_SESSION['kronolith']['display_cal'])) {
            /* Specifying a value for display_cal is always to make sure
             * that only the specified calendars are shown. Use the
             * "toggle_calendar" argument  to toggle the state of a single
             * calendar. */
            $GLOBALS['display_calendars'] = array();
            $GLOBALS['display_remote_calendars'] = array();
            $GLOBALS['display_external_calendars'] = array();
            $GLOBALS['display_resource_calendars'] = array();
            $GLOBALS['display_holidays'] = array();
            $calendars = $_SESSION['kronolith']['display_cal'];
            if (!is_array($calendars)) {
                $calendars = array($calendars);
            }
            foreach ($calendars as $calendarId) {
                if (strncmp($calendarId, 'remote_', 7) === 0) {
                    $calendarId = substr($calendarId, 7);
                    if (!in_array($calendarId, $GLOBALS['display_remote_calendars'])) {
                        $GLOBALS['display_remote_calendars'][] = $calendarId;
                    }
                } elseif (strncmp($calendarId, 'external_', 9) === 0) {
                    $calendarId = substr($calendarId, 9);
                    if (!in_array($calendarId, $GLOBALS['display_external_calendars'])) {
                        $GLOBALS['display_external_calendars'][] = $calendarId;
                    }
                } elseif (strncmp($calendarId, 'resource_', 9) === 0) {
                    if (!in_array($calendarId, $GLOBALS['display_resource_calendars'])) {
                        $GLOBALS['display_resource_calendars'][] = $calendarId;
                    }
                } elseif (strncmp($calendarId, 'holidays_', 9) === 0) {
                    $calendarId = substr($calendarId, 9);
                    if (!in_array($calendarId, $GLOBALS['display_holidays'])) {
                        $GLOBALS['display_holidays'][] = $calendarId;
                    }
                } else {
                    if (!in_array($calendarId, $GLOBALS['display_calendars'])) {
                        $GLOBALS['display_calendars'][] = $calendarId;
                    }
                }
            }
        }

        /* Check for single "toggle" calendars. */
        if (($calendarId = Horde_Util::getFormData('toggle_calendar')) !== null) {
            if (strncmp($calendarId, 'remote_', 7) === 0) {
                $calendarId = substr($calendarId, 7);
                if (in_array($calendarId, $GLOBALS['display_remote_calendars'])) {
                    $key = array_search($calendarId, $GLOBALS['display_remote_calendars']);
                    unset($GLOBALS['display_remote_calendars'][$key]);
                } else {
                    $GLOBALS['display_remote_calendars'][] = $calendarId;
                }
            } elseif ((strncmp($calendarId, 'external_', 9) === 0 &&
                       $calendarId = substr($calendarId, 9)) ||
                      (strncmp($calendarId, 'tasklists_', 10) === 0 &&
                       $calendarId = substr($calendarId, 10))) {
                if (in_array($calendarId, $GLOBALS['display_external_calendars'])) {
                    $key = array_search($calendarId, $GLOBALS['display_external_calendars']);
                    unset($GLOBALS['display_external_calendars'][$key]);
                } else {
                    $GLOBALS['display_external_calendars'][] = $calendarId;
                }
            } elseif (strncmp($calendarId, 'holiday_', 8) === 0) {
                $calendarId = substr($calendarId, 8);
                if (in_array($calendarId, $GLOBALS['display_holidays'])) {
                    $key = array_search($calendarId, $GLOBALS['display_holidays']);
                    unset($GLOBALS['display_holidays'][$key]);
                } else {
                    $GLOBALS['display_holidays'][] = $calendarId;
                }
            } else {
                if (in_array($calendarId, $GLOBALS['display_calendars'])) {
                    $key = array_search($calendarId, $GLOBALS['display_calendars']);
                    unset($GLOBALS['display_calendars'][$key]);
                } else {
                    $GLOBALS['display_calendars'][] = $calendarId;
                }
            }

            $GLOBALS['prefs']->setValue('display_cals', serialize($GLOBALS['display_calendars']));
        }

        /* Make sure all shares exists now to save on checking later. */
        $GLOBALS['all_calendars'] = self::listCalendars();
        $calendar_keys = array_values($GLOBALS['display_calendars']);
        $GLOBALS['display_calendars'] = array();
        foreach ($calendar_keys as $id) {
            if (isset($GLOBALS['all_calendars'][$id])) {
                $GLOBALS['display_calendars'][] = $id;
            }
        }

        /* Make sure all the remote calendars still exist. */
        $_temp = $GLOBALS['display_remote_calendars'];
        $GLOBALS['display_remote_calendars'] = array();
        $GLOBALS['all_remote_calendars'] = @unserialize($GLOBALS['prefs']->getValue('remote_cals'));
        if (!is_array($GLOBALS['all_remote_calendars'])) {
            $GLOBALS['all_remote_calendars'] = array();
        }
        foreach ($GLOBALS['all_remote_calendars'] as $id) {
            if (in_array($id['url'], $_temp)) {
                $GLOBALS['display_remote_calendars'][] = $id['url'];
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
                    $GLOBALS['all_holidays'][] = $driver;
                }
                asort($GLOBALS['all_holidays']);
            }
        }
        $_temp = $GLOBALS['display_holidays'];
        $GLOBALS['display_holidays'] = array();
        foreach ($GLOBALS['all_holidays'] as $holiday) {
            if (in_array($holiday['id'], $_temp)) {
                $GLOBALS['display_holidays'][] = $holiday['id'];
            }
        }
        $GLOBALS['prefs']->setValue('holiday_drivers', serialize($GLOBALS['display_holidays']));

        /* Get a list of external calendars. */
        if (isset($_SESSION['all_external_calendars'])) {
            $GLOBALS['all_external_calendars'] = $_SESSION['all_external_calendars'];
        } else {
            $GLOBALS['all_external_calendars'] = array();
            $apis = array_unique($GLOBALS['registry']->listAPIs());
            foreach ($apis as $api) {
                if ($GLOBALS['registry']->hasMethod($api . '/listTimeObjects')) {
                    try {
                        $categories = $GLOBALS['registry']->call($api . '/listTimeObjectCategories');
                    } catch (Horde_Exception $e) {
                        Horde::logMessage($e->getMessage(), __FILE__, __LINE__, PEAR_LOG_DEBUG);
                        continue;
                    }

                    if (!count($categories)) {
                        continue;
                    }

                    $GLOBALS['all_external_calendars'][$api] = $categories;
                } elseif ($api == 'tasks' && $GLOBALS['registry']->hasMethod('tasks/listTasks')) {
                    $GLOBALS['all_external_calendars'][$api] = array('_listTasks' => $GLOBALS['registry']->get('name', $GLOBALS['registry']->hasInterface($api)));
                }
            }
            $_SESSION['all_external_calendars'] = $GLOBALS['all_external_calendars'];
        }

        /* Make sure all the external calendars still exist. */
        $_temp = $GLOBALS['display_external_calendars'];
        $GLOBALS['display_external_calendars'] = array();
        foreach ($GLOBALS['all_external_calendars'] as $api => $categories) {
            foreach ($categories as $id => $name) {
                $calendarId = $api . '/' . $id;
                if (in_array($calendarId, $_temp)) {
                    $GLOBALS['display_external_calendars'][] = $calendarId;
                } else {
                    /* Convert Kronolith 2 preferences.
                     * @todo: remove in Kronolith 3.1. */
                    list($oldid,) = explode('/', $id);
                    if (in_array($api . '/' . $oldid, $_temp)) {
                        $GLOBALS['display_external_calendars'][] = $calendarId;
                    }
                }
            }
        }
        $GLOBALS['prefs']->setValue('display_external_cals', serialize($GLOBALS['display_external_calendars']));

        /* If an authenticated user has no calendars visible and their
         * personal calendar doesn't exist, create it. */
        if (Horde_Auth::getAuth() &&
            !count($GLOBALS['display_calendars']) &&
            !$GLOBALS['kronolith_shares']->exists(Horde_Auth::getAuth())) {
            require_once 'Horde/Identity.php';
            $identity = &Identity::singleton();
            $name = $identity->getValue('fullname');
            if (trim($name) == '') {
                $name = Horde_Auth::getOriginalAuth();
            }
            $share = &$GLOBALS['kronolith_shares']->newShare(Horde_Auth::getAuth());
            $share->set('name', sprintf(_("%s's Calendar"), $name));
            $GLOBALS['kronolith_shares']->addShare($share);
            $GLOBALS['all_calendars'][Horde_Auth::getAuth()] = &$share;

            /* Make sure the personal calendar is displayed by default. */
            if (!in_array(Horde_Auth::getAuth(), $GLOBALS['display_calendars'])) {
                $GLOBALS['display_calendars'][] = Horde_Auth::getAuth();
            }

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
                $groups = &Group::singleton();
                $group_list = $groups->getGroupMemberships(Horde_Auth::getAuth());
                if (!is_a($group_list, 'PEAR_Error') && count($group_list)) {
                    $perm = $share->getPermission();
                    // Add the default perm, not added otherwise
                    $perm->addUserPermission(Horde_Auth::getAuth(), Horde_Perms::ALL, false);
                    foreach ($group_list as $group_id => $group_name) {
                        $perm->addGroupPermission($group_id, $perm_value, false);
                    }
                    $share->setPermission($perm);
                    $share->save();
                    $GLOBALS['notification']->push(sprintf(_("New calendar created and automatically shared with the following group(s): %s."), implode(', ', $group_list)), 'horde.success');
                }
            }

            $GLOBALS['prefs']->setValue('display_cals', serialize($GLOBALS['display_calendars']));
        }

    }

    /**
     * Returns the real name, if available, of a user.
     */
    public static function getUserName($uid)
    {
        static $names = array();

        if (!isset($names[$uid])) {
            require_once 'Horde/Identity.php';
            $ident = &Identity::singleton('none', $uid);
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
            require_once 'Horde/Identity.php';
            $ident = &Identity::singleton('none', $uid);
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
    public static function isUserEmail($uid, $email)
    {
        static $emails = array();

        if (!isset($emails[$uid])) {
            require_once 'Horde/Identity.php';
            $ident = &Identity::singleton('none', $uid);

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
    public static function buildStatusWidget($name, $current = self::STATUS_CONFIRMED,
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
     * Returns all calendars a user has access to, according to several
     * parameters/permission levels.
     *
     * @param boolean $owneronly   Only return calenders that this user owns?
     *                             Defaults to false.
     * @param integer $permission  The permission to filter calendars by.
     *
     * @return array  The calendar list.
     */
    public static function listCalendars($owneronly = false, $permission = Horde_Perms::SHOW)
    {
        $calendars = $GLOBALS['kronolith_shares']->listShares(Horde_Auth::getAuth(), $permission, $owneronly ? Horde_Auth::getAuth() : null, 0, 0, 'name');
        if (is_a($calendars, 'PEAR_Error')) {
            Horde::logMessage($calendars, __FILE__, __LINE__, PEAR_LOG_ERR);
            return array();
        }

        return $calendars;
    }

    /**
     * Returns the default calendar for the current user at the specified
     * permissions level.
     */
    public static function getDefaultCalendar($permission = Horde_Perms::SHOW)
    {
        global $prefs;

        $default_share = $prefs->getValue('default_share');
        $calendars = self::listCalendars(false, $permission);

        if (isset($calendars[$default_share]) ||
            $prefs->isLocked('default_share')) {
            return $default_share;
        } elseif (isset($GLOBALS['all_calendars'][Horde_Auth::getAuth()]) &&
                  $GLOBALS['all_calendars'][Horde_Auth::getAuth()]->hasPermission(Horde_Auth::getAuth(), $permission)) {
            return Horde_Auth::getAuth();
        } elseif (count($calendars)) {
            return key($calendars);
        }

        return false;
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
            $feed_url = 'feed/' . $calendar;
        } else {
            $feed_url = Horde_Util::addParameter('feed/index.php', 'c', $calendar);
        }
        return Horde::applicationUrl($feed_url, true, -1);
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
        $imple = Horde_Ajax_Imple::factory(array('kronolith', 'Embed'),
                                           array('container' => 'kronolithCal',
                                                 'view' => 'month',
                                                 'calendar' => $calendar));


        $url = $imple->getUrl();

        $html = '<div id="kronolithCal"></div><script src="' . $url
            . '?imple=Embed/container=kronolithCal/view=month/calendar='
            . $calendar . '" type="text/javascript"></script>';

        return $html;
    }

    /**
     * Returns a comma separated list of attendees and resources
     *
     * @return string  Attendee/Resource list.
     */
    public static function attendeeList()
    {
        $attendees = array();

        /* Attendees */
        if (isset($_SESSION['kronolith']['attendees']) ||
            is_array($_SESSION['kronolith']['attendees'])) {

            $attendees = array();
            foreach ($_SESSION['kronolith']['attendees'] as $email => $attendee) {
                $attendees[] = empty($attendee['name']) ? $email : Horde_Mime_Address::trimAddress($attendee['name'] . (strpos($email, '@') === false ? '' : ' <' . $email . '>'));
            }

        }

        /* Resources */
        if (isset($_SESSION['kronolith']['resources']) ||
            is_array($_SESSION['kronolith']['resources'])) {

            foreach ($_SESSION['kronolith']['resources'] as $resource) {
                $attendees[] = $resource['name'];
            }
        }

        return implode(', ', $attendees);
    }

    /**
     * Sends out iTip event notifications to all attendees of a specific
     * event. Can be used to send event invitations, event updates as well as
     * event cancellations.
     *
     * @param Kronolith_Event $event      The event in question.
     * @param Notification $notification  A notification object used to show
     *                                    result status.
     * @param integer $action             The type of notification to send.
     *                                    One of the Kronolith::ITIP_* values.
     * @param Horde_Date $instance        If cancelling a single instance of a
     *                                    recurring event, the date of this
     *                                    intance.
     */
    public static function sendITipNotifications(&$event, &$notification,
                                                 $action, $instance = null)
    {
        global $conf;

        $attendees = $event->getAttendees();
        if (!$attendees) {
            return;
        }

        require_once 'Horde/Identity.php';
        $ident = &Identity::singleton('none', $event->getCreatorId());

        $myemail = $ident->getValue('from_addr');
        if (!$myemail) {
            $notification->push(sprintf(_("You do not have an email address configured in your Personal Information Options. You must set one %shere%s before event notifications can be sent."), Horde::link(Horde_Util::addParameter(Horde::getServiceLink('options', 'kronolith'), array('app' => 'horde', 'group' => 'identities'))), '</a>'), 'horde.error', array('content.raw'));
            return;
        }

        $myemail = explode('@', $myemail);
        $from = Horde_Mime_Address::writeAddress($myemail[0], isset($myemail[1]) ? $myemail[1] : '', $ident->getValue('fullname'));

        $share = &$GLOBALS['kronolith_shares']->getShare($event->getCalendar());

        foreach ($attendees as $email => $status) {
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
                $subject = sprintf(_("Cancelled: %s"), $event->getTitle());
                break;

            case self::ITIP_REQUEST:
            default:
                if ($status['response'] == self::RESPONSE_NONE) {
                    /* Invitation. */
                    $method = 'REQUEST';
                    $filename = 'event-invitation.ics';
                    $subject = $event->getTitle();
                } else {
                    /* Update. */
                    $method = 'ADD';
                    $filename = 'event-update.ics';
                    $subject = sprintf(_("Updated: %s."), $event->getTitle());
                }
                break;
            }

            $message = $subject . ' (' .
                sprintf(_("on %s at %s"), $event->start->strftime('%x'), $event->start->strftime('%X')) .
                ")\n\n";

            if ($event->getLocation() != '') {
                $message .= sprintf(_("Location: %s"), $event->getLocation()) . "\n\n";
            }

            if ($event->getAttendees()) {
                $attendee_list = array();
                foreach ($event->getAttendees() as $mail => $attendee) {
                    $attendee_list[] = empty($attendee['name']) ? $mail : Horde_Mime_Address::trimAddress($attendee['name'] . (strpos($mail, '@') === false ? '' : ' <' . $mail . '>'));
                }
                $message .= sprintf(_("Attendees: %s"), implode(', ', $attendee_list)) . "\n\n";
            }

            if ($event->getDescription() != '') {
                $message .= _("The following is a more detailed description of the event:") . "\n\n" . $event->getDescription() . "\n\n";
            }
            $message .= _("Attached is an iCalendar file with more information about the event. If your mail client supports iTip requests you can use this file to easily update your local copy of the event.");

            if ($action == self::ITIP_REQUEST) {
                $attend_link = Horde_Util::addParameter(Horde::applicationUrl('attend.php', true, -1), array('c' => $event->getCalendar(), 'e' => $event->getId(), 'u' => $email), null, false);
                $message .= "\n\n" . sprintf(_("If your email client doesn't support iTip requests you can use one of the following links to accept or decline the event.\n\nTo accept the event:\n%s\n\nTo accept the event tentatively:\n%s\n\nTo decline the event:\n%s\n"), Horde_Util::addParameter($attend_link, 'a', 'accept', false), Horde_Util::addParameter($attend_link, 'a', 'tentative', false), Horde_Util::addParameter($attend_link, 'a', 'decline', false));
            }

            /* Build the iCalendar data */
            $iCal = new Horde_iCalendar();
            $iCal->setAttribute('METHOD', $method);
            $iCal->setAttribute('X-WR-CALNAME', Horde_String::convertCharset($share->get('name'), Horde_Nls::getCharset(), 'utf-8'));
            $vevent = $event->toiCalendar($iCal);
            if ($action == self::ITIP_CANCEL && !empty($instance)) {
                $vevent->setAttribute('RECURRENCE-ID', $instance, array('VALUE' => 'DATE'));
            }
            $iCal->addComponent($vevent);

            /* text/calendar part */
            $ics = new Horde_Mime_Part();
            $ics->setType('text/calendar');
            $ics->setContents($iCal->exportvCalendar());
            $ics->setName($filename);
            $ics->setContentTypeParameter('METHOD', $method);
            $ics->setCharset(Horde_Nls::getCharset());

            $recipient = empty($status['name']) ? $email : Horde_Mime_Address::trimAddress($status['name'] . ' <' . $email . '>');
            $mail = new Horde_Mime_Mail(array('subject' => $subject,
                                              'body' => $message,
                                              'to' => $recipient,
                                              'from' => $from,
                                              'charset' => Horde_Nls::getCharset()));
            $mail->addHeader('User-Agent', 'Kronolith ' . $GLOBALS['registry']->getVersion());
            $mail->addMimePart($ics);

            try {
                $mail->send(Horde::getMailerConfig());
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
     */
    public static function sendNotification(&$event, $action)
    {
        global $conf;

        if (!in_array($action, array('add', 'edit', 'delete'))) {
            return PEAR::raiseError('Unknown event action: ' . $action);
        }

        require_once 'Horde/Group.php';
        require_once 'Horde/Identity.php';

        $groups = &Group::singleton();
        $calendar = $event->getCalendar();
        $recipients = array();
        $share = &$GLOBALS['kronolith_shares']->getShare($calendar);
        if (is_a($share, 'PEAR_Error')) {
            return $share;
        }

        $identity = &Identity::singleton();
        $from = $identity->getDefaultFromAddress(true);

        $owner = $share->get('owner');
        $recipients[$owner] = self::_notificationPref($owner, 'owner');

        foreach ($share->listUsers(Horde_Perms::READ) as $user) {
            if (!isset($recipients[$user])) {
                $recipients[$user] = self::_notificationPref($user, 'read', $calendar);
            }
        }

        foreach ($share->listGroups(Horde_Perms::READ) as $group) {
            $group = $groups->getGroupById($group);
            if (is_a($group, 'PEAR_Error')) {
                continue;
            }
            $group_users = $group->listAllUsers();
            if (is_a($group_users, 'PEAR_Error')) {
                Horde::logMessage($group_users, __FILE__, __LINE__, PEAR_LOG_ERR);
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
            $identity = &Identity::singleton('none', $user);
            $email = $identity->getValue('from_addr');
            if (strpos($email, '@') === false) {
                continue;
            }
            list($mailbox, $host) = explode('@', $email);
            if (!isset($addresses[$vals['lang']][$vals['tf']][$vals['df']])) {
                $addresses[$vals['lang']][$vals['tf']][$vals['df']] = array();
            }
            $addresses[$vals['lang']][$vals['tf']][$vals['df']][] = Horde_Mime_Address::writeAddress($mailbox, $host, $identity->getValue('fullname'));
        }

        if (!$addresses) {
            return;
        }

        foreach ($addresses as $lang => $twentyFour) {
            Horde_Nls::setLang($lang);

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
                        . "\n\n" . $event->getDescription();

                    $mime_mail = new Horde_Mime_Mail(array('subject' => $subject . ' ' . $event->title,
                                                           'to' => implode(',', $df_recipients),
                                                           'from' => $from,
                                                           'charset' => Horde_Nls::getCharset()));
                    $mail->addHeader('User-Agent', 'Kronolith ' . $GLOBALS['registry']->getVersion());
                    $mime_mail->setBody($message, Horde_Nls::getCharset(), true);
                    Horde::logMessage(sprintf('Sending event notifications for %s to %s', $event->title, implode(', ', $df_recipients)), __FILE__, __LINE__, PEAR_LOG_DEBUG);
                    try {
                        $mime_mail->send(Horde::getMailerConfig(), false, false);
                    } catch (Horde_Mime_Exception $e) {}
                }
            }
        }

        return true;
    }

    /**
     * Check for resource declines and push notice to stack if found.
     *
     * @param Kronolith_Event #
     */
    public static function notifyOfResoruceRejection($event)
    {
        $declined = array();
        $accepted = array();
        foreach ($event->getResources() as $id => $resource) {
            if ($resource['response'] == Kronolith::RESPONSE_DECLINED) {
                $r = Kronolith::getDriver('Resource')->getResource($id);
                $declined[] = $r->get('name');
            } elseif ($resource['response'] == Kronolith::RESPONSE_ACCEPTED) {
                $r = Kronolith::getDriver('Resource')->getResource($id);
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
    public static function _notificationPref($user, $mode, $calendar = null)
    {
        $prefs = &Prefs::singleton($GLOBALS['conf']['prefs']['driver'],
                                   'kronolith', $user, '', null,
                                   false);
        $prefs->retrieve();
        $vals = array('lang' => $prefs->getValue('language'),
                      'tf' => $prefs->getValue('twentyFour'),
                      'df' => $prefs->getValue('date_format'));

        if ($prefs->getValue('event_notification_exclude_self') &&
            $user == Horde_Auth::getAuth()) {
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
     * Returns the specified permission for the current user.
     *
     * @param string $permission  A permission, currently only 'max_events'.
     *
     * @return mixed  The value of the specified permission.
     */
    public static function hasPermission($permission)
    {
        global $perms;

        if (!$perms->exists('kronolith:' . $permission)) {
            return true;
        }

        $allowed = $perms->getPermissions('kronolith:' . $permission);
        if (is_array($allowed)) {
            switch ($permission) {
            case 'max_events':
                $allowed = max($allowed);
                break;
            }
        }

        return $allowed;
    }

    /**
     * @param string $tabname
     */
    public static function tabs($tabname = null)
    {
        $date = self::currentDate();
        $date_stamp = $date->dateString();

        $tabs = new Horde_Ui_Tabs('view', Horde_Variables::getDefaultVariables());
        $tabs->preserve('date', $date_stamp);

        $tabs->addTab(_("Day"), Horde::applicationUrl('day.php'),
                      array('tabname' => 'day', 'id' => 'tabday', 'onclick' => 'return ShowView(\'Day\', \'' . $date_stamp . '\');'));
        $tabs->addTab(_("Work Week"), Horde::applicationUrl('workweek.php'),
                      array('tabname' => 'workweek', 'id' => 'tabworkweek', 'onclick' => 'return ShowView(\'WorkWeek\', \'' . $date_stamp . '\');'));
        $tabs->addTab(_("Week"), Horde::applicationUrl('week.php'),
                      array('tabname' => 'week', 'id' => 'tabweek', 'onclick' => 'return ShowView(\'Week\', \'' . $date_stamp . '\');'));
        $tabs->addTab(_("Month"), Horde::applicationUrl('month.php'),
                      array('tabname' => 'month', 'id' => 'tabmonth', 'onclick' => 'return ShowView(\'Month\', \'' . $date_stamp . '\');'));
        $tabs->addTab(_("Year"), Horde::applicationUrl('year.php'),
                      array('tabname' => 'year', 'id' => 'tabyear', 'onclick' => 'return ShowView(\'Year\', \'' . $date_stamp . '\');'));

        if ($tabname === null) {
            $tabname = basename($_SERVER['PHP_SELF']) == 'index.php' ? $GLOBALS['prefs']->getValue('defaultview') : str_replace('.php', '', basename($_SERVER['PHP_SELF']));
        }
        echo $tabs->render($tabname);
    }

    /**
     * @param string $tabname
     * @param Kronolith_Event $event
     */
    public static function eventTabs($tabname, $event)
    {
        if (!$event->isInitialized()) {
            return;
        }

        $tabs = new Horde_Ui_Tabs('event', Horde_Variables::getDefaultVariables());

        $date = self::currentDate();
        $tabs->preserve('datetime', $date->dateString());

        $tabs->addTab(
            htmlspecialchars($event->getTitle()),
            $event->getViewUrl(),
            array('tabname' => 'Event',
                  'id' => 'tabEvent',
                  'onclick' => 'return ShowTab(\'Event\');'));
        if ((!$event->isPrivate() ||
             $event->getCreatorId() == Horde_Auth::getAuth()) &&
            $event->hasPermission(Horde_Perms::EDIT)) {
            $tabs->addTab(
                $event->isRemote() ? _("Save As New") : _("_Edit"),
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
     *                           instance, or a PEAR_Error on error.
     */
    public static function getDriver($driver = null, $calendar = null)
    {
        if (empty($driver)) {
            $driver = Horde_String::ucfirst($GLOBALS['conf']['calendar']['driver']);
        }

        if (!isset(self::$_instances[$driver])) {
            $params = array();
            switch ($driver) {
            case 'Sql':
            case 'Resource':
                $params = Horde::getDriverConfig('calendar', 'sql');
                break;

            case 'Kolab':
                $params = Horde::getDriverConfig('calendar', 'kolab');
                break;

            case 'Ical':
                /* Check for HTTP proxy configuration */
                if (!empty($GLOBALS['conf']['http']['proxy']['proxy_host'])) {
                    $params['proxy'] = $GLOBALS['conf']['http']['proxy'];
                }
                $params = self::getRemoteParams($calendar);
                break;

            case 'Horde':
                $params['registry'] = $GLOBALS['registry'];
                break;

            case 'Holidays':
                if (empty($GLOBALS['conf']['holidays']['enable'])) {
                    return PEAR::raiseError(_("Holidays are disabled"));
                }
                $params['language'] = $GLOBALS['language'];
                break;
            }

            self::$_instances[$driver] = Kronolith_Driver::factory($driver, $params);
        }

        if (!is_null($calendar)) {
            self::$_instances[$driver]->open($calendar);
        }

        return self::$_instances[$driver];
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
                $key = Horde_Auth::getCredential('password');
                if ($key && $user) {
                    $user = Horde_Secret::read($key, base64_decode($user));
                    $password = Horde_Secret::read($key, base64_decode($password));
                }
                if (!empty($user)) {
                    return array('user' => $user, 'password' => $password);
                }
            }
        }

        return array();
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
            if (Horde_Util::getFormData('calendar') == '**remote') {
                $event = self::getDriver('Ical', Horde_Util::getFormData('remoteCal'))
                    ->getEvent(Horde_Util::getFormData('eventID'));
            } elseif (strncmp(Horde_Util::getFormData('calendar'), 'resource_', 9) === 0) {
                $event = self::getDriver('Resource', Horde_Util::getFormData('calendar'))->getEvent(Horde_Util::getFormData('eventID'));
            } elseif ($uid = Horde_Util::getFormData('uid')) {
                $event = self::getDriver()->getByUID($uid);
            } else {
                $event = self::getDriver(null, Horde_Util::getFormData('calendar'))
                    ->getEvent(Horde_Util::getFormData('eventID'));
            }
            if (!is_a($event, 'PEAR_Error') &&
                !$event->hasPermission(Horde_Perms::READ)) {
                $event = PEAR::raiseError(_("Permission Denied"));
            }

            return new Kronolith_View_Event($event);

        case 'EditEvent':
            if (Horde_Util::getFormData('calendar') == '**remote') {
                $event = self::getDriver('Ical', Horde_Util::getFormData('remoteCal'))
                    ->getEvent(Horde_Util::getFormData('eventID'));
            } elseif (strncmp(Horde_Util::getFormData('calendar'), 'resource_', 9) === 0) {
                $event = self::getDriver('Resource', Horde_Util::getFormData('calendar'))->getEvent(Horde_Util::getFormData('eventID'));
            } else {
                $event = self::getDriver(null, Horde_Util::getFormData('calendar'))
                    ->getEvent(Horde_Util::getFormData('eventID'));
            }
            if (!is_a($event, 'PEAR_Error') &&
                !$event->hasPermission(Horde_Perms::EDIT)) {
                $event = PEAR::raiseError(_("Permission Denied"));
            }

            return new Kronolith_View_EditEvent($event);

        case 'DeleteEvent':
            if (strncmp(Horde_Util::getFormData('calendar'), 'resource_', 9) === 0) {
                $event = self::getDriver('Resource', Horde_Util::getFormData('calendar'))->getEvent(Horde_Util::getFormData('eventID'));
            } else {
                $event = self::getDriver(null, Horde_Util::getFormData('calendar'))
                    ->getEvent(Horde_Util::getFormData('eventID'));
            }
            if (!is_a($event, 'PEAR_Error') &&
                !$event->hasPermission(Horde_Perms::DELETE)) {
                $event = PEAR::raiseError(_("Permission Denied"));
            }

            return new Kronolith_View_DeleteEvent($event);

        case 'ExportEvent':
            if (Horde_Util::getFormData('calendar') == '**remote') {
                $event = self::getDriver('Ical', Horde_Util::getFormData('remoteCal'))
                    ->getEvent(Horde_Util::getFormData('eventID'));
            } elseif ($uid = Horde_Util::getFormData('uid')) {
                $event = self::getDriver()->getByUID($uid);
            } else {
                $event = self::getDriver(null, Horde_Util::getFormData('calendar'))
                    ->getEvent(Horde_Util::getFormData('eventID'));
            }
            if (!is_a($event, 'PEAR_Error') &&
                !$event->hasPermission(Horde_Perms::READ)) {
                $event = PEAR::raiseError(_("Permission Denied"));
            }

            return new Kronolith_View_ExportEvent($event);
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
        $color = is_array($calendar) ? @$calendar['color'] : $calendar->get('color');
        return empty($color) ? '#dddddd' : $color;
    }

    /**
     * Returns the foreground color for a calendar.
     *
     * @param array|Horde_Share_Object $calendar  A calendar share or a hash
     *                                            from a remote calender
     *                                            definition.
     * @return string  A HTML color code.
     */
    public static function foregroundColor($calendar)
    {
        return Horde_Image::brightness(self::backgroundColor($calendar)) < 128 ? '#fff' : '#000';
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
     * Builds Kronolith's list of menu items.
     */
    public static function getMenu()
    {
        global $conf, $registry, $browser, $prefs;

        /* Check here for guest calendars so that we don't get multiple
         * messages after redirects, etc. */
        if (!Horde_Auth::getAuth() && !count($GLOBALS['all_calendars'])) {
            $GLOBALS['notification']->push(_("No calendars are available to guests."));
        }

        $menu = new Horde_Menu();

        $menu->add(Horde::applicationUrl($prefs->getValue('defaultview') . '.php'), _("_Today"), 'today.png', null, null, null, '__noselection');
        if (self::getDefaultCalendar(Horde_Perms::EDIT) &&
            (!empty($conf['hooks']['permsdenied']) ||
             self::hasPermission('max_events') === true ||
             self::hasPermission('max_events') > self::countEvents())) {
            $menu->add(Horde_Util::addParameter(Horde::applicationUrl('new.php'), 'url', Horde::selfUrl(true, false, true)), _("_New Event"), 'new.png');
        }
        if ($browser->hasFeature('dom')) {
            Horde::addScriptFile('goto.js', 'kronolith', array('direct' => false));
            $menu->add('#', _("_Goto"), 'goto.png', null, '', 'openKGoto(kronolithDate, event); return false;');
        }
        $menu->add(Horde::applicationUrl('search.php'), _("_Search"), 'search.png', $registry->getImageDir('horde'));

        /* Import/Export. */
        if ($conf['menu']['import_export']) {
            $menu->add(Horde::applicationUrl('data.php'), _("_Import/Export"), 'data.png', $registry->getImageDir('horde'));
        }

        return $menu;
    }

    /**
     * Used with usort() to sort events based on their start times.
     */
    public static function _sortEventStartTime($a, $b)
    {
        $diff = $a->start->compareDateTime($b->start);
        if ($diff == 0) {
            return strcoll($a->title, $b->title);
        } else {
            return $diff;
        }
    }

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
     * @return mixed  Kronolith_Resource or Horde_Share_Object
     */
    static public function getInternalCalendar($target)
    {
        if (Kronolith_Resource::isResourceCalendar($target)) {
            $driver = self::getDriver('Resource');
            $id = $driver->getResourceIdByCalendar($target);
            return $driver->getResource($id);
        } else {
            return $GLOBALS['kronolith_shares']->getShare($target);
        }
    }

}
