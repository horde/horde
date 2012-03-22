<?php
/**
 * Test script for the Horde/Imap_Client package.
 *
 * Usage:
 *   test_client.php [[username] [[password] [[IMAP URL] [driver]]]]
 *
 * Username/password/hostspec on the command line will override the $params
 * values.
 * Driver on the command line will override the $driver value.
 *
 * TODO:
 *   + Test for 'charset' searching
 *   + setQuota(), getQuota(), getQuotaRoot()
 *   + setACL(), listACLRights(), getMyACLRights()
 *   + setLanguage()
 *   + setComparator()
 *   + RFC 4551 (CONDSTORE) related functions
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */

/** Configuration **/
$driver = 'Socket';
$params = array(
    'username' => '',
    'password' => '',
    'hostspec' => '',
    'port' => '',
    'secure' => '', // empty, 'ssl', or 'tls'
    'debug' => 'php://output'
);

$cache_params = array(
    'driver' => 'file', // REQUIRED - Horde_Cache driver.
    'driver_params' => array( // REQUIRED
        'dir' => '/tmp',
        'prefix' => 'iclient'
    ),
    'compress' => null, // false, 'gzip', or 'lzf'
    'lifetime' => null, // (integer) Lifetime, in seconds
    'slicesize' => null // (integer) Slicesize
);

// Test mailbox names (without namespace information)
$test_mbox = 'TestMailboxTest';
$test_mbox_utf8 = 'TestMailboxTest1è';
/** End Configuration **/

require_once 'Horde/Autoloader.php';
$currdir = __DIR__;

/* Check for Horde_Cache::. */
if (@include_once 'Horde/Cache.php') {
    $horde_cache = true;
    print "Using Horde_Imap_Client_Cache (driver: " . $cache_params['driver'] . ").\n\n";
    $params['cache'] = $cache_params;
} else {
    $horde_cache = false;
}

if (!empty($argv[1])) {
    $params['username'] = $argv[1];
}
if (empty($params['username'])) {
    exit("Need username. Exiting.\n");
}

if (empty($params['password'])) {
    $params['password'] = $argv[2];
}
if (empty($params['password'])) {
    exit("Need password. Exiting.\n");
}

$imap_utils = new Horde_Imap_Client_Utils();
if (!empty($argv[3])) {
    $parseurl = $imap_utils->parseUrl($argv[3]);
    if ($parseurl === false) {
        exit("Bad URL. Exiting.\n");
    }
    $params = array_merge($params, $imap_utils->parseUrl($argv[3]));
}

if (!empty($argv[4])) {
    $driver = $argv[4];
}

function exception_handler($e) {
    print "\n=====================================\n" .
          'UNCAUGHT EXCEPTION: ' . $e->getMessage() .
          "\n=====================================\n";
}
set_exception_handler('exception_handler');

if (@include_once 'Benchmark/Timer.php') {
    $timer = new Benchmark_Timer();
    $timer->start();
}

if (require_once 'Horde/Secret.php') {
    $params['encryptKey'] = uniqid();
}

// Add an ID field to send to server (ID extension)
$params['id'] = array('name' => 'Horde_Imap_Client test program');

$imap_client = Horde_Imap_Client::factory($driver, $params);
if ($driver == 'Socket_Pop3') {
    $pop3 = true;
    $test_mbox = $test_mbox_utf8 = 'INBOX';

    print "============================================================\n" .
          "NOTE: Due to the absence of an APPEND command in POP3, the test\n" .
          "script is unable to build a test mailbox with messages. Various\n" .
          "status and fetching commands will therefore either not be\n" .
          "successful or else not match up with the expected output.\n" .
          "============================================================\n\n";
} else {
    $pop3 = false;
}

$use_imapproxy = false;
print "CAPABILITY listing:\n";
try {
    print_r($imap_client->capability());
    $use_imapproxy = $imap_client->queryCapability('XIMAPPROXY');
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
}

print "\nLOGIN:\n";
try {
    $imap_client->login();
    print "Login: OK.\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    exit("Login: FAILED. EXITING.\n");
}

print "\nUsing a secure connection: " . ($imap_client->isSecureConnection() ? 'YES' : 'NO') . "\n";

print "\nID information from server:\n";
try {
    print_r($imap_client->getID());
    print "ID information: OK.\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "ID information: FAILED.\n";
}

