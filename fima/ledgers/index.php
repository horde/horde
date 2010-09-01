<?php
/**
 * Copyright 2001-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 */

@define('FIMA_BASE', dirname(dirname(__FILE__)));
require_once FIMA_BASE . '/lib/base.php';

/* Exit if this isn't an authenticated user. */
if (!$GLOBALS['registry']->getAuth()) {
    require FIMA_BASE . '/postings.php';
    exit;
}

$edit_url_base = Horde::url('ledgers/edit.php');
$perms_url_base = Horde::url($registry->get('webroot', 'horde') . '/services/shares/edit.php?app=fima', true);
$delete_url_base = Horde::url('ledgers/delete.php');

// Get the shares owned by the current user, and figure out what we will
// display the share name as to the user.
$ledgers = Fima::listLedgers(true);
$sorted_ledgers = array();
foreach ($ledgers as $ledger) {
    $sorted_ledgers[$ledger->getName()] = $ledger->get('name');
}
asort($sorted_ledgers);

$browse_img = Horde::img('accounts.png', _("Ledger"));
$edit_img = Horde::img('edit.png', _("Edit"));
$perms_img = Horde::img('perms.png', _("Change Permissions"));
$delete_img = Horde::img('delete.png', _("Delete"));

Horde::addScriptFile('tables.js', 'horde');
$title = _("Manage Ledgers");
require FIMA_TEMPLATES . '/common-header.inc';
require FIMA_TEMPLATES . '/menu.inc';
require FIMA_TEMPLATES . '/ledgers_list.php';
require $registry->get('templates', 'horde') . '/common-footer.inc';
