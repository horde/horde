<?php
/**
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Meilof Veeningen <meilof@gmail.com>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
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

    $q_title = Horde_Util::getFormData('title');
    if (strlen($q_title)) {
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
            if (!empty($GLOBALS['conf']['share']['hidden']) &&
                !in_array($cal->getName(), $GLOBALS['display_calendars'])) {
                continue;
            }
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
    Horde::addInlineScript(array(
        '$("pattern_title").focus()'
    ), 'dom');
} else {
    Horde::addInlineScript(array(
        '$("title").focus()'
    ), 'dom');
    Horde_Core_Ui_JsCalendar::init();
    Horde::addScriptFile('edit.js', 'kronolith');
}

$menu = Horde::menu();
$title = _("Search");
Horde::addScriptFile('tooltips.js', 'horde');
require KRONOLITH_TEMPLATES . '/common-header.inc';
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
require $registry->get('templates', 'horde') . '/common-footer.inc';