print "\nLanguage information from server:\n";
try {
    print_r($imap_client->getLanguage(true));
    $lang = $imap_client->getLanguage();
    print "Language information (" . ($lang ? $lang : 'NONE') . "): OK.\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Language information: FAILED.\n";
}

print "\nComparator information from server:\n";
try {
    print_r($imap_client->getComparator());
    print "Comparator information: OK.\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Comparator information: FAILED.\n";
}

print "\nNAMESPACES:\n";
try {
    $namespaces = $imap_client->getNamespaces();
    print_r($namespaces);
    print "Namespaces: OK.\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Namespaces: FAILED.\n";
    $namespaces = array();
}

// Tack on namespace information to folder names.
$base_ns = reset($namespaces);
if (empty($base_ns['name'])) {
    $ns_prefix = '';
} else {
    $ns_prefix = rtrim($base_ns['name'], $base_ns['delimiter']) . $base_ns['delimiter'];
}
$test_mbox = $ns_prefix . $test_mbox;
$test_mbox_utf8 = $ns_prefix . $test_mbox_utf8;

print "\nOpen INBOX read-only, read-write, and auto.\n";
try {
    $imap_client->openMailbox('INBOX', Horde_Imap_Client::OPEN_READONLY);
    print "Read-only: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Read-only: FAILED\n";
}

try {
    $imap_client->openMailbox('INBOX', Horde_Imap_Client::OPEN_READWRITE);
    print "Read-write: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Read-write: FAILED\n";
}

try {
    $imap_client->openMailbox('INBOX', Horde_Imap_Client::OPEN_AUTO);
    print "Auto: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Auto: FAILED\n";
}

print "\nCurrent mailbox information:\n";
print_r($imap_client->currentMailbox());

print "\nCreating mailbox " . $test_mbox . ".\n";
try {
    $imap_client->createMailbox($test_mbox);
    print "Creating: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Creating: FAILED\n";
}

print "\nSubscribing to mailbox " . $test_mbox . ".\n";
try {
    $imap_client->subscribeMailbox($test_mbox, true);
    print "Subscribing: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Subscribing: FAILED\n";
}

print "\nUnsubscribing to mailbox " . $test_mbox . ".\n";
try {
    $imap_client->subscribeMailbox($test_mbox, false);
    print "Unsubscribing: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Unsubscribing: FAILED\n";
}

print "\nRenaming mailbox " . $test_mbox . " to " . $test_mbox_utf8 . ".\n";
try {
    $imap_client->renameMailbox($test_mbox, $test_mbox_utf8);
    print "Renaming: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Renaming: FAILED\n";
}

print "\nDeleting mailbox " . $test_mbox_utf8 . ".\n";
try {
    $imap_client->deleteMailbox($test_mbox_utf8);
    print "Deleting: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Deleting: FAILED\n";
}

print "\nDeleting (non-existent) mailbox " . $test_mbox_utf8 . ".\n";
try {
    $imap_client->deleteMailbox($test_mbox_utf8);
    print "Failed deletion: FAILED\n";
} catch (Horde_Imap_Client_Exception $e) {
    print "Deleting: OK\n";
    print 'Error returned from IMAP server: ' . $e->getMessage() . "\n";
}

print "\nListing all mailboxes in base level (flat format).\n";
print_r($imap_client->listMailboxes('%', Horde_Imap_Client::MBOX_ALL, array('flat' => true)));

print "\nListing all mailboxes (flat format).\n";
print_r($imap_client->listMailboxes('*', Horde_Imap_Client::MBOX_ALL, array('flat' => true)));

print "\nListing subscribed mailboxes (flat format, in UTF-8 encoding).\n";
print_r($imap_client->listMailboxes('*', Horde_Imap_Client::MBOX_SUBSCRIBED, array('flat' => true, 'utf8' => true)));

print "\nListing unsubscribed mailboxes in base level (with attribute and delimiter information).\n";
print_r($imap_client->listMailboxes('%', Horde_Imap_Client::MBOX_UNSUBSCRIBED, array('attributes' => true, 'delimiter' => true)));

print "\nAll status information for INBOX.\n";
print_r($imap_client->status('INBOX', Horde_Imap_Client::STATUS_ALL));

print "\nOnly UIDNEXT status information for INBOX.\n";
print_r($imap_client->status('INBOX', Horde_Imap_Client::STATUS_UIDNEXT));

