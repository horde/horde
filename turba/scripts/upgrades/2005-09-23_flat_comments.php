#!/usr/bin/php
<?php
/**
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Jan Schneider <jan@horde.org>
 */

// Do CLI checks and environment setup first.
require_once dirname(__FILE__) . '/../lib/base.load.php';
require_once HORDE_BASE . '/lib/core.php';

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment - make sure there's no time limit, init some
// variables, etc.
Horde_Cli::init();

$turba_authentication = 'none';
require_once TURBA_BASE . '/lib/base.php';

// Instantiate DataTree.
require_once 'Horde/DataTree.php';
$driver = $conf['datatree']['driver'];
$params = array_merge(Horde::getDriverConfig('datatree', $driver),
                      array('group' => 'agora.forums.turba'));
$datatree = &DataTree::singleton($driver, $params);

// Load comments.
$forums = $datatree->get(DATATREE_FORMAT_TREE, DATATREE_ROOT);
if (!is_array($forums[DATATREE_ROOT])) {
    exit("No comments.\n");
}

// Loop through comments.
$converted = 0;
foreach ($forums[DATATREE_ROOT] as $source => $comments) {
    if (!is_array($comments)) {
        exit("Comments have already been flattened.\n");
    }
    $source_name = $datatree->getName($source);
    foreach (array_keys($comments) as $comment) {
        $name = $datatree->getName($comment);
        $datatree->rename($name, $source_name . '.' . $datatree->getShortName($name));
        $converted++;
    }
}
$forums = $datatree->get(DATATREE_FORMAT_TREE, DATATREE_ROOT, true);
foreach ($forums[DATATREE_ROOT] as $source => $comments) {
    $source_name = $datatree->getName($source);
    foreach (array_keys($comments) as $comment) {
        $datatree->move($datatree->getName($comment));
    }
    $datatree->remove($source_name);
}

echo $converted . " comments flattened.\n";
