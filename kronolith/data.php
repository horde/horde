<?php
/**
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */

function _cleanupData()
{
    $GLOBALS['import_step'] = 1;
    return Horde_Data::IMPORT_FILE;
}

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

if (Kronolith::showAjaxView() && !(Horde_Util::getPost('import_ajax')) &&
    (!Horde_Util::getFormData('actionID') == 'export')) {

    Horde::url('', true)->redirect();
}

if (!$conf['menu']['import_export']) {
    require KRONOLITH_BASE . '/index.php';
    exit;
}

/* Importable file types. */
$file_types = array('csv'       => _("Comma separated values"),
                    'icalendar' => _("vCalendar/iCalendar"));

/* Templates for the different import steps. */
$templates = array(
    Horde_Data::IMPORT_CSV => array($registry->get('templates', 'horde') . '/data/csvinfo.inc'),
    Horde_Data::IMPORT_MAPPED => array($registry->get('templates', 'horde') . '/data/csvmap.inc'),
    Horde_Data::IMPORT_DATETIME => array($registry->get('templates', 'horde') . '/data/datemap.inc')
);

$perms = $GLOBALS['injector']->getInstance('Horde_Perms');
if ($perms->hasAppPermission('max_events') !== true &&
    $perms->hasAppPermission('max_events') <= Kronolith::countEvents()) {
    try {
        $message = Horde::callHook('perms_denied', array('kronolith:max_events'));
    } catch (Horde_Exception_HookNotSet $e) {
        $message = htmlspecialchars(sprintf(_("You are not allowed to create more than %d events."), $perms->hasAppPermission('max_events')));
    }
    $notification->push($message, 'horde.warning', array('content.raw'));
    $templates[Horde_Data::IMPORT_FILE] = array(KRONOLITH_TEMPLATES . '/data/export.inc');
} else {
    $templates[Horde_Data::IMPORT_FILE] = array(KRONOLITH_TEMPLATES . '/data/import.inc', KRONOLITH_TEMPLATES . '/data/export.inc');
}

/* Initial values. */
$import_step   = Horde_Util::getFormData('import_step', 0) + 1;
$actionID      = Horde_Util::getFormData('actionID');
$next_step     = Horde_Data::IMPORT_FILE;
$app_fields    = array('title' => _("Title"),
                       'start_date' => _("Start Date"),
                       'start_time' => _("Start Time"),
                       'end_date' => _("End Date"),
                       'end_time' => _("End Time"),
                       'alarm' => _("Alarm Span (minutes)"),
                       'alarm_date' => _("Alarm Date"),
                       'alarm_time' => _("Alarm Time"),
                       'description' => _("Description"),
                       'location' => _("Location"),
                       'recur_type' => _("Recurrence Type"),
                       'recur_end_date' => _("Recurrence End Date"),
                       'recur_interval' => _("Recurrence Interval"),
                       'recur_data' => _("Recurrence Data"));
$time_fields   = array('start_date'     => 'date',
                       'start_time'     => 'time',
                       'end_date'       => 'date',
                       'end_time'       => 'time',
                       'recur_end_date' => 'date');
$param         = array('time_fields' => $time_fields,
                       'file_types'  => $file_types);
$import_format = Horde_Util::getFormData('import_format', '');
$error         = false;