print "\nOnly FIRSTUNSEEN, FLAGS, PERMFLAGS, HIGHESTMODSEQ, and UIDNOTSTICKY status information for INBOX.\n";
try {
    print_r($imap_client->status('INBOX', Horde_Imap_Client::STATUS_FIRSTUNSEEN | Horde_Imap_Client::STATUS_FLAGS | Horde_Imap_Client::STATUS_PERMFLAGS | Horde_Imap_Client::STATUS_HIGHESTMODSEQ | Horde_Imap_Client::STATUS_UIDNOTSTICKY));
    print "Status: OK.\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Status: FAILED.\n";
}

print "\nCreating mailbox " . $test_mbox . " for message tests.\n";
try {
    $imap_client->createMailbox($test_mbox);
    print "Created " . $test_mbox . " OK.\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Creation: FAILED.\n";
}

print "\nCreating mailbox " . $test_mbox_utf8 . " for message tests.\n";
try {
    $imap_client->createMailbox($test_mbox_utf8);
    print "Created " . $test_mbox_utf8 . " OK.\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Creation: FAILED.\n";
}

print "\nAll status information for " . $test_mbox . ", including 'firstunseen' and 'highestmodseq'.\n";
try {
    print_r($imap_client->status($test_mbox, Horde_Imap_Client::STATUS_ALL | Horde_Imap_Client::STATUS_FIRSTUNSEEN | Horde_Imap_Client::STATUS_HIGHESTMODSEQ));
    print "Status: OK.\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Status: FAILED.\n";
}

$test_email = <<<EOE
Return-Path: <test@example.com>
Delivered-To: foo@example.com
Received: from test1.example.com (test1.example.com [192.168.10.10])
    by test2.example.com (Postfix) with ESMTP id E6F7890AF
    for <foo@example.com>; Sat, 26 Jul 2008 20:09:03 -0600 (MDT)
Message-ID: <abcd1234efgh5678@test1.example.com>
Date: Sat, 26 Jul 2008 21:10:00 -0500 (CDT)
From: Test <test@example.com>
To: foo@example.com
Subject: Test e-mail 1
Mime-Version: 1.0
Content-Type: text/plain

Test.
EOE;

$test_email2 = <<<EOE
Return-Path: <test@example.com>
Delivered-To: foo@example.com
Received: from test1.example.com (test1.example.com [192.168.10.10])
    by test2.example.com (Postfix) with ESMTP id E8796FBA
    for <foo@example.com>; Sat, 26 Jul 2008 21:19:13 -0600 (MDT)
Message-ID: <98761234@test1.example.com>
Date: Sat, 26 Jul 2008 21:19:00 -0500 (CDT)
From: Test <test@example.com>
To: foo@example.com
Subject: Re: Test e-mail 1
Mime-Version: 1.0
Content-Type: text/plain
In-Reply-To: <abcd1234efgh5678@test1.example.com>
References: <abcd1234efgh5678@test1.example.com>
    <originalreply123123123@test1.example.com>

Test reply.
EOE;

$uid1 = $uid2 = $uid3 = $uid4 = null;

print "\nAppending test e-mail 1 (with Flagged), 2 via a stream (with Seen), 3 via a stream, and 4 (with internaldate):\n";
try {
    $handle = fopen($currdir . '/fixtures/test_email.txt', 'r');
    $handle2 = fopen($currdir . '/fixtures/test_email2.txt', 'r');
    $uid = $imap_client->append($test_mbox, array(
        array('data' => $test_email, 'flags' => array(Horde_Imap_Client::FLAG_FLAGGED), 'messageid' => 'abcd1234efgh5678@test1.example.com'),
        array('data' => $handle, 'flags' => array(Horde_Imap_Client::FLAG_SEEN), 'messageid' => 'aaabbbcccddd111222333444@test1.example.com'),
        array('data' => $handle2, 'messageid' => '2008yhnujm@foo.example.com'),
        array('data' => $test_email2, 'internaldate' => new DateTime('17 August 2003'), 'messageid' => '98761234@test1.example.com')
    ));
    if (!($uid instanceof Horde_Imap_Client_Ids)) {
        throw new Horde_Imap_Client_Exception('Append successful but UIDs not properly returned.');
    }
    list($uid1, $uid2, $uid3, $uid4) = $uid->ids;
    print "Append test-email 1 OK [UID: $uid1]\n";
    print "Append test-email 2 OK [UID: $uid2]\n";
    print "Append test-email 3 OK [UID: $uid3]\n";
    print "Append test-email 4 OK [UID: $uid4]\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Appending: FAILED.\n";
}
fclose($handle);
fclose($handle2);

