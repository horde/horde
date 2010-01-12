#!@php_bin@
<?php
/**
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @package admintools
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

// Do CLI checks and environment setup first.
require_once dirname(__FILE__) . '/horde-base.php';
require_once $horde_base . '/lib/core.php';

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment - make sure there's no time limit, init some
// variables, etc.
Horde_Cli::init();

// Include needed libraries.
$horde_authentication = 'none';
require_once HORDE_BASE . '/lib/base.php';

$dbh = DB::connect($conf['sql']);
if (is_a($dbh, 'PEAR_Error')) {
    throw new Horde_Exception($dbh);
}
$dbh->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);

// list databases command
// $result = $dbh->getListOf('databases');

// list tables command
// $result = $dbh->getListOf('tables');

// read sql file for statements to run
$statements = new Horde_Db_StatementParser($_SERVER['argv'][1]);
foreach ($statements as $stmt) {
    echo "Running:\n  " . preg_replace('/\s+/', ' ', $stmt) . "\n";
    $result = $dbh->query($stmt);
    if (is_a($result, 'PEAR_Error')) {
        var_dump($result);
        exit;
    }

    echo "  ...done\n\n";
}
