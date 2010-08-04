#!/usr/bin/env php
<?php
/**
 * Script for migrating Ansel 1.x tags to the Content_Tagger system in Ansel 2.
 *
 * Warning: This script may take a LONG time, depending on the number of users
 * and images.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */
require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('ansel', array('authentication' => 'none', 'cli' => true));

/* Gallery tags */
$sql = 'SELECT gallery_id, tag_name, share_owner FROM ansel_shares RIGHT JOIN '
    . 'ansel_galleries_tags ON ansel_shares.share_id = ansel_galleries_tags.gallery_id '
    . 'LEFT JOIN ansel_tags ON ansel_tags.tag_id = ansel_galleries_tags.tag_id;';

// Maybe iterate over results and aggregate them by user and gallery so we can
// tag all tags for a single gallery at once. Probably not worth it for a one
// time upgrade script.
$cli->message('Migrating gallery tags. This may take a while.', 'cli.message');
$rows = $ansel_db->queryAll($sql);
foreach ($rows as $row) {
    $GLOBALS['injector']->getInstance('Ansel_Tagger')->tag($row[0], $row[1], $row[2], 'gallery');
}
$cli->message('Gallery tags finished.', 'cli.success');

$sql = 'SELECT ansel_images.image_id, tag_name, share_owner FROM ansel_images '
    . 'RIGHT JOIN ansel_images_tags ON ansel_images.image_id = ansel_images_tags.image_id '
    . 'LEFT JOIN ansel_shares ON ansel_shares.share_id = ansel_images.gallery_id '
    . 'LEFT JOIN ansel_tags ON ansel_tags.tag_id = ansel_images_tags.tag_id';
$cli->message('Migrating image tags. This may take even longer...', 'cli.message');
$rows = $ansel_db->queryAll($sql);
foreach ($rows as $row) {
    $GLOBALS['injector']->getInstance('Ansel_Tagger')->tag($row[0], $row[1], $row[2], 'image');
}
$cli->message('Image tags finished.', 'cli.success');

