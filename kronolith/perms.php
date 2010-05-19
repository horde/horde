<?php
/**
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

require_once 'Horde/Group.php';

// Exit if the user shouldn't be able to change share permissions.
if (!empty($conf['share']['no_sharing'])) {
    throw new Horde_Exception('Permission denied.');
}

$shares = $GLOBALS['injector']->getInstance('Horde_Share')->getScope();
$groups = Group::singleton();
$auth = Horde_Auth::singleton($conf['auth']['driver']);

$reload = false;
$actionID = Horde_Util::getFormData('actionID', 'edit');
switch ($actionID) {
case 'edit':
    try {
        $share = $shares->getShareById(Horde_Util::getFormData('cid'));
        $perm = $share->getPermission();
    } catch (Horde_Share_Exception $e) {
        if (($category = Horde_Util::getFormData('share')) !== null) {
            try {
                $share = $shares->getShare($category);
                $perm = $share->getPermission();
            } catch (Exception $e) {
                $notification->push($e, 'horde.error');
            }
            $perm = $share->getPermission();
        }
    }
    
    if (!Horde_Auth::getAuth() ||
        (isset($share) &&
         !Horde_Auth::isAdmin() &&
         Horde_Auth::getAuth() != $share->get('owner'))) {
        exit('permission denied');
    }
    break;

case 'editform':
    try {
        $share = $shares->getShareById(Horde_Util::getFormData('cid'));
        if (!Horde_Auth::getAuth() ||
            (!Horde_Auth::isAdmin() &&
             Horde_Auth::getAuth() != $share->get('owner'))) {
            exit('permission denied');
        }
        try {
            $errors = Kronolith::readPermsForm($share);
            if ($errors) {
                foreach ($errors as $error) {
                    $notification->push($error, 'horde.error');
                }
            } elseif (Horde_Util::getFormData('save_and_finish')) {
                echo Horde::wrapInlineScript(array('window.close();'));
                exit;
            }
            $notification->push(sprintf(_("Updated \"%s\"."), $share->get('name')), 'horde.success');
        } catch (Exception $e) {
            $notification->push($e, 'horde.error');
        }
        $perm = $share->getPermission();
    } catch (Horde_Share_Exception $e) {
        $notification->push(_("Attempt to edit a non-existent share."), 'horde.error');
    }

    break;
}

if (empty($share)) {
    $title = _("Edit Permissions");
} else {
    $title = sprintf(_("Edit Permissions for %s"), $share->get('name'));
}

if ($auth->hasCapability('list') &&
    ($conf['auth']['list_users'] == 'list' ||
     $conf['auth']['list_users'] == 'both')) {
    try {
        $userList = $auth->listUsers();
    } catch (Exception $e) {
        Horde::logMessage($e, 'ERR');
        $userList = array();
    }
    sort($userList);
} else {
    $userList = array();
}

if (!empty($conf['share']['any_group'])) {
    $groupList = $groups->listGroups();
} else {
    $groupList = $groups->getGroupMemberships(Horde_Auth::getAuth(), true);
}
if ($groupList instanceof PEAR_Error) {
    Horde::logMessage($groupList, 'NOTICE');
    $groupList = array();
}
asort($groupList);

require KRONOLITH_TEMPLATES . '/common-header.inc';
$notification->notify(array('listeners' => 'status'));
require KRONOLITH_TEMPLATES . '/perms/perms.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
