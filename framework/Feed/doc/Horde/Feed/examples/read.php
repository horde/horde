<?php
/**
 * Example of reading feeds with Horde_Feed.
 *
 * @package Feed
 */

/* Get a Horde framework include_path set up. */
require 'Horde/Autoloader.php';

/* Get the New York Times headlines. */
$uri = 'http://graphics8.nytimes.com/services/xml/rss/nyt/HomePage.xml';
try {
    $feed = Horde_Feed::readUri($uri);
} catch (Exception $e) {
    die('An error occurred loading the feed: ' . $e->getMessage() . "\n");
}

/* You can iterate over the entries in the feed simply by
 * iterating over the feed itself. */
foreach ($feed as $entry) {
    echo "title: {$entry->title()}\n";
    if ($entry->author->name()) {
        echo "author: {$entry->author->name()}\n";
    }
    echo "description:\n{$entry->description()}\n\n";
}

/* The properties of the feed itself are available through
 * regular member variable access: */
echo "feed title: {$feed->title()}\n";
if ($feed->author->name()) {
    echo "feed author: $feed->author->name()\n";
}
foreach ($feed->link as $link) {
    echo "link: {$link['href']}\n";
}
