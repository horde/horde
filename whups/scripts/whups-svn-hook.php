#!/usr/bin/env php
<?php
/**
 * commit-update-tickets.php: scan commit logs for ticket numbers
 * (denoted by a flexible regular expression and post the log message
 * and a link to the changeset diff in a comment to those tickets.
 *
 * Usage: commit-update-tickets.php REPOS REVISION
 *
 * @category Horde
 * @package  maintainer_tools
 */


/**
 ** Includes
 **/

require_once 'Horde/RPC.php';


/**
 ** Initialize expected values to rule out pre-existing input by any
 ** means, then include our configuration file.
 **/

$svnlook = null;
$rpc_endpoint = null;
$rpc_method = null;
$rpc_options = array();

include dirname(__FILE__) . '/commit-update-tickets-conf.php';


/**
 ** Sanity checks
 **/

if (!is_executable($svnlook)) {
    abort("Required program $svnlook is not executable.");
}

if (is_null($rpc_endpoint) || is_null($rpc_method)) {
    abort("Required XML-RPC configuration is missing or incomplete.");
}

if (count($_SERVER['argv']) != 3) {
    usage();
}


/**
 ** Command-line parsing
 **/

$repos = $_SERVER['argv'][1];
$rev = $_SERVER['argv'][2];

if (!file_exists($repos)) {
    abort("Repository $repos does not exist.");
}

if (!is_dir($repos)) {
    abort("Repository $repos is not a directory.");
}


/**
 ** Read the log message for this revision
 **/

// Run svnlook log to get the log message for this revision
exec(implode(' ', array($svnlook,
                        'log',
                        '--revision',
                        escapeshellarg($rev),
                        escapeshellarg($repos))),
     $log_message);

$tickets = find_tickets($log_message);
if (count($tickets)) {
    foreach ($tickets as $ticket) {
        post_comment($ticket, $log_message);
    }
}

exit(0);


/**
 ** Functions
 **/

function abort($msg) {
    fputs(STDERR, $msg . "\n");
    exit(1);
}

function usage() {
    abort("usage: commit-update-tickets.php REPOS REVISION");
}

function find_tickets($log_message) {
    preg_match_all('/#(\d+)/', $log_message, $matches_1);
    preg_match_all('/(bug|ticket|request|enhancement|issue):\s*#?(\d+)/i', $log_message, $matches_2);
    return array_unique(array_merge($matches_1[1], $matches_2[2]));
}

function post_comment($ticket, $log_message) {
    $http = new Horde_Http_Client($GLOBALS['rpc_options']);
    try {
        $result = Horde_RPC::request(
            'xmlrpc',
            $GLOBALS['rpc_endpoint'],
            $GLOBALS['rpc_method'],
            $http,
            array((int)$ticket, $log_message));
    } catch (Horde_Http_Client_Exception $e) {
        abort($e->getMessage());
    }

    return true;
}
