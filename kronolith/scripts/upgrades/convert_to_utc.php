#!/usr/bin/env php
<?php
/**
 * This script converts all dates from the user's timezone to UTC.
 */

/* Set up the CLI environment. */
require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('kronolith', array('cli' => true));

/* Prepare DB stuff. */
PEAR::pushErrorHandling(PEAR_ERROR_CALLBACK, create_function('$e', 'echo $e->toString()."\n";exit;'));
$db = $injector->getInstance('Horde_Core_Factory_DbPear')->create();
$result = $db->query('SELECT event_title, event_id, event_creator_id, event_start, event_end, event_allday, event_recurenddate FROM ' . $conf['calendar']['params']['table'] . ' ORDER BY event_creator_id');
$stmt = $db->prepare('UPDATE kronolith_events SET event_start = ?, event_end = ?, event_recurenddate = ? WHERE event_id = ?');

/* Confirm changes. */
if (!isset($argv[1]) || $argv[1] != '--yes') {
    $answer = $cli->prompt('Running this script will convert all existing events to UTC. This conversion is not reversible. Is this what you want?', array('y' => 'Yes', 'n' => 'No'));
    if ($answer != 'y') {
        exit;
    }
}

/* Loop through all events. */
$creator = null;
$utc = new DateTimeZone('UTC');
echo "Converting events for:\n";
while ($row = $result->fetchRow(DB_FETCHMODE_ASSOC)) {
    if ($row['event_allday']) {
        continue;
    }
    if ($row['event_creator_id'] != $creator) {
        if (!is_null($creator)) {
            echo "$count\n";
        }
        $prefs = $injector->getInstance('Horde_Core_Factory_Prefs')->create('horde', array(
            'cache' => false,
            'user' => $row['event_creator_id']
        ));

        $timezone = $prefs->getValue('timezone');
        if (empty($timezone)) {
            $timezone = date_default_timezone_get();
        }
        $timezone = new DateTimeZone($timezone);
        $creator = $row['event_creator_id'];
        $count = 0;
        echo $creator . ': ';
    }
    $start = new DateTime($row['event_start'], $timezone);
    $start->setTimezone($utc);
    $end = new DateTime($row['event_end'], $timezone);
    $end->setTimezone($utc);
    $recur_end = new DateTime($row['event_recurenddate'], $timezone);
    $recur_end->setTimezone($utc);
    $db->execute($stmt, array($start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $recur_end->format('Y-m-d H:i:s'), $row['event_id']));
    $count++;
}
echo "$count\n";
