#!/usr/bin/env php
<?php
/**
 * This script imports iCalendar/vCalendar data into Kronolith calendars.
 * The data is read from standard input, the calendar and user name passed as
 * parameters.
 *
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('kronolith', array('cli' => true));

// Read command line parameters.
$cli = Horde_Cli::singleton();
if (count($argv) != 3) {
    $cli->message('Too many or too few parameters.', 'cli.error');
    usage();
}
$cal = $argv[1];
$user = $argv[2];

// Read standard input.
$ical = $cli->readStdin();
if (empty($ical)) {
    $cli->message('No import data provided.', 'cli.error');
    usage();
}

// Set user.
Horde_Auth::setAuth($user, array());

// Import data.
try {
    $result = $registry->call('calendar/import', array($ical, 'text/calendar', $cal));
} catch (Horde_Exception $e) {
    $cli->fatal($e->toString());
}

$cli->message('Imported successfully ' . count($result) . ' events', 'cli.success');

function usage()
{
    $GLOBALS['cli']->writeln('Usage: import_icals.php calendar user');
    exit;
}

