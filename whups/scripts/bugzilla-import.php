#!/usr/bin/env php
<?php
/**
 * Bugzilla Import Script.
 *
 * This script imports the contents of an existing Bugzilla bug database into
 * a Whups database.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * @author Jon Parise <jon@horde.org>
 */

/* CONFIGURATION */
$BUGZILLA_DSN = 'mysql://root:password@localhost/bugzilla';
$BUGZILLA_STATES = array('NEW', 'ASSIGNED', 'RESOLVED', 'REOPENED', 'CLOSED');
$BUGZILLA_BUG_TYPE = array('Bug', 'Imported Bugzilla Bug');
$BUGZILLA_PRIORITIES = array('P1', 'P2', 'P3', 'P4', 'P5');
/* END CONFIGURATION */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('whups', array('authentication' => 'none', 'cli' => true));

function sectionHeader($text)
{
    global $cli;
    $cli->writeln($cli->bold($text));
}

function error($text, $error = null)
{
    global $cli;

    if (is_a($error, 'PEAR_Error')) {
        $text .= ' (' . $error->getMessage() . ')';
    }
    $cli->message($text, 'cli.error');
}

function success($text)
{
    global $cli;

    $cli->message($text, 'cli.success');
}

function info($text)
{
    global $cli;

    $cli->message($text, 'cli.message');
}

/* Connect to the Bugzilla database. */
$bugzilla = DB::connect($BUGZILLA_DSN);
if ($bugzilla instanceof PEAR_Error) {
    error('Failed to connect to Bugzilla database', $bugzilla);
    exit;
}

// Set DB portability options
switch ($bugzilla->phptype) {
case 'mssql':
    $bugzilla->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
    break;
default:
    $bugzilla->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
}

$bugzilla->setFetchMode(DB_FETCHMODE_ASSOC);

sectionHeader('Creating Types');
$type = $whups_driver->addType($BUGZILLA_BUG_TYPE[0], $BUGZILLA_BUG_TYPE[1]);
if (is_a($type, 'PEAR_Error')) {
    error("Failed to add '" . $BUGZILLA_BUG_TYPE[0] . "' type", $type);
    exit;
}
info("Created '" . $BUGZILLA_BUG_TYPE[0] . "' type");
$cli->writeln();

sectionHeader('Creating States');
$states = array();
foreach ($BUGZILLA_STATES as $state) {
    $result = $whups_driver->addState($type, $state, "Bugzilla - $state", $state);
    if (is_a($result, 'PEAR_Error')) {
        error("Failed to add '$state' state", $result);
        continue;
    }
    $states[$state] = $result;
    info("Created '$state' state");
}
$cli->writeln();

sectionHeader('Creating Priorities');
$priorities = array();
foreach ($BUGZILLA_PRIORITIES as $priority) {
    $result = $whups_driver->addPriority($type, $priority, "Bugzilla - $priority");
    if (is_a($result, 'PEAR_Error')) {
        error("Failed to add '$priority' priority", $result);
        continue;
    }
    $priorities[$priority] = $result;
    info("Created '$priority' priority");
}
$cli->writeln();

/* Create a mapping of products and components. */
$components = array();

sectionHeader('Importing Components');
$res = $bugzilla->query('select value, program, description from components');
while ($row = $res->fetchRow()) {
    $result = $whups_driver->addQueue($row['value'], $row['description']);
    if (is_a($result, 'PEAR_Error')) {
        error('Failed to add queue: ' . $row['value'], $result);
        continue;
    }

    /* Set the queue's parameters. */
    $whups_driver->updateQueue($result,
                        $row['value'],
                        $row['description'],
                        array($type),
                        false);

    /* Add this component to the map. */
    $components[($row['program'])][] = $row['value'];

    success('Created queue: ' . $row['value']);
}
$cli->writeln();

/* Get a mapping of queue IDs to queue names. */
$queues = $whups_driver->getQueues();

/* Maintain a mapping of version names. */
$versions = array();

sectionHeader('Importing Versions');
$res = $bugzilla->query('select value, program from versions');
while ($row = $res->fetchRow()) {
    /* Bugzilla manages versions on a per-product basis.  Whups manages
     * versions on a per-queue (i.e., per-component) basis.  Add this
     * product's versions to each each of its components. */
    foreach ($components[($row['program'])] as $component) {
        $queueID = array_search($component, $queues);

        if ($queueID === false) {
            error('Unknown queue: ' . $component);
            continue;
        }

        $result = $whups_driver->addVersion($queueID, $row['value'], '', true, 0);
        if (is_a($result, 'PEAR_Error')) {
            error('Failed to add version: ' . $row['value'], $result);
            continue;
        }

        $versions[$queueID][($row['value'])] = $result;
        success('Added version: ' . $row['value'] . " ($component)");
    }
}
$cli->writeln();

/* Maintain a mapping of Bugzilla userid's to email addresses. */
$profiles = array();

sectionHeader('Loading Profiles');
$res = $bugzilla->query('select userid, login_name from profiles');
while ($row = $res->fetchRow()) {
    $profiles[($row['userid'])] = $row['login_name'];
}
info('Loaded ' . count($profiles) . ' profiles');
$cli->writeln();

sectionHeader('Importing Bugs');
$res = $bugzilla->query('select * from bugs');
while ($row = $res->fetchRow()) {
    $info = array();

    $info['queue'] = array_search($row['component'], $queues);
    if ($info['queue'] === false) {
        error('Unknown queue: ' . $row['component']);
        continue;
    }

    $info['version'] = null;
    if (isset($versions[($info['queue'])][($row['version'])])) {
        $info['version'] = $versions[($info['queue'])][($row['version'])];
    }

    $info['type'] = $type;

    if (!isset($priorities[($row['priority'])])) {
        error('Unknown priority: ' . $row['priority']);
        continue;
    }
    $info['priority'] = $priorities[($row['priority'])];

    if (!isset($states[($row['bug_status'])])) {
        error('Unknown state: ' . $row['bug_status']);
        continue;
    }
    $info['state'] = $states[($row['bug_status'])];

    if (isset($profiles[($row['reporter'])])) {
        $info['user_email'] = $profiles[($row['reporter'])];
    }

    $info['summary'] = htmlspecialchars($row['short_desc']);
    $info['comment'] = $row['long_desc'];

    $result = $whups_driver->addTicket($info);
    if (is_a($result, 'PEAR_Error')) {
        error('Failed to add ticket', $result);
        continue;
    }

    success('Added new ticket ' . $result . ' to ' . $row['component']);
}
$cli->writeln();
