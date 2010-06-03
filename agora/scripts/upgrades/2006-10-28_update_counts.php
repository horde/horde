#!/usr/bin/php
<?php
/**
 * Upgrades counters
 *
 * TODO Get rid of moderation
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('agora', array('authentication' => 'none', 'cli' => true));

/* Open the database. */
$db = $injector->getInstance('Horde_Db_Pear')->getDb();

/* Get threads. */
$sql = 'SELECT message_id, forum_id FROM agora_messages WHERE message_thread = ?';
$threads = $db->getAssoc($sql, false, array(0));
if ($threads instanceof PEAR_Error) {
    var_dump($threads);
    exit;
}

/* Reset message count */
$db->query('UPDATE agora_messages SET message_seq = 0');
echo 'Processing ' . count($threads) . ' threads' . "\n";

$sql = 'SELECT message_thread, COUNT(*) FROM agora_messages WHERE message_thread > ? GROUP BY message_thread';
$counts = $db->getAssoc($sql, false, array(0));
if ($counts instanceof PEAR_Error) {
    var_dump($counts);
    exit;
}

/* Update the number of messages in thread */
$forums = array();
foreach ($threads as $message_id => $forum_id) {
    if (!isset($counts[$message_id])) {
        continue;
    }
    $count = $counts[$message_id];
    $db->query('UPDATE agora_messages SET message_seq = ? WHERE message_id = ?', array($count, $message_id));

    if (!isset($forums[$forum_id])) {
        $forums[$forum_id] = array('threads' => 0,
                                   'messages' => 0,
                                   'forum_id' => $forum_id);
    }

    $forums[$forum_id]['threads'] += 1;
    $forums[$forum_id]['messages'] += ($count + 1);
}

echo "Update forums \n";

/* Update thread and message count for forums */
$db->query('UPDATE agora_forums SET thread_count = 0, message_count = 0');
$sth = $db->prepare('UPDATE agora_forums SET thread_count = ?, message_count = ? WHERE forum_id = ?');
$result = $db->executeMultiple($sth, $forums);
if ($result instanceof PEAR_Error) {
    var_dump($result);
    exit;
}

echo "Clean cache \n";

/* Clean cache */
$forums = Agora_Messages::singleton('agora');
foreach ($forums->getForums(0, false) as $forum_id) {
    @$forums->cleanCache($forum_id);
}

echo "done\n";
