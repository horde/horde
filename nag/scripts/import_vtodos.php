#!/usr/bin/php
<?php
/**
 * This script imports vTodo data into Nag tasklists.
 * The data is read from standard input, the tasklist and user name passed as
 * parameters.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

// Do CLI checks and environment setup first.
require_once dirname(__FILE__) . '/../lib/Application.php';

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment - make sure there's no time limit, init some
// variables, etc.
$cli = Horde_Cli::singleton();
$cli->init();

// Read command line parameters.
if (count($argv) != 3) {
    $cli->message('Too many or too few parameters.', 'cli.error');
    usage();
}
$tasklist = $argv[1];
$user = $argv[2];

// Read standard input.
$vtodo = $cli->readStdin();
if (empty($vtodo)) {
    $cli->message('No import data provided.', 'cli.error');
    usage();
}

// Registry.
$registry = Horde_Registry::singleton();

// Set user.
Horde_Auth::setAuth($user, array());

// Import data.
$result = $registry->call('tasks/import',
                          array($vtodo, 'text/calendar', $tasklist));
if (is_a($result, 'PEAR_Error')) {
    $cli->fatal($result->toString());
}

$cli->message('Imported successfully ' . count($result) . ' tasks', 'cli.success');

function usage()
{
    $GLOBALS['cli']->writeln('Usage: import_vtodos.php tasklist user');
    exit;
}

