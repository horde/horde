#!/usr/bin/env php
<?php
/**
 * Move basic messsage from phorum5 to agora preserving message ids
 *
 * Usage example:
 *      Move fist 1000 messages from phorum ID 5 to agora ID 1
 *      php phorum2agora.php -p 5 -a 1 -t p5_messages -f 0 -c 1000
 *
 * To move all phorum you can use loop semgents like:
 *  for i in 0 1 2 3 4 5 6 7; do
 *      php phorum2agora.php -p 7 -a 8 -f $i -c 1000;
 *  done
 *
 * TODO: Moderation, attachments, ID swaping
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('agora', array('authentication' => 'none', 'cli' => true));

/* Open Agora database. */
$db_agora = $db_phorum = $injector->getInstance('Horde_Core_Factory_DbPear')->create();

// We accept the user name on the command-line.
$ret = Console_Getopt::getopt(Console_Getopt::readPHPArgv(), 'h:p:a:t:f:c:',
                              array('help', 'phorum_id=', 'agora_id=', 'phorum_table=', 'from=', 'count='));

if ($ret instanceof PEAR_Error) {
    $error = _("Couldn't read command-line options.");
    Horde::logMessage($error, 'DEBUG');
    $cli->fatal($error);
}

// Show help and exit if no arguments were set.
list($opts, $args) = $ret;
if (!$opts) {
    showHelp();
    exit;
}

foreach ($opts as $opt) {
    list($optName, $optValue) = $opt;
    switch ($optName) {
    case 'p':
    case '--phorum_id':
        $phorum_id = $optValue;
        break;

    case 'a':
    case '--agora_id':
        $agora_id = $optValue;
        break;

    case 't':
    case '--phorum_table':
        $phorum_table = $optValue;
        break;

    case 'f':
    case '--from':
        $from = $optValue;
        break;

    case 'c':
    case '--count':
        $count = $optValue;
        break;

    case 'h':
    case '--help':
        showHelp();
        exit;
    }
}

/* Set up defaults */
if (empty($phorum_table)) {
    $phorum_table = 'p5_messages';
}
if (empty($from)) {
    $cfrom = 0;
}
if (empty($count)) {
    $cfrom = 100;
}

/* Get phorum messages data */
$sql = 'SELECT message_id, thread, author, subject, body, '
     . ' datestamp, ip, viewcount, closed  '
     . ' FROM ' . $phorum_table . ' WHERE forum_id = ? ORDER BY message_id ASC';
$sql = $db_phorum->modifyLimitQuery($sql, $from * $count, $count);
$messages = $db_phorum->getAll($sql, array($phorum_id), DB_FETCHMODE_ASSOC);
if ($messages instanceof PEAR_Error) {
    echo $messages->getMessage() . ': ' . $messages->getDebugInfo() . "\n";
    exit;
}

/* Display some info of messages imported */
$msgs = $db_phorum->getOne('SELECT COUNT(*) FROM ' . $phorum_table . ' WHERE forum_id = ? ', array($phorum_id));
echo 'Agora forum ID: ' . $agora_id . "\n";
echo 'Phorum forum ID: ' . $phorum_id . "\n";
echo 'Processing ' . count($messages) . ' (' . ($from * $count) . '/' . $count . ') messages of ' . $msgs . ' in phorum' . "\n";

/* SQL mesage insert statement */
$sql = 'INSERT INTO agora_messages  '
     . '(message_id, forum_id, parents, message_thread, message_author, message_subject, body, '
     .  'message_timestamp, attachments, ip, view_count, locked)'
     . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ';

// Delete old messages * DO NOT USE THIS. IS ONLY FOR TESTING
// $b_agora->query('TRUNCATE TABLE agora_messages');
// $db_agora->query('TRUNCATE TABLE agora_messages_seq');

/* Start inserting */
foreach ($messages as $message) {
    $params = array();
    $params[] = $message['message_id'];
    $params[] = $agora_id;
    $params[] = makeParents($message['thread'], $message['message_id']);
    $params[] = ($message['thread'] == $message['message_id']) ? 0 : $message['thread'];
    $params[] = $message['author'];
    $params[] = Horde_String::convertCharset($message['subject'], $conf['sql']['charset'], 'UTF-8');
    $params[] = Horde_String::convertCharset($message['body'], $conf['sql']['charset'], 'UTF-8');
    $params[] = $message['datestamp'];
    $params[] = 0;
    $params[] = $message['ip'];
    $params[] = $message['viewcount'];
    $params[] = $message['closed'];
    $result = $db_agora->query($sql, $params);
    if ($result instanceof PEAR_Error) {
        echo $result->getMessage() . ': ' . $result->getDebugInfo() . "\n";
        exit;
    }
}

/* Update message sql */
$max = $db_agora->getOne('SELECT MAX(message_id) FROM agora_messages');
$db_agora->query('UPDATE agora_messages_seq SET ID = ?' , array($max));

/* Clean cache */
$forums = Agora_Messages::singleton('agora');
@$forums->cleanCache($agora_id);

echo "done\n";

/* Constuct message parents */
function makeParents($thread_id, $message_id)
{
    if (intval($thread_id) < 1) {
        return '';
    }

    $sql = 'SELECT message_id FROM ' . $GLOBALS['phorum_table']
         . ' WHERE thread = ? AND message_id < ? ORDER BY message_id ASC';

    $mesages = $GLOBALS['db_phorum']->getCol($sql, 0, array($thread_id, $message_id));
    if ($mesages instanceof PEAR_Error) {
        echo $mesages->getMessage() . ': ' . $mesages->getDebugInfo() . "\n";
        exit;
    }

    if (empty($mesages)) {
        return '';
    } else {
        return ':' . implode(':', $mesages);
    }
}

/**
 * Show the command line arguments that the script accepts.
 */
function showHelp()
{
    global $cli;

    $cli->writeln(sprintf(_("Usage: %s [OPTIONS]..."), basename(__FILE__)));
    $cli->writeln();
    $cli->writeln(_("Mandatory arguments to long options are mandatory for short options too."));
    $cli->writeln();
    $cli->writeln(_("-h, --help                   Show this help"));
    $cli->writeln(_("-p, --phorum_id[=pid]        Phorum forum id to read message from"));
    $cli->writeln(_("-a, --agora_id[=aid]         Agora forum id to save message to"));
    $cli->writeln(_("-t, --phorum_table[=table]   Phorum messages tablename"));
    $cli->writeln(_("-f, --from[=offset]          Offset from where to start to read messages"));
    $cli->writeln(_("-c, --count[=messages]       Number of messages to move at once"));
    $cli->writeln();
}
