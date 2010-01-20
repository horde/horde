#!/usr/bin/env php
<?php
/**
 * Correct geolocation data
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ansel', array('authentication' => 'none', 'cli' => true));
$cli = Horde_Cli::singleton();

$sql = 'SELECT image_id, image_latitude, image_longitude FROM ansel_images_geolocation;';
$results = $ansel_db->queryAll($sql, null, MDB2_FETCHMODE_ASSOC);
$sql = $ansel_db->prepare('UPDATE ansel_images SET image_latitude = ?, image_longitude = ? WHERE image_id = ?');
foreach ($results as $image) {
    $cli->message(sprintf("Image %d updated. %s - %s", $image['image_id'], $image['image_latitude'], $image['image_longitude']), 'cli.message');
    $sql->execute(array($image['image_latitude'], $image['image_longitude'], $image['image_id']));
}

$cli->message('Done.', 'cli.success');
