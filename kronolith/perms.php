<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Jan Schneider <jan@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

if (Kronolith::showAjaxView()) {
    Horde::url('', true)->redirect();
}

// Exit if the user shouldn't be able to change share permissions.
if (!empty($conf['share']['no_sharing'])) {
    throw new Horde_Exception('Permission denied.');
}

$shares = $injector->getInstance('Horde_Core_Factory_Share')->create();
$groups = $injector->getInstance('Horde_Group');
$auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();

$reload = false;
$actionID = Horde_Util::getFormData('actionID', 'edit');
switch ($actionID) {
case 'edit':
    if ($cid = Horde_Util::getFormData('cid') !== null) {
        try {
            $share = $shares->getShareById(Horde_Util::getFormData('cid', 0));
            $perm = $share->getPermission();
        } catch (Horde_Exception_NotFound $e) {
            $notification->push($e, 'horde.error');
        }
    } elseif (($category = Horde_Util::getFormData('share')) !== null) {
        try {
            $share = $shares->getShare($category);
            $perm = $share->getPermission();
        } catch (Exception $e) {
            $notification->push($e, 'horde.error');
        }
        $perm = $share->getPermission();
    } else {
        throw new Horde_Exception('No share identifier provided.');
    }

    if (!$GLOBALS['registry']->getAuth() ||
        (isset($share) &&
         !$registry->isAdmin() &&
         $GLOBALS['registry']->getAuth() != $share->get('owner'))) {
        exit('permission denied');
    }
    break;

case 'editform':
    try {
        $share = $shares->getShareById(Horde_Util::getFormData('cid'));
        if (!$GLOBALS['registry']->getAuth() ||
            (!$registry->isAdmin() &&
             $GLOBALS['registry']->getAuth() != $share->get('owner'))) {
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

$title = empty($share)
    ? _("Edit Permissions")
    : sprintf(_("Edit Permissions for %s"), $share->get('name'));

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

$groupList = array();
try {
    $groupList = $groups->listAll(empty($conf['share']['any_group'])
                                  ? $registry->getAuth()
                                  : null);
    asort($groupList);
} catch (Horde_Group_Exception $e) {
    Horde::logMessage($e, 'NOTICE');
}

$page_output->topbar = $page_output->sidebar = false;

$page_output->header(array(
    'title' => $title
));
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
$notification->notify(array('listeners' => 'status'));
require KRONOLITH_TEMPLATES . '/perms/perms.inc';
$page_output->footer();
