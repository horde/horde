#!/usr/bin/env php
<?php
/**
 * Obliterate Whups Data.
 *
 * This script deletes all queues, types, and tickets from the current Whups
 * database.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * @author Jon Parise <jon@horde.org>
 */
@define('HORDE_BASE', dirname(__FILE__) . '/../..');
@define('WHUPS_BASE', dirname(__FILE__) . '/..');

/* Do CLI checks and environment setup first. */
require_once HORDE_BASE . '/lib/core.php';

/* Make sure no one runs this from the web. */
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

/* Load the command line environment. */
$cli = Horde_Cli::init();

$confirm = $cli->prompt('Are you sure you want to obliterate all Whups data?',
                        array('n' => 'No', 'y' => 'Yes'));
$cli->writeln();
if ($confirm !== 'y') {
    exit;
}

/* Load the Whups libraries. */
require_once HORDE_BASE . '/lib/core.php';

$registry = new Horde_Registry();
$registry->pushApp('whups', false);
$conf = &$GLOBALS['conf'];

require_once WHUPS_BASE . '/lib/Whups.php';
require_once WHUPS_BASE . '/lib/Driver.php';

$GLOBALS['whups_driver'] = Whups_Driver::factory();
$GLOBALS['whups_driver']->initialise();

$cli->writeln($cli->bold('Obliterating queues'));
$queues = $whups_driver->getQueues();
foreach ($queues as $queue_id => $queue_name) {
    $cli->message("Deleting queue: $queue_name");
    $whups_driver->deleteQueue($queue_id);
}
$cli->writeln();

$cli->writeln($cli->bold('Obliterating types'));
$types = $whups_driver->getAllTypes();
foreach ($types as $type_id => $type_name) {
    $cli->message("Deleting type: $type_name");
    $whups_driver->deleteType($type_id);
}
$cli->writeln();

$cli->writeln($cli->bold('Obliterating tickets'));
$tickets = $whups_driver->_getAll('select ticket_id from whups_tickets');
foreach ($tickets as $ticket) {
    $info = array('id' => $ticket['ticket_id']);
    $cli->message('Deleting ticket: ' . $info['id']);
    $whups_driver->deleteTicket($info);
}
$cli->writeln();
