<?php
/**
 * Nag data script.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

function _cleanupData()
{
    $GLOBALS['import_step'] = 1;
    return Horde_Data::IMPORT_FILE;
}

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('nag');

if (!$conf['menu']['import_export']) {
    require NAG_BASE . '/index.php';
    exit;
}

/* Importable file types. */
$file_types = array('csv' => _("CSV"),
                    'vtodo' => _("iCalendar (vTodo)"));

/* Templates for the different import steps. */
$templates = array(
    Horde_Data::IMPORT_CSV => array($registry->get('templates', 'horde') . '/data/csvinfo.inc'),
    Horde_Data::IMPORT_MAPPED => array($registry->get('templates', 'horde') . '/data/csvmap.inc'),
    Horde_Data::IMPORT_DATETIME => array($registry->get('templates', 'horde') . '/data/datemap.inc')
);

$perms = $GLOBALS['injector']->getInstance('Horde_Perms');
if ($perms->hasAppPermission('max_tasks') !== true &&
    $perms->hasAppPermission('max_tasks') <= Nag::countTasks()) {
    try {
        $message = Horde::callHook('perms_denied', array('nag:max_tasks'));
    } catch (Horde_Exception_HookNotSet $e) {
        $message = htmlspecialchars(sprintf(_("You are not allowed to create more than %d tasks."), $perms->hasAppPermission('max_tasks')));
    }
    $notification->push($message, 'horde.warning', array('content.raw'));
    $templates[Horde_Data::IMPORT_FILE] = array(NAG_TEMPLATES . '/data/export.inc');
} else {
    $templates[Horde_Data::IMPORT_FILE] = array(NAG_TEMPLATES . '/data/import.inc', NAG_TEMPLATES . '/data/export.inc');
}

/* Field/clear name mapping. */
$app_fields = array(
    'name'           => _("Name"),
    'desc'           => _("Description"),
    'category'       => _("Category"),
    'assignee'       => _("Assignee"),
    'due'            => _("Due By"),
    'alarm'          => _("Alarm"),
    'start'          => _("Start"),
    'priority'       => _("Priority"),
    'private'        => _("Private Task"),
    'estimate'       => _("Estimated Time"),
    'completed'      => _("Completion Status"),
    'completed_date' => _("Completion Date"),
    'uid'            => _("Unique ID")
);

/* Date/time fields. */
$time_fields = array('due' => 'datetime');

/* Initial values. */
$param = array('time_fields' => $time_fields,
               'file_types'  => $file_types);
$import_format = Horde_Util::getFormData('import_format', '');
$import_step   = Horde_Util::getFormData('import_step', 0) + 1;
$next_step     = Horde_Data::IMPORT_FILE;
$actionID      = Horde_Util::getFormData('actionID');
$error         = false;

/* Loop through the action handlers. */
switch ($actionID) {
case 'export':
    $exportID = Horde_Util::getFormData('exportID');
    $tasklists = Horde_Util::getFormData('exportList', $display_tasklists);
    if (!is_array($tasklists)) {
        $tasklists = array($tasklists);
    }

    /* Get the full, sorted task list. */
    $tasks = Nag::listTasks(null, null, null, $tasklists,
                            Horde_Util::getFormData('exportTasks'));
    if (is_a($tasks, 'PEAR_Error')) {
        $notification->push($tasks);
        $error = true;
    } elseif (!$tasks->hasTasks()) {
        $notification->push(_("There were no tasks to export."), 'horde.message');
        $error = true;
    } else {
        $tasks->reset();
        switch ($exportID) {
        case Horde_Data::EXPORT_CSV:
            $data = array();
            while ($task = $tasks->each()) {
                $task = $task->toHash();
                unset($task['task_id']);
                $task['desc'] = str_replace(',', '', $task['desc']);
                unset($task['tasklist_id']);
                unset($task['parent']);
                unset($task['view_link']);
                unset($task['complete_link']);
                unset($task['edit_link']);
                unset($task['delete_link']);
                $data[] = $task;
            }
            $injector->getInstance('Horde_Core_Factory_Data')->create('Csv', array('cleanup' => '_cleanupData'))->exportFile(_("tasks.csv"), $data, true);
            exit;

        case Horde_Data::EXPORT_ICALENDAR:
            $iCal = new Horde_iCalendar();
            $iCal->setAttribute(
                'PRODID',
                '-//The Horde Project//Nag ' . $registry->getVersion() . '//EN');
            while ($task = $tasks->each()) {
                $iCal->addComponent($task->toiCalendar($iCal));
            }
            $data = $iCal->exportvCalendar();
            $browser->downloadHeaders(_("tasks.ics"), 'text/calendar', false, strlen($data));
            echo $data;
            exit;
        }
    }
    break;

case Horde_Data::IMPORT_FILE:
    $_SESSION['import_data']['target'] = Horde_Util::getFormData('tasklist_target');
    break;
}

