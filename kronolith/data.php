<?php
/**
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */

require_once __DIR__ . '/lib/Application.php';
$app_ob = Horde_Registry::appInit('kronolith');

if ((Kronolith::showAjaxView() && !(Horde_Util::getPost('import_ajax'))) ||
    (!$conf['menu']['import_export'])) {
    Horde::url('', true)->redirect();
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

$perms = $GLOBALS['injector']->getInstance('Horde_Core_Perms');
if ($perms->hasAppPermission('max_events') !== true &&
    $perms->hasAppPermission('max_events') <= Kronolith::countEvents()) {
    Horde::permissionDeniedError(
        'kronolith',
        'max_events',
        sprintf(_("You are not allowed to create more than %d events."), $perms->hasAppPermission('max_events'))
    );
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
$storage = $injector->getInstance('Horde_Core_Data_Storage');

switch ($actionID) {
case Horde_Data::IMPORT_FILE:
    $storage->get('import_cal', Horde_Util::getFormData('importCal'));
    $storage->get('purge', Horde_Util::getFormData('purge'));
    break;
}

if ($import_format) {
    $data = null;
    try {
        $data = $injector->getInstance('Horde_Core_Factory_Data')->create($import_format, array('cleanup' => array($app_ob, 'cleanupData')));

        if ($actionID == Horde_Data::IMPORT_FILE) {
            $cleanup = true;
            try {
                if (!in_array($storage->get('import_cal'), array_keys(Kronolith::listCalendars(Horde_Perms::EDIT)))) {
                    $notification->push(_("You have specified an invalid calendar or you do not have permission to add events to the selected calendar."), 'horde.error');
                } else {
                    $next_step = $data->nextStep($actionID, $param);
                    $cleanup = false;
                }
            } catch (Exception $e) {
                $notification->push($e, 'horde.error');
            }

            if ($cleanup) {
                $next_step = $data->cleanup();
            }
        } else {
            $next_step = $data->nextStep($actionID, $param);
        }
    } catch (Exception $e) {
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
    list($type, $calendar) = explode('_', $storage->get('import_cal'), 2);
    $kronolith_driver = Kronolith::getDriver($type, $calendar);

    if (!count($next_step)) {
        $notification->push(sprintf(_("The %s file didn't contain any events."),
                                    $file_types[$storage->get('format')]), 'horde.error');
        $error = true;
    } else {
        /* Purge old calendar if requested. */
        if ($storage->get('purge')) {
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
            Horde::permissionDeniedError(
                'kronolith',
                'max_events',
                sprintf(_("You are not allowed to create more than %d events."), $perms->hasAppPermission('max_events'))
            );
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
                                    $file_types[$storage->get('format')]), 'horde.success');
        if (Horde_Util::getFormData('import_ajax')) {
            $page_output->includeScriptFiles();
            $page_output->addInlineScript('(function(window){window.KronolithCore.loading--;if(!window.KronolithCore.loading)window.$(\'kronolithLoading\').hide();window.KronolithCore.loadCalendar(\'' . $type . '\', \'' . $calendar . '\');})(window.parent)');
        }
    }
    $next_step = $data->cleanup();
}

if (Horde_Util::getFormData('import_ajax')) {
    new Horde_Core_Ajax_Response_Notifications();
    $page_output->addInlineScript('window.parent.$(window.name).remove();');
    $page_output->outputInlineScript();
    exit;
}

$import_calendars = $export_calendars = array();
if ($GLOBALS['registry']->getAuth()) {
    $import_calendars = Kronolith::listCalendars(Horde_Perms::EDIT, true);
}
$export_calendars = Kronolith::listCalendars(Horde_Perms::READ, true);

$menu = Kronolith::menu();
$page_output->header(array(
    'title' => _("Import/Export Calendar")
));
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
echo $menu;
$notification->notify(array('listeners' => 'status'));

echo '<div id="page">';
foreach ($templates[$next_step] as $template) {
    require $template;
}
echo '</div>';

$page_output->footer();
