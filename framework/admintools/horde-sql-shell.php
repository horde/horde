#!@php_bin@
<?php
/**
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @package admintools
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/horde-base.php';
Horde_Registry::appInit('horde', array(
    'authentication' => 'none',
    'cli' => true
));

$dbh = $injector->getInstance('Horde_Db_Adapter');

// read sql file for statements to run
$statements = new Horde_Db_StatementParser($_SERVER['argv'][1]);
foreach ($statements as $stmt) {
    echo "Running:\n  " . preg_replace('/\s+/', ' ', $stmt) . "\n";
    try {
        $dbh->execute($stmt);
    } catch (Horde_Db_Exception $e) {
        print_r($e);
        exit;
    }

    echo "  ...done\n\n";
}
