#!/usr/bin/php
<?php
/**
 * $Horde: agora/scripts/upgrades/2006-10-26_forums_table.php,v 1.6 2009/06/10 19:57:52 slusarz Exp $
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
require_once 'Horde/Cli.php';

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
$db = DB::connect($conf['sql']);
if ($db instanceof PEAR_Error) {
    var_dump($db);
    exit;
}
$db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);

/* Copy forums. */
$max_id = 0;
$forums = $db->getAll('SELECT * FROM horde_datatree WHERE group_uid LIKE \'agora.forums%\'', DB_FETCHMODE_ASSOC);
foreach ($forums as $forum) {
    if ($forum['datatree_id'] > $max_id) {
        $max_id = $forum['datatree_id'];
    }

    $result = $db->getAll('SELECT * FROM horde_datatree_attributes WHERE datatree_id = ?', array($forum['datatree_id']), DB_FETCHMODE_ASSOC);
    $attributes = array();
    foreach ($result as $attr) {
        $attributes[$attr['attribute_name'] . '_' . $attr['attribute_key']] = $attr['attribute_value'];
    }

    $sql = 'INSERT INTO agora_forums' .
        ' (forum_id, scope, active, forum_name, forum_description, author, forum_moderated, message_count, forum_attachments, forum_parent_id, count_views, thread_count)' .
        ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 0)';

    $values = array($forum['datatree_id'],
                    ($forum['group_uid'] == 'agora.forums') ? 'agora' : substr($forum['group_uid'], 13),
                    1,
                    $forum['datatree_name'],
                    $attributes['forum_description'],
                    $forum['user_uid'],
                    (int)$attributes['forum_moderated'],
                    $attributes['message_seq'],
                    (int)$attributes['forum_attachments']);

    $result = $db->query($sql, $values);
    if ($result instanceof PEAR_Error) {
        var_dump($result);
        exit;
    }
}

// Update DB sequence.
while ($db->nextId('agora_forums') < $max_id);
