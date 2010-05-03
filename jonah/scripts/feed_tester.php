#!/usr/bin/php -q
<?php
/**
 * $Horde: jonah/scripts/feed_tester.php,v 1.7 2009/07/09 06:08:48 slusarz Exp $
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 */

// Find the base file path of Horde.
@define('HORDE_BASE', dirname(__FILE__) . '/../..');

// Find the base file path of Jonah.
@define('JONAH_BASE', dirname(__FILE__) . '/..');

// Do CLI checks and environment setup first.
require_once HORDE_BASE . '/lib/core.php';

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment - make sure there's no time limit, init
// some variables, etc.
Horde_Cli::init();
$cli = &Horde_Cli::singleton();

// Now load the Registry and setup conf, etc.
$registry = Horde_Registry::singleton();
$registry->pushApp('jonah', false);

// Include needed libraries.
require_once JONAH_BASE . '/lib/Jonah.php';
require_once JONAH_BASE . '/lib/FeedParser.php';

/* Make sure there's no compression. */
@ob_end_clean();

if (empty($argv[1]) || !file_exists($argv[1])) {
    exit("Need a valid filename.\n");
}

$data = file_get_contents($argv[1]);

if (preg_match('/.*;\s?charset="?([^"]*)/', 'text/xml', $match)) {
    $charset = $match[1];
} elseif (preg_match('/<\?xml[^>]+encoding=["\']?([^"\'\s?]+)[^?].*?>/i', $data, $match)) {
    $charset = $match[1];
} else {
    $charset = 'utf-8';
}

$parser = new Jonah_FeedParser($charset);
if (!$parser->parse($data)) {
    $cli->writeln($cli->red(_("Parse failed:")));
    var_dump($parser->error);
} else {
    $cli->writeln($cli->green(_("Parse succeeded, structure is:")));
    var_dump($parser->structure);
}