if (!$error && $import_format) {
    $data = null;
    try {
        $data = $injector->getInstance('Horde_Core_Factory_Data')->create($import_format, array('cleanup' => '_cleanupData'));
        $next_step = $data->nextStep($actionID, $param);
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
    /* Create a category manager. */
    $cManager = new Horde_Prefs_CategoryManager();
    $categories = $cManager->get();

    /* Create a Nag storage instance. */
    $storage = Nag_Driver::singleton($_SESSION['import_data']['target']);
    $max_tasks = $perms->hasAppPermission('max_tasks');
    $num_tasks = Nag::countTasks();
    $result = null;
    foreach ($next_step as $row) {
        if ($max_tasks !== true && $num_tasks >= $max_tasks) {
            try {
                $message = Horde::callHook('perms_denied', array('nag:max_tasks'));
            } catch (Horde_Exception_HookNotSet $e) {
                $message = htmlspecialchars(sprintf(_("You are not allowed to create more than %d tasks."), $perms->hasAppPermission('max_tasks')));
            }
            $notification->push($message, 'horde.error', array('content.raw'));
            break;
        }

        if (!is_array($row)) {
            if (!is_a($row, 'Horde_iCalendar_vtodo')) {
                continue;
            }
            $task = new Nag_Task();
            $task->fromiCalendar($row);
            $row = $task->toHash();
            foreach ($app_fields as $field => $null) {
                if (!isset($row[$field])) {
                    $row[$field] = '';
                }
            }
        }

        $result = $storage->add($row['name'], $row['desc'], $row['start'],
                                $row['due'], $row['priority'],
                                $row['estimate'], $row['completed'],
                                $row['category'], $row['alarm'], $row['uid'],
                                isset($row['parent']) ? $row['parent'] : '',
                                $row['private'], $GLOBALS['registry']->getAuth(),
                                $row['assignee']);
        if (is_a($result, 'PEAR_Error')) {
            break;
        }

        if (!empty($row['category']) &&
            !in_array($row['category'], $categories)) {
            $cManager->add($row['category']);
            $categories[] = $row['category'];
        }

        $num_tasks++;
    }


    if (!count($next_step)) {
        $notification->push(sprintf(_("The %s file didn't contain any tasks."),
                                    $file_types[$_SESSION['import_data']['format']]), 'horde.error');
    } elseif (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("There was an error importing the data: %s"),
                                    $result->getMessage()), 'horde.error');
    } else {
        $notification->push(sprintf(_("%s successfully imported"),
                                    $file_types[$_SESSION['import_data']['format']]), 'horde.success');
    }
    $next_step = $data->cleanup();
}

$import_tasklists = $export_tasklists = array();
if ($GLOBALS['registry']->getAuth()) {
    $tasklists = Nag::listTasklists(false, Horde_Perms::EDIT);
    foreach ($tasklists as $id => $tasklist) {
        if ($tasklist->get('owner') != $GLOBALS['registry']->getAuth() &&
            !empty($GLOBALS['conf']['share']['hidden']) &&
            !in_array($tasklist->getName(), $GLOBALS['display_tasklists'])) {
            continue;
        }
        $import_tasklists[$id] = $tasklist;
    }
}
$tasklists = Nag::listTasklists(false, Horde_Perms::READ);
foreach ($tasklists as $id => $tasklist) {
    if ($tasklist->get('owner') != $GLOBALS['registry']->getAuth() &&
        !empty($GLOBALS['conf']['share']['hidden']) &&
        !in_array($tasklist->getName(), $GLOBALS['display_tasklists'])) {
        continue;
    }
    $export_tasklists[$id] = $tasklist;
}

$title = _("Import/Export Tasks");
require NAG_TEMPLATES . '/common-header.inc';
echo Horde::menu();
Nag::status();

foreach ($templates[$next_step] as $template) {
    require $template;
    echo '<br />';
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
