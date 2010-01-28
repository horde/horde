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

// Find the base file path of Horde.
$horde_base = '/path/to/horde';

require_once $horde_base . '/lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none', 'cli' => true));
$cli = Horde_Cli::singleton();

/* Check for sessionhandler object. */
$registry->setupSessionHandler();
if (!$registry->sessionHandler) {
    throw new Horde_Exception('Horde is unable to load the session handler');
}

$type = !empty($conf['sessionhandler']['type'])
    ? $conf['sessionhandler']['type']
    : 'builtin';

if ($type == 'external') {
    throw new Horde_Exception('Session counting is not supported in the \'external\' SessionHandler.');
}

$sessions = $registry->sessionHandler->getSessionsInfo();

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
