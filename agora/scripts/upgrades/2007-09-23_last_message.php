#!/usr/bin/php
<?php
/**
 * Upgrades last messages
 *
 * $Horde: agora/scripts/upgrades/2007-09-23_last_message.php,v 1.6 2009/06/10 19:57:52 slusarz Exp $
 *
 */

// No need for auth.
define('AUTH_HANDLER', true);

// Find the base file paths.
define('AGORA_BASE', dirname(dirname(dirname(__FILE__))));
define('HORDE_BASE', dirname(AGORA_BASE));

// Do CLI checks and environment setup first.
require_once HORDE_BASE . '/lib/core.php';

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment.
Horde_Cli::init();
$cli = &Horde_Cli::singleton();

require_once 'MDB2.php';
require_once HORDE_BASE . '/lib/base.php';
require_once AGORA_BASE . '/lib/Messages.php';

/* Open the database. */
$params = Horde::getDriverConfig('storage', 'sql');
Horde::assertDriverConfig($params, 'storage', array('phptype', 'charset'));

unset($params['charset']);
$mdb = MDB2::factory($params);
if ($mdb instanceof PEAR_Error) {
    var_dump($mdb);
    exit;
}

$mdb->loadModule('Extended');

/* Update last forum messages */
$forums = $mdb->getCol('SELECT forum_id FROM agora_forums WHERE last_message_id = 0 ORDER BY forum_id');
if ($forums instanceof PEAR_Error) {
    var_dump($forums);
    exit;
}

foreach ($forums as $forum_id) {
    $sql = 'SELECT message_id, message_author, message_timestamp FROM agora_messages' .
            ' WHERE forum_id = ' . (int)$forum_id . ' ORDER BY message_id DESC';
    $mdb->setLimit(1, 0);
    $last = $mdb->getRow($sql);
    if (empty($last)) {
        continue;
    }

    list($message_id, $message_author, $message_timestamp) = $last;

    $sql = 'UPDATE agora_forums' .
        ' SET last_message_id = ?, last_message_author = ?, last_message_timestamp = ? WHERE forum_id = ?';

    $statement = $mdb->prepare($sql);
    $statement->execute(array($message_id, $message_author, $message_timestamp, $forum_id));

    echo "Forums: $forum_id \n";

}

/* Update last messages in threads */
$mdb->setLimit(500, 0);
$threads = $mdb->getCol('SELECT message_id FROM agora_messages WHERE message_thread = 0 AND last_message_id = 0 ORDER BY message_id');
if ($threads instanceof PEAR_Error) {
    var_dump($threads);
    exit;
}

foreach ($threads as $thread_id) {
    $sql = 'SELECT message_id, message_author, message_timestamp FROM agora_messages' .
            ' WHERE message_thread = ' . (int)$thread_id . ' ORDER BY message_id DESC';
    $mdb->setLimit(1, 0);
    $last = $mdb->getRow($sql);
    if (empty($last)) {
        continue;
    }

    list($message_id, $message_author, $message_timestamp) = $last;

    $sql = 'UPDATE agora_messages' .
        ' SET last_message_id = ?, last_message_author = ?, message_modifystamp = ? WHERE message_id = ?';

    $statement = $mdb->prepare($sql);
    $statement->execute(array($message_id, $message_author, $message_timestamp, $thread_id));

    echo "Thread: $thread_id \n";
}

echo "done\n";
