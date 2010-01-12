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
require_once dirname(__FILE__) . '/../../lib/base.load.php';
require_once HORDE_BASE . '/lib/core.php';

// Make sure no one runs this from the web.
if (!Horde_CLI::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment.
Horde_CLI::init();
$cli = Horde_CLI::singleton();

$ansel_authentication = 'none';
require_once ANSEL_BASE . '/lib/base.php';

// First update the tables
$alter = array("ALTER TABLE ansel_images ADD COLUMN image_latitude VARCHAR(32) NOT NULL DEFAULT ''",
               "ALTER TABLE ansel_images ADD COLUMN image_longitude VARCHAR(32) NOT NULL DEFAULT ''",
               "ALTER TABLE ansel_images ADD COLUMN image_location VARCHAR(255) NOT NULL DEFAULT ''",
               "ALTER TABLE ansel_images ADD COLUMN image_geotag_date INT NOT NULL DEFAULT 0");

foreach ($alter as $sql) {
    $cli->message(sprintf("Executing %s", $sql));
    $ansel_db->exec($sql);
}

$sql = 'SELECT image_id, image_latitude, image_longitude FROM ansel_images_geolocation;';
$results = $ansel_db->queryAll($sql, null, MDB2_FETCHMODE_ASSOC);
$sql = $ansel_db->prepare('UPDATE ansel_images SET image_latitude = ?, image_longitude = ? WHERE image_id = ?');
foreach ($results as $image) {
    // Clean up from a bug in Exifer
    if (strlen(trim($image['image_latitude'])) <= 1 || strlen(trim($image['image_longitude'])) <= 1) {
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

$cli->message('Done.', 'cli.success');
