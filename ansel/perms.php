<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

$fieldsList = array(
    'show' => 0,
    'read' => 1,
    'edit' => 2,
    'delete' => 3
);

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ansel');

$groups = $injector->getInstance('Horde_Group');
$auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();

$form = null;
$reload = false;
$actionID = Horde_Util::getFormData('actionID', 'edit');
switch ($actionID) {
case 'edit':
    try {
        $gallery = $GLOBALS['injector']
            ->getInstance('Ansel_Storage')
            ->getGallery(Horde_Util::getFormData('cid', 0));
        $share = $gallery->getShare();
        $form = 'edit.inc';
        $perm = $share->getPermission();
    } catch (Horde_Exception_NotFound $e) {
        $notification->push($e->getMessage(), 'horde.error');
    }
    if (!$GLOBALS['registry']->getAuth() ||
        (isset($share) && $GLOBALS['registry']->getAuth() != $share->get('owner'))) {
        exit('permission denied');
    }
    break;

case 'editform':
    try {
        $gallery = $GLOBALS['injector']
            ->getInstance('Ansel_Storage')
            ->getGallery(Horde_Util::getFormData('cid'));
        $share = $gallery->getShare();
    } catch (Horde_Share_Exception $e) {
        $notification->push(_("Attempt to edit a non-existent share."), 'horde.error');
    }

    if (!empty($share)) {
        if (!$GLOBALS['registry']->getAuth() ||
            $GLOBALS['registry']->getAuth() != $share->get('owner')) {
            exit('permission denied');
        }
        $perm = $share->getPermission();

        // Process owner and owner permissions.
        $old_owner = $share->get('owner');
        $new_owner_backend = Horde_Util::getFormData(
            'owner_select',
            Horde_Util::getFormData('owner_input', $old_owner));
        $new_owner = $GLOBALS['registry']->convertUsername($new_owner_backend, true);
        if ($old_owner !== $new_owner && !empty($new_owner)) {
            if ($old_owner != $GLOBALS['registry']->getAuth() && !$registry->isAdmin()) {
                $notification->push(_("Only the owner or system administrator may change ownership or owner permissions for a share"), 'horde.error');
            } else {
                $gallery->set('owner', $new_owner, true);
                if (Horde_Util::getFormData('owner_show')) {
                    $perm->addUserPermission($new_owner, Horde_Perms::SHOW, false);
                } else {
                    $perm->removeUserPermission($new_owner, Horde_Perms::SHOW, false);
                }
                if (Horde_Util::getFormData('owner_read')) {
                    $perm->addUserPermission($new_owner, Horde_Perms::READ, false);
                } else {
                    $perm->removeUserPermission($new_owner, Horde_Perms::READ, false);
                }
                if (Horde_Util::getFormData('owner_edit')) {
                    $perm->addUserPermission($new_owner, Horde_Perms::EDIT, false);
                } else {
                    $perm->removeUserPermission($new_owner, Horde_Perms::EDIT, false);
                }
                if (Horde_Util::getFormData('owner_delete')) {
                    $perm->addUserPermission($new_owner, Horde_Perms::DELETE, false);
                } else {
                    $perm->removeUserPermission($new_owner, Horde_Perms::DELETE, false);
                }
            }
        }

        // Process default permissions.
        if (Horde_Util::getFormData('default_show')) {
            $perm->addDefaultPermission(Horde_Perms::SHOW, false);
        } else {
            $perm->removeDefaultPermission(Horde_Perms::SHOW, false);
        }
        if (Horde_Util::getFormData('default_read')) {
            $perm->addDefaultPermission(Horde_Perms::READ, false);
        } else {
            $perm->removeDefaultPermission(Horde_Perms::READ, false);
        }
        if (Horde_Util::getFormData('default_edit')) {
            $perm->addDefaultPermission(Horde_Perms::EDIT, false);
        } else {
            $perm->removeDefaultPermission(Horde_Perms::EDIT, false);
        }
        if (Horde_Util::getFormData('default_delete')) {
            $perm->addDefaultPermission(Horde_Perms::DELETE, false);
        } else {
            $perm->removeDefaultPermission(Horde_Perms::DELETE, false);
        }

        // Process guest permissions.
        if (Horde_Util::getFormData('guest_show')) {
            $perm->addGuestPermission(Horde_Perms::SHOW, false);
        } else {
            $perm->removeGuestPermission(Horde_Perms::SHOW, false);
        }
        if (Horde_Util::getFormData('guest_read')) {
            $perm->addGuestPermission(Horde_Perms::READ, false);
        } else {
            $perm->removeGuestPermission(Horde_Perms::READ, false);
        }
        if (Horde_Util::getFormData('guest_edit')) {
            $perm->addGuestPermission(Horde_Perms::EDIT, false);
        } else {
            $perm->removeGuestPermission(Horde_Perms::EDIT, false);
        }
        if (Horde_Util::getFormData('guest_delete')) {
            $perm->addGuestPermission(Horde_Perms::DELETE, false);
        } else {
            $perm->removeGuestPermission(Horde_Perms::DELETE, false);
        }

        // Process creator permissions.
        if (Horde_Util::getFormData('creator_show')) {
            $perm->addCreatorPermission(Horde_Perms::SHOW, false);
        } else {
            $perm->removeCreatorPermission(Horde_Perms::SHOW, false);
        }
        if (Horde_Util::getFormData('creator_read')) {
            $perm->addCreatorPermission(Horde_Perms::READ, false);
        } else {
            $perm->removeCreatorPermission(Horde_Perms::READ, false);
        }
        if (Horde_Util::getFormData('creator_edit')) {
            $perm->addCreatorPermission(Horde_Perms::EDIT, false);
        } else {
            $perm->removeCreatorPermission(Horde_Perms::EDIT, false);
        }
        if (Horde_Util::getFormData('creator_delete')) {
            $perm->addCreatorPermission(Horde_Perms::DELETE, false);
        } else {
            $perm->removeCreatorPermission(Horde_Perms::DELETE, false);
        }

        // Process user permissions.
        $u_names = Horde_Util::getFormData('u_names');
        $u_show = Horde_Util::getFormData('u_show');
        $u_read = Horde_Util::getFormData('u_read');
        $u_edit = Horde_Util::getFormData('u_edit');
        $u_delete = Horde_Util::getFormData('u_delete');

        foreach ($u_names as $key => $user) {
            // If the user is empty, or we've already set permissions
            // via the owner_ options, don't do anything here.
            if (empty($user) || $user == $new_owner) {
                continue;
            }

            if (!empty($u_show[$key])) {
                $perm->addUserPermission($user, Horde_Perms::SHOW, false);
            } else {
                $perm->removeUserPermission($user, Horde_Perms::SHOW, false);
            }
            if (!empty($u_read[$key])) {
                $perm->addUserPermission($user, Horde_Perms::READ, false);
            } else {
                $perm->removeUserPermission($user, Horde_Perms::READ, false);
            }
            if (!empty($u_edit[$key])) {
                $perm->addUserPermission($user, Horde_Perms::EDIT, false);
            } else {
                $perm->removeUserPermission($user, Horde_Perms::EDIT, false);
            }
            if (!empty($u_delete[$key])) {
                $perm->addUserPermission($user, Horde_Perms::DELETE, false);
            } else {
                $perm->removeUserPermission($user, Horde_Perms::DELETE, false);
            }
        }

        // Process group permissions.
        $g_names = Horde_Util::getFormData('g_names');
        $g_show = Horde_Util::getFormData('g_show');
        $g_read = Horde_Util::getFormData('g_read');
        $g_edit = Horde_Util::getFormData('g_edit');
        $g_delete = Horde_Util::getFormData('g_delete');

        foreach ($g_names as $key => $group) {
            if (empty($group)) {
                continue;
            }

            if (!empty($g_show[$key])) {
                $perm->addGroupPermission($group, Horde_Perms::SHOW, false);
            } else {
                $perm->removeGroupPermission($group, Horde_Perms::SHOW, false);
            }
            if (!empty($g_read[$key])) {
                $perm->addGroupPermission($group, Horde_Perms::READ, false);
            } else {
                $perm->removeGroupPermission($group, Horde_Perms::READ, false);
            }
            if (!empty($g_edit[$key])) {
                $perm->addGroupPermission($group, Horde_Perms::EDIT, false);
            } else {
                $perm->removeGroupPermission($group, Horde_Perms::EDIT, false);
            }
            if (!empty($g_delete[$key])) {
                $perm->addGroupPermission($group, Horde_Perms::DELETE, false);
            } else {
                $perm->removeGroupPermission($group, Horde_Perms::DELETE, false);
            }
        }

        try {
            $share->setPermission($perm);
        } catch (Horde_Share_Exception $e) {
            $notification->push($e->getMessage(), 'horde.error');
        }
        if ($conf['ansel_cache']['usecache']) {
            $injector->getInstance('Horde_Cache')->expire(
                'Ansel_Gallery' . Horde_Util::getFormData('cid'));
        }
        if (Horde_Util::getFormData('save_and_finish')) {
            echo Horde::wrapInlineScript(array('window.close();'));
            exit;
        }
        $notification->push(
            sprintf(_("Updated %s."), $share->get('name')),
            'horde.success');
        $form = 'edit.inc';
    }
    break;
}

if (empty($share)) {
    $title = _("Edit Permissions");
} else {
    $children = $GLOBALS['injector']
        ->getInstance('Ansel_Storage')
        ->listGalleries(array('perm' => Horde_Perms::READ,
                              'parent' => $gallery->id));
    $title = sprintf(_("Edit Permissions for %s"), $share->get('name'));
}

if ($auth->hasCapability('list')) {
    $userList = $auth->listUsers();
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

$page_output->header(array(
    'title' => $title
));
$notification->notify(array('listeners' => 'status'));
if (!empty($form)) {
    /* Need to temporarily put the gallery name in the share so the form
    * template will find it when rendering the Save button */
    $share->set('name', $share->get('name'));
    require $registry->get('templates', 'horde') . '/shares/' . $form;
}

$page_output->footer();
