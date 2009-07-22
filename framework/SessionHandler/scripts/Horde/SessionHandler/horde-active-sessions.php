#!@php_bin@
<?php
/**
 * This script counts the number of active authenticated user sessions.
 *
 * Command line options:
 *   '-l'   List the username of active authenticated users
 *   '-ll'  List the username and login time of active authenticated users
 *
 * @package Horde_SessionHandler
 */

// No auth.
@define('AUTH_HANDLER', true);

// Find the base file path of Horde.
@define('HORDE_BASE', dirname(__FILE__) . '/..');

// Do CLI checks and environment setup first.
require_once HORDE_BASE . '/lib/core.php';

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment - make sure there's no time limit, init some
// variables, etc.
$cli = Horde_Cli::singleton();
$cli->init();

require_once HORDE_BASE . '/lib/base.php';

/* Make sure there's no compression. */
@ob_end_clean();

/* Check for sessionhandler object. */
if (empty($GLOBALS['horde_sessionhandler'])) {
    throw new Horde_Exception('Horde is unable to load the session handler');
}

$type = !empty($conf['sessionhandler']['type']) ?
    $conf['sessionhandler']['type'] : 'builtin';
if ($type == 'external') {
    throw new Horde_Exception('Session counting is not supported in the \'external\' SessionHandler at this time.');
}

$sessions = $GLOBALS['horde_sessionhandler']->getSessionsInfo();

if (($argc < 2) || (($argv[1] != '-l') && ($argv[1] != '-ll'))) {
    $cli->writeln(count($sessions));
} else {
    foreach ($sessions as $data) {
        if ($argv[1] == '-ll') {
            $cli->writeln($data['userid'] . ' [' . date('r', $data['timestamp']) . ']');
        } else {
            $cli->writeln($data['userid']);
        }
    }
    $cli->writeln($cli->green('Total Sessions: ' . count($sessions)));
}
