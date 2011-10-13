#!@php_bin@
<?php
/**
 * @package Feed
 */

/* Get a Horde framework include_path set up. */
require 'Horde/Autoloader.php';

$p = new Horde_Argv_Parser(array(
    'usage' => "%prog opml_url\n\nExample:\n%prog subscriptions.opml",
));
list($values, $args) = $p->parseArgs();
if (count($args) != 1) {
    $p->printHelp();
    exit(1);
}

$blogroll = Horde_Feed::readFile($args[0]);
echo $blogroll->title . "\n\n";
foreach ($blogroll as $blog) {
    $feed = $blog->getFeed();
    echo $feed->title . "\n\n";
    foreach ($feed as $entry) {
        echo "$entry->title\n";
    }
    echo "\n\n";
}

exit(0);
