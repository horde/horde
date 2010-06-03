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

require_once dirname(__FILE__) . '/horde-base.php';
Horde_Registry::appInit('horde', array('authentication' => 'none', 'cli' => true));

$db_lib = 'DB';
$sequence = null;

if (isset($_SERVER['argv']) && count($_SERVER['argv']) >= 2) {
    array_shift($_SERVER['argv']);
    while ($arg = array_shift($_SERVER['argv'])) {
        if ($arg == '--mdb2') {
            $db_lib = 'MDB2';
        } else {
            $sequence = $arg;
        }
    }
}
if (is_null($sequence)) {
    $sequence = $cli->prompt(_("What sequence do you want to create (_seq will be added automatically)?"));
}

switch ($db_lib) {
case 'DB':
    $dbh = $injector->getInstance('Horde_Db_Pear')->getDb();
    break;

case 'MDB2':
    require_once 'MDB2.php';
    $params = $conf['sql'];
    unset($params['charset']);
    $dbh = MDB2::factory($params);
    break;

default:
    throw new Horde_Exception('Unknown database abstraction library');
}
if (is_a($dbh, 'PEAR_Error')) {
    throw new Horde_Exception_Prior($dbh);
}

if (!preg_match('/^\w+$/', $sequence)) {
    $cli->fatal('Invalid sequence name');
}

switch ($db_lib) {
case 'DB':
    $result = $dbh->createSequence($sequence);
    break;

case 'MDB2':
    $dbh->loadModule('Manager', null, true);
    $result = $dbh->manager->createSequence($sequence);
    break;
}
if (is_a($result, 'PEAR_Error')) {
    $cli->fatal($result->getMessage());
}

$cli->green('Sequence created.');
exit(0);
