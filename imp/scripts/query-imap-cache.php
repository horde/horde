#!/bin/env php
<?php
/**
 * Query the contents of a user's cached IMAP data.
 *
 * Usage: query-imap-cache.php [--user=username] [--pass=password]
 *                             [--server=serverkey]
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('imp', array('authentication' => 'none', 'cli' => true));

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

$imp_imap = $injector->getInstance('IMP_Imap')->getOb();

if (is_null($server)) {
    /* Set first entry to 1, not 0. */
    $sconfig = $slookup = array('');
    $i = 1;

    foreach ($imp_imap->loadServerConfig() as $key => $val) {
        $sconfig[$i] = $val['name'] . ' [' . $key . ']';
        $slookup[$i++] = $key;
    }

    unset($sconfig[0]);

    while (is_null($server)) {
        $server = $cli->prompt('Server:', $sconfig);
    }
    $server = $slookup[$server];
} else {
    $cli->message('Server: ' . $server);
}

if (is_null($user)) {
    while (is_null($user)) {
        $user = $cli->prompt('Username:');
        if (!strlen($user)) {
            $user = null;
        }
    }
} else {
    $cli->message('Username: ' . $user);
}

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

if ($ob->cache) {
    $cli->fatal('Caching not setup for this server.');
} else {
    $driver = $ob->getParam('cache');
    if (!isset($driver['cacheob'])) {
        $cli->message('Caching has been disabled for this server.', 'cli.error');
        exit;
    }
    $cli->message('Cache driver used: ' . get_class($driver['cacheob']));
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
    1 => 'Summary Statistics (All Mailboxes)',
    2 => 'Detailed Statistics (All Mailboxes)',
    3 => 'Detailed Statistics (Single Mailbox)',
    4 => 'Summary Statistics (Single UID)',
    5 => 'Detailed Statistics (Single UID)',
    6 => 'Expire All Mailboxes',
    7 => 'Expire Mailbox',
    8 => 'Expire specific UIDs',
    0 => 'Exit'
);

$use_lzf = (!empty($conf['cache']['compress']) && Horde_Util::extensionExists('lzf'));

while (true) {
    $cli->writeln();

    $action = $cli->prompt('Action:', $opts);
    switch ($action) {
    case 0:
        exit;

    case 1:
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
        $cli->message($cli->bold('Cached mailboxes:') . ' ' . count($mbox_list));
        $cli->message($cli->bold('Cached messages:') . ' ' . $msg_cnt);
        $cli->message($cli->bold('Cached searches:') . ' ' . $search_cnt);
        break;

    case 2:
    case 3:
        if ($action == 3) {
            $prompt = $cli->prompt('Mailbox:');
            if (!strlen($prompt)) {
                break;
            }
            $mbox_list = array($prompt);
        } else {
            $mbox_list = $mboxes;
        }

        foreach ($mbox_list as $mbox) {
            if ($res = $ob->cache->get($mbox)) {
                $cli->writeln();

                $cli->message('Mailbox: ' . $cli->green($mbox));
                $cli->message('Cached messages: ' . count($res) . ' [' . $ob->utils->toSequenceString($res) . ']');

                $lzf_size = $total_size = 0;
                foreach ($ob->cache->get($mbox, $res, null) as $val) {
                    $data = serialize($val);
                    $total_size += strlen($data);
                    if ($use_lzf) {
                        $lzf_size += strlen(lzf_compress($data));
                    }
                }

                $cli->message('Approximate size (bytes): ' . $total_size);
                if (!empty($lzf_size)) {
                    $cli->message('Approximate size - LZF (bytes): ' . $lzf_size . ' [' . $cli->red(100 - round($lzf_size / $total_size * 100, 1) . '% savings') . ']');
                }

                if ($res = $ob->cache->getMetaData($mbox)) {
                    try {
                        $status = $ob->status($mbox, Horde_Imap_Client::STATUS_UIDVALIDITY | Horde_Imap_Client::STATUS_HIGHESTMODSEQ);
                    } catch (Horde_Imap_Client_Exception $e) {
                        $cli->writeln();
                        $cli->message('IMAP error: ' . $e->getMessage(), 'cli.error');
                    }
                    $cli->message('UIDVALIDITY: ' . $res['uidvalid'] . ' [Server value: ' . (($status['uidvalidity'] != $res['uidvalid']) ? $cli->red($status['uidvalidity']) : $status['uidvalidity']) . ']');
                    if (isset($res['HICmodseq'])) {
                        $cli->message('Highest MODSEQ seen: ' . $res['HICmodseq'] . ' [Server value: ' . (($status['highestmodseq'] != $res['HICmodseq']) ? $cli->red($status['highestmodseq']) : $status['highestmodseq']) . ']');
                    }
                    if (isset($res['HICsearch'])) {
                        $cli->message('Cached searches: ' . (count($res['HICsearch']) - 1));
                    }
                }
            } elseif ($action == 3) {
                $cli->writeln();
                $cli->message(sprintf('No cache information found for "%s".', $mbox), 'cli.error');
            }
        }
        break;

    case 4:
    case 5:
        $mbox = $cli->prompt('Mailbox:');
        if (!strlen($mbox)) {
            break;
        }
        $uid = $cli->prompt('UID:');
        if (!strlen($uid)) {
            break;
        }
        if ($res = $ob->cache->get($mbox, array($uid), null)) {
            $cli->writeln();
            $cli->message(sprintf('Message information [%s:%d]', $mbox, $uid));
            $cli->message('Cached fields: ' . implode(', ', array_keys($res[$uid])));

            $data = serialize($res[$uid]);
            $cli->message('Approximate size (bytes): ' . strlen($data));

            if ($use_lzf) {
                $lzf_size += strlen(lzf_compress($data));
                $cli->message('Approximate size - LZF (bytes): ' . $lzf_size . ' [' . $cli->red(100 -round($lzf_size / $total_size * 100, 1) . '% savings') . ']');
            }

            if ($action == 5) {
                $cli->writeln();
                $cli->writeln(print_r($res[$uid], true));
            }
        } else {
            $cli->writeln();
            $cli->message(sprintf('No cache information found for "%s:%d".', $mbox, $uid), 'cli.error');
        }
        break;

    case 6:
    case 7:
        if ($action == 7) {
            $prompt = $cli->prompt('Mailbox:');
            if (!strlen($prompt)) {
                break;
            }
            $mbox_list = array($prompt);
        } else {
            $mbox_list = $mboxes;
        }

        if ($cli->prompt('Delete mailbox cache(s)?', array('1' => 'No', '2' => 'Yes'), 1) == 2) {
            $cli->writeln();
            foreach ($mbox_list as $val) {
                $ob->cache->deleteMailbox($val);
                $cli->message('Deleted cache: ' . $val, 'cli.success');
            }
        }
        break;

    case 8:
        $mbox = $cli->prompt('Mailbox:');
        if (!strlen($mbox)) {
            break;
        }
        $uids = $cli->prompt('UIDs (IMAP sequence string format):');
        if (!strlen($uids)) {
            break;
        }
        $uids = $ob->utils->fromSequenceString($uids);
        if (empty($uids)) {
            $cli->writeln();
            $cli->message('No UIDs found', 'cli.error');
            break;
        }

        $cli->writeln();

        try {
            $ob->cache->deleteMsgs($mbox, $uids);
            $cli->message(sprintf('Deleted %d UIDs from cache.', count($uids)), 'cli.success');
        } catch (Horde_Imap_Client_Exception $e) {
            $cli->writeln();
            $cli->message('Failed deleting UIDs. Error: ' . $e->getMessage(), 'cli.error');
        }
        break;
    }
}
