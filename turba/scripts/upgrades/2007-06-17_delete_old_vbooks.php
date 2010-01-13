#!/usr/bin/env php
<?php
/**
 * This script deletes old virtual address books that will otherwise
 * confuse the new Turba code.
 */

// Do CLI checks and environment setup first.
require_once dirname(__FILE__) . '/../lib/Application.php';

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment - make sure there's no time limit, init
// some variables, etc.
Horde_Cli::init();

Horde_Registry::appInit('turba', array('authentication' => 'none'));

// Re-load source config.
// require TURBA_BASE . '/config/sources.php';

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
if (is_a($vbook_parent, 'PEAR_Error')) {
    var_dump($vbook_parent);
    exit(1);
}
$vbook_parent = (int)$vbook_parent;

// Get child vbooks.
$sql = "SELECT datatree_id FROM horde_datatree WHERE group_uid = 'horde.shares.turba' AND (datatree_parents = ':$vbook_parent' OR datatree_parents LIKE ':$vbook_parent:%')";
$vbook_children = $db->getCol($sql);
if (is_a($vbook_children, 'PEAR_Error')) {
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
