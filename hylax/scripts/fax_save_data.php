#!/usr/bin/env php
<?php

require_once dirname(__FILE__) . '/../lib/Application.php';
$hylax = Horde_Registry::appInit('hylax', array('authentication' => 'none', 'cli' => true));

/* Store the raw fax postscript data. */
$data = $cli->readStdin();
if (empty($data)) {
    Horde::logMessage('No print data received from standard input. Exiting fax submission.', 'ERR');
    exit;
}

$fax_id = $hylax_storage->saveFaxData($data);
if (is_a($fax_id, 'PEAR_Error')) {
    echo '0';
}
echo $fax_id;
