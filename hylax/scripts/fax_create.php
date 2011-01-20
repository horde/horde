#!/usr/bin/env php
<?php

require_once dirname(__FILE__) . '/../lib/Application.php';
$hylax = Horde_Registry::appInit('hylax', array('cli' => true));

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
Horde::logMessage(sprintf('Creating fax ID %s for user %s.', $info['fax_id'], $info['fax_user']), 'DEBUG');

$hylax->storage->createFax($info, true);
