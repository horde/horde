#!/usr/bin/env php
<?php
/**
 * This script imports vNote data into Mnemo notepads.
 * The data is read from standard input, the notepad and user name passed as
 * parameters.
 *
 * $Horde: mnemo/scripts/import_vnotes.php,v 1.11 2009/07/09 06:08:55 slusarz Exp $
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Jan Schneider <jan@horde.org>
 */

@define('AUTH_HANDLER', true);
@define('HORDE_BASE', dirname(__FILE__) . '/../..');

// Do CLI checks and environment setup first.
require_once HORDE_BASE . '/lib/core.php';

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment - make sure there's no time limit, init some
// variables, etc.
$cli = &Horde_Cli::singleton();
$cli->init();

// Read command line parameters.
if (count($argv) != 3) {
    $cli->message('Too many or too few parameters.', 'cli.error');
    usage();
}
$notepad = $argv[1];
$user = $argv[2];

// Read standard input.
$vnote = $cli->readStdin();
if (empty($vnote)) {
    $cli->message('No import data provided.', 'cli.error');
    usage();
}

// Registry.
$registry = Horde_Registry::singleton();

// Set user.
Horde_Auth::setAuth($user, array());

// Import data.
$result = $registry->call('notes/import', array($vnote, 'text/x-vnote', $notepad));
$cli->message('Imported successfully ' . count($result) . ' notes', 'cli.success');

function usage()
{
    $GLOBALS['cli']->writeln('Usage: import_vnotes.php notepad user');
    exit;
}

