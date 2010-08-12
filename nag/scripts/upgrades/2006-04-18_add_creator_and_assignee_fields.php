#!/usr/bin/php
<?php
/**
 * This script adds and fills the creator and assignee fields in the Nag task
 * table.
 *
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

// Do CLI checks and environment setup first.
require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('nag', array('authentication' => 'none', 'cli' => true));

if ($conf['storage']['driver'] != 'sql') {
    exit('No conversion for drivers other than SQL currently.');
}

$storage = Nag_Driver::singleton('');
$storage->initialize();
$db = $storage->_db;

// Add db fields. We don't check for success/failure here in case someone did
// this manually.
$result = $db->query('ALTER TABLE nag_tasks ADD task_creator VARCHAR(255)');
if (is_a($result, 'PEAR_Error')) {
    echo $result->toString() . "\n";
}
$result = $db->query('ALTER TABLE nag_tasks ADD task_assignee VARCHAR(255)');
if (is_a($result, 'PEAR_Error')) {
    echo $result->toString() . "\n";
}

// Run through every tasklist.
$sql = 'UPDATE nag_tasks SET task_creator = ? WHERE task_id = ? AND task_owner = ?';
$sth = $db->prepare($sql);
$tasklists = $nag_shares->listAllShares();
foreach ($tasklists as $tasklist => $share) {
    echo "Storing task creators for $tasklist ...\n";

    // List all tasks.
    $tasks = Nag::listTasks(null, null, null, $tasklist, 1);
    $owner = $share->get('owner');

    $tasks->reset();
    while ($task = $tasks->each()) {
        $values = array($owner, $task->id, $task->tasklist);
        $result = $db->execute($sth, $values);
        if ($result instanceof PEAR_Error) {
            throw new Nag_Exception($result);
        }
    }
}

echo "\n** Creators successfully stored. ***\n\n".
    "** Please manually apply NOT NULL constraint to 'task_creator' column. ***\n";
