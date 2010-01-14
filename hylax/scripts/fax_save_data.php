#!/usr/bin/php
<?php

require_once dirname(__FILE__) . '/../lib/Application.php';
$hylax = Horde_Registry::appInit('hylax', array('authentication' => 'none'));

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

/* Load the CLI environment - make sure there's no time limit, init some
 * variables, etc. */
$cli = new Horde_Cli();
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
