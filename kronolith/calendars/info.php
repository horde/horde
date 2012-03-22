<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('kronolith');

$calendar = null;
$calendarId = Horde_Util::getFormData('c');
if (strncmp($calendarId, 'remote_', 7) === 0) {
    $calendarId = substr($calendarId, 7);
    $remote_calendars = unserialize($prefs->getValue('remote_cals'));
    foreach ($remote_calendars as $remote_calendar) {
        if ($remote_calendar['url'] == $calendarId) {
            $calendar = $remote_calendar;
            break;
        }
    }
} elseif (isset($GLOBALS['all_calendars'][$calendarId])) {
    $calendar = $GLOBALS['all_calendars'][$calendarId];
    $rewrite = isset($conf['urls']['pretty']) &&
        $conf['urls']['pretty'] == 'rewrite';
    $subscribe_url = Horde::url($registry->get('webroot', 'horde') . ($rewrite ? '/rpc/kronolith/' : '/rpc.php/kronolith/'), true, -1)
        . ($calendar->owner() ? $calendar->owner() : '-system-')
        . '/' . $calendarId . '.ics';
}

if (is_null($calendar)) {
    exit;
}

require KRONOLITH_TEMPLATES . '/calendar_info.php';
