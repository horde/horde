<?php
/**
 * Gollem permissions administration page.
 *
 * Copyright 2005-2007 Vijay Mahrra <vijay.mahrra@es.easynet.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Vijay Mahrra <vijay.mahrra@es.easynet.net>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Gollem
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('gollem', array('admin' => true));

if (!Gollem::getBackends('all')) {
    $notification->push(_("You need at least one backend defined to set permissions."), 'horde.error');

    $title = _("Gollem Backend Permissions Administration");
    $menu = Gollem::menu();
    require GOLLEM_TEMPLATES . '/common-header.inc';
    echo $menu;
    Gollem::status();
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;
}

/* Edit permissions for the preferred backend if none is selected. */
$key = Horde_Util::getFormData('backend', Gollem::getPreferredBackend());
$app = $registry->getApp();
$backendTag = $app . ':backends:' . $key;
$perms = $GLOBALS['injector']->getInstance('Horde_Perms');

if ($perms->exists($backendTag)) {
    $permission = $perms->getPermission($backendTag);
    $perm_id = $perms->getPermissionId($permission);
} else {
    $permission = $perms->newPermission($backendTag);
    try {
        $perms->addPermission($permission, $app);
    } catch (Horde_Perms_Exception $e) {
        $notification->push(sprintf(_("Unable to create backend permission: %s"), $e->getMessage()), 'horde.error');
        Horde::url('redirect.php', true)->redirect();
    }

    $perm_id = $perms->getPermissionId($permission);
    $notification->push(sprintf(_("Created empty permissions for \"%s\". You must explicitly grant access to this backend now."), $key), 'horde.warning');
}

/* Redirect to horde permissions administration interface. */
Horde::url($registry->get('webroot', 'horde') . '/admin/perms/edit.php', true)
  ->add('perm_id', $permission->getId())
  ->redirect();
