#!/usr/bin/env php
<?php
/**
 * This script looks for images in the VFS that have no pointer in the
 * database. Any non-referenced images it finds get moved to a garbage
 * folder in Ansel's VFS directory.
 *
 * Make sure to run this as a user who has full permissions on the VFS
 * directory.
 *
 * @author Ben Chavet <ben@horde.org>
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @pacakge Ansel
 */

if (file_exists(dirname(__FILE__) . '/../../ansel/lib/Application.php')) {
    $baseDir = dirname(__FILE__) . '/../';
} else {
    require_once 'PEAR/Config.php';
    $baseDir = PEAR_Config::singleton()
        ->get('horde_dir', null, 'pear.horde.org') . '/ansel/';
}
require_once $baseDir . 'lib/Application.php';
Horde_Registry::appInit('ansel', array('cli' => true));

$parser = new Horde_Argv_Parser(
    array(
        'usage' => '%prog [--options]',
        'optionList' => array(
            new Horde_Argv_Option(
                '-m',
                '--move',
                array(
                    'help' => 'Actually move dangling images to GC folder.',
                    'default' => false,
                    'action' => 'store_true'
                )
            ),

            new Horde_Argv_Option(
                '-v',
                '--verbose',
                array(
                    'help' => 'Verbose output',
                    'default' => false,
                    'action' => 'store_true'
                )
            )
        )
    )
);
list($opts, $args) = $parser->parseArgs();

$vfs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create();
$vfspath = '.horde/ansel/';
$garbagepath = $vfspath . 'garbage/';
$hash = $vfs->listFolder($vfspath, null, false, true);
sort($hash);

$count = 0;
foreach ($hash as $dir) {
    if ($dir['name'] == 'garbage') {
        continue;
    }
    $images = $vfs->listFolder($vfspath . $dir['name'] . '/full/');
    foreach ($images as $image) {
        $image_id = strpos($image['name'], '.') ? substr($image['name'], 0, strpos($image['name'], '.')) : $image['name'];
        $result = $ansel_db->queryOne('SELECT 1 FROM ansel_images WHERE image_id = ' . (int)$image_id);
        if (!$result) {
            if (!$count && !$vfs->isFolder($vfspath, 'garbage')) {
                $vfs->createFolder($vfspath, 'garbage');
            }

            $count++;

            if ($opts['verbose']) {
                echo $vfspath . $image['name'] . ' -> ' . $garbagepath . $image['name'] . "\n";
            }

            if ($opts['move']) {
                $vfs->move($vfspath . $dir['name'] . '/full/', $image['name'], $garbagepath);
                $vfs->deleteFile($vfspath . $dir['name'] . '/screen/', $image['name']);
                $vfs->deleteFile($vfspath . $dir['name'] . '/thumb/', $image['name']);
                $vfs->deleteFile($vfspath . $dir['name'] . '/mini/', $image['name']);

                // Try to clean directories too.
                $vfs->deleteFolder($vfspath . $dir['name'], 'full');
                $vfs->deleteFolder($vfspath . $dir['name'], 'screen');
                $vfs->deleteFolder($vfspath . $dir['name'], 'thumb');
                $vfs->deleteFolder($vfspath . $dir['name'], 'mini');
                $vfs->deleteFolder($vfspath, $dir['name']);
            }
        }
    }
}

if ($count) {
    echo "\nFound dangling images";
    if ($opts['move']) {
        echo " and moved $count to $garbagepath.\n";
    } else {
        echo ", run this script with --move to clean them up.\n";
    }
} else {
    echo "No cleanup necessary.\n";
}
