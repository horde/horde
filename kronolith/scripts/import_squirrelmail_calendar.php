#!/usr/bin/env php
<?php
/**
 * This script imports SquirrelMail database calendars into Kronolith.
 *
 * The first argument must be a DSN to the database containing the calendar
 * and event tables, e.g.: "mysql://root:password@localhost/squirrelmail".
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

// Do CLI checks and environment setup first.
require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('kronolith', array('authentication' => 'none', 'cli' => true, 'user_admin' => true));

// Read command line parameters.
if ($argc != 2) {
    $cli->message('Too many or too few parameters.', 'cli.error');
    $cli->writeln('Usage: import_squirrelmail_file_abook.php DSN');
    exit;
}
$dsn = $argv[1];
$default_tz = date_default_timezone_get();

// Connect to database.
$db = DB::connect($dsn);
if ($db instanceof PEAR_Error) {
    $cli->fatal($db->toString());
}

// Loop through SquirrelMail calendars.
$read_stmt = $db->prepare('SELECT reader_name FROM calendar_readers WHERE calendar_id = ?');
$write_stmt = $db->prepare('SELECT writer_name FROM calendar_writers WHERE calendar_id = ?');
$handle = $db->query('SELECT id, name, owner_name FROM calendars, calendar_owners WHERE calendars.id = calendar_owners.calendar_id');
if ($handle instanceof PEAR_Error) {
    $cli->fatal($handle->toString());
}
while ($row = $handle->fetchRow(DB_FETCHMODE_ASSOC)) {
    $user = $row['owner_name'];
    Horde_Auth::setAuth($user, array());
    $cli->message('Creating calendar ' . $row['name']);
    $share = $kronolith_shares->newShare($GLOBALS['registry']->getAuth(), $row['id']);
    $share->set('name', $row['name']);
    $kronolith_shares->addShare($share);

    // Add permissions.
    $permissions = array();
    $result = $db->execute($read_stmt, array($row['id']));
    if ($result instanceof PEAR_Error) {
        $cli->fatal($result->toString());
    }
    while ($perm_row = $result->fetchRow()) {
        $permissions[$perm_row[0]] = Horde_Perms::READ | Horde_Perms::SHOW;
    }
    $result = $db->execute($write_stmt, array($row['id']));
    if ($result instanceof PEAR_Error) {
        $cli->fatal($result->toString());
    }
    while ($perm_row = $result->fetchRow()) {
        if (isset($permissions[$perm_row[0]])) {
            $permissions[$perm_row[0]] |= Horde_Perms::EDIT;
        } else {
            $permissions[$perm_row[0]] = Horde_Perms::EDIT;
        }
    }
    if (count($permissions)) {
        $perm = $share->getPermission();
        $perm->addUserPermission($user, Horde_Perms::ALL, false);
        foreach ($permissions as $key => $value) {
            $perm->addUserPermission($key, $value, false);
        }
        $share->setPermission($perm);
        $share->save();
    }
}

$handle = $db->query('SELECT event_id, calendar_id, ical_raw, owner_name, prefval FROM events, event_owners LEFT JOIN userprefs ON event_owners.owner_name = userprefs.user AND userprefs.prefkey = \'timezone\' WHERE events.id = event_owners.event_key ORDER BY calendar_id, userprefs.prefval, event_owners.owner_name');
if ($handle instanceof PEAR_Error) {
    $cli->fatal($handle->toString());
}
$ical = new Horde_Icalendar();
$tz = $calendar = $user = $count = null;
while ($row = $handle->fetchRow(DB_FETCHMODE_ASSOC)) {
    // Open calendar.
    if ($calendar != $row['calendar_id']) {
        if (!is_null($count)) {
            $cli->message('  Added ' . $count . ' events', 'cli.success');
        }
        $calendar = $row['calendar_id'];
        $cli->message('Importing events into ' . $calendar);
        $kronolith_driver->open($calendar);
        $count = 0;
    }
    // Set timezone.
    if ($tz != $row['prefval']) {
        $tz = $row['prefval'];
        date_default_timezone_set($tz ? $tz : $default_tz);
    }
    // Set user.
    if ($user != $row['owner_name']) {
        $user = $row['owner_name'];
        Horde_Auth::setAuth($user, array());
    }
    // Parse event.
    try {
        $ical->parsevCalendar($row['ical_raw']);
    } catch (Horde_Icalendar_Exception $e) {
        $cli->message('  ' . $e->getMessage(), 'cli.warning');
        continue;
    }
    $components = $ical->getComponents();
    if (!count($components)) {
        $cli->message('  No iCalendar data was found.', 'cli.warning');
        continue;
    }

    // Save event.
    $event = $kronolith_driver->getEvent();
    $event->fromiCalendar($components[0]);
    try {
        $event->save();
    } catch (Exception $e) {
        $cli->message('  ' . $e->getMessage(), 'cli.error');
        continue;
    }
    $count++;
}
if (!is_null($count)) {
    $cli->message('  Added ' . $count . ' events', 'cli.success');
}
