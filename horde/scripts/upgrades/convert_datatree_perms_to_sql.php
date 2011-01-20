#!/usr/bin/env php
<?php
/**
 * A script to migrate permissions from the DataTree backend to the
 * new (Horde 3.2+) native SQL Perms backend.
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array('cli' => true));

$p = Horde_Perms::factory('datatree');

$query = '
INSERT INTO
    horde_perms (perm_id, perm_name, perm_parents, perm_data)
VALUES
    (?, ?, ?, ?)
';

$db = $injector->getInstance('Horde_Core_Factory_DbPear')->create();

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