/* Loop through the action handlers. */
switch ($actionID) {
case 'export':
    if (Horde_Util::getFormData('all_events')) {
        $start = null;
        $end = null;
    } else {
        $start = new Horde_Date(Horde_Util::getFormData('start_year'),
                                Horde_Util::getFormData('start_month'),
                                Horde_Util::getFormData('start_day'));
        $end = new Horde_Date(Horde_Util::getFormData('end_year'),
                              Horde_Util::getFormData('end_month'),
                              Horde_Util::getFormData('end_day'));
    }

    $events = array();
    $calendars = Horde_Util::getFormData('exportCal', $display_calendars);
    if (!is_array($calendars)) {
        $calendars = array($calendars);
    }

    $exportID = Horde_Util::getFormData('exportID');
    foreach ($calendars as $calendar) {
        list($type, $cal) = explode('_', $calendar, 2);
        $kronolith_driver = Kronolith::getDriver($type, $cal);
        $events[$calendar] = $kronolith_driver->listEvents(
            $start, // Start date
            $end,   // End date
            false,  // Only return recurrences once
            false,  // Don't limit to alarms
            false,  // Don't cache json
            false,  // Don't add events to all days
            ($exportID == Horde_Data::EXPORT_ICALENDAR) ? true : false);  // Don't return exception events
    }

    if (!$events) {
        $notification->push(_("There were no events to export."), 'horde.message');
        $error = true;
        break;
    }

    switch ($exportID) {
    case Horde_Data::EXPORT_CSV:
        $data = array();
        foreach ($events as $calevents) {
            foreach ($calevents as $dayevents) {
                foreach ($dayevents as $event) {
                    $row = array();
                    $row['title'] = $event->getTitle();
                    $row['location'] = $event->location;
                    $row['description'] = $event->description;
                    $row['private'] = (int)$event->private;
                    $row['start_date'] = sprintf('%d-%02d-%02d', $event->start->year, $event->start->month, $event->start->mday);
                    $row['start_time'] = sprintf('%02d:%02d:%02d', $event->start->hour, $event->start->min, $event->start->sec);
                    $row['end_date'] = sprintf('%d-%02d-%02d', $event->end->year, $event->end->month, $event->end->mday);
                    $row['end_time'] = sprintf('%02d:%02d:%02d', $event->end->hour, $event->end->min, $event->end->sec);
                    $row['alarm'] = $event->alarm;
                    if ($event->recurs()) {
                        $row['recur_type'] = $event->recurrence->getRecurType();
                        $row['recur_end_date'] = sprintf('%d-%02d-%02d',
                                                         $event->recurrence->recurEnd->year,
                                                         $event->recurrence->recurEnd->month,
                                                         $event->recurrence->recurEnd->mday);
                        $row['recur_interval'] = $event->recurrence->getRecurInterval();
                        $row['recur_data'] = $event->recurrence->recurData;
                    } else {
                        $row['recur_type'] = null;
                        $row['recur_end_date'] = null;
                        $row['recur_interval'] = null;
                        $row['recur_data'] = null;
                    }
                    $tags = $event->getTags();
                    $row['tags'] = $tags ? implode(', ', $tags) : '';
                    $data[] = $row;
                }
            }
        }

        $injector->getInstance('Horde_Core_Factory_Data')->create('Csv', array('cleanup' => '_cleanupData'))->exportFile(_("events.csv"), $data, true);
        exit;

    case Horde_Data::EXPORT_ICALENDAR:
        $iCal = new Horde_Icalendar();

        $calIds = array();
        foreach ($events as $calevents) {
            foreach ($calevents as $dayevents) {
                foreach ($dayevents as $event) {
                    $calIds[$event->calendar] = true;
                    $iCal->addComponent($event->toiCalendar($iCal));
                }
            }
        }

        $calNames = array();
        foreach (array_keys($calIds) as $calId) {
            $share = $kronolith_shares->getShare($calId);
            $calNames[] = $share->get('name');
        }

        $iCal->setAttribute('X-WR-CALNAME', implode(', ', $calNames));
        $data = $iCal->exportvCalendar();
        $browser->downloadHeaders(_("events.ics"), 'text/calendar', false, strlen($data));
        echo $data;
        exit;
    }
    break;

case Horde_Data::IMPORT_FILE:
    $_SESSION['import_data']['import_cal'] = Horde_Util::getFormData('importCal');
    $_SESSION['import_data']['purge'] = Horde_Util::getFormData('purge');
    break;
}

if (!$error && $import_format) {
    $data = null;
    try {
        $data = $injector->getInstance('Horde_Core_Factory_Data')->create($import_format, array('cleanup' => '_cleanupData'));

        if ($actionID == Horde_Data::IMPORT_FILE) {
            $cleanup = true;
            try {
                if (!in_array($_SESSION['import_data']['import_cal'], Kronolith::listCalendars(Horde_Perms::EDIT))) {
                    $notification->push(_("You do not have permission to add events to the selected calendar."), 'horde.error');
                } else {
                    $next_step = $data->nextStep($actionID, $param);
                    $cleanup = false;
                }
            } catch (Exception $e) {
                $notification->push(_("You have specified an invalid calendar."), 'horde.error');
            }

            if ($cleanup) {
                $next_step = $data->cleanup();
            }
        } else {
            $next_step = $data->nextStep($actionID, $param);
        }
    } catch (Horde_Data_Exception $e) {
        if ($data) {
            $notification->push($e, 'horde.error');
            $next_step = $data->cleanup();
        } else {
            $notification->push(_("This file format is not supported."), 'horde.error');
            $next_step = Horde_Data::IMPORT_FILE;
        }
    }
}

