<?php
/**
 * Copyright 2005-2007 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/lib/Application.php';
$beatnik = Horde_Registry::appInit('beatnik');

try {
    $zonedata = $beatnik->driver->getRecords($_SESSION['beatnik']['curdomain']['zonename']);
} catch (Exception $e) {
    $notification->push($e, 'horde.error');
    Horde::url('listzones.php')->redirect();
}

$title = $_SESSION['beatnik']['curdomain']['zonename'];
Horde::addScriptFile('stripe.js', 'horde');
require BEATNIK_TEMPLATES . '/common-header.inc';
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

$img_dir = Horde_Themes::img(null, 'horde');
$delete = Horde_Util::addParameter(Horde::url('delete.php'), 'curdomain', $_SESSION['beatnik']['curdomain']['zonename']);
$edit = Horde_Util::addParameter(Horde::url('editrec.php'), 'curdomain', $_SESSION['beatnik']['curdomain']['zonename']);
$rectypes = Beatnik::getRecTypes();

require BEATNIK_TEMPLATES . '/view/header.inc';
foreach ($rectypes as $type => $typedescr) {
    if (!isset($zonedata[$type])) {
        continue;
    }
    require BEATNIK_TEMPLATES . '/view/record.inc';
}
require BEATNIK_TEMPLATES . '/view/footer.inc';


require $registry->get('templates', 'horde') . '/common-footer.inc';
