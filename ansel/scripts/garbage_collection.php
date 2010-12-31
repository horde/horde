#!/usr/bin/env php
<?php
/**
 * This script looks for images in the VFS that have no pointer in the
 * database. Any non-referenced images it finds get moved to a garbage
 * folder in Ansel's VFS directory.
 *
 * Make sure to run this as a user who has full permissions on the VFS
 * directory.
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('ansel', array('authentication' => 'none', 'cli' => true));

// Default arguments.
$move = $verbose = false;

// Parse command-line arguments.
$ret = Console_Getopt::getopt(Console_Getopt::readPHPArgv(), 'mv',
                              array('move', 'verbose'));

if ($ret instanceof PEAR_Error) {
    die("Couldn't read command-line options.\n");
}
list($opts, $args) = $ret;
foreach ($opts as $opt) {
    list($optName, $optValue) = $opt;
    switch ($optName) {
    case '--move':
        $move = true;
        break;

    case 'v':
    case '--verbose':
        $verbose = true;
    }
}

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

            if ($verbose) {
                echo $vfspath . $image['name'] . ' -> ' . $garbagepath . $image['name'] . "\n";
            }

            if ($move) {
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
    if ($move) {
        echo " and moved $count to $garbagepath.\n";
    } else {
        echo ", run this script with --move to clean them up.\n";
    }
} else {
    echo "No cleanup necessary.\n";
}