if (!is_null($uid1)) {
    print "\nCopying test e-mail 1 to " . $test_mbox_utf8 . ".\n";
    try {
        $uid5 = $imap_client->copy($test_mbox, $test_mbox_utf8, array('ids' => new Horde_Imap_Client_Ids($uid1)));
        if ($uid5 === true) {
            print "Copy: OK\n";
            $uid5 = null;
        } elseif (is_array($uid5)) {
            print_r($uid5);
            reset($uid5);
            print "Copy: OK [From UID " . key($uid5) . " to UID " . current($uid5) . "]\n";
        }
    } catch (Horde_Imap_Client_Exception $e) {
        print 'ERROR: ' . $e->getMessage() . "\n";
        print "Copy: FAILED\n";
        $uid5 = null;
    }
} else {
    $uid5 = null;
}

if (!is_null($uid2)) {
    print "\nFlagging test e-mail 2 with the Deleted flag.\n";
    try {
        $imap_client->store($test_mbox, array('add' => array(Horde_Imap_Client::FLAG_DELETED), 'ids' => new Horde_Imap_Client_Ids($uid2)));
        print "Flagging: OK\n";
    } catch (Horde_Imap_Client_Exception $e) {
        print 'ERROR: ' . $e->getMessage() . "\n";
        print "Flagging: FAILED\n";
    }
}

if (!is_null($uid1)) {
    print "\nExpunging mailbox by specifying non-deleted UID.\n";
    try {
        $imap_client->expunge($test_mbox, array('ids' => array($uid1)));
        print "Expunging: OK\n";
    } catch (Horde_Imap_Client_Exception $e) {
        print 'ERROR: ' . $e->getMessage() . "\n";
        print "Expunging: FAILED\n";
    }
}

print "\nGet status of " . $test_mbox . " (should have 4 messages).\n";
try {
    print_r($imap_client->status($test_mbox, Horde_Imap_Client::STATUS_ALL));
    print "Status: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Status: FAILED\n";
}

print "\nExpunging mailbox (should remove test e-mail 2).\n";
try {
    $imap_client->expunge($test_mbox);
    print "Expunging: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Expunging: FAILED\n";
}

print "\nGet status of " . $test_mbox . " (should have 3 messages).\n";
print_r($imap_client->status($test_mbox, Horde_Imap_Client::STATUS_ALL));

if (!is_null($uid5)) {
    print "\nMove test e-mail 1 from " . $test_mbox_utf8 . " to " . $test_mbox . ".\n";
    try {
        $uid6 = $imap_client->copy($test_mbox_utf8, $test_mbox, array('ids' => new Horde_Imap_Client_Ids(current($uid5)), 'move' => true));
        if ($uid6 === true) {
            print "Move: OK\n";
        } elseif (is_array($uid6)) {
            print_r($uid6);
            reset($uid6);
            print "Move: OK [From UID " . key($uid6) . " to UID " . current($uid6) . "]\n";
        }
    } catch (Horde_Imap_Client_Exception $e) {
        print 'ERROR: ' . $e->getMessage() . "\n";
        print "Move: FAILED\n";
    }
}

print "\nDeleting mailbox " . $test_mbox_utf8 . ".\n";
try {
    $imap_client->deleteMailbox($test_mbox_utf8);
    print "Deleting: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Deleting: FAILED\n";
}

print "\nFlagging test e-mail 3 with the Deleted flag.\n";
if (!is_null($uid3)) {
    try {
        $imap_client->store($test_mbox, array('add' => array(Horde_Imap_Client::FLAG_DELETED), 'ids' => new Horde_Imap_Client_Ids($uid3)));
        print "Flagging: OK\n";
    } catch (Horde_Imap_Client_Exception $e) {
        print 'ERROR: ' . $e->getMessage() . "\n";
        print "Flagging: FAILED\n";
    }
}

print "\nClosing " . $test_mbox . " without expunging.\n";
try {
    $imap_client->close();
    print "Closing: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Closing: FAILED\n";
}

