<?php
/**
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Meilof Veeningen <meilof@gmail.com>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

if (Kronolith::showAjaxView()) {
    Horde::url('', true)->redirect();
}

/* Get search parameters. */
$search_mode = Horde_Util::getFormData('search_mode', 'basic');
$search_calendar = explode('|', Horde_Util::getFormData('calendar', '|__any'), 2);
$events = null;

if ($search_mode == 'basic') {
    $desc = Horde_Util::getFormData('pattern_desc');
    $title = Horde_Util::getFormData('pattern_title');
    if (strlen($desc) || strlen($title)) {
        $event = Kronolith::getDriver()->getEvent();
        $event->description = $desc;
        $event->title = $title;
        $event->status = null;

        $time1 = $_SERVER['REQUEST_TIME'];
        $range = Horde_Util::getFormData('range');
        if ($range == '+') {
            $event->start = new Horde_Date($time1);
            $event->end = null;
        } elseif ($range == '-') {
            $event->start = null;
            $event->end = new Horde_Date($time1);
        } else {
            $time2 = $time1 + $range;
            $event->start = new Horde_Date(min($time1, $time2));
            $event->end = new Horde_Date(max($time1, $time2));
        }
        $events = Kronolith::search($event);
    }
} else {
    /* Make a new empty event object with default values. */
    $event = Kronolith::getDriver($search_calendar[0], $search_calendar[1])->getEvent();
    $event->title = $event->location = $event->status = $event->description = null;

    /* Set start on today, stop on tomorrow. */
    $event->start = new Horde_Date(mktime(0, 0, 0));
    $event->end = new Horde_Date($event->start);
    $event->end->mday++;

    /* We need to set the event to initialized, otherwise we will end up with
     * a default end date. */
    $event->initialized = true;

    if (Horde_Util::getFormData('actionID') == 'search_calendar') {
        $event->readForm();
        if (Horde_Util::getFormData('status') == Kronolith::STATUS_NONE) {
            $event->status = null;
        }

        $events = Kronolith::search($event, $search_calendar[1] == '__any' ? null : $search_calendar[0] . '|' . $search_calendar[1]);
    }

    $optgroup = $GLOBALS['browser']->hasFeature('optgroup');
    $current_user = $GLOBALS['registry']->getAuth();
    $calendars = array();
    foreach (Kronolith::listInternalCalendars(false, Horde_Perms::READ) as $id => $cal) {
        if ($cal->get('owner') && $cal->get('owner') == $current_user) {
            $calendars[_("My Calendars:")]['|' . $id] = $cal->get('name');
        } else {
            $calendars[_("Shared Calendars:")]['|' . $id] = $cal->get('name');
        }
    }
    foreach ($GLOBALS['all_external_calendars'] as $id => $cal) {
        $app = $GLOBALS['registry']->get('name', $GLOBALS['registry']->hasInterface($cal->api()));
        if (!empty($GLOBALS['conf']['share']['hidden']) &&
            !in_array($id, $GLOBALS['display_external_calendars'])) {
            continue;
        }
        $calendars[$app . ':']['Horde|external_' . $id] = $cal->name();
    }
    foreach ($GLOBALS['all_remote_calendars'] as $id => $cal) {
        $calendars[_("Remote Calendars:")]['Ical|' . $id] = $cal->name();
    }
    foreach ($GLOBALS['all_holidays'] as $id => $holiday) {
        $calendars[_("Holidays:")]['Holidays|' . $id] = $holiday->name();
    }
}

if ($search_mode == 'basic') {
    $page_output->addInlineScript(array(
        '$("pattern_title").focus()'
    ), true);
} else {
    $page_output->addInlineScript(array(
        '$("title").focus()'
    ), true);
    Horde_Core_Ui_JsCalendar::init(array('full_weekdays' => true));
    $page_output->addScriptFile('edit.js');
}

$menu = Kronolith::menu();
$page_output->addScriptFile('tooltips.js', 'horde');
$page_output->header(array(
    'title' => _("Search")
));
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
echo $menu;
$notification->notify(array('listeners' => 'status'));

echo '<div id="page">';

if ($search_mode == 'basic') {
    require KRONOLITH_TEMPLATES . '/search/search.inc';
} else {
    require KRONOLITH_TEMPLATES . '/search/search_advanced.inc';
}

/* Display search results. */
if (!is_null($events)) {
    if (count($events)) {
        require KRONOLITH_TEMPLATES . '/search/header.inc';
        require KRONOLITH_TEMPLATES . '/search/event_headers.inc';
        foreach ($events as $day => $day_events) {
            foreach ($day_events as $event) {
                require KRONOLITH_TEMPLATES . '/search/event_summaries.inc';
            }
        }
        require KRONOLITH_TEMPLATES . '/search/event_footers.inc';
    } else {
        require KRONOLITH_TEMPLATES . '/search/empty.inc';
    }
}

echo '</div>';
require KRONOLITH_TEMPLATES . '/panel.inc';
$page_output->footer();
