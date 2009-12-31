<?php
/**
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
new Horde_Application(array('admin' => true));

/* Set up the form variables. */
$vars = Horde_Variables::getDefaultVariables();
$perm_id = $vars->get('perm_id');

try {
    $permission = $perms->getPermissionById($perm_id);
} catch (Horde_Exception $e) {
    $notification->push(_("Invalid parent permission."), 'horde.error');
    $url = Horde::applicationUrl('admin/perms/index.php', true);
    header('Location: ' . $url);
    exit;
}

/* Set up form. */
$ui = new Horde_Perms_Ui($perms);
$ui->setVars($vars);
$ui->setupAddForm($permission);

if ($ui->validateAddForm($info)) {
    if ($info['perm_id'] == Horde_Perms::ROOT) {
        $child = &$perms->newPermission($info['child']);
        $result = $perms->addPermission($child);
    } else {
        $pOb = &$perms->getPermissionById($info['perm_id']);
        $name = $pOb->getName() . ':' . str_replace(':', '.', $info['child']);
        $child = &$perms->newPermission($name);
        $result = $perms->addPermission($child);
    }
    if (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("\"%s\" was not created: %s."), $perms->getTitle($child->getName()), $result->getMessage()), 'horde.error');
    } else {
        $notification->push(sprintf(_("\"%s\" was added to the permissions system."), $perms->getTitle($child->getName())), 'horde.success');
        $url = Horde::applicationUrl('admin/perms/edit.php', true);
        $url = Horde_Util::addParameter($url, 'perm_id', $child->getId(), false);
        header('Location: ' . $url);
        exit;
    }
}

$title = _("Permissions Administration");
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/admin/menu.inc';

/* Render the form and tree. */
$ui->renderForm('addchild.php');
echo '<br />';
$ui->renderTree($perm_id);

require HORDE_TEMPLATES . '/common-footer.inc';
