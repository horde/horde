#!@php_bin@
<?php
/**
 * This script counts the number of active authenticated user sessions.
 *
 * Command line options:
 *   '-l'   List the username of active authenticated users
 *   '-ll'  List the username and login time of active authenticated users
 *
 * $Horde: framework/SessionHandler/scripts/horde-active-sessions.php,v 1.7 2008/09/22 03:50:58 slusarz Exp $
 *
 * @package Horde_SessionHandler
 */

// No auth.
@define('AUTH_HANDLER', true);

// Find the base file path of Horde.
@define('HORDE_BASE', dirname(__FILE__) . '/..');

// Do CLI checks and environment setup first.
require_once HORDE_BASE . '/lib/core.php';
require_once 'Horde/CLI.php';

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment - make sure there's no time limit, init some
// variables, etc.
$cli = &Horde_Cli::singleton();
$cli->init();

require_once HORDE_BASE . '/lib/base.php';

/* Make sure there's no compression. */
@ob_end_clean();

/* Check for sessionhandler object. */
if (empty($GLOBALS['horde_sessionhandler'])) {
    Horde::fatal(PEAR::raiseError('Horde is unable to load the session handler'), __FILE__, __LINE__, false);
}

$type = !empty($conf['sessionhandler']['type']) ?
    $conf['sessionhandler']['type'] : 'builtin';
if ($type == 'external') {
    Horde::fatal(PEAR::raiseError('Session counting is not supported in the \'external\' SessionHandler at this time.'), __FILE__, __LINE__, false);
}

$sessions = $GLOBALS['horde_sessionhandler']->getSessionsInfo();
if (is_a($sessions, 'PEAR_Error')) {
    Horde::fatal($sessions, __FILE__, __LINE__, false);
}

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
