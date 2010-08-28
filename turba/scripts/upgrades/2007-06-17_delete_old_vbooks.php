#!/usr/bin/env php
<?php
/**
 * This script deletes old virtual address books that will otherwise
 * confuse the new Turba code.
 */

// Do CLI checks and environment setup first.
require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('turba', array('authentication' => 'none', 'cli' => true));

// See if any of our sources are configured to use Horde_Share.
if (empty($_SESSION['turba']['has_share'])) {
    echo "No shares to convert. Done.\n";
    exit(0);
}

$datatree = $turba_shares->_storage;
$db = $datatree->_db;

// Get the root vbook element.
$sql = "SELECT datatree_id FROM horde_datatree WHERE group_uid = 'horde.shares.turba' AND datatree_name = 'vbook'";
$vbook_parent = $db->getOne($sql);
if ($vbook_parent instanceof PEAR_Error) {
    var_dump($vbook_parent);
    exit(1);
}
$vbook_parent = (int)$vbook_parent;

// Get child vbooks.
$sql = "SELECT datatree_id FROM horde_datatree WHERE group_uid = 'horde.shares.turba' AND (datatree_parents = ':$vbook_parent' OR datatree_parents LIKE ':$vbook_parent:%')";
$vbook_children = $db->getCol($sql);
if ($vbook_children instanceof PEAR_Error) {
    var_dump($vbook_children);
    exit(1);
}

// Build list of ids to delete.
$datatree_ids = array($vbook_parent);
foreach ($vbook_children as $child) {
    $datatree_ids[] = (int)$child;
}
$datatree_ids = implode(',', $datatree_ids);

// Delete.
$db->query("DELETE FROM horde_datatree_attributes WHERE group_uid = 'horde.shares.turba' AND datatree_id IN ($datatree_ids)");
$db->query("DELETE FROM horde_datatree WHERE group_uid = 'horde.shares.turba' AND datatree_id IN ($datatree_ids)");

// Done.
echo "Successfully deleted old virtual address books.\n";
exit(0);
