<?php
/**
 * Turba addressbooks - index.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('turba');

// Exit if this isn't an authenticated user, or if there's no source
// configured for shares.
if (!$GLOBALS['registry']->getAuth() || !$session->get('turba', 'has_share')) {
    require TURBA_BASE . '/'
        . ($browse_source_count ? basename($prefs->getValue('initial_page')) : 'search.php');
    exit;
}

$browse_url_base = Horde::url($browse_source_count ? basename($prefs->getValue('initial_page')) : 'search.php');
$edit_url_base = Horde::url('addressbooks/edit.php');
$perms_url_base = Horde::url($registry->get('webroot', 'horde') . '/services/shares/edit.php?app=turba', true);
$delete_url_base = Horde::url('addressbooks/delete.php');

// Get the shares owned by the current user, and figure out what we will
// display the share name as to the user.
$addressbooks = Turba::listShares(true);
$sorted_addressbooks = array();
foreach ($addressbooks as $addressbook) {
    if (!isset($cfgSources[$addressbook->getName()])) {
        continue;
    }
    $sorted_addressbooks[$addressbook->getName()] = $addressbook->get('name');
}
asort($sorted_addressbooks);

$browse_img = Horde::img('turba.png', _("Browse"));
$edit_img = Horde::img('edit.png', _("Edit"));
$perms_img = Horde::img('perms.png', _("Change Permissions"));
$delete_img = Horde::img('delete.png', _("Delete"));

Horde::addScriptFile('tables.js', 'horde');
$title = _("Manage Address Books");
require TURBA_TEMPLATES . '/common-header.inc';
require TURBA_TEMPLATES . '/menu.inc';
require TURBA_TEMPLATES . '/addressbook_list.php';
require $registry->get('templates', 'horde') . '/common-footer.inc';
