<?php
/**
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Karsten Fourmont <karsten@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
new Horde_Application();

$backend = SyncML_Backend::factory('Horde');

$actionID = Horde_Util::getFormData('actionID');
switch ($actionID) {
case 'deleteanchor':
    $deviceid = Horde_Util::getFormData('deviceid');
    $db = Horde_Util::getFormData('db');
    $result = $backend->removeAnchor(Horde_Auth::getAuth(), $deviceid, $db);
    if (is_a($result, 'PEAR_Error')) {
        $notification->push(_("Error deleting synchronization session:")
                            . ' ' . $result->getMessage(),
                            'horde.error');
    } else {
        $notification->push(sprintf(_("Deleted synchronization session for device \"%s\" and database \"%s\"."),
                                    $deviceid, $db),
                            'horde.success');
    }
    break;

case 'deleteall':
    $result = $backend->removeAnchor(Horde_Auth::getAuth());
    if (is_a($result, 'PEAR_Error')) {
        $notification->push(_("Error deleting synchronization sessions:")
                            . ' ' . $result->getMessage(),
                            'horde.error');
    } else {
        $notification->push(_("All synchronization sessions deleted."),
                            'horde.success');
    }
    break;
}

$devices = $backend->getUserAnchors(Horde_Auth::getAuth());

/* Show the header. */
$chunk = Horde_Util::nonInputVar('chunk');
Horde_Prefs_UI::generateHeader('horde', null, 'syncml', $chunk);

require HORDE_TEMPLATES . '/syncml/syncml.inc';
if (!$chunk) {
    require HORDE_TEMPLATES . '/common-footer.inc';
}
