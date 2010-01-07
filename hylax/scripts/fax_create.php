#!/usr/bin/php
<?php
/**
 * $Horde: incubator/hylax/scripts/fax_create.php,v 1.4 2009/06/10 19:57:57 slusarz Exp $
 */

// No need for auth.
@define('AUTH_HANDLER', true);

// Find the base file paths.
@define('HORDE_BASE', dirname(__FILE__) . '/../..');
@define('HYLAX_BASE', dirname(__FILE__) . '/..');

// Do CLI checks and environment setup first.
require_once HYLAX_BASE . '/lib/base.php';
require_once 'Console/Getopt.php';

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

/* Load the CLI environment - make sure there's no time limit, init
 * some variables, etc. */
$cli = &new Horde_Cli();
$cli->init();

/* Create the fax information array. Set fax_type to 1 for outgoing. */
$info = array('fax_type' => 1);

/* Get the arguments. The third argument is the user submitting the job, used
 * to differentiate jobs between users.*/
$args = Console_Getopt::readPHPArgv();
if (isset($args[1])) {
    $info['fax_id'] = $args[1];
}
if (isset($args[2])) {
    $info['fax_user'] = $args[2];
}
Horde::logMessage(sprintf('Creating fax ID %s for user %s.', $info['fax_id'], $info['fax_user']), __FILE__, __LINE__, PEAR_LOG_DEBUG);

$hylax_storage->createFax($info, true);
