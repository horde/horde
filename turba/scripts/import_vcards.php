#!/usr/bin/php
<?php
/**
 * This script imports VCARD data into turba address books.
 * The VCARD data is read from standard input, the address book and user name
 * passed as parameters.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
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
$source = $argv[1];
$user = $argv[2];

// Read standard input.
$vcard = $cli->readStdin();
if (empty($vcard)) {
    $cli->message('No import data provided.', 'cli.error');
    usage();
}

// Registry.
Horde_Registry::appInit('turba', array('authentication' => 'none', 'user' => $user));

// Import data.
$result = $registry->call('contacts/import',
                          array($vcard, 'text/x-vcard', $source));
if (is_a($result, 'PEAR_Error')) {
    $cli->fatal($result->toString());
}

$cli->message('Imported successfully ' . count($result) . ' contacts', 'cli.success');

function usage()
{
    $GLOBALS['cli']->writeln('Usage: import_vcards.php source user');
    exit;
}

