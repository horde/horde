#!@php_bin@
<?php
/**
 * Update database definitions from the given .xml schema file.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  admintools
 */

// Do CLI checks and environment setup first.
require_once dirname(__FILE__) . '/horde-base.php';
require_once $horde_base . '/lib/core.php';

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment - make sure there's no time limit, init
// some variables, etc.
$cli = Horde_Cli::singleton();
$cli->init();

// Include needed libraries.
$horde_authentication = 'none';
require_once HORDE_BASE . '/lib/base.php';

$manager = Horde_SQL_Manager::getInstance();
if (is_a($manager, 'PEAR_Error')) {
    $cli->fatal($manager->toString());
}

// Get arguments.
array_shift($_SERVER['argv']);
if (!count($_SERVER['argv'])) {
    exit("You must specify the schema file to update.\n");
}
$file = array_shift($_SERVER['argv']);
$debug = count($_SERVER['argv']) && array_shift($_SERVER['argv']) == 'debug';

$result = $manager->updateSchema($file, $debug);
if (is_a($result, 'PEAR_Error')) {
    $cli->fatal('Failed to update database definitions: ' . $result->toString());
    exit(1);
} elseif ($debug) {
    echo $result;
} else {
    $cli->message('Successfully updated the database with definitions from "' . $file . '".', 'cli.success');
}
exit(0);
