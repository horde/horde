#!/usr/bin/env php
<?php
/**
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('jonah', array('authentication' => 'none', 'cli' => true));

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
