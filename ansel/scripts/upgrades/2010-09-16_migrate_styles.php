#!/usr/bin/env php
<?php
/**
 * Script for migrating Ansel 1.x styles to the new style object in Ansel 2.
 * This script should be run *after* the migration has run for the schema, but
 * before users are allowed to log back in.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */
require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('ansel', array('authentication' => 'none', 'cli' => true));

// Make sure we have the full styles array.
require ANSEL_BASE . '/config/styles.php';

$sql = 'SELECT share_id, attribute_style FROM ansel_shares';
$cli->message('Migrating gallery styles.', 'cli.message');
$defaults = array(
            'thumbstyle' => 'Thumb',
            'background' => 'none',
            'gallery_view' => 'Gallery',
            'widgets' => array(
                 'Tags' => array('view' => 'gallery'),
                 'OtherGalleries' => array(),
                 'Geotag' => array(),
                 'Links' => array(),
                 'GalleryFaces' => array(),
                 'OwnerFaces' => array()));

$rows = $ansel_db->queryAll($sql);
$update = $ansel_db->prepare('UPDATE ansel_shares SET attribute_style=? WHERE share_id=?;');
foreach ($rows as $row) {
    if ($row[1] == 'ansel_default' || empty($styles[$row[1]])) {
        $newStyle = '';
    } else {
        $properties = array_merge($defaults, $styles[$row[1]]);
        unset($properties['requires_png']);
        unset($properties['name']);
        unset($properties['title']);
        unset($properties['hide']);
        $newStyle = serialize(new Ansel_Style($properties));
    }
    $update->execute(array($newStyle, $row[0]));
}
$cli->message('Gallery styles successfully migrated.', 'cli.success');
