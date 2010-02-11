#!/usr/bin/env php
<?php
/**
 * This script migrates Whups' share data from the datatree
 * Horde_Share driver to the new SQL Horde_Share driver. You should
 * run the 2008-04-29_add_sql_share_tables.sql upgrade script before
 * executing this script.
 */

@define('AUTH_HANDLER', true);
@define('HORDE_BASE', dirname(__FILE__) . '/../../..');

/* Set up the CLI environment */
require_once HORDE_BASE . '/lib/core.php';
if (!Horde_Cli::runningFromCli()) {
    exit("Must be run from the command line\n");
}
$cli = Horde_Cli::init();

/* Grab what we need to steal the DB config */
require_once HORDE_BASE . '/config/conf.php';
require_once 'MDB2.php';

$config = $GLOBALS['conf']['sql'];
unset($config['charset']);
$db = MDB2::factory($config);
$db->setOption('seqcol_name', 'id');

$error_cnt = 0;
$delete_dt_data = false;
$answer = $cli->prompt('Do you want to keep your old datatree data or delete it?', array('Keep', 'Delete'));
if ($answer == 1) {
    $delete_dt_data = true;
}
$answer = $cli->prompt(sprintf("Data will be copied into the new tables, and %s be deleted from the datatree.\n Is this what you want?", (($delete_dt_data) ? 'WILL' : 'WILL NOT')), array('y' => 'Yes', 'n' => 'No'));
if ($answer != 'y') {
    exit;
}

/* Get the share entries */
$shares_result = $db->query('SELECT datatree_id, datatree_name FROM horde_datatree WHERE group_uid = \'horde.shares.whups\'');
if (is_a($shares_result, 'PEAR_Error')) {
    die($shares_result->toString());
}

$query = $db->prepare('SELECT attribute_name, attribute_key, attribute_value FROM horde_datatree_attributes WHERE datatree_id = ?');
$maxId = 0;
while ($row = $shares_result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
    $share_id = $row['datatree_id'];
    $share_name = $row['datatree_name'];
    $maxId = max($maxId, $share_id);

    /* Build an array to hold the new row data */
    $data = array('share_id' => $share_id,
                  'share_name' => $share_name);

    $query_result = $query->execute($share_id);
    $rows = $query_result->fetchAll(MDB2_FETCHMODE_ASSOC);
    if (!count($rows)) {
        continue;
    }
    $users = array();
    $groups = array();

    foreach ($rows as $row) {
        if ($row['attribute_name'] == 'perm_groups') {
            /* Group table entry */
            $groups[] = array('share_id' => $data['share_id'],
                              'group_uid' => $row['attribute_key'],
                              'perm' => $row['attribute_value']);
        } elseif ($row['attribute_name'] == 'perm_users') {
            /* User table entry */
            $users[] = array('share_id' => $data['share_id'],
                             'user_uid' => $row['attribute_key'],
                             'perm' => $row['attribute_value']);
        } else {
            /* Everything else goes in the main share table */
            switch ($row['attribute_name']) {
            case 'perm_creator':
            case 'perm_default':
            case 'perm_guest':
                $data[$row['attribute_name']] = $row['attribute_value'];
                break;

            case 'owner':
                $data['share_owner'] = $row['attribute_value'];
                break;

            case 'name':
                // Note the key to the $data array is not related to
                // the attribute_name field in the dt_attributes table.
                $data['attribute_name'] = $row['attribute_value'];
                break;

            case 'slug':
                // Note the key to the $data array is not related to
                // the attribute_name field in the dt_attributes table.
                $data['attribute_slug'] = $row['attribute_value'];
                break;
            }
        }
    }

    /* Set flags */
    $data['share_flags'] = 0;
    if (count($users)) {
        $data['share_flags'] |= 1;
    }
    if (count($groups)) {
        $data['share_flags'] |= 2;
    }

    /* Insert the new data */
    $cli->message('Migrating share data for share_id: ' . $share_id, 'cli.message');
    $result = insertData('whups_shares', $data);
    if (is_a($result, 'PEAR_Error')) {
        ++$error_cnt;
        $cli->message($result->toString(), 'cli.error');
    }
    if (count($groups)) {
        foreach ($groups as $group) {
            $result = insertData('whups_shares_groups', $group);
            if (is_a($result, 'PEAR_Error')) {
                ++$error_cnt;
                $cli->message($result->getMessage(), 'cli.error');
            }
        }
    }
    if (count($users)) {
        foreach ($users as $user) {
            $result = insertData('whups_shares_users', $user);
            if (is_a($result, 'PEAR_Error')) {
                ++$error_cnt;
                $cli->message($result->getMessage(), 'cli.error');
            }
        }
    }

    /* Delete the datatree data, but ONLY if it was requested */
    if ($delete_dt_data && !$error_cnt) {
        $cli->message('DELETING datatree data for share_id: ' . $share_id, 'cli.message');
        $delete = $db->prepare('DELETE FROM horde_datatree_attributes WHERE datatree_id = ?', null, MDB2_PREPARE_MANIP);
        $delete->execute(array($share_id));
        $delete->free();

        $delete = $db->prepare('DELETE FROM horde_datatree WHERE datatree_id = ?', null, MDB2_PREPARE_MANIP);
        $delete->execute(array($share_id));
        $delete->free();
    }

    /* Cleanup */
    $query_result->free();
    unset($row, $rows, $data, $groups, $users);
}

while ($nextId = $db->nextId('whups_shares') < $maxId) {
}

if ($error_cnt) {
    $cli->message(sprintf("Encountered %u errors. No data was deleted from your database.", $error_cnt));
}
echo "\nDone.\n";

/**
 * Helper function
 */
function insertData($table, $data)
{
    $fields = array_keys($data);
    $values = array_map(array($GLOBALS['db'], 'quote'), array_values($data));

    return $GLOBALS['db']->exec('INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')');
}
