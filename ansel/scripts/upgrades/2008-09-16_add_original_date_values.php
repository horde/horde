#!/usr/bin/env php
<?php
/**
* Bare bones script to populate the ansel_images.image_original_date field with
* either the exif DateTimeOriginal field, or the
* ansel_images.image_uploaded_date value if the exif field is not present.
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
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment.
Horde_Cli::init();
$cli  Horde_Cli::singleton();

$ansel_authentication = 'none';
require_once ANSEL_BASE . '/lib/base.php';

$sql = 'SELECT image_id, image_original_date, image_uploaded_date FROM ansel_images';
$results = $ansel_db->queryAll($sql, null, MDB2_FETCHMODE_ASSOC);
foreach ($results as $image) {
    $sql = "SELECT attr_value FROM ansel_image_attributes WHERE attr_name='DateTimeOriginal' AND image_id = " . (int)$image['image_id'];
    $datetime = $ansel_db->queryOne($sql);
    if (!$datetime) {
        $datetime = $image['image_uploaded_date'];
    }
    $sql = 'UPDATE ansel_images SET image_original_date = ' . (int)$datetime . ' WHERE image_id = ' . (int)$image['image_id'];
    $result = $ansel_db->exec($sql);
    if (is_a($result, 'PEAR_Error')) {
        $cli->fatal($result->getMessage());
    }
    $cli->message(sprintf("Image %d updated.", $image['image_id']), 'cli.message');
}
$cli->message('Done.', 'cli.success');
