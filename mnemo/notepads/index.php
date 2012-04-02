<?php
/**
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @package Mnemo
 */

@define('MNEMO_BASE', dirname(__DIR__));
require_once MNEMO_BASE . '/lib/Application.php';
Horde_Registry::appInit('mnemo');

// Exit if this isn't an authenticated user.
if (!$GLOBALS['registry']->getAuth()) {
    require MNEMO_BASE . '/list.php';
    exit;
}

$edit_url_base = Horde::url('notepads/edit.php');
$perms_url_base = Horde::url($registry->get('webroot', 'horde') . '/services/shares/edit.php?app=mnemo');
$delete_url_base = Horde::url('notepads/delete.php');

$notepads = Mnemo::listNotepads(true);
$sorted_notepads = array();
foreach ($notepads as $notepad) {
    $sorted_notepads[$notepad->getName()] = $notepad->get('name');
}
asort($sorted_notepads);

$edit_img = Horde::img('edit.png', _("Edit"), null);
$perms_img = Horde::img('perms.png', _("Change Permissions"), null);
$delete_img = Horde::img('delete.png', _("Delete"), null);

$page_output->addScriptFile('popup.js', 'horde');
$page_output->addScriptFile('tables.js', 'horde');

$page_output->header(array(
    'title' => _("Manage Notepads")
));
echo Horde::menu();
$notification->notify();
require MNEMO_TEMPLATES . '/notepad_list.php';
$page_output->footer();
