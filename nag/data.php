<?php
/**
 * Nag data script.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
$app_ob = Horde_Registry::appInit('nag');

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

$perms = $GLOBALS['injector']->getInstance('Horde_Core_Perms');
if ($perms->hasAppPermission('max_tasks') !== true &&
    $perms->hasAppPermission('max_tasks') <= Nag::countTasks()) {
    Horde::permissionDeniedError(
        'nag',
        'max_tasks',
        sprintf(_("You are not allowed to create more than %d tasks."), $perms->hasAppPermission('max_tasks'))
    );
    $templates[Horde_Data::IMPORT_FILE] = array(NAG_TEMPLATES . '/data/export.inc');
} else {
    $templates[Horde_Data::IMPORT_FILE] = array(NAG_TEMPLATES . '/data/import.inc', NAG_TEMPLATES . '/data/export.inc');
}

/* Field/clear name mapping. */
$app_fields = array(
    'name'           => _("Name"),
    'desc'           => _("Description"),
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
$storage = $injector->getInstance('Horde_Core_Data_Storage');

/* Loop through the action handlers. */
switch ($actionID) {
case Horde_Data::IMPORT_FILE:
    $storage->set('target', Horde_Util::getFormData('tasklist_target'));
    break;
}

if ($import_format) {
    $data = null;
    try {
        $data = $injector->getInstance('Horde_Core_Factory_Data')->create($import_format, array('cleanup' => array($app_ob, 'cleanupData')));
        $next_step = $data->nextStep($actionID, $param);
    } catch (Horde_Exception $e) {
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

    /* Create a Nag storage instance. */
    $storage = $GLOBALS['injector']->getInstance('Nag_Factory_Driver')->create($storage->set('target'));
    $max_tasks = $perms->hasAppPermission('max_tasks');
    $num_tasks = Nag::countTasks();
    $result = null;
    foreach ($next_step as $row) {
        if ($max_tasks !== true && $num_tasks >= $max_tasks) {
            Horde::permissionDeniedError(
                'nag',
                'max_tasks',
                sprintf(_("You are not allowed to create more than %d tasks."), $perms->hasAppPermission('max_tasks'))
            );
            break;
        }

        if (!is_array($row)) {
            if (!is_a($row, 'Horde_Icalendar_Vtodo')) {
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

        try {
            $storage->add($row);
        } catch (Nag_Exception $e) {
            $haveError = true;
            $notification->push(sprintf(_("There was an error importing the data: %s"),
                                        $result->getMessage()), 'horde.error');
            break;
        }

        $num_tasks++;
    }


    if (!count($next_step)) {
        $notification->push(sprintf(_("The %s file didn't contain any tasks."),
                                    $file_types[$storage->get('format')]), 'horde.error');
    } elseif (empty($haveError)) {
        $notification->push(sprintf(_("%s successfully imported"),
                                    $file_types[$storage->get('format')]), 'horde.success');
    }
    $next_step = $data->cleanup();
}

$import_tasklists = $export_tasklists = array();
if ($GLOBALS['registry']->getAuth()) {
    $import_tasklists = Nag::listTasklists(false, Horde_Perms::EDIT);
}
$export_tasklists = Nag::listTasklists(false, Horde_Perms::READ);

$page_output->header(array(
    'title' => _("Import/Export Tasks")
));
Nag::status();
foreach ($templates[$next_step] as $template) {
    require $template;
    echo '<br />';
}
$page_output->footer();
