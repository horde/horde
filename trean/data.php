<?php
/**
 * Copyright 2001-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Trean
 */

require_once __DIR__ . '/lib/Application.php';
$app_ob = Horde_Registry::appInit('trean');

/* Importable file types. */
$file_types = array('json' => _("Firefox JSON"));

/* Templates for the different import steps. */
$templates = array(
    Horde_Data::IMPORT_FILE => array($registry->get('templates', 'trean') . '/data/import.inc')
);

/* Initial values. */
$actionID      = Horde_Util::getFormData('actionID');
$next_step     = Horde_Data::IMPORT_FILE;
$import_step   = Horde_Data::IMPORT_FILE;
$param = array('file_types' => $file_types);

$import_format = Horde_Util::getFormData('import_format', '');
$storage = $injector->getInstance('Horde_Core_Data_Storage');

if ($import_format) {
    $data = null;
    try {
        // @TODO: So far, only Firefox JSON
        $data = new Trean_Data_Json(
            $injector->getInstance('Horde_Core_Data_Storage'),
            array(
                'browser' => $injector->getInstance('Horde_Browser'),
                'cleanup' => array($app_ob, 'cleanupData')
            )
        );

        if ($actionID == Horde_Data::IMPORT_FILE) {
            $cleanup = true;
            try {
                $next_step = $data->nextStep($actionID, $param);
                $cleanup = false;
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
        $notification->push($e, 'horde.error');
        $next_step = $data->cleanup();
    }
}

/* We have a final result set. */
if (is_array($next_step)) {
    $events = array();
    $error = false;
    if (!count($next_step)) {
        $notification->push(_("The file didn't contain any bookmarks."), 'horde.error');
        $error = true;
    }

    foreach ($next_step as $row) {
        try {
            $trean_gateway->newBookmark($row, false);
        } catch (Exception $e) {
            $msg = _("Can't create a new bookmark.")
                . ' ' . sprintf(_("This is what the server said: %s"), $e->getMessage());
            $notification->push($msg, 'horde.error');
            $error = true;
            break;
        }
    }

    if (!$error) {
        $notification->push(_("Bookmarks successfully imported"), 'horde.success');
    }
    $next_step = $data->cleanup();
}

$page_output->header(array(
    'title' => _("Import Bookmarks")
));
$notification->notify(array('listeners' => 'status'));

foreach ($templates[$next_step] as $template) {
    require $template;
}

$page_output->footer();
