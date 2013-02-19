<?php
/**
 * Copyright 2002-2013 Horde LLC (http://www.horde.org/)
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
$vars = $injector->getInstance('Horde_Variables');

$reload = false;
switch ($vars->get('actionID', 'edit')) {
case 'edit':
    try {
        $shareid = $vars->cid;
        if (!$shareid) {
            throw new Horde_Exception_NotFound();
        }
        $share = $shares->getShareById($shareid);
        $perm = $share->getPermission();
    } catch (Horde_Exception_NotFound $e) {
        if (($category = $vars->share) !== null) {
            try {
                $share = $shares->getShare($category);
                $perm = $share->getPermission();
            } catch (Horde_Share_Exception $e) {
                $notification->push($e->getMessage(), 'horde.error');
            }
        }
    }

    if (!$registry->getAuth() ||
        (isset($share) &&
         !$registry->isAdmin() &&
         ($registry->getAuth() != $share->get('owner')))) {
        throw new Horde_Exception('Permission denied.');
    }
    break;

case 'editform':
    try {
        $share = $shares->getShareById($vars->cid);
    } catch (Horde_Share_Exception $e) {
        $notification->push(_("Attempt to edit a non-existent share."), 'horde.error');
    }

    if (empty($share)) {
        break;
    }

    if (!$registry->getAuth() ||
        (!$registry->isAdmin() &&
         ($registry->getAuth() != $share->get('owner')))) {
        throw new Horde_Exception('Permission denied.');
    }

    try {
        $errors = Kronolith::readPermsForm($share);
        if ($errors) {
            foreach ($errors as $error) {
                $notification->push($error, 'horde.error');
            }
        } elseif ($vars->save_and_finish) {
            echo Horde::wrapInlineScript(array('window.close();'));
            exit;
        }
        $notification->push(sprintf(_("Updated \"%s\"."), $share->get('name')), 'horde.success');
    } catch (Exception $e) {
        $notification->push($e, 'horde.error');
    }
    $perm = $share->getPermission();

    break;
}

$title = ($share instanceof Horde_Share_Object)
    ? sprintf(_("Edit permissions for \"%s\""), $share->get('name'))
    : _("Edit permissions");

$userList = array();
if ($auth->hasCapability('list') &&
    ($conf['auth']['list_users'] == 'list' ||
     $conf['auth']['list_users'] == 'both')) {
    try {
        $userList = $auth->listUsers();
        sort($userList);
    } catch (Horde_Auth_Exception $e) {
        Horde::logMessage($e, 'ERR');
    }
}

try {
    $groupList = $groups->listAll(empty($conf['share']['any_group'])
                                  ? $registry->getAuth()
                                  : null);
    asort($groupList);
} catch (Horde_Group_Exception $e) {
    Horde::logMessage($e, 'NOTICE');
    $groupList = array();
}

$page_output->topbar = $page_output->sidebar = false;

$page_output->header(array(
    'title' => $title
));
$notification->notify(array('listeners' => 'status'));
require KRONOLITH_TEMPLATES . '/perms/perms.inc';
$page_output->footer();
