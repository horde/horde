<?php
/**
 * Copyright 2005-2007 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once __DIR__ . '/lib/Application.php';
$beatnik = Horde_Registry::appInit('beatnik');

try {
    $zonedata = $beatnik->driver->getRecords($_SESSION['beatnik']['curdomain']['zonename']);
} catch (Exception $e) {
    $notification->push($e, 'horde.error');
    Horde::url('listzones.php')->redirect();
}

$page_output->addScriptFile('stripe.js', 'horde');
Beatnik::notifyCommits();
$page_output->header(array(
    'title' => $_SESSION['beatnik']['curdomain']['zonename']
));
require BEATNIK_TEMPLATES . '/menu.inc';

// Get a list of all the fields for all record typess we'll be processing
$fields = array();
foreach ($zonedata as $type => $data) {
    $fields = array_merge($fields, Beatnik::getRecFields($type));
}

// Remove fields that should not be shown
foreach ($fields as $field_id => $field) {
    if ($field['type'] == 'hidden' ||
        ($field['infoset'] != 'basic' && !$_SESSION['beatnik']['expertmode'])) {
        unset($field[$field_id]);
    }
}

$delete = Horde::url('delete.php')->add('curdomain', $_SESSION['beatnik']['curdomain']['zonename']);
$edit = Horde::url('editrec.php')->add('curdomain', $_SESSION['beatnik']['curdomain']['zonename']);
$autogen = Horde::url('autogenerate.php')->add('curdomain', $_SESSION['beatnik']['curdomain']['zonename']);
$rectypes = Beatnik::getRecTypes();

require BEATNIK_TEMPLATES . '/view/header.inc';
foreach ($rectypes as $type => $typedescr) {
    if (!isset($zonedata[$type])) {
        continue;
    }
    require BEATNIK_TEMPLATES . '/view/record.inc';
}
require BEATNIK_TEMPLATES . '/view/footer.inc';

$page_output->footer();
