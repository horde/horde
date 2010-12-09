<?php
/**
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Mnemo
 */

function _cleanupData()
{
    $GLOBALS['import_step'] = 1;
    return Horde_Data::IMPORT_FILE;
}

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('mnemo');

if (!$conf['menu']['import_export']) {
    require MNEMO_BASE . '/index.php';
    exit;
}

/* Importable file types. */
$file_types = array('csv' => _("CSV"),
                    'vnote' => _("vNote"));

/* Templates for the different import steps. */
$templates = array(
    Horde_Data::IMPORT_CSV => array($registry->get('templates', 'horde') . '/data/csvinfo.inc'),
    Horde_Data::IMPORT_MAPPED => array($registry->get('templates', 'horde') . '/data/csvmap.inc'),
);
if ($GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_notes') !== true &&
    $GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_notes') <= Mnemo::countMemos()) {
    Horde::permissionDeniedError(
        'mnemo',
        'max_notes',
        sprintf(_("You are not allowed to create more than %d notes."), $GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_notes'))
    );
    $templates[Horde_Data::IMPORT_FILE] = array(MNEMO_TEMPLATES . '/data/export.inc');
} else {
    $templates[Horde_Data::IMPORT_FILE] = array(MNEMO_TEMPLATES . '/data/import.inc', MNEMO_TEMPLATES . '/data/export.inc');
}

/* Field/clear name mapping. */
$app_fields = array('body' => _("Memo Text"),
                    'category' => _("Category"));

/* Initial values. */
$param = array('file_types'  => $file_types);
$import_format = Horde_Util::getFormData('import_format', '');
$import_step   = Horde_Util::getFormData('import_step', 0) + 1;
$next_step     = Horde_Data::IMPORT_FILE;
$actionID      = Horde_Util::getFormData('actionID');
$error         = false;

/* Loop through the action handlers. */
switch ($actionID) {
case 'export':
    $exportID = Horde_Util::getFormData('exportID');

    /* Create a Mnemo storage instance. */
    $storage = &Mnemo_Driver::singleton($GLOBALS['registry']->getAuth());
    $storage->retrieve();

    /* Get the full, sorted memo list. */
    $notes = Mnemo::listMemos();

    switch ($exportID) {
    case Horde_Data::EXPORT_CSV:
        if (count($notes) == 0) {
            $notification->push(_("There were no memos to export."), 'horde.message');
            $error = true;
        } else {
            $data = array();
            foreach ($notes as $note) {
                unset($note['memo_id']);
                unset($note['memolist_id']);
                unset($note['desc']);
                unset($note['uid']);
                $data[] = $note;
            }
            $injector->getInstance('Horde_Core_Factory_Data')->create('Csv', array('cleanup' => '_cleanupData'))->exportFile(_("notes.csv"), $data, true);
            exit;
        }
    }
    break;

case Horde_Data::IMPORT_FILE:
    $session->set('horde', 'import_data/target', Horde_Util::getFormData('notepad_target'));
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

    /* Create a Mnemo storage instance. */
    $storage = &Mnemo_Driver::singleton($session->get('horde', 'import_data/target'));
    $max_memos = $GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_notes');
    $num_memos = Mnemo::countMemos();
    foreach ($next_step as $row) {
        if ($max_memos !== true && $num_memos >= $max_memos) {
            Horde::permissionDeniedError(
                'mnemo',
                'max_notes',
                sprintf(_("You are not allowed to create more than %d notes."), $GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_notes'))
            );
            break;
        }

        /* Check if we need to convert from iCalendar data into an array. */
        if (is_a($row, 'Horde_Icalendar_vnote')) {
            $row = $storage->fromiCalendar($row);
        }

        foreach ($app_fields as $field => $null) {
            if (!isset($row[$field])) {
                $row[$field] = '';
            }
        }

        /* Default the category if there isn't one. */
        if (empty($row['category'])) {
            $row['category'] = '';
        }

        /* Parse out the first line as the description if necessary. */
        if (empty($row['desc'])) {
            $tmp = explode("\n", $row['body'], 2);
            $row['desc'] = array_shift($tmp);
        }

        $result = $storage->add($row['desc'], $row['body'], $row['category']);
        if (is_a($result, 'PEAR_Error')) {
            break;
        }
        $note = $storage->get($result);

        /* If we have created or modified dates for the note, set them
         * correctly in the history log. */
        if (!empty($row['created'])) {
            $history = $GLOBALS['injector']->getInstance('Horde_History');
            if (is_array($row['created'])) {
                $row['created'] = $row['created']['ts'];
            }
            $history->log('mnemo:' . $session->get('horde', 'import_data/target') . ':' . $note['uid'],
                          array('action' => 'add', 'ts' => $row['created']), true);
        }
        if (!empty($row['modified'])) {
            $history = $GLOBALS['injector']->getInstance('Horde_History');
            if (is_array($row['modified'])) {
                $row['modified'] = $row['modified']['ts'];
            }
            $history->log('mnemo:' . $session->get('horde', 'import_data/target') . ':' . $note['uid'],
                          array('action' => 'modify', 'ts' => $row['modified']), true);
        }

        if (!empty($row['category']) &&
            !in_array($row['category'], $categories)) {
            $cManager->add($row['category']);
            $categories[] = $row['category'];
        }

        $num_memos++;
    }

    if (!count($next_step)) {
        $notification->push(sprintf(_("The %s file didn't contain any notes."),
                                    $file_types[$session->get('horde', 'import_data/format')]), 'horde.error');
    } elseif (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("There was an error importing the data: %s"),
                                    $result->getMessage()), 'horde.error');
    } else {
        $notification->push(sprintf(_("%s file successfully imported"),
                                    $file_types[$session->get('horde', 'import_data/format')]), 'horde.success');
    }
    $next_step = $data->cleanup();
}

$title = _("Import/Export Notes");
require $registry->get('templates', 'horde') . '/common-header.inc';
echo Horde::menu();
$notification->notify();

if (isset($templates[$next_step])) {
    foreach ($templates[$next_step] as $template) {
        require $template;
    }
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
