<?php
/**
 * Copyright 1999-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

require_once __DIR__ . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array(
    'permission' => array('horde:administration:perms')
));

/* Set up the form variables. */
$vars = $injector->getInstance('Horde_Variables');
$perms = $injector->getInstance('Horde_Perms');
$corePerms = $injector->getInstance('Horde_Core_Perms');
$perm_id = $vars->get('perm_id');
$category = $vars->get('category');

/* See if we need to (and are supposed to) autocreate the permission. */
$redirect = false;
if ($category !== null) {
    try {
        $permission = $perms->getPermission($category);
        $perm_id = $perms->getPermissionId($permission);
    } catch (Horde_Perms_Exception $e) {
        if ($vars->autocreate) {
            /* Check to see if the permission we are copying from exists
             * before we autocreate. */
            $copyFrom = $vars->autocreate_copy;
            if ($copyFrom && !$perms->exists($copyFrom)) {
                $copyFrom = null;
            }

            /* Create parents if necessary. Remove root from the name. */
            $root = Horde_Perms::ROOT . ':';
            if (substr($category, 0, strlen($root)) == ($root)) {
                $category = substr($category, strlen($root));
            }
            $pos = -1;
            while (($pos = strpos($category, ':', $pos + 1)) !== false) {
                $parent = substr($category, 0, $pos);
                if (!$perms->exists($parent)) {
                    try {
                        $permission = $corePerms->newPermission($parent);
                        $perms->addPermission($permission);
                        $permission->addDefaultPermission(Horde_Perms::ALL);
                    } catch (Exception $e) {
                        $notification->push($e);
                        Horde::url('admin/perms/index.php', true)->redirect();
                    }
                }
            }

            try {
                $permission = $corePerms->newPermission($category);
                $perms->addPermission($permission);
                $form = 'edit.inc';
                $perm_id = $perms->getPermissionId($permission);

                if ($copyFrom) {
                    /* We have autocreated the permission and we have been told
                     * to copy an existing permission for the defaults. */
                    $copyFromObj = $perms->getPermission($copyFrom);
                    $permission->addGuestPermission($copyFromObj->getGuestPermissions(), false);
                    $permission->addDefaultPermission($copyFromObj->getDefaultPermissions(), false);
                    $permission->addCreatorPermission($copyFromObj->getCreatorPermissions(), false);
                    foreach ($copyFromObj->getUserPermissions() as $user => $uperm) {
                        $permission->addUserPermission($user, $uperm, false);
                    }
                    foreach ($copyFromObj->getGroupPermissions() as $group => $gperm) {
                        $permission->addGroupPermission($group, $gperm, false);
                    }
                } else {
                    /* We have autocreated the permission and we don't have an
                     * existing permission to copy.  See if some defaults were
                     * supplied. */
                    $addPerms = $vars->autocreate_guest;
                    if ($addPerms) {
                        $permission->addGuestPermission($addPerms, false);
                    }
                    $addPerms = $vars->autocreate_default;
                    if ($addPerms) {
                        $permission->addDefaultPermission($addPerms, false);
                    }
                    $addPerms = $vars->autocreate_creator;
                    if ($addPerms) {
                        $permission->addCreatorPermission($addPerms, false);
                    }
                }
                $permission->save();
            } catch (Exception $e) {
                $notification->push($e);
                Horde::url('admin/perms/index.php', true)->redirect();
            }
        } else {
            $redirect = true;
        }
    } catch (Exception $e) {
        $redirect = true;
    }
    $vars->set('perm_id', $perm_id);
} else {
    try {
        $permission = $perms->getPermissionById($perm_id);
    } catch (Exception $e) {
        $redirect = true;
    }
}

if ($redirect) {
    $notification->push(_("Attempt to edit a non-existent permission."), 'horde.error');
    Horde::url('admin/perms/index.php', true)->redirect();
}

$ui = new Horde_Core_Perms_Ui($perms, $corePerms);
$ui->setVars($vars);
$ui->setupEditForm($permission);

if ($ui->validateEditForm($info)) {
    /* Update and save the permissions. */
    $permission->updatePermissions($info);
    $permission->save();
    $notification->push(sprintf(_("Updated \"%s\"."), $corePerms->getTitle($permission->getName())), 'horde.success');
    Horde::url('admin/perms/edit.php', true)
        ->add('perm_id', $permission->getId())
        ->redirect();
}

// Buffer the tree rendering
Horde::startBuffer();
$ui->renderForm('edit.php');
echo '<br />';
$ui->renderTree($perm_id);
$tree_output = Horde::endBuffer();

// Buffer the menu output
Horde::startBuffer();
require HORDE_TEMPLATES . '/admin/menu.inc';
$menu_output = Horde::endBuffer();

$page_output->header(array(
    'title' => _("Permissions Administration")
));

/* Render the form and tree. */
echo $menu_output;
echo $tree_output;
$page_output->footer();
