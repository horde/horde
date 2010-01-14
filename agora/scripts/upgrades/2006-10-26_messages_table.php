#!/usr/bin/php
<?php
/**
 * TODO: Attachments
 *
 * Script to migrate forums from datatree agora_forms table
 */

// No need for auth.
define('AUTH_HANDLER', true);

// Find the base file paths.
define('HORDE_BASE', dirname(__FILE__) . '/../../..');
define('AGORA_BASE', dirname(__FILE__) . '/../..');

// Do CLI checks and environment setup first.
require_once HORDE_BASE . '/lib/core.php';

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment - make sure there's no time limit, init
// some variables, etc.
Horde_Cli::init();

require_once 'DB.php';
require_once HORDE_BASE . '/lib/base.php';

/* Open the database. */
$db = &DB::connect($conf['sql']);
if ($db instanceof PEAR_Error) {
    var_dump($db);
    exit;
}
$db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);

/* Get messages. */
$sql = 'SELECT DISTINCT d.datatree_id, d.datatree_parents FROM horde_datatree d, horde_datatree_attributes a '
     . ' WHERE d.group_uid = \'agora.threads\' AND d.datatree_id = a.datatree_id';
$threads = $db->getAssoc($sql);
if ($threads instanceof PEAR_Error) {
    var_dump($threads);
    exit;
}

/* SQL mesage insert statement */
$sql = 'INSERT INTO agora_messages  '
     . '(message_id, forum_id, parents, message_thread, message_author, message_subject, body, message_timestamp, attachments, ip)'
     . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, \'\') ';

foreach ($threads as $id => $parents) {
    $result = $db->getAll('SELECT * FROM horde_datatree_attributes WHERE datatree_id = ?', array($id), DB_FETCHMODE_ASSOC);
    $attributes = array();
    foreach ($result as $attr) {
        $attributes[$attr['attribute_key']] = $attr['attribute_value'];
    }

    // Remove main thread
    $thread = explode(':', $parents);
    unset($thread[1]);

    $params = array($id);
    $params[] = $attributes['forum_id'];
    $params[] = implode(':', $thread);
    $params[] = count($thread) > 1 ? $thread[2] : 0;
    $params[] = $attributes['author'];
    $params[] = $attributes['subject'];
    $params[] = $attributes['body'];
    $params[] = $attributes['timestamp'];

    $result = $db->query($sql, $params);
    if ($result instanceof PEAR_Error) {
        var_dump($result);
        exit;
    }
}
