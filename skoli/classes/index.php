<?php
/**
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

@define('SKOLI_BASE', dirname(dirname(__FILE__)));
require_once SKOLI_BASE . '/lib/base.php';

// Exit if this isn't an authenticated user.
if (!$GLOBALS['registry']->getAuth()) {
    Horde::applicationUrl('list.php', true)->redirect();
}

$edit_url_base = Horde::applicationUrl('classes/edit.php');
$perms_url_base = Horde::url($registry->get('webroot', 'horde') . '/services/shares/edit.php?app=skoli', true);
$delete_url_base = Horde::applicationUrl('classes/delete.php');

$classes = Skoli::listClasses(true);
$sorted_classes = array();
foreach ($classes as $class) {
    $sorted_classes[$class->getName()] = $class->get('name');
}
asort($sorted_classes);

$edit_img = Horde::img('edit.png', _("Edit"));
$perms_img = Horde::img('perms.png', _("Change Permissions"));
$delete_img = Horde::img('delete.png', _("Delete"));

Horde::addScriptFile('tables.js', 'horde');
$title = _("Manage Classes");
require SKOLI_TEMPLATES . '/common-header.inc';
require SKOLI_TEMPLATES . '/menu.inc';
require SKOLI_TEMPLATES . '/classes/list.php';
require $registry->get('templates', 'horde') . '/common-footer.inc';
