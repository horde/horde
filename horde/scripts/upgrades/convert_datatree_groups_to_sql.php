#!/usr/bin/php -q
<?php
/**
 * A script to migrate groups from the DataTree backend to the new
 * (Horde 3.2+) native SQL Group backend.
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none', 'cli' => true));

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

$db = $injector->getInstance('Horde_Db_Pear')->getDb();

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
                    Horde_String::convertCharset($object->name, $GLOBALS['registry']->getCharset(), $conf['sql']['charset']),
                    Horde_String::convertCharset($parents, $GLOBALS['registry']->getCharset(), $conf['sql']['charset']),
                    Horde_String::convertCharset($object->get('email'), $GLOBALS['registry']->getCharset(), $conf['sql']['charset']),
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