print "\nGet status of " . $test_mbox . " (should have 4 messages).\n";
print_r($imap_client->status($test_mbox, Horde_Imap_Client::STATUS_ALL));

// Create a simple 'ALL' search query
$all_query = new Horde_Imap_Client_Search_Query();

print "\nSearching " . $test_mbox . " for all messages (returning UIDs).\n";
try {
    print_r($imap_client->search($test_mbox, $all_query));
    print "Search: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Search: FAILED\n";
}

print "\nSearching " . $test_mbox . " for all messages (returning message sequence numbers).\n";
try {
    print_r($imap_client->search($test_mbox, $all_query, array('results' => array(Horde_Imap_Client::SEARCH_RESULTS_COUNT, Horde_Imap_Client::SEARCH_RESULTS_MATCH, Horde_Imap_Client::SEARCH_RESULTS_MAX, Horde_Imap_Client::SEARCH_RESULTS_MIN), 'sequence' => true)));
    print "Search: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Search: FAILED\n";
}

print "\nSearching " . $test_mbox . " (should be optimized by using internal status instead).\n";
try {
    $query1 = $query2 = $all_query;
    print_r($imap_client->search($test_mbox, $all_query, array('results' => array(Horde_Imap_Client::SEARCH_RESULTS_COUNT))));
    $query1->flag(Horde_Imap_Client::FLAG_RECENT);
    print_r($imap_client->search($test_mbox, $query1, array('results' => array(Horde_Imap_Client::SEARCH_RESULTS_COUNT))));
    $query2->flag(Horde_Imap_Client::FLAG_SEEN, false);
    print_r($imap_client->search($test_mbox, $query2, array('results' => array(Horde_Imap_Client::SEARCH_RESULTS_COUNT))));
    print_r($imap_client->search($test_mbox, $query2, array('results' => array(Horde_Imap_Client::SEARCH_RESULTS_MIN))));
    print "Search: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Search: FAILED\n";
}

print "\nSort " . $test_mbox . " by from and reverse date for all messages (returning UIDs).\n";
try {
    print_r($imap_client->search($test_mbox, $all_query, array('sort' => array(Horde_Imap_Client::SORT_FROM, Horde_Imap_Client::SORT_REVERSE, Horde_Imap_Client::SORT_DATE))));
    print "Search: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Search: FAILED\n";
}

print "\nSort " . $test_mbox . " by thread - references algorithm (UIDs).\n";
try {
    print_r($imap_client->thread($test_mbox, array('criteria' => Horde_Imap_Client::THREAD_REFERENCES)));
    print "Thread search: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Thread search: FAILED\n";
}

print "\nSort 1st 5 messages in " . $test_mbox . " by thread - references algorithm (UIDs).\n";
try {
    $ten_query = new Horde_Imap_Client_Search_Query();
    $ten_query->ids(new Horde_Imap_Client_Ids($imap_utils->fromSequenceString('1:5'), true));
    print_r($imap_client->thread($test_mbox, array('search' => $ten_query, 'criteria' => Horde_Imap_Client::THREAD_REFERENCES)));
    print "Thread search: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Thread search: FAILED\n";
}

print "\nSort " . $test_mbox . " by thread - orderedsubject algorithm (sequence numbers).\n";
try {
    print_r($imap_client->thread($test_mbox, array('criteria' => Horde_Imap_Client::THREAD_ORDEREDSUBJECT, 'sequence' => true)));
    print "Thread search: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Thread search: FAILED\n";
}

$simple_fetch = new Horde_Imap_Client_Fetch_Query();
$simple_fetch->structure();
$simple_fetch->envelope();
$simple_fetch->imapDate();
$simple_fetch->size();

if (!$pop3) {
    $simple_fetch->flags();
}

print "\nSimple fetch example:\n";
try {
    print_r($imap_client->fetch($test_mbox, $simple_fetch));
    print "Fetch: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Fetch: FAILED\n";
}

if ($horde_cache) {
   print "\nRepeat simple fetch example - should retrieve data from cache:\n";
    try {
        print_r($imap_client->fetch($test_mbox, $simple_fetch));
        print "Fetch: OK\n";
    } catch (Horde_Imap_Client_Exception $e) {
        print 'ERROR: ' . $e->getMessage() . "\n";
        print "Fetch: FAILED\n";
    }
}

