#!/usr/bin/php -q
<?php
/**
 * A script to migrate permissions from the DataTree backend to the
 * new (Horde 3.2+) native SQL Perms backend.
 */

// Do CLI checks and environment setup first.
require_once dirname(__FILE__) . '/../../lib/core.php';

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment - make sure there's no time limit, init
// some variables, etc.
Horde_Cli::init();

new Horde_Application(array('authentication' => 'none'));

$p = Perms::factory('datatree');

$query = '
INSERT INTO
    horde_perms (perm_id, perm_name, perm_parents, perm_data)
VALUES
    (?, ?, ?, ?)
';

$db = DB::connect($conf['sql']);

foreach ($p->getTree() as $id => $row) {
    if ($id == -1) {
        continue;
    }

    $object = $p->getPermissionById($id);
    echo $id . "\n";

    $parents = $object->datatree->getParentList($id);
    asort($parents);
    $parents = implode(':', array_keys($parents));

    $params = array($id, $object->name, $parents, serialize($object->data));
    $db->query($query, $params);
}

$max = (int)$db->getOne('SELECT MAX(perm_id) FROM horde_perms');
while ($max > $db->nextId('horde_perms'));

echo "\nDone.\n";
