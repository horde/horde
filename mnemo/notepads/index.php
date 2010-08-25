<?php
/**
 * $Horde: mnemo/notepads/index.php,v 1.5 2009/07/08 18:29:43 slusarz Exp $
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 */

@define('MNEMO_BASE', dirname(dirname(__FILE__)));
require_once MNEMO_BASE . '/lib/Application.php';
Horde_Registry::appInit('mnemo');

// Exit if this isn't an authenticated user.
if (!$GLOBALS['registry']->getAuth()) {
    require MNEMO_BASE . '/list.php';
    exit;
}

$edit_url_base = Horde::applicationUrl('notepads/edit.php');
$perms_url_base = Horde::url($registry->get('webroot', 'horde') . '/services/shares/edit.php?app=mnemo');
$delete_url_base = Horde::applicationUrl('notepads/delete.php');


$notepads = Mnemo::listNotepads(true);
$sorted_notepads = array();
foreach ($notepads as $notepad) {
    $sorted_notepads[$notepad->getName()] = $notepad->get('name');
}
asort($sorted_notepads);

$edit_img = Horde::img('edit.png', _("Edit"), null);
$perms_img = Horde::img('perms.png', _("Change Permissions"), null);
$delete_img = Horde::img('delete.png', _("Delete"), null);

Horde::addScriptFile('popup.js', 'horde', true);
Horde::addScriptFile('tables.js', 'horde', true);
$title = _("Manage Notepads");
require MNEMO_TEMPLATES . '/common-header.inc';
require MNEMO_TEMPLATES . '/menu.inc';
$notification->notify();
require MNEMO_TEMPLATES . '/notepad_list.php';
require $registry->get('templates', 'horde') . '/common-footer.inc';
