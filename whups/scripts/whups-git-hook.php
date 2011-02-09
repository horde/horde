#!/usr/bin/env php
<?php
/**
 * commit-update-tickets.php: scan commit logs for ticket numbers
 * denoted by a flexible regular expression and post the log message
 * and a link to the changeset diff in a comment to those tickets.
 *
 * Usage: commit-update-tickets.php PATH_TO_REPO REVISION
 *
 * @category Horde
 * @package  maintainer_tools
 */

require_once 'Horde/Autoloader/Default.php';


/**
 ** Initialize expected values to rule out pre-existing input by any
 ** means, then include our configuration file.
 **/

$git = $rpc_endpoint = $rpc_method = null;
$rpc_options = array();

require dirname(__FILE__) . '/commit-update-tickets-conf.php';


/**
 ** Sanity checks
 **/

if (!is_executable($git)) {
    abort("Required program $git is not executable.");
}

if (is_null($rpc_endpoint) || is_null($rpc_method)) {
    abort("Required XML-RPC configuration is missing or incomplete.");
}

if (count($_SERVER['argv']) < 4) {
    usage();
}


/**
 ** Command-line parsing
 **/

array_shift($_SERVER['argv']);
$repo = array_shift($_SERVER['argv']);
$rev = array_shift($_SERVER['argv']);
$links = implode("\n", $_SERVER['argv']);

if (!file_exists($repo)) {
    abort("Repository $repo does not exist.");
}

if (!is_dir($repo)) {
    abort("Repository $repo is not a directory.");
}


/**
 ** Read the log message for this revision
 **/

// Run git show to get the log message for this revision
$log_message = shell_exec(implode(' ', array(
    escapeshellcmd($git),
    '--git-dir=' . escapeshellarg($repo),
    'show',
    '--summary',
    '--pretty=format:%s%n%b',
    escapeshellarg($rev)
)));

if (!empty($log_message)) {
    $tickets = find_tickets($log_message);
    $kolab_tickets = find_kolab_tickets($log_message);

    foreach ($kolab_tickets as $ticket) {
        $log_message .= "\nThis ticket also references kolab issue: http://issues.kolab.org/issue$ticket\n\n";
    }

    if (count($tickets)) {
        $log_message = "Changes have been made in Git for this ticket:\n\n$log_message$links";
        foreach ($tickets as $ticket) {
            post_comment($ticket, $log_message);
        }
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
    abort("usage: commit-update-tickets.php PATH_TO_REPO REVISION LINKS");
}

function find_tickets($log_message) {
    preg_match_all('/(?:(?<!kolab\/)(?:bug|ticket|request|enhancement|issue):?\s*#?|#)(\d+)/i', $log_message, $matches);
    return array_unique($matches[1]);
}

/**
 * Locate kolab tickets with kolab/issue1234
 */
function find_kolab_tickets($log_message) {
    preg_match_all('/(?:(?:kolab\/issue))(\d+)/i', $log_message, $matches);
    return array_unique($matches[1]);
}

function post_comment($ticket, $log_message) {
    $http = new Horde_Http_Client($GLOBALS['rpc_options']);
    try {
        $result = Horde_Rpc::request(
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
