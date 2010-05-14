#!@php_bin@
<?php
/**
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * This script removes a pref from users' settings. Helps when a setting is
 * to be moved from locked = false, to locked = true and there have already
 * been prefs set by the users.
 *
 * @package admintools
 */

/* Set this to true if you want DB modifications done.*/
$live = false;

require_once dirname(__FILE__) . '/horde-base.php';
Horde_Registry::appInit('horde', array('authentication' => 'none', 'cli' => true, 'nocompress' => true));

$scope = $cli->prompt(_("Enter value for pref_scope:"));
$name = $cli->prompt(_("Enter value for pref_name:"));

/* Open the database. */
$db = $injector->getInstance('Horde_Db_Pear')->getOb();

if ($live) {
    $sql = 'DELETE FROM horde_prefs WHERE pref_scope = ? AND pref_name = ?';
    $values = array($scope, $name);
    $result = $db->getAll($sql, $values);
    if (is_a($result, 'PEAR_Error')) {
        var_dump($result);
    } elseif (empty($result)) {
        $cli->writeln(sprintf(_("No preference \"%s\" found in scope \"%s\"."), $name, $scope));
    } else {
        $cli->writeln(sprintf(_("Preferences \"%s\" deleted in scope \"%s\"."), $name, $scope));
    }
} else {
    $sql = 'SELECT * FROM horde_prefs WHERE pref_scope = ? AND pref_name = ?';
    $values = array($scope, $name);
    $result = $db->getAll($sql, $values);
    if (empty($result)) {
        $cli->writeln(sprintf(_("No preference \"%s\" found in scope \"%s\"."), $name, $scope));
    } else {
        var_dump($result);
    }
}
