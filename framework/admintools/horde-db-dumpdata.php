#!@php_bin@
<?php
/**
 * Dump the requested tables (or all) from the Horde database to XML data
 * format.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
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

// Get rid of the script name
array_shift($_SERVER['argv']);
$tables = array_values($_SERVER['argv']);

$result = $manager->dumpData('php://stdout', $tables);
if (is_a($result, 'PEAR_Error')) {
    $cli->fatal($result->toString());
}

exit(0);
