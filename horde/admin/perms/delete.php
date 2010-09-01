<?php
/**
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array('admin' => true));

/* Set up the form variables. */
$vars = Horde_Variables::getDefaultVariables();
$perms = $GLOBALS['injector']->getInstance('Horde_Perms');
$perm_id = $vars->get('perm_id');
$category = $vars->get('category');
try {
    $permission = $perms->getPermissionById($perm_id);
} catch (Exception $e) {
    /* If the permission fetched is an error return to permissions list. */
    $notification->push(_("Attempt to delete a non-existent permission."), 'horde.error');
    Horde::url('admin/perms/index.php', true)->redirect();
}

/* Set up form. */
$ui = new Horde_Core_Perms_Ui($perms);
$ui->setVars($vars);
$ui->setupDeleteForm($permission);

if ($confirmed = $ui->validateDeleteForm($info)) {
    try {
        $result = $perms->removePermission($permission, true);
        $notification->push(sprintf(_("Successfully deleted \"%s\"."), $perms->getTitle($permission->getName())), 'horde.success');
        Horde::url('admin/perms/index.php', true)->redirect();
    } catch (Exception $e) {
        $notification->push(sprintf(_("Unable to delete \"%s\": %s."), $perms->getTitle($permission->getName()), $result->getMessage()), 'horde.error');
    }
} elseif ($confirmed === false) {
    $notification->push(sprintf(_("Permission \"%s\" not deleted."), $perms->getTitle($permission->getName())), 'horde.success');
    Horde::url('admin/perms/index.php', true)->redirect();
}

$title = _("Permissions Administration");
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/admin/menu.inc';

/* Render the form and tree. */
$ui->renderForm('delete.php');
echo '<br />';
$ui->renderTree($perm_id);

require HORDE_TEMPLATES . '/common-footer.inc';