/* We have a final result set. */
if (is_array($next_step)) {
    $events = array();
    $error = false;
    $max_events = $perms->hasAppPermission('max_events');
    if ($max_events !== true) {
        $num_events = Kronolith::countEvents();
    }
    list($type, $calendar) = explode('_', $_SESSION['import_data']['import_cal'], 2);
    $kronolith_driver = Kronolith::getDriver($type, $calendar);

    if (!count($next_step)) {
        $notification->push(sprintf(_("The %s file didn't contain any events."),
                                    $file_types[$_SESSION['import_data']['format']]), 'horde.error');
        $error = true;
    } else {
        /* Purge old calendar if requested. */
        if ($_SESSION['import_data']['purge']) {
            try {
                $kronolith_driver->delete($calendar);
                $notification->push(_("Calendar successfully purged."), 'horde.success');
            } catch (Exception $e) {
                $notification->push(sprintf(_("The calendar could not be purged: %s"), $e->getMessage()), 'horde.error');
            }
        }
    }

    $recurrences = array();
    foreach ($next_step as $row) {
        if ($max_events !== true && $num_events >= $max_events) {
            try {
                $message = Horde::callHook('perms_denied', array('kronolith:max_events'));
            } catch (Horde_Exception_HookNotSet $e) {
                $message = htmlspecialchars(sprintf(_("You are not allowed to create more than %d events."), $perms->hasAppPermission('max_events')));
            }
            $notification->push($message, 'horde.error', array('content.raw'));
            break;
        }
        try {
            $event = $kronolith_driver->getEvent();
        } catch (Exception $e) {
            $msg = _("Can't create a new event.")
                . ' ' . sprintf(_("This is what the server said: %s"), $e->getMessage());
            $notification->push($msg, 'horde.error');
            $error = true;
            break;
        }
        if ($row instanceof Horde_Icalendar_Vevent) {
            // RECURRENCE-ID entries must be imported after the original
            // recurring event is imported.
            try {
                $row->getAttribute('RECURRENCE-ID');
                $recurrences[] = $row;
                continue;
            } catch (Horde_Icalendar_Exception $e) {
                $event->fromiCalendar($row);
            }
        } elseif ($row instanceof Horde_Icalendar) {
            // Skip other iCalendar components for now.
            continue;
        } else {
            try {
                $event->fromHash($row);
            } catch (Exception $e) {
                $notification->push($e, 'horde.error');
                $error = true;
                break;
            }
        }

        try {
            $event->save();
        } catch (Exception $e) {
            $notification->push($e, 'horde.error');
            $error = true;
            break;
        }

        if ($max_events !== true) {
            $num_events++;
        }
    }

    // Any RECURRENCE-ID entries?
    foreach ($recurrences as $recurrence) {
        $event = $kronolith_driver->getEvent();
        $event->fromiCalendar($recurrence);
        try {
            $event->save();
        } catch (Exception $e) {
            $notification->push($e, 'horde.error');
            $error = true;
            break;
        }
    }

    if (!$error) {
        $notification->push(sprintf(_("%s file successfully imported"),
                                    $file_types[$_SESSION['import_data']['format']]), 'horde.success');
        if (Horde_Util::getFormData('import_ajax')) {
            Horde::addInlineScript('window.parent.KronolithCore.loadCalendar(\'' . $type . '\', \'' . $calendar . '\');');
        }
    }
    $next_step = $data->cleanup();
}

if (Horde_Util::getFormData('import_ajax')) {
    $stack = $notification->notify(array('listeners' => 'status', 'raw' => true));
    if ($stack) {
        Horde::addInlineScript('window.parent.KronolithCore.showNotifications(window.parent.$A(' . Horde_Serialize::serialize($stack, Horde_Serialize::JSON) . '));');
    }
    Horde::addInlineScript('window.parent.$(window.name).remove();');
    Horde::outputInlineScript();
    exit;
}

$import_calendars = $export_calendars = array();
if ($GLOBALS['registry']->getAuth()) {
    $import_calendars = Kronolith::listCalendars(Horde_Perms::EDIT, true);
}
$export_calendars = Kronolith::listCalendars(Horde_Perms::READ, true);

$title = _("Import/Export Calendar");
$menu = Horde::menu();
require KRONOLITH_TEMPLATES . '/common-header.inc';
echo $menu;
$notification->notify(array('listeners' => 'status'));

echo '<div id="page">';
foreach ($templates[$next_step] as $template) {
    require $template;
}
echo '</div>';

require $registry->get('templates', 'horde') . '/common-footer.inc';
