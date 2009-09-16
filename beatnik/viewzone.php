<?php
/**
 * $Horde: beatnik/viewzone.php,v 1.21 2009/07/03 10:05:29 duck Exp $
 *
 * Copyright 2005-2007 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

define('BEATNIK_BASE', dirname(__FILE__));
require_once BEATNIK_BASE . '/lib/base.php';
require_once BEATNIK_BASE . '/lib/Beatnik.php';

$zonedata = $beatnik_driver->getRecords($_SESSION['beatnik']['curdomain']['zonename']);
if (is_a($zonedata, 'PEAR_Error')) {
    $notification->push($zonedata, 'horde.error');
    header('Location:' . Horde::applicationUrl('listzones.php'));
    exit;
}

$title = $_SESSION['beatnik']['curdomain']['zonename'];
Horde::addScriptFile('stripe.js', 'horde', true);
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

$img_dir = $registry->getImageDir('horde');
$delete = Horde_Util::addParameter(Horde::applicationUrl('delete.php'), 'curdomain', $_SESSION['beatnik']['curdomain']['zonename']);
$edit = Horde_Util::addParameter(Horde::applicationUrl('editrec.php'), 'curdomain', $_SESSION['beatnik']['curdomain']['zonename']);
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
