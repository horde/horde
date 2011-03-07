#!/usr/bin/env php
<?php
/**
 * A script to migrate groups from the Horde_DataTree backend to the new
 * (Horde 3.2+) native SQL Group backend.
 */

die("The Horde_DataTree driver for the groups system is gone. This script needs to be upated to work directly on the datatree table.\n");

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array('cli' => true));

$g = Horde_Group::factory();

$group_query = '
INSERT INTO
    horde_groups (group_uid, group_name, group_parents, group_email)
VALUES
    (?, ?, ?, ?)
';

$member_query = '
INSERT INTO
    horde_groups_members (group_uid, user_uid)
VALUES
    (?, ?)
';

$db = $injector->getInstance('Horde_Core_Factory_DbPear')->create();

foreach ($g->listGroups(true) as $id => $name) {
    if ($id == -1) {
        continue;
    }

    echo $id . "\n";

    $object = $g->getGroupById($id);

    $parents = $object->datatree->getParentList($id);
    asort($parents);
    $parents = implode(':', array_keys($parents));

    $params = array($id,
                    Horde_String::convertCharset($object->name, 'UTF-8', $conf['sql']['charset']),
                    Horde_String::convertCharset($parents, 'UTF-8', $conf['sql']['charset']),
                    Horde_String::convertCharset($object->get('email'), 'UTF-8', $conf['sql']['charset']),
    );
    $result = $db->query($group_query, $params);
    if (is_a($result, 'PEAR_Error')) {
        echo $result->toString();
        continue;
    }

    $members = $object->listUsers();
    foreach ($members as $user_uid) {
        $params = array($id, $user_uid);
        $result = $db->query($member_query, $params);
        if (is_a($result, 'PEAR_Error')) {
            echo $result->toString();
        }
    }
}

$max = (int)$db->getOne('SELECT MAX(group_uid) FROM horde_groups');
while ($max > $db->nextId('horde_groups'));

echo "\nDone.\n";
