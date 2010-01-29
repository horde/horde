#!@php_bin@
<?php
/**
 * Query the contents of a user's cached IMAP data.
 *
 * Usage: query-imap-cache.php [--user=username] [--pass=password]
 *                             [--server=serverkey]
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * @author  Michael Slusarz <slusarz@curecanti.org>
 * @package IMP
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('imp', array('authentication' => 'none', 'cli' => true));

$cli = Horde_Cli::singleton();

$c = new Console_Getopt();
$argv = $c->readPHPArgv();
array_shift($argv);
$options = $c->getopt2($argv, '', array('user=', 'pass=', 'server='));
if (PEAR::isError($options)) {
    $cli->fatal("Invalid arguments.\n");
}

$pass = $server = $user = null;
foreach ($options[0] as $val) {
    switch ($val[0]) {
    case '--user':
        $user = $val[1];
        break;

    case '--pass':
        $pass = $val[1];
        break;

    case '--server':
        $server = $val[1];
        break;
    }
}

if (is_null($server)) {
    $keys = array_keys($imp_imap->loadServerConfig());

    /* Set first entry to 1, not 0. */
    array_unshift($keys, '');
    unset($keys[0]);

    while (is_null($server)) {
        $server = $cli->prompt('Server:', $keys);
    }
    $server = $keys[$server];
    $cli->message('Using server "' . $server . '"', 'cli.message');
}

while (is_null($user)) {
    $user = $cli->prompt('Username:');
    if (!strlen($user)) {
        $user = null;
    }
}
$cli->message('Using username "' . $user . '"', 'cli.message');

while (is_null($pass)) {
    $pass = $cli->passwordPrompt('Password:');
    if (!strlen($pass)) {
        $pass = null;
    }
}

$cli->writeln();

$ob = $imp_imap->createImapObject($user, $pass, $server);
if (!$ob) {
    $cli->fatal('Could not create Imap Client object.');
}

try {
    $ob->login();
    $cli->message('Successfully logged in to IMAP server.');

    $mboxes = $ob->listMailboxes('*', Horde_Imap_Client::MBOX_ALL, array('flat' => true, 'sort' => true));
    $cli->message('User mailbox count: ' . count($mboxes));
} catch (Horde_Imap_Client_Exception $e) {
    $cli->fatal('IMAP error: ' . $e->getMessage());
}

$opts = array(
    1 => 'Full Statistics',
    2 => 'Summary',
    3 => 'Exit'
);

while (true) {
    $cli->writeln();

    $action = $cli->prompt('Action:', $opts);
    switch ($action) {
    case 1:
    case 2:
        $mbox_list = array();
        $msg_cnt = $search_cnt = 0;

        foreach ($mboxes as $val) {
            if ($res = $ob->cache->get($val)) {
                $mbox_list[$val] = array(
                    'msgs' => count($res)
                );
                $msg_cnt += $mbox_list[$val]['msgs'];

                if ($res = $ob->cache->getMetaData($val, null, array('HICsearch'))) {
                    $mbox_list[$val]['search'] = count($res['HICsearch']) - 1;
                    $search_cnt += $mbox_list[$val]['search'];
                }
            }
        }

        $cli->writeln();
        $cli->message($cli->bold('Cached mailboxes:') . ' ' . count($mbox_list), 'cli.message');
        $cli->message($cli->bold('Cached messages:') . ' ' . $msg_cnt, 'cli.message');
        $cli->message($cli->bold('Cached searches:') . ' ' . $search_cnt, 'cli.message');

        if ($action == 1) {
            $cli->writeln();
            foreach ($mbox_list as $key => $val) {
                $cli->writeln($cli->indent(sprintf("%s (%d msgs, %d searches)", $key, $val['msgs'], $val['search'])));
            }
        }
        break;

    case 3:
        exit;
    }
}