print "\nFetching message information from complex MIME message:\n";
try {
    $complex_fetch = new Horde_Imap_Client_Fetch_Query();
    $complex_fetch->fullText(array(
        'length' => 100,
        'peek' => true
    ));
    // Header of entire message
    $complex_fetch->headerText(array(
        'length' => 100,
        'peek' => true
    ));
    // Header of message/rfc822 part
    $complex_fetch->headerText(array(
        'id' => 2,
        'length' => 100,
        'peek' => true
    ));
    // Body text of entire message
    $complex_fetch->bodyText(array(
        'length' => 100,
        'peek' => true
    ));
    // Body text of message/rfc822 part
    $complex_fetch->bodyText(array(
        'id' => 2,
        'length' => 100,
        'peek' => true
    ));
    // MIME Header of multipart/alternative part
    $complex_fetch->mimeHeader('1', array(
        'length' => 100,
        'peek' => true
    ));
    // MIME Header of text/plain part embedded in message/rfc822 part
    $complex_fetch->mimeHeader('2.1', array(
        'length' => 100,
        'peek' => true
    ));
    // Body text of multipart/alternative part
    $complex_fetch->bodyPart('1', array(
        'length' => 100,
        'peek' => true
    ));
    // Body text of image/png part embedded in message/rfc822 part
    // Try to do server-side decoding, if available
    $complex_fetch->mimeHeader('2.2', array(
        'decode' => true,
        'length' => 100,
        'peek' => true
    ));
    // If supported, return decoded body part size
    $complex_fetch->bodyPartSize('2.2');
    // Select message-id header from base message header
    $complex_fetch->headers('headersearch1', array('message-id'), array(
        'length' => 100,
        'peek' => true
    ));
    // Select everything but message-id header from message/rfc822 header
    $complex_fetch->headers('headersearch2', array('message-id'), array(
        'id' => '2',
        'length' => 100,
        'notsearch' => true,
        'peek' => true
    ));
    $complex_fetch->structure();
    $complex_fetch->envelope();
    $complex_fetch->flags();
    $complex_fetch->imapDate();
    $complex_fetch->size();
    $complex_fetch->uid();
    $complex_fetch->modseq();

    $fetch_res = $imap_client->fetch($test_mbox, $complex_fetch, array('ids' => new Horde_Imap_Client_Ids($uid3)));
    print_r($fetch_res);
    print "Fetch: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Fetch: FAILED\n";

    // If POP3, try easier fetch criteria
    if ($pop3) {
        $pop3_fetch = new Horde_Imap_Client_Fetch_Query();
        $pop3_fetch->fullText(array(
            'length' => 100,
            'peek' => true
        ));

        try {
            print_r($imap_client->fetch('INBOX', $pop3_fetch, array('ids' => new Horde_Imap_Client_Ids(1))));
            print "Fetch: OK\n";
        } catch (Horde_Imap_Client_Exception $e) {
            print 'ERROR: ' . $e->getMessage() . "\n";
            print "Fetch (POP3): FAILED\n";
        }
    }
}

print "\nFetching parsed header information (requires Horde MIME library):\n";

$hdr_fetch = new Horde_Imap_Client_Fetch_Query();
$hdr_fetch->headers('headersearch1', array('message-id'), array(
    'parse' => true,
    'peek' => true
));

try {
    print_r($imap_client->fetch($test_mbox, $hdr_fetch, array('ids' => new Horde_Imap_Client_Ids($uid3))));
    print "Fetch: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Fetch: FAILED\n";
}

print "\nSet METADATA on " . $test_mbox . ".\n";
try {
    $imap_client->setMetadata($test_mbox, array('/shared/comment' => 'test'));
    print "Set Metadata: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Set Metadata: FAILED\n";
}

print "\nGet METADATA from " . $test_mbox . ".\n";
try {
    print_r($imap_client->getMetadata($test_mbox, '/shared/comment'));
    print "Get Metadata: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Get Metadata: FAILED\n";
}

print "\nRe-open " . $test_mbox . " READ-WRITE.\n";
try {
    $imap_client->openMailbox($test_mbox, Horde_Imap_Client::OPEN_READWRITE);
    print "Read-write: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Read-write: FAILED\n";
}

print "\nClosing " . $test_mbox . " while expunging.\n";
try {
    $imap_client->close(array('expunge' => true));
    print "Closing: OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Closing: FAILED\n";
}

