<?php
/**
 * Copyright 2002-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

require_once __DIR__ . '/../../lib/Application.php';
Horde_Registry::appInit('horde');

$vars = $injector->getInstance('Horde_Variables');

// Exit if the user shouldn't be able to change share permissions.
if (!empty($conf['share']['no_sharing'])) {
    throw new Horde_Exception('Permission denied.');
}

$app = $vars->app;
$shares = $injector->getInstance('Horde_Core_Factory_Share')->create($app);
$groups = $injector->getInstance('Horde_Group');
$auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();
$help = $registry->hasMethod('shareHelp', $app)
    ? $registry->callByPackage($app, 'shareHelp')
    : null;

$form = null;
$reload = false;
switch ($vars->get('actionID', 'edit')) {
case 'edit':
    try {
        $shareid = $vars->cid;
        if (!$shareid) {
            throw new Horde_Exception_NotFound();
        }
        $share = $shares->getShareById($shareid);
        $form = 'edit.inc';
        $perm = $share->getPermission();
    } catch (Horde_Exception_NotFound $e) {
        if (($category = $vars->share) !== null) {
            try {
                $share = $shares->getShare($category);
                $form = 'edit.inc';
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

    $perm = $share->getPermission();

    // Process owner and owner permissions.
    $old_owner = $share->get('owner');
    $new_owner_backend = $vars->get('owner_select', $vars->get('owner_input', $old_owner));
    $new_owner = $registry->convertUsername($new_owner_backend, true);
    if ($old_owner !== $new_owner && !empty($new_owner)) {
        if ($old_owner != $registry->getAuth() && !$registry->isAdmin()) {
            $notification->push(_("Only the owner or system administrator may change ownership or owner permissions for a share"), 'horde.error');
        } elseif ($auth->hasCapability('list') && !$auth->exists($new_owner_backend)) {
            $notification->push(sprintf(_("The user \"%s\" does not exist."), $new_owner_backend), 'horde.error');
        } else {
            $share->set('owner', $new_owner);
            $share->save();
        }
    }

    if ($registry->isAdmin() ||
        !empty($conf['share']['world'])) {
        // Process default permissions.
        if ($vars->default_show) {
            $perm->addDefaultPermission(Horde_Perms::SHOW, false);
        } else {
            $perm->removeDefaultPermission(Horde_Perms::SHOW, false);
        }
        if ($vars->default_read) {
            $perm->addDefaultPermission(Horde_Perms::READ, false);
        } else {
            $perm->removeDefaultPermission(Horde_Perms::READ, false);
        }
        if ($vars->default_edit) {
            $perm->addDefaultPermission(Horde_Perms::EDIT, false);
        } else {
            $perm->removeDefaultPermission(Horde_Perms::EDIT, false);
        }
        if ($vars->default_delete) {
            $perm->addDefaultPermission(Horde_Perms::DELETE, false);
        } else {
            $perm->removeDefaultPermission(Horde_Perms::DELETE, false);
        }

        // Process guest permissions.
        if ($vars->guest_show) {
            $perm->addGuestPermission(Horde_Perms::SHOW, false);
        } else {
            $perm->removeGuestPermission(Horde_Perms::SHOW, false);
        }
        if ($vars->guest_read) {
            $perm->addGuestPermission(Horde_Perms::READ, false);
        } else {
            $perm->removeGuestPermission(Horde_Perms::READ, false);
        }
        if ($vars->guest_edit) {
            $perm->addGuestPermission(Horde_Perms::EDIT, false);
        } else {
            $perm->removeGuestPermission(Horde_Perms::EDIT, false);
        }
        if ($vars->guest_delete) {
            $perm->addGuestPermission(Horde_Perms::DELETE, false);
        } else {
            $perm->removeGuestPermission(Horde_Perms::DELETE, false);
        }
    }

    // Process creator permissions.
    if ($vars->creator_show) {
        $perm->addCreatorPermission(Horde_Perms::SHOW, false);
    } else {
        $perm->removeCreatorPermission(Horde_Perms::SHOW, false);
    }
    if ($vars->creator_read) {
        $perm->addCreatorPermission(Horde_Perms::READ, false);
    } else {
        $perm->removeCreatorPermission(Horde_Perms::READ, false);
    }
    if ($vars->creator_edit) {
        $perm->addCreatorPermission(Horde_Perms::EDIT, false);
    } else {
        $perm->removeCreatorPermission(Horde_Perms::EDIT, false);
    }
    if ($vars->creator_delete) {
        $perm->addCreatorPermission(Horde_Perms::DELETE, false);
    } else {
        $perm->removeCreatorPermission(Horde_Perms::DELETE, false);
    }

    foreach ($vars->u_names as $key => $user_backend) {
        // Apply backend hooks
        $user = $registry->convertUsername($user_backend, true);
        // If the user is empty, or we've already set permissions
        // via the owner_ options, don't do anything here.
        if (empty($user) || $user == $new_owner) {
            continue;
        }
        if ($auth->hasCapability('list') && !$auth->exists($user_backend)) {
            $notification->push(sprintf(_("The user \"%s\" does not exist."), $user_backend), 'horde.error');
            continue;
        }

        if (!empty($vars->u_show[$key])) {
            $perm->addUserPermission($user, Horde_Perms::SHOW, false);
        } else {
            $perm->removeUserPermission($user, Horde_Perms::SHOW, false);
        }
        if (!empty($vars->u_read[$key])) {
            $perm->addUserPermission($user, Horde_Perms::READ, false);
        } else {
            $perm->removeUserPermission($user, Horde_Perms::READ, false);
        }
        if (!empty($vars->u_edit[$key])) {
            $perm->addUserPermission($user, Horde_Perms::EDIT, false);
        } else {
            $perm->removeUserPermission($user, Horde_Perms::EDIT, false);
        }
        if (!empty($vars->u_delete[$key])) {
            $perm->addUserPermission($user, Horde_Perms::DELETE, false);
        } else {
            $perm->removeUserPermission($user, Horde_Perms::DELETE, false);
        }
    }

    foreach ($vars->g_names as $key => $group) {
        if (empty($group)) {
            continue;
        }

        if (!empty($vars->g_show[$key])) {
            $perm->addGroupPermission($group, Horde_Perms::SHOW, false);
        } else {
            $perm->removeGroupPermission($group, Horde_Perms::SHOW, false);
        }
        if (!empty($vars->g_read[$key])) {
            $perm->addGroupPermission($group, Horde_Perms::READ, false);
        } else {
            $perm->removeGroupPermission($group, Horde_Perms::READ, false);
        }
        if (!empty($vars->g_edit[$key])) {
            $perm->addGroupPermission($group, Horde_Perms::EDIT, false);
        } else {
            $perm->removeGroupPermission($group, Horde_Perms::EDIT, false);
        }
        if (!empty($vars->g_delete[$key])) {
            $perm->addGroupPermission($group, Horde_Perms::DELETE, false);
        } else {
            $perm->removeGroupPermission($group, Horde_Perms::DELETE, false);
        }
    }

    try {
        $share->setPermission($perm);
    } catch (Exception $e) {
        $notification->push($e->getMessage(), 'horde.error');
    }
    if ($vars->save_and_finish) {
        echo Horde::wrapInlineScript(array('window.close();'));
        exit;
    }
    $notification->push(
        sprintf(_("Updated \"%s\"."), $share->get('name')), 'horde.success');

    $form = 'edit.inc';
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
if (!empty($form)) {
    require HORDE_TEMPLATES . '/shares/' . $form;
}
$page_output->footer();
