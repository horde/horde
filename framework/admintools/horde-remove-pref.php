#!@php_bin@
<?php
/**
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * This script removes a preference from users' preferences. Helps when a
 * preference is to be moved from locked = false, to locked = true and there
 * have already been preferences set by the users.
 *
 * @package admintools
 */

/* Set this to true if you want DB modifications done.*/
$live = false;

require_once dirname(__FILE__) . '/horde-base.php';
Horde_Registry::appInit('horde', array(
    'authentication' => 'none',
    'cli' => true
));

$scope = $cli->prompt('Enter value for pref_scope:');
$name = $cli->prompt('Enter value for pref_name:');

/* Open the database. */
$db = $injector->getInstance('Horde_Db_Adapter');

if ($live) {
    $sql = 'DELETE FROM horde_prefs WHERE pref_scope = ? AND pref_name = ?';
    $values = array($scope, $name);
    try {
        if ($db->delete($sql, $values)) {
            $cli->writeln(sprintf('Preferences "%s" deleted in scope "%s".', $name, $scope));
        } else {
            $cli->writeln(sprintf('No preference "%s" found in scope "%s".', $name, $scope));
        }
    } catch (Horde_Db_Exception $e) {
        print_r($e);
    }
} else {
    $sql = 'SELECT * FROM horde_prefs WHERE pref_scope = ? AND pref_name = ?';
    $values = array($scope, $name);
    try {
        if ($result = $db->selectAll($sql, $values)) {
            var_dump($result);
        } else {
            $cli->writeln(sprintf('No preference "%s" found in scope "%s".', $name, $scope));
        }
    } catch (Horde_Db_Exception $e) {
        print_r($e);
    }
}
