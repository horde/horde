<?php
/**
 * Administrative management of ActiveSync devices.
 *
 * Copyright 1999-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde', array(
    'permission' => array('horde:administration:activesync')
));

try {
    $state = $injector->getInstance('Horde_ActiveSyncState');
} catch (Horde_Exception $e) {
    throw new Horde_Exception_PermissionDenied(_("ActiveSync not activated."));
}

$state->setLogger($injector->getInstance('Horde_Log_Logger'));

/** Check for any actions **/
if ($actionID = Horde_Util::getPost('actionID')) {
    $deviceID = Horde_Util::getPost('deviceID');

    $device_desc = explode(':', $deviceID);
    $deviceID = $device_desc[0];
    $user = $device_desc[1];

    switch ($actionID) {
    case 'wipe':
        $state->setDeviceRWStatus($deviceID, Horde_ActiveSync::RWSTATUS_PENDING);
        $GLOBALS['notification']->push(_("A device wipe has been requested. Device will be wiped on next syncronization attempt."), 'horde.success');
        break;

    case 'cancelwipe':
        $state->setDeviceRWStatus($deviceID, Horde_ActiveSync::RWSTATUS_OK);
        $GLOBALS['notification']->push(_("Device wipe successfully canceled."), 'horde.success');
        break;

    case 'delete':
        $state->removeState(array(
            'devId' => $deviceID,
            'user' => Horde_Util::getPost('uid'))
        );
        $GLOBALS['notification']->push(_("Device successfully removed."), 'horde.success');
        break;

    case 'reset':
        $state->resetAllPolicyKeys();
        $GLOBALS['notification']->push(_("All policy keys successfully reset."), 'horde.success');
        break;

    case 'block':
        $device = $state->loadDeviceInfo($deviceID);
        $device->blocked = true;
        $device->save(false);
        break;

    case 'unblock':
        $device = $state->loadDeviceInfo($deviceID);
        $device->blocked = false;
        $device->save(false);
        break;
    }
}

switch (Horde_Util::getPost('searchBy')) {
case 'username':
    $devices = $state->listDevices(Horde_Util::getPost('searchInput'));
    break;

default:
    $devices = $state->listDevices(null, array(Horde_Util::getPost('searchBy') => Horde_Util::getPost('searchInput')));
}

$view = new Horde_View(array(
    'templatePath' => array(HORDE_TEMPLATES . '/admin', HORDE_TEMPLATES . '/activesync')));
$view->addHelper('Tag');

$selfurl = Horde::selfUrl();
$view->reset = $selfurl->copy()->add('reset', 1);
$devs = array();
$js = array();
$collections = array();
foreach (array_values($devices) as $device) {
    $dev = $state->loadDeviceInfo($device['device_id'], $device['device_user']);
    $syncCache = new Horde_ActiveSync_SyncCache($state, $dev->id, $dev->user, $injector->getInstance('Horde_Log_Logger'));
    $dev->hbinterval = $syncCache->hbinterval
        ? $syncCache->hbinterval
        : ($syncCache->wait ? $syncCache->wait * 60 : _("Unavailable"));
    $js[$dev->id . ':' . $dev->user] = array(
        'id' => $dev->id,
        'user' => $dev->user
    );
    $devs[] = $dev;
}
$view->devices = $devs;
$view->isAdmin = true;
$page_output->header(array(
    'title' => _("ActiveSync Administration")
));

require HORDE_TEMPLATES . '/admin/menu.inc';
$page_output->addScriptFile('activesyncadmin.js', 'horde');
$page_output->addInlineJsVars(array(
    'HordeActiveSyncAdmin.devices' => $js
));
echo $view->render('activesync');
echo $page_output->footer();

