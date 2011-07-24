#!@php_bin@
<?php
/**
 * @package Feed
 */

/* Get a Horde framework include_path set up. */
require 'Horde/Autoloader.php';

$p = new Horde_Argv_Parser(array(
    'usage' => "%prog feed_url\n\nExample:\n%prog http://graphics8.nytimes.com/services/xml/rss/nyt/HomePage.xml",
));
list($values, $args) = $p->parseArgs();
if (count($args) != 1) {
    $p->printHelp();
    exit(1);
}

$feed = Horde_Feed::readUri($args[0]);
echo count($feed) . " entries:\n\n";
foreach ($feed as $i => $entry) {
    echo ($i + 1) . '. ' . $entry->title() . "\n";
}

exit(0);
