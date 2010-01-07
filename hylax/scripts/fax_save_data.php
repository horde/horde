#!/usr/bin/php
<?php
/**
 * $Horde: incubator/hylax/scripts/fax_save_data.php,v 1.4 2009/06/10 19:57:57 slusarz Exp $
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

/* Load the CLI environment - make sure there's no time limit, init some
 * variables, etc. */
$cli = &new Horde_Cli();
$cli->init();

/* Store the raw fax postscript data. */
$data = $cli->readStdin();
if (empty($data)) {
    Horde::logMessage('No print data received from standard input. Exiting fax submission.', __FILE__, __LINE__, PEAR_LOG_ERR);
    exit;
}

$fax_id = $hylax_storage->saveFaxData($data);
if (is_a($fax_id, 'PEAR_Error')) {
    echo '0';
}
echo $fax_id;
