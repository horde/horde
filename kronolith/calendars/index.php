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
Horde_Registry::appInit('kronolith');

if (Kronolith::showAjaxView()) {
    Horde::url('', true)->redirect();
}

// Exit if this isn't an authenticated user.
if (!$GLOBALS['registry']->getAuth()) {
    Horde::url($prefs->getValue('defaultview') . '.php')->redirect();
}

$edit_url_base = Horde::url('calendars/edit.php');
$remote_edit_url_base = Horde::url('calendars/remote_edit.php');
$delete_url_base = Horde::url('calendars/delete.php');
$remote_unsubscribe_url_base = Horde::url('calendars/remote_unsubscribe.php');
$perms_url_base = Horde::url('perms.php', true);
$display_url_base = Horde::url('month.php', true, -1);
$subscribe_url_base = $registry->get('webroot', 'horde');
if (isset($conf['urls']['pretty']) && $conf['urls']['pretty'] == 'rewrite') {
    $subscribe_url_base .= '/rpc/kronolith/';
} else {
    $subscribe_url_base .= '/rpc.php/kronolith/';
}
$subscribe_url_base = Horde::url($subscribe_url_base, true, -1);

$calendars = array();
$sorted_calendars = array();
$my_calendars = Kronolith::listInternalCalendars(true);
foreach ($my_calendars as $calendar) {
    $calendars[$calendar->getName()] = $calendar;
    $sorted_calendars[$calendar->getName()] = $calendar->get('name');
}
if ($registry->isAdmin()) {
    $system_calendars = $kronolith_shares->listSystemShares();
    foreach ($system_calendars as $calendar) {
        $calendars[$calendar->getName()] = $calendar;
        $sorted_calendars[$calendar->getName()] = $calendar->get('name');
    }
}
$remote_calendars = unserialize($prefs->getValue('remote_cals'));
foreach ($remote_calendars as $calendar) {
    $calendars[$calendar['url']] = $calendar;
    $sorted_calendars[$calendar['url']] = $calendar['name'];
}
asort($sorted_calendars);

$edit_img = Horde::img('edit.png', _("Edit"));
$perms_img = Horde::img('perms.png', _("Change Permissions"));
$delete_img = Horde::img('delete.png', _("Delete"));

Horde::addScriptFile('tables.js', 'horde');
$title = _("Manage Calendars");
$menu = Horde::menu();
require KRONOLITH_TEMPLATES . '/common-header.inc';
echo $menu;
$notification->notify(array('listeners' => 'status'));
require KRONOLITH_TEMPLATES . '/calendar_list.php';
require $registry->get('templates', 'horde') . '/common-footer.inc';
