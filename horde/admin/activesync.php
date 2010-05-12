<?php
/**
 * Administrative management of activesync devices.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('admin' => true));

if (!empty($GLOBALS['conf']['activesync']['enabled'])) {
        $state_params = $GLOBALS['conf']['activesync']['state']['params'];
        $state_params['db'] = $GLOBALS['injector']->getInstance('Horde_Db_Adapter_Base');
        $stateMachine = new Horde_ActiveSync_State_History($state_params);
} else {
    throw new Horde_Exception_PermissionDenied(_("ActiveSync not activated."));
}

$devices = $stateMachine->listDevices();

$title = _("ActiveSync Device Administration");
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/admin/menu.inc';

$spacer = '&nbsp;&nbsp;&nbsp;&nbsp;';
$icondir = array('icondir' => Horde_Themes::img());
$base_node_params = $icondir + array('icon' => 'administration.png');
$device_node = $icondir + array('icon' => 'mobile.png');
$user_node = $icondir + array('icon' => 'user.png');

$users = array();
$tree = Horde_Tree::factory('admin_devices', 'Javascript');
$tree->setOption(array('alternate' => true));
$tree->setHeader(array(array('width' => '40%'),
                 array('width' => '10%', 'html' => 'last sync'),
                 array('html' => $spacer),
                 array('width' => '10%', 'html' => 'policy key'),
                 array('html' => $spacer),
                 array('width' => '10%', 'html' => 'remotewipe status'),
                 array('html' => $spacer),
                 array('width' => '15%' , 'html' => 'device id'),));
$tree->addNode('root', null, _("Registered User Devices"), 0, true, $base_node_params);
foreach ($devices as $device) {
    $node_params = array();
    if (array_search($device['device_user'], $users) === false) {
        $users[] = $device['device_user'];
        $tree->addNode($device['device_user'], 'root', $device['device_user'], 0, true, $user_node);
    }
    $stateMachine->loadDeviceInfo($device['device_id'], $device['device_user']);
    $ts = new Horde_Date($stateMachine->getLastSyncTimestamp($device['device_id']));
    $tree->addNode($device['device_id'],
                   $device['device_user'],
                   $device['device_type']. ' | ' . $device['device_agent'],
                   0,
                   true,
                   $device_node + $node_params,
                   array($ts->format('r'), $spacer, $device['device_policykey'], $spacer, $device['device_rwstatus'], $spacer, $device['device_id']));
}

echo '<h1 class="header">' . Horde::img('group.png') . ' ' . _("ActiveSync Devices") . '</h1>';
$tree->renderTree();

require HORDE_TEMPLATES . '/common-footer.inc';
