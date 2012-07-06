<?php
/**
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Jan Schneider <jan@horde.org>
 */

require_once __DIR__ . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array(
    'permission' => array('horde:administration:perms')
));

/* Set up the form variables. */
$vars = Horde_Variables::getDefaultVariables();
$perms = $GLOBALS['injector']->getInstance('Horde_Perms');
$corePerms = $injector->getInstance('Horde_Core_Perms');
$perm_id = $vars->get('perm_id');

try {
    $permission = $perms->getPermissionById($perm_id);
} catch (Exception $e) {
    $notification->push(_("Invalid parent permission."), 'horde.error');
    Horde::url('admin/perms/index.php', true)->redirect();
}

/* Set up form. */
$ui = new Horde_Core_Perms_Ui($perms, $corePerms);
$ui->setVars($vars);
$ui->setupAddForm($permission);

if ($ui->validateAddForm($info)) {
    try {
        if ($info['perm_id'] == Horde_Perms::ROOT) {
            $child = $corePerms->newPermission($info['child']);
            $result = $perms->addPermission($child);
        } else {
            $pOb = $perms->getPermissionById($info['perm_id']);
            $name = $pOb->getName() . ':' . str_replace(':', '.', $info['child']);
            $child = $corePerms->newPermission($name);
            $result = $perms->addPermission($child);
        }
        $notification->push(sprintf(_("\"%s\" was added to the permissions system."), $corePerms->getTitle($child->getName())), 'horde.success');
        Horde::url('admin/perms/edit.php', true)->add('perm_id', $child->getId())->redirect();
    } catch (Exception $e) {
        Horde::logMessage($e, 'ERR');
        $notification->push(sprintf(_("\"%s\" was not created: %s."), $corePerms->getTitle($child->getName()), $e->getMessage()), 'horde.error');
    }
}

$page_output->header(array(
    'title' => _("Permissions Administration")
));
require HORDE_TEMPLATES . '/admin/menu.inc';

/* Render the form and tree. */
$ui->renderForm('addchild.php');
echo '<br />';
$ui->renderTree($perm_id);

$page_output->footer();
