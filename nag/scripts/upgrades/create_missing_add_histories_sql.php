#!/usr/bin/env php
<?php
/**
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

// Do CLI checks and environment setup first.
require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('nag', array('authentication' => 'none', 'cli' => true));

$history = $GLOBALS['injector']->getInstance('Horde_History');

// Run through every tasklist.
$tasklists = $nag_shares->listAllShares();
foreach ($tasklists as $tasklist => $share) {
    $cli->writeln("Creating default histories for $tasklist ...");

    // List all tasks.
    $storage = Nag_Driver::singleton($tasklist);
    $storage->retrieve();
    $tasks = $storage->listTasks();

    foreach ($tasks as $taskId => $task) {
        $log = $history->getHistory('nag:' . $tasklist . ':' . $task['uid']);
        $created = false;
        foreach ($log as $entry) {
            if ($entry['action'] == 'add') {
                $created = true;
                break;
            }
        }
        if (!$created) {
            $history->log('nag:' . $tasklist . ':' . $task['uid'], array('action' => 'add'), true);
        }
    }
}

$cli->writeln("** Default histories successfully created ***");
