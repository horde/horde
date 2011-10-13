<?php
/**
 * Example of editing an Atom post with Horde_Feed.
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

/* Grab the first entry in the feed. */
foreach ($feed as $entry) {
    break;
}

/* Display the entry's unchanged state. */
echo "entry last updated at: {$entry->updated()}\n";
echo "current EditURI is: {$entry->edit()}\n";

/* Just change the entry's properties directly. */
$entry->content = 'This is an updated post.';

/* Then save the changes. */
try {
    $entry->save();
} catch (Horde_Feed_Exception $e) {
    die('An error occurred saving changes: ' . $e->getMessage() . "\n");
}

/* Display the new state. The updated time and edit URI will have been
 * updated by the server, and $entry automatically picks up those
 * changes. */
echo "entry last updated at: {$entry->updated()}\n";
echo "new EditURI is: {$entry->edit()}\n";
