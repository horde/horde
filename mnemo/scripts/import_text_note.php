#!/usr/bin/env php
<?php
/**
 * This script imports vNote data into Mnemo notepads.
 * The data is read from standard input, the notepad and user name passed as
 * parameters.
 *
 * $Horde: mnemo/scripts/import_text_note.php,v 1.5 2009/07/09 06:08:55 slusarz Exp $
 *
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
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
if (count($argv) != 4) {
    $cli->message('Too many or too few parameters.', 'cli.error');
    usage();
}
$notepad = $argv[1];
$user = $argv[2];
$file = $argv[3];

// Read standard input.
if (!file_exists($file)) {
    $cli>message("$file does not exist", 'cli.error');
    usage();
}
$data = file_get_contents($file);
if (empty($data)) {
    $cli->message('No import data provided.', 'cli.error');
    usage();
}
$data = pathinfo($file, PATHINFO_FILENAME) . "\n\n" . $data;

// Registry.
$registry = Horde_Registry::singleton();

// Set user.
Horde_Auth::setAuth($user, array());

// Import data.
$result = $registry->call('notes/import', array($data, 'text/plain', $notepad));
$cli->message('Imported successfully ' . count($result) . ' notes', 'cli.success');

function usage()
{
    $GLOBALS['cli']->writeln('Usage: import_text_note.php notepad user file');
    exit;
}

