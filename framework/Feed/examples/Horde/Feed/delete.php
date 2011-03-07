<?php
/**
 * Example of deleting Atom posts with Horde_Feed.
 *
 * @package Feed
 */

/* Get a Horde framework include_path set up. */
require 'Horde/Autoloader.php';

/* Load the feed we want to delete something from. */
try {
    $feed = Horde_Feed::readUri('http://www.example.com/Feed/');
} catch (Horde_Feed_Exception $e) {
    die('An error occurred loading the feed: ' . $e->getMessage() . "\n");
}

/* We want to delete all entries that are two weeks old. */
$twoWeeksAgo = strtotime('-2 weeks');
foreach ($feed as $entry) {
    /* Check the updated timestamp. */
    if (strtotime($entry->updated) >= $twoWeeksAgo) {
        continue;
    }

    /* Deleting the old posts is easy. */
    try {
        $entry->delete();
    } catch (Horde_Feed_Exception $e) {
        die('An error occurred deleting feed entries: ' . $e->getMessage() . "\n");
    }
}
