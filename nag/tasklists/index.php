<?php
/**
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

/**
 * Show just the beginning and end of long URLs.
 */
function shorten_url($url, $separator = '...', $first_chunk_length = 35, $last_chunk_length = 15)
{
    $url_length = strlen($url);
    $max_length = $first_chunk_length + strlen($separator) + $last_chunk_length;

    if ($url_length > $max_length) {
        return substr_replace($url, $separator, $first_chunk_length, -$last_chunk_length);
    }

    return $url;
}

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('nag');

/* Exit if this isn't an authenticated user. */
if (!$GLOBALS['registry']->getAuth()) {
    require NAG_BASE . '/list.php';
    exit;
}

$edit_url_base = Horde::url('tasklists/edit.php');
$perms_url_base = Horde::url($registry->get('webroot', 'horde') . '/services/shares/edit.php?app=nag');
$delete_url_base = Horde::url('tasklists/delete.php');
$display_url_base = Horde::url('list.php', true, -1);
$subscribe_url_base = $registry->get('webroot', 'horde');
if (isset($conf['urls']['pretty']) && $conf['urls']['pretty'] == 'rewrite') {
    $subscribe_url_base .= '/rpc/nag/';
} else {
    $subscribe_url_base .= '/rpc.php/nag/';
}
$subscribe_url_base = Horde::url($subscribe_url_base, true, -1);

$tasklists = Nag::listTasklists(true);
$sorted_tasklists = array();
foreach ($tasklists as $tasklist) {
    $sorted_tasklists[$tasklist->getName()] = $tasklist->get('name');
}
if ($registry->isAdmin()) {
    $system_tasklists = $nag_shares->listSystemShares();
    foreach ($system_tasklists as $tasklist) {
        $tasklists[$tasklist->getName()] = $tasklist;
        $sorted_tasklists[$tasklist->getName()] = $tasklist->get('name');
    }
}
asort($sorted_tasklists);

$edit_img = Horde::img('edit.png', _("Edit"));
$perms_img = Horde::img('perms.png', _("Change Permissions"));
$delete_img = Horde::img('delete.png', _("Delete"));

Horde::addScriptFile('tables.js', 'horde');
$title = _("Manage Task Lists");
require NAG_TEMPLATES . '/common-header.inc';
echo Horde::menu();
Nag::status();
require NAG_TEMPLATES . '/tasklist_list.php';
require $registry->get('templates', 'horde') . '/common-footer.inc';