print "\nGet status of " . $test_mbox . " (should have 3 messages).\n";
print_r($imap_client->status($test_mbox, Horde_Imap_Client::STATUS_ALL));

print "\nDeleting mailbox " . $test_mbox . ".\n";
try {
    $imap_client->deleteMailbox($test_mbox);
    print "Deleting " . $test_mbox . ": OK\n";
} catch (Horde_Imap_Client_Exception $e) {
    print 'ERROR: ' . $e->getMessage() . "\n";
    print "Deleting: FAILED\n";
}

print "\nTesting a complex search query built using Horde_Imap_Client_Search_Query:\n";
$query = new Horde_Imap_Client_Search_Query();
$query->flag(Horde_Imap_Client::FLAG_ANSWERED);
$query->flag(Horde_Imap_Client::FLAG_DELETED, true);
$query->flag(Horde_Imap_Client::FLAG_RECENT);
$query->flag(Horde_Imap_Client::FLAG_SEEN, true);
$query->flag('TestKeyword');
// This second flag request for Answered should overrule the first request
$query->flag(Horde_Imap_Client::FLAG_ANSWERED, true);
// Querying for new should clear both Recent and Seen flag
$query->newMsgs();
$query->headerText('cc', 'Testing');
$query->headerText('message-id', 'abcdefg1234567', true);
$query->headerText('subject', '8bit char 1è.');
$query->charset('UTF-8');
$query->text('Test1');
$query->text('Test2', false);
$query->size('1024', true);
$query->size('4096', false);
$query->ids(new Horde_Imap_Client_Ids(array(1, 5, 50, 51, 52, 55, 54, 53, 55, 55, 100, 500, 501)));
$date = new DateTime('2008-06-15');
$query->dateSearch($date, Horde_Imap_Client_Search_Query::DATE_BEFORE, true, true);
$date = new DateTime('2008-06-20');
$query->dateSearch($date, Horde_Imap_Client_Search_Query::DATE_ON, false);
// Add 2 simple OR queries
$query2 = new Horde_Imap_Client_Search_Query();
$query2->text('Test3', false, true);
$query3 = new Horde_Imap_Client_Search_Query();
$query3->newMsgs(false);
$query3->intervalSearch(1000000, Horde_Imap_Client_Search_Query::INTERVAL_YOUNGER);
if ($imap_client->queryCapability('CONDSTORE')) {
    $query3->modseq(1234, '/flags/\deleted', 'all');
}
$query->orSearch(array($query2, $query3));
print_r($query->build($imap_client->capability()));

print "\nTesting mailbox sorting:\n";
$test_sort = array(
    'A',
    'Testing.JJ',
    'A1',
    'INBOX',
    'a',
    'A1.INBOX',
    '2.A',
    'a.A.1.2',
    $test_mbox,
    $test_mbox_utf8
);
print_r($test_sort);
Horde_Imap_Client_Sort::sortMailboxes($test_sort, array('delimiter' => '.', 'inbox' => true));
print_r($test_sort);

print "Testing serialization of object. Will automatically logout.\n";
$serialized_data = serialize($imap_client);
print "\nSerialized object:\n";
print_r($serialized_data);

$unserialized_data = unserialize($serialized_data);
print "\n\nUnserialized object:\n";
print_r($unserialized_data);

if ($use_imapproxy) {
    print "\nTesting reuse of imapproxy connection.\n";
    try {
        $unserialized_data->status('INBOX', Horde_Imap_Client::STATUS_MESSAGES);
        print "OK.\n";
    } catch (Horde_Imap_Client_Exception $e) {
        print 'ERROR: ' . $e->getMessage() . "\n";
    }

    $unserialized_data->logout();
    print "\nLogging out: OK\n";
}

if (isset($fetch_res)) {
    print "\nTesting Horde_Mime_Part::parseMessage() on complex MIME message:\n";
    $parse_text_res = Horde_Mime_Part::parseMessage(file_get_contents($currdir . '/fixtures/test_email2.txt'));
    print_r($parse_text_res);
}

if (isset($timer)) {
    $timer->stop();
    print "\nTime elapsed: " . $timer->timeElapsed() . " seconds";
}

print "\nMemory used: " . memory_get_usage() . " (Peak: " . memory_get_peak_usage() . ")\n";
