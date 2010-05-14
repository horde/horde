#!/usr/bin/php
<?php
/**
 * Script to migrate forums from datatree agora_forms table
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('agora', array('authentication' => 'none', 'cli' => true));

/* Open the database. */
$db = $injector->getInstance('Horde_Db_Pear')->getOb();

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
