#!/usr/bin/env php
<?php
/**
 * Script for migrating Ansel 1.x categories to the tags system in Ansel 2.
 * This script should be run *after* tags are migrated to content/.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */
require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('ansel', array('authentication' => 'none', 'cli' => true));

/* Gallery tags */
$sql = 'SELECT share_id, attribute_category, share_owner FROM ansel_shares';

// Maybe iterate over results and aggregate them by user and gallery so we can
// tag all tags for a single gallery at once. Probably not worth it for a one
// time upgrade script.
$cli->message('Migrating gallery categories.', 'cli.message');
$rows = $ansel_db->queryAll($sql);
foreach ($rows as $row) {
    $GLOBALS['injector']->getInstance('Ansel_Tagger')->tag($row[0], $row[1], $row[2], 'gallery');
}
$cli->message('Gallery categories successfully migrated.', 'cli.success');
