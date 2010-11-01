#!/usr/bin/env php
<?php
/**
 * This is a script to migrate SyncML anchor information out of the datatree
 * tables and into its own database table.
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none', 'cli' => true));

require_once 'Horde/DataTree.php';
$datatree = DataTree::factory('sql',
                              array_merge(
                                  Horde::getDriverConfig('datatree', 'sql'),
                                  array('group' => 'horde.syncml')));
$db = &$datatree->_db;
$stmt = $db->prepare('INSERT INTO horde_syncml_anchors (syncml_syncpartner, syncml_db, syncml_uid, syncml_clientanchor, syncml_serveranchor) VALUES (?, ?, ?, ?, ?)');

$cli->writeln('Processing all users:');
$users = $datatree->getById(DATATREE_FORMAT_FLAT, DATATREE_ROOT, false,
                            DATATREE_ROOT, 1);
if (is_a($users, 'PEAR_Error')) {
    $cli->fatal($users->toString());
}
foreach ($users as $user_id => $user) {
    if ($user_id == DATATREE_ROOT) {
        continue;
    }
    $cli->writeln($user);
    $devices = $datatree->getById(DATATREE_FORMAT_FLAT, $user_id, false,
                                  DATATREE_ROOT, 1);
    foreach ($devices as $device_id => $device) {
        if ($device_id == $user_id) {
            continue;
        }
        $device = $datatree->getShortName($device);
        echo '  device ' . $device . ':';
        $databases = $datatree->getById(DATATREE_FORMAT_FLAT, $device_id,
                                        false, DATATREE_ROOT, 1);
        foreach ($databases as $database_id => $database) {
            if ($database_id == $device_id) {
                continue;
            }
            $database = $datatree->getShortName($database);
            echo ' ' . $database;
            $data = $datatree->getData($database_id);
            $result = $db->execute($stmt, array($device, $database, $user,
                                                (string)$data['ClientAnchor'],
                                                (string)$data['ServerAnchor']));
            if (is_a($result, 'PEAR_Error')) {
                $cli->fatal($result->toString());
            }
        }
        $cli->writeln();
    }

    $datatree->remove($user, true);
}
