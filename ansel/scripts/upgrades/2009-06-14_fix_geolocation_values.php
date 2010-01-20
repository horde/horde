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

// Do CLI checks and environment setup first.
require_once dirname(__FILE__) . '/../../Application.php';

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment.
Horde_Cli::init();
$cli = Horde_Cli::singleton();

Horde_Registry::appInit('ansel', array('authentication' => 'none'));

$sql = 'SELECT image_id, image_latitude, image_longitude FROM ansel_images_geolocation;';
$results = $ansel_db->queryAll($sql, null, MDB2_FETCHMODE_ASSOC);
$sql = $ansel_db->prepare('UPDATE ansel_images_geolocation SET image_latitude = ?, image_longitude = ? WHERE image_id = ?');
foreach ($results as $image) {
    // Clean up from a bug in Exifer
    if (strlen(trim($image['image_latitude']) <= 1) || strlen(trim($image['image_longitude']) <= 1)) {
        $cli->message(sprintf("Erroneous geoloction data for Image %d deleted", $image['image_id']), 'cli.message');
        $ansel_db->query('DELETE FROM ansel_images_geolocation WHERE image_id = ' . $image['image_id']);
    } else {
        $image['image_latitude'] = (strpos($image['image_latitude'], 'S') !== false ? '-' : '') . $image['image_latitude'];
        $image['image_latitude'] = str_replace(array('N', 'S'), array('', ''), $image['image_latitude']);
        $image['image_longitude'] = (strpos($image['image_longitude'], 'W') !== false ? '-' : '') . $image['image_longitude'];
        $image['image_longitude'] = str_replace(array('E', 'W'), array('', ''), $image['image_longitude']);
        $cli->message(sprintf("Image %d updated. %s - %s", $image['image_id'], $image['image_latitude'], $image['image_longitude']), 'cli.message');
        $sql->execute(array($image['image_latitude'], $image['image_longitude'], $image['image_id']));
    }
}

// Add the location column.
$sql = 'ALTER TABLE ansel_images_geolocation ADD COLUMN image_location VARCHAR(255)';
$ansel_db->exec($sql);
$cli->message('Done.', 'cli.success');
