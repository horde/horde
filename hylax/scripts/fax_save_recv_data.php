#!/usr/bin/env php
<?php

require_once dirname(__FILE__) . '/../lib/Application.php';
$hylax = Horde_Registry::appInit('hylax', array('authentication' => 'none', 'cli' => true));

/* Get the arguments. The first argument is the filename from which the job ID
 * is obtained, in the format 'recvq/faxNNNNN.tif'. */
$args = Console_Getopt::readPHPArgv();
if (isset($args[1])) {
    $file = $args[1];
    $job_id = (int)substr($file, 9, -4);
}

/* Store the raw fax postscript data. */
$data = $cli->readStdin();
if (empty($data)) {
    Horde::logMessage('No print data received from standard input. Exiting fax submission.', 'ERR');
    exit;
}

/* Get the file and store into VFS. */
$fax_id = $hylax->storage->saveFaxData($data, '.ps');
if (is_a($fax_id, 'PEAR_Error')) {
    echo '0';
    exit;
}
Horde::logMessage(sprintf('Creating fax ID %s for received fax.', $fax_id), 'DEBUG');
echo $fax_id;
